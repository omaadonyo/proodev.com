<?php

use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\AutoScanRun;
use App\Models\AutoScanUrl;
use App\Models\Payment;
use App\Services\AutoScanService;
use App\Services\BillingService;
use App\Services\Payments\PaymentMethodSettings;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Credits & Auto-Scan')] class extends Component
{
    use ExportsSelectedRows;

    public int $bundle = 0;

    public ?string $newUrl = null;

    public bool $scanning = false;

    public ?array $scanResult = null;

    public function purchase(int $index): void
    {
        $payment = app(BillingService::class)->createCreditPayment(auth()->user(), $index);

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    public function purchaseAutoScan(): void
    {
        $payment = app(BillingService::class)->createAutoScanPayment(auth()->user());

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    public function addUrl(): void
    {
        $validated = $this->validate([
            'newUrl' => ['required', 'string', 'max:255'],
        ]);

        $url = trim((string) $validated['newUrl']);

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://'.$url;
        }

        $user = auth()->user();

        if ($user->autoScanUrls()->where('url', $url)->exists()) {
            Flux::toast(variant: 'warning', text: 'That URL is already queued.');

            return;
        }

        $user->autoScanUrls()->create([
            'url' => $url,
            'status' => AutoScanUrl::STATUS_QUEUED,
        ]);

        $this->newUrl = null;
        $this->scanResult = null;

        Flux::toast(variant: 'success', text: 'URL added — it will be scanned with your next auto-scan.');
    }

    public function removeUrl(int $id): void
    {
        auth()->user()->autoScanUrls()->whereKey($id)->delete();

        $this->scanResult = null;

        Flux::toast(variant: 'success', text: 'Repository URL removed.');
    }

    public function scanNow(): void
    {
        $this->scanning = true;

        try {
            $this->scanResult = app(AutoScanService::class)->scan(auth()->user());
            Flux::toast(variant: 'success', text: 'Auto-scan finished — your DevID is up to date.');
        } catch (Throwable $e) {
            $this->scanResult = [
                'scanned' => 0,
                'new_evidence' => 0,
                'new_projects' => 0,
                'new_journal' => 0,
                'xp' => 0,
                'error' => 'Something went wrong while scanning. Please try again later.',
            ];
        } finally {
            $this->scanning = false;
        }
    }

    public function cancelAutoScan(): void
    {
        auth()->user()->forceFill(['auto_scan_enabled' => false])->save();
        Flux::toast(variant: 'success', text: 'Auto-scan turned off — your existing work stays on your DevID.');
    }

    #[Computed]
    public function balance(): int
    {
        return auth()->user()->creditBalance();
    }

    #[Computed]
    public function tokensPerCredit(): int
    {
        return (int) config('billing.developer.credits.tokens_per_credit', 1000);
    }

    #[Computed]
    public function freeAllowance(): int
    {
        return (int) config('billing.developer.daily_free_submissions', 3);
    }

    #[Computed]
    public function usedToday(): int
    {
        $user = auth()->user();

        return $user->daily_evidence_date === now()->toDateString()
            ? (int) $user->daily_evidence_count
            : 0;
    }

    #[Computed]
    public function freeRemaining(): int
    {
        return max(0, $this->freeAllowance - $this->usedToday);
    }

    #[Computed]
    public function freeUsagePercent(): int
    {
        return (int) round($this->usedToday / max(1, $this->freeAllowance) * 100);
    }

    #[Computed]
    public function consumptionPercent(): int
    {
        $total = $this->lifetimeEarned + $this->lifetimeSpent;

        return $total > 0 ? (int) round($this->lifetimeSpent / $total * 100) : 0;
    }

    #[Computed]
    public function lifetimeEarned(): int
    {
        return (int) auth()->user()
            ->creditTransactions()
            ->where('change', '>', 0)
            ->sum('change');
    }

    #[Computed]
    public function lifetimeSpent(): int
    {
        return (int) abs(auth()->user()
            ->creditTransactions()
            ->where('change', '<', 0)
            ->sum('change'));
    }

    #[Computed]
    public function spentThisMonth(): int
    {
        return (int) abs(auth()->user()
            ->creditTransactions()
            ->where('change', '<', 0)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('change'));
    }

    #[Computed]
    public function weeklyUsage(): array
    {
        $rows = collect(range(6, 0))->map(fn (int $daysAgo) => [
            'date' => now()->subDays($daysAgo),
            'label' => now()->subDays($daysAgo)->format('D'),
            'spent' => (int) abs(auth()->user()
                ->creditTransactions()
                ->where('change', '<', 0)
                ->whereDate('created_at', now()->subDays($daysAgo)->toDateString())
                ->sum('change')),
        ]);

        $max = max(1, (int) $rows->max('spent'));

        return $rows->map(fn (array $row) => array_merge($row, [
            'percent' => (int) round($row['spent'] / $max * 100),
        ]))->all();
    }

    #[Computed]
    public function bundles(): array
    {
        return collect(config('billing.developer.credits.bundles', []))
            ->map(fn (array $bundle, int $index) => array_merge($bundle, [
                'index' => $index,
                'tokens' => (int) $bundle['credits'] * $this->tokensPerCredit,
            ]))
            ->all();
    }

    #[Computed]
    public function pendingPayments()
    {
        return auth()->user()
            ->payments()
            ->where('purpose', 'credits')
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function autoScanActive(): bool
    {
        return auth()->user()->autoScanActive();
    }

    #[Computed]
    public function autoScanPrice(): float
    {
        return (float) config('billing.developer.auto_scan.price', 8);
    }

    #[Computed]
    public function autoScanInterval(): int
    {
        return (int) config('billing.developer.auto_scan.interval_days', 30);
    }

    #[Computed]
    public function autoScanUrls()
    {
        return auth()->user()->autoScanUrls()->latest('id')->get();
    }

    #[Computed]
    public function autoScanPending()
    {
        return auth()->user()
            ->payments()
            ->where('purpose', 'auto-scan')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    #[Computed]
    public function autoScanPayments()
    {
        return auth()->user()
            ->payments()
            ->where('purpose', 'auto-scan')
            ->where('status', 'paid')
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function autoScanRuns()
    {
        return auth()->user()
            ->autoScanRuns()
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return app(PaymentMethodSettings::class)->usableMethods();
    }

    #[Computed]
    public function githubHandle(): ?string
    {
        $url = auth()->user()->github_url;

        if (! $url) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        return $parts[0] ?? null;
    }

    #[Computed]
    public function transactions()
    {
        return auth()->user()
            ->creditTransactions()
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function invoices()
    {
        return Payment::query()
            ->where('user_id', auth()->id())
            ->where('status', PaymentStatus::Paid)
            ->latest('paid_at')
            ->limit(20)
            ->get();
    }

    protected function selectableIds(): array
    {
        return $this->autoScanRuns->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $runs = AutoScanRun::whereIn('id', $this->selectedIds)->latest()->get();

        $rows = $runs->map(fn (AutoScanRun $run) => [
            $run->created_at->toDateTimeString(),
            (string) $run->scanned,
            (string) $run->new_evidence,
            (string) $run->new_projects,
            (string) $run->new_journal,
            (string) $run->xp,
            $run->error ? 'Failed: '.$run->error : 'OK',
        ])->all();

        return [
            ['Ran', 'Repos', 'Evidence', 'Projects', 'Journal', 'XP', 'Status'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Auto-scan history';
    }

    protected function exportBasename(): string
    {
        return 'auto-scan-history';
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Credits & Auto-Scan</flux:heading>
            <flux:text>
                One feature for keeping your DevID fresh: credits power AI analyses beyond your free
                allowance, and auto-scan imports your repositories on a schedule.
            </flux:text>
        </div>

        {{-- Auto-Scan: pitch when inactive, URL queue + scan controls when active. --}}
        @if ($this->autoScanActive)
            <div class="overflow-hidden rounded-2xl border border-emerald-300/50 bg-white dark:border-emerald-400/20 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-emerald-400/10 text-emerald-500">
                                <flux:icon name="arrow-path" class="size-5" />
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Auto-scan is active</div>
                                <div class="text-xs text-zinc-500">
                                    @if ($this->githubHandle)
                                        Scanning <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->githubHandle }}</span> — add repository URLs below to scan specific work
                                    @else
                                        Queue repository URLs below to scan specific work
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            Active · until {{ auth()->user()->auto_scan_active_until?->format('M j, Y') }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-5 p-6">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-2">
                                <flux:icon name="link" class="size-4 text-accent" />
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Scan any URL</div>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">
                                Paste any link — a GitHub repo, package, article, demo or site — and the AI will scan it on the next run. Repos import as evidence, projects and journal entries.
                            </p>

                            <form wire:submit="addUrl" class="mt-3 flex gap-2">
                                <flux:input
                                    wire:model="newUrl"
                                    placeholder="Paste any URL to scan…"
                                    class="flex-1"
                                />
                                <flux:button type="submit" variant="primary">
                                    <flux:icon name="plus" variant="micro" />
                                    Add URL
                                </flux:button>
                            </form>

                            @if ($this->autoScanUrls->isNotEmpty())
                                <div class="mt-4 grid gap-2">
                                    @foreach ($this->autoScanUrls as $url)
                                        @php($status = $url->status)
                                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                                            <flux:icon name="link" variant="micro" class="size-4 shrink-0 text-zinc-400" />
                                            <span class="min-w-0 flex-1 truncate font-mono text-xs text-zinc-700 dark:text-zinc-300" title="{{ $url->url }}">{{ $url->url }}</span>

                                            @if ($status === \App\Models\AutoScanUrl::STATUS_SCANNED)
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400" title="Scanned {{ $url->last_scanned_at?->diffForHumans() }}">
                                                    <flux:icon name="check" variant="micro" class="size-3" />
                                                    Scanned
                                                </span>
                                            @elseif ($status === \App\Models\AutoScanUrl::STATUS_FAILED)
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-rose-400/10 px-2 py-0.5 text-[10px] font-semibold text-rose-600 dark:text-rose-400" title="{{ $url->last_error }}">
                                                    <flux:icon name="exclamation-triangle" variant="micro" class="size-3" />
                                                    Failed
                                                </span>
                                            @else
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                                    <flux:icon name="clock" variant="micro" class="size-3" />
                                                    Queued
                                                </span>
                                            @endif

                                            <button type="button" wire:click="removeUrl({{ $url->id }})" class="text-zinc-400 transition hover:text-rose-500" title="Remove URL">
                                                <flux:icon name="x-mark" variant="micro" class="size-4" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 text-xs text-zinc-400">No URLs queued yet — auto-scan falls back to your GitHub profile.</p>
                            @endif
                        </div>

                        <div class="grid content-start gap-4">
                            <div class="grid grid-cols-3 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
                                <div class="bg-zinc-50 px-3 py-3 text-center dark:bg-zinc-900">
                                    <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">{{ auth()->user()->last_auto_scan_at?->diffForHumans() ?? '—' }}</div>
                                    <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Last scan</div>
                                </div>
                                <div class="bg-zinc-50 px-3 py-3 text-center dark:bg-zinc-900">
                                    <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">Daily · 4:00 AM</div>
                                    <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">Next auto scan</div>
                                </div>
                                <div class="bg-zinc-50 px-3 py-3 text-center dark:bg-zinc-900">
                                    <div class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($this->autoScanPrice, 0) }}</div>
                                    <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">per {{ $this->autoScanInterval }} days</div>
                                </div>
                            </div>

                            @if ($this->scanResult)
                                <div class="rounded-lg border border-accent/20 bg-accent/5 p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">Scan complete</span>
                                        @if (($this->scanResult['xp'] ?? 0) > 0)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2.5 py-1 text-xs font-semibold tabular-nums text-accent">
                                                +{{ number_format($this->scanResult['xp']) }} XP
                                            </span>
                                        @endif
                                    </div>
                                    @if ($this->scanResult['error'])
                                        <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">{{ $this->scanResult['error'] }}</p>
                                    @else
                                        <div class="mt-3 grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-zinc-200 dark:bg-white/10">
                                            <div class="bg-white px-1 py-2 text-center dark:bg-zinc-900">
                                                <div class="text-sm font-bold tabular-nums">{{ number_format($this->scanResult['scanned']) }}</div>
                                                <div class="text-[9px] uppercase tracking-wide text-zinc-500">Repos</div>
                                            </div>
                                            <div class="bg-white px-1 py-2 text-center dark:bg-zinc-900">
                                                <div class="text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($this->scanResult['new_evidence']) }}</div>
                                                <div class="text-[9px] uppercase tracking-wide text-zinc-500">Evidence</div>
                                            </div>
                                            <div class="bg-white px-1 py-2 text-center dark:bg-zinc-900">
                                                <div class="text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($this->scanResult['new_projects']) }}</div>
                                                <div class="text-[9px] uppercase tracking-wide text-zinc-500">Projects</div>
                                            </div>
                                            <div class="bg-white px-1 py-2 text-center dark:bg-zinc-900">
                                                <div class="text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($this->scanResult['new_journal']) }}</div>
                                                <div class="text-[9px] uppercase tracking-wide text-zinc-500">Journal</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-3">
                                <flux:button variant="primary" wire:click="scanNow" wire:loading.attr="disabled" :disabled="$this->scanning">
                                    <flux:icon name="arrow-path" variant="micro" />
                                    {{ $this->scanning ? 'Scanning…' : 'Scan now' }}
                                </flux:button>
                                <flux:button variant="subtle" wire:click="cancelAutoScan" wire:confirm="Turn off auto-scan? Your existing work stays on your DevID.">
                                    Turn off auto-scan
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="grid gap-8 p-6 lg:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Automatic repository scanning</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-5xl font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($this->autoScanPrice, 0) }}</span>
                            <span class="text-sm text-zinc-500">per {{ $this->autoScanInterval }} days · cancel anytime</span>
                        </div>

                        <div class="mt-6 grid gap-3">
                            @foreach ([
                                ['icon' => 'arrow-path', 'title' => 'Daily scans', 'text' => 'Every new URL you queue is picked up automatically — repos, packages, articles, demos and sites.'],
                                ['icon' => 'document-plus', 'title' => 'Evidence on autopilot', 'text' => 'New links are imported as evidence and queued for AI analysis.'],
                                ['icon' => 'folder', 'title' => 'Projects & journal from history', 'text' => 'Strong repos become published projects, and meaningful work is dated into your journal.'],
                                ['icon' => 'link', 'title' => 'Scan any URL', 'text' => 'Add any link after activation to scan the exact work you want.'],
                                ['icon' => 'bolt', 'title' => 'Feed & DevID stay live', 'text' => 'Your level, engineering magnitude, and community feed update as work lands.'],
                            ] as $benefit)
                                <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                                        <flux:icon name="{{ $benefit['icon'] }}" variant="solid" />
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $benefit['title'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $benefit['text'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900/50">
                        <flux:heading size="sm">Activate auto-scan</flux:heading>
                        <flux:text>One checkout — after payment is confirmed, you can queue the exact repository URLs for AI to scan.</flux:text>

                        <form wire:submit="purchaseAutoScan" class="mt-5 grid gap-4">
                            <div class="rounded-lg border border-zinc-200 bg-white p-3 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="flex justify-between">
                                    <span>Auto-Scan</span>
                                    <span class="font-semibold tabular-nums">${{ number_format($this->autoScanPrice, 2) }}</span>
                                </div>
                                <div class="mt-1 flex justify-between">
                                    <span>{{ $this->autoScanInterval }} days of automatic scanning</span>
                                    <span class="font-semibold tabular-nums text-emerald-600">Included</span>
                                </div>
                                <div class="mt-2 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                    <div class="flex justify-between font-semibold text-zinc-900 dark:text-white">
                                        <span>Total due</span>
                                        <span class="tabular-nums">${{ number_format($this->autoScanPrice, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                <flux:icon name="arrow-path" variant="micro" />
                                Activate auto-scan — ${{ number_format($this->autoScanPrice, 0) }}/{{ $this->autoScanInterval }} days
                            </flux:button>
                        </form>

                        @if ($this->autoScanPending)
                            <div class="mt-4 rounded-lg border border-amber-300/40 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                                <div class="font-semibold">Checkout #{{ $this->autoScanPending->id }} awaiting confirmation</div>
                                <div class="mt-1">Auto-scan activates as soon as an admin confirms the payment — then you can add repository URLs.</div>
                            </div>
                        @endif

                        @if (! $this->githubHandle)
                            <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-3 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="font-semibold text-zinc-700 dark:text-zinc-300">No GitHub profile linked yet</div>
                                <p class="mt-1">That's fine — once auto-scan is active you can queue repository URLs directly instead.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($this->autoScanRuns->isNotEmpty())
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">Scan history</flux:heading>
                        <flux:text>Every auto-scan run and the XP it awarded, most recent first.</flux:text>
                    </div>
                    @php($totalXp = $this->autoScanRuns->sum('xp'))
                    @if ($totalXp > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                            <flux:icon name="bolt" variant="micro" />
                            +{{ number_format($totalXp) }} XP total
                        </span>
                    @endif
                    @if (count($this->selectedIds) > 0)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                        <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                            <flux:icon name="document-arrow-down" variant="micro" />
                            PDF
                        </button>
                        <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                            <flux:icon name="table-cells" variant="micro" />
                            Excel
                        </button>
                    @endif
                </div>

                <div class="mt-3 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                                <th class="w-8 px-3 py-2.5 font-medium">
                                    <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->autoScanRuns->count() && $this->autoScanRuns->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                                </th>
                                <th class="px-3 py-2.5 font-medium">Ran</th>
                                <th class="px-3 py-2.5 font-medium">Repos</th>
                                <th class="px-3 py-2.5 font-medium">Evidence</th>
                                <th class="px-3 py-2.5 font-medium">Projects</th>
                                <th class="px-3 py-2.5 font-medium">Journal</th>
                                <th class="px-3 py-2.5 font-medium">XP</th>
                                <th class="px-3 py-2.5 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->autoScanRuns as $run)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($run->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                                    <td class="px-3 py-2.5">
                                        <input type="checkbox" wire:click="toggleSelect({{ $run->id }})" {{ in_array($run->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-zinc-500" title="{{ $run->created_at->toDateTimeString() }}">{{ $run->created_at->diffForHumans() }}</td>
                                    <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->scanned) }}</td>
                                    <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_evidence) }}</td>
                                    <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_projects) }}</td>
                                    <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_journal) }}</td>
                                    <td class="px-3 py-2.5">
                                        @if ($run->xp > 0)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">+{{ number_format($run->xp) }} XP</span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if ($run->error)
                                            <span class="text-xs text-rose-500" title="{{ $run->error }}">Failed</span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($this->invoices->isNotEmpty())
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">Invoices & receipts</flux:heading>
                        <flux:text>Download any invoice as a PDF, or email yourself a fresh copy.</flux:text>
                    </div>
                    <a href="{{ route('billing') }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-semibold text-accent hover:underline">
                        View all
                        <flux:icon name="arrow-right" variant="micro" class="size-3" />
                    </a>
                </div>
                <div class="mt-3 grid gap-2">
                    @foreach ($this->invoices as $payment)
                        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                    {{ $payment->invoiceNumber() }}
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $payment->purpose->label() }}</span>
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-500">{{ $payment->lineDescription() }} · {{ $payment->paid_at?->format('M j, Y') }}</div>
                            </div>
                            <span class="font-semibold tabular-nums">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</span>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <flux:button size="xs" variant="ghost" :href="route('invoices.show', $payment)" target="_blank" title="Open printable invoice">
                                    <flux:icon name="arrow-down-tray" variant="micro" />
                                    Download
                                </flux:button>
                                <form method="POST" action="{{ route('invoices.email', $payment) }}" class="inline-flex">
                                    @csrf
                                    <flux:button size="xs" variant="ghost" type="submit" title="Email a copy of this invoice">
                                        <flux:icon name="envelope" variant="micro" />
                                        Email copy
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-accent/20 bg-white p-6 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Credit balance</div>
                <div class="mt-2 text-4xl font-bold tabular-nums text-accent">{{ number_format($this->balance) }}</div>
                <div class="mt-1 text-xs text-zinc-500">~ {{ number_format($this->balance * $this->tokensPerCredit) }} tokens of AI analysis</div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Consumed lifetime</span>
                        <span class="tabular-nums">{{ number_format($this->lifetimeSpent) }} / {{ number_format($this->lifetimeEarned + $this->lifetimeSpent) }}</span>
                    </div>
                    <flux:progress :value="$this->consumptionPercent" />
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Free allowance today</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <div class="text-4xl font-bold tabular-nums">{{ $this->usedToday }}</div>
                    <div class="text-sm text-zinc-500">of {{ number_format($this->freeAllowance) }} used</div>
                </div>
                <div class="mt-1 text-xs text-zinc-500">
                    {{ $this->freeRemaining > 0 ? number_format($this->freeRemaining).' link analyses left today' : 'Free allowance exhausted — credits cover the rest' }}
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Today's usage</span>
                        <span class="tabular-nums">{{ number_format($this->usedToday) }} / {{ number_format($this->freeAllowance) }}</span>
                    </div>
                    <flux:progress :value="$this->freeUsagePercent" />
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">This month</div>
                <div class="mt-2 text-4xl font-bold tabular-nums">{{ number_format($this->spentThisMonth) }}</div>
                <div class="mt-1 text-xs text-zinc-500">credits used since {{ now()->startOfMonth()->format('M j') }}</div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Weekly usage</span>
                        <span class="tabular-nums">last 7 days</span>
                    </div>
                    <div class="flex h-10 items-end gap-1.5">
                        @foreach ($this->weeklyUsage as $day)
                            <div class="flex-1">
                                <div
                                    class="w-full rounded-sm {{ $day['spent'] > 0 ? 'bg-accent' : 'bg-zinc-200 dark:bg-zinc-700' }}"
                                    style="height: {{ max(3, $day['percent']) }}px;"
                                    title="{{ $day['label'] }} · {{ $day['spent'] }} credits"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1 flex gap-1.5">
                        @foreach ($this->weeklyUsage as $day)
                            <div class="flex-1 text-center text-[10px] text-zinc-400">{{ $day['label'] }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($this->pendingPayments->isNotEmpty())
            <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="font-semibold">Checkout(s) awaiting confirmation</div>
                <div class="mt-1 text-xs">
                    This build uses manual checkout — after payment, an admin confirms and your credits are granted instantly.
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($this->pendingPayments as $payment)
                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium dark:bg-zinc-900">
                            #{{ $payment->id }} · {{ number_format($payment->amount, 2) }} {{ $payment->currency }} · {{ $payment->metadata['credits'] ?? '—' }} credits
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">Purchase credits</flux:heading>
                    <flux:text>Credits are granted once your checkout is confirmed.</flux:text>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-3 py-1 text-xs font-medium text-accent">
                    <flux:icon name="bolt" variant="micro" />
                    Best value at {{ number_format($this->bundles[count($this->bundles) - 1]['credits'] ?? 0) }} credits
                </span>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach ($this->bundles as $bundle)
                    <div class="relative flex flex-col rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                        @if ($loop->last)
                            <span class="absolute -top-2.5 right-4 rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                Best value
                            </span>
                        @endif
                        <div class="text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ $bundle['credits'] }}</div>
                        <div class="text-xs text-zinc-500">credits · ~{{ number_format($bundle['tokens']) }} tokens</div>
                        <div class="mt-3 text-sm text-zinc-500">≈ {{ number_format($bundle['credits']) }} link analyses</div>
                        <div class="mt-4 flex items-end justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold">${{ number_format($bundle['price'], 0) }}</div>
                                <div class="text-[11px] text-zinc-400">${{ number_format($bundle['price'] / $bundle['credits'], 2) }}/credit</div>
                            </div>
                            <flux:button size="sm" variant="primary" wire:click="purchase({{ $bundle['index'] }})">
                                Buy now
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">Payment methods</flux:heading>
                    <flux:text>Pay with any of these at checkout — credits are granted once the payment is confirmed.</flux:text>
                </div>
            </div>

            <x-secure-checkout-notice class="mt-3 bg-zinc-50 dark:bg-zinc-900/50" />

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($this->paymentMethods as $option)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <x-payment-method-logo :method="$option" class="shrink-0" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold">{{ $option->label() }}</div>
                                <div class="text-xs text-zinc-500">{{ $option->description() }}</div>
                            </div>
                        </div>
                        @if ($option === \App\Enums\PaymentMethod::WorldRemit)
                            @php($worldremit = app(\App\Services\Payments\PaymentMethodSettings::class)->for($option))
                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-900/60">
                                <span class="text-zinc-500">Pay to</span>
                                <span class="font-semibold">{{ $worldremit['payout_country'] ?? 'Uganda' }}</span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ffcb05] px-2 py-0.5 text-[10px] font-bold text-black">
                                    {{ $worldremit['mobile_money_provider'] ?? 'MTN Mobile Money' }}
                                </span>
                                <span class="font-mono font-medium">{{ $worldremit['mobile_money_number'] ?? '—' }}</span>
                                <span class="text-zinc-500">{{ $worldremit['account_name'] ?? '' }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">Transaction history</flux:heading>
                    <flux:text>Every credit earned and spent, most recent first.</flux:text>
                </div>
                <span class="text-xs text-zinc-500">{{ number_format($this->transactions->count()) }} entries</span>
            </div>
            <div class="mt-4 grid gap-2">
                @forelse ($this->transactions as $transaction)
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $transaction->change > 0 ? 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-400/10 text-rose-600 dark:text-rose-400' }}">
                            {{ $transaction->change > 0 ? '+' : '' }}{{ $transaction->change }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium">{{ $transaction->type->label() }}</div>
                            @if ($transaction->description)
                                <div class="truncate text-xs text-zinc-500">{{ $transaction->description }}</div>
                            @endif
                        </div>
                        <span class="text-xs text-zinc-400">{{ $transaction->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No transactions yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>