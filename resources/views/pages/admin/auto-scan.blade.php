<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\AutoScanRun;
use App\Models\AutoScanUrl;
use App\Models\Payment;
use App\Models\User;
use App\Services\AutoScanService;
use App\Services\BillingService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Auto-Scan')] class extends Component
{
    use ExportsSelectedRows;
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public ?int $historyUserId = null;

    public bool $showHistoryModal = false;

    public function confirmPayment(int $id): void
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== PaymentStatus::Pending) {
            Flux::toast(variant: 'warning', text: 'Only pending payments can be confirmed.');

            return;
        }

        app(BillingService::class)->markPaid($payment, auth()->user());

        unset($this->summary, $this->pendingPayments, $this->subscribers);

        Flux::toast(variant: 'success', text: 'Payment confirmed — auto-scan activated.');
    }

    public function cancelPayment(int $id): void
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== PaymentStatus::Pending) {
            Flux::toast(variant: 'warning', text: 'Only pending payments can be cancelled.');

            return;
        }

        $payment->update(['status' => PaymentStatus::Cancelled]);

        unset($this->summary, $this->pendingPayments);

        Flux::toast(variant: 'warning', text: 'Payment cancelled.');
    }

    public function scanNow(int $id): void
    {
        $user = User::findOrFail($id);

        try {
            $result = app(AutoScanService::class)->scan($user);

            unset($this->subscribers, $this->urls, $this->summary);

            if ($result['error'] !== null) {
                Flux::toast(variant: 'warning', text: $result['error']);

                return;
            }

            Flux::toast(
                variant: 'success',
                text: 'Scanned — '.$result['new_evidence'].' new evidence, '.$result['new_projects'].' projects, '.$result['new_journal'].' journal entries.'
            );
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: 'Scan failed: '.$e->getMessage());
        }
    }

    public function showHistory(int $id): void
    {
        $this->historyUserId = $id;
        $this->showHistoryModal = true;
    }

    public function closeHistory(): void
    {
        $this->historyUserId = null;
        $this->showHistoryModal = false;
    }

    public function toggleAutoScan(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->auto_scan_enabled) {
            $user->forceFill(['auto_scan_enabled' => false])->save();
            Flux::toast(variant: 'warning', text: 'Auto-scan turned off for '.$user->name.'.');
        } else {
            $user->forceFill([
                'auto_scan_enabled' => true,
                'auto_scan_active_until' => $user->auto_scan_active_until ?? now()->addDays((int) config('billing.developer.auto_scan.interval_days', 30)),
            ])->save();
            Flux::toast(variant: 'success', text: 'Auto-scan enabled for '.$user->name.'.');
        }

        unset($this->subscribers, $this->summary);
    }

    public function retryUrl(int $id): void
    {
        $url = AutoScanUrl::findOrFail($id);

        $url->update([
            'status' => AutoScanUrl::STATUS_QUEUED,
            'last_error' => null,
        ]);

        unset($this->urls, $this->summary);

        Flux::toast(variant: 'success', text: 'URL re-queued for scanning.');
    }

    public function removeUrl(int $id): void
    {
        $url = AutoScanUrl::findOrFail($id);

        $url->delete();

        unset($this->urls, $this->summary);

        Flux::toast(variant: 'warning', text: 'URL removed from the scan queue.');
    }

    #[Computed]
    public function summary(): array
    {
        return [
            'subscribers' => User::where('auto_scan_enabled', true)->count(),
            'active' => User::query()
                ->where('auto_scan_enabled', true)
                ->where('auto_scan_active_until', '>', now())
                ->count(),
            'pending_payments' => Payment::where('purpose', PaymentPurpose::AutoScan)
                ->where('status', PaymentStatus::Pending)
                ->count(),
            'revenue' => (float) Payment::where('purpose', PaymentPurpose::AutoScan)
                ->where('status', PaymentStatus::Paid)
                ->sum('amount'),
            'paid_payments' => Payment::where('purpose', PaymentPurpose::AutoScan)
                ->where('status', PaymentStatus::Paid)
                ->count(),
            'queued_urls' => AutoScanUrl::where('status', AutoScanUrl::STATUS_QUEUED)->count(),
            'failed_urls' => AutoScanUrl::where('status', AutoScanUrl::STATUS_FAILED)->count(),
            'scanned_urls' => AutoScanUrl::where('status', AutoScanUrl::STATUS_SCANNED)->count(),
        ];
    }

    #[Computed]
    public function pendingPayments()
    {
        return Payment::with('user')
            ->where('purpose', PaymentPurpose::AutoScan)
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->limit(50)
            ->get();
    }

    public function updatedStatus(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
        $this->resetPage();
    }

    #[Computed]
    public function subscribers()
    {
        return User::query()
            ->withCount(['autoScanUrls', 'autoScanRuns'])
            ->with(['autoScanUrls' => fn ($query) => $query->orderByDesc('id')])
            ->where(function ($query) {
                $query->where('auto_scan_enabled', true)
                    ->orWhereHas('autoScanUrls');
            })
            ->when($this->status === 'active', fn ($query) => $query
                ->where('auto_scan_enabled', true)
                ->where('auto_scan_active_until', '>', now()))
            ->when($this->status === 'expired', fn ($query) => $query
                ->where('auto_scan_enabled', true)
                ->where(function ($query) {
                    $query->whereNull('auto_scan_active_until')
                        ->orWhere('auto_scan_active_until', '<=', now());
                }))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->orWhere('email', 'like', '%'.trim($this->search).'%')
                ->orWhere('username', 'like', '%'.trim($this->search).'%')))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function urls()
    {
        return AutoScanUrl::with('user')
            ->when($this->status !== 'all' && in_array($this->status, ['queued', 'scanned', 'failed'], true), fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('url', 'like', '%'.trim($this->search).'%')
                ->orWhereHas('user', fn ($query) => $query
                    ->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhere('email', 'like', '%'.trim($this->search).'%'))))
            ->latest()
            ->paginate(25);
    }

    public function urlColor(string $status): string
    {
        return match ($status) {
            'scanned' => 'emerald',
            'failed' => 'rose',
            default => 'amber',
        };
    }

    #[Computed]
    public function historyUser(): ?User
    {
        return $this->historyUserId === null
            ? null
            : User::find($this->historyUserId);
    }

    #[Computed]
    public function historyRuns()
    {
        if ($this->historyUserId === null) {
            return collect();
        }

        return AutoScanRun::query()
            ->where('user_id', $this->historyUserId)
            ->latest()
            ->limit(50)
            ->get();
    }

    protected function selectableIds(): array
    {
        return $this->subscribers->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = User::withCount(['autoScanUrls', 'autoScanRuns'])
            ->with(['autoScanUrls' => fn ($query) => $query->orderByDesc('id')])
            ->whereIn('id', $this->selectedIds)
            ->latest()
            ->get();

        $rows = $selected->map(fn (User $user) => [
            $user->name,
            $user->email,
            $user->handle(),
            $user->autoScanActive() ? 'Active' : 'Off / expired',
            $user->auto_scan_active_until?->toDateString() ?? '—',
            $user->last_auto_scan_at?->toDateTimeString() ?? 'Never',
            (string) $user->autoScanUrls->where('status', 'queued')->count(),
            (string) $user->autoScanUrls->where('status', 'failed')->count(),
            (string) $user->auto_scan_urls_count,
            (string) $user->auto_scan_runs_count,
        ])->all();

        return [
            ['Name', 'Email', 'Handle', 'Status', 'Active until', 'Last scan', 'Queued', 'Failed', 'URLs', 'Runs'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected auto-scan subscribers';
    }

    protected function exportBasename(): string
    {
        return 'auto-scan-subscribers';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Auto-Scan</flux:heading>
        <flux:text>Monitor repo auto-scan subscribers, confirm activations and manage the URL scan queue.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold">{{ number_format($this->summary['subscribers']) }}</div>
            <div class="text-xs text-zinc-500">Subscribers</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->summary['active']) }}</div>
            <div class="text-xs text-zinc-500">Active now</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->summary['pending_payments']) }}</div>
            <div class="text-xs text-zinc-500">Pending payments</div>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="text-2xl font-bold tabular-nums text-emerald-600">{{ number_format($this->summary['revenue'], 2) }} {{ config('billing.currency', 'USD') }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Lifetime revenue · {{ number_format($this->summary['paid_payments']) }} paid</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->summary['queued_urls']) }}</div>
            <div class="text-xs text-zinc-500">Queued URLs</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-rose-500">{{ number_format($this->summary['failed_urls']) }}</div>
            <div class="text-xs text-zinc-500">Failed URLs</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->summary['scanned_urls']) }}</div>
            <div class="text-xs text-zinc-500">Scanned URLs</div>
        </div>
    </div>

    @if ($this->pendingPayments->isNotEmpty())
        <div>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <flux:heading size="sm">Pending activations</flux:heading>
                <flux:text>Confirming a payment activates auto-scan for the developer.</flux:text>
            </div>

            <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                            <th class="px-3 py-2.5 font-medium">Payment</th>
                            <th class="px-3 py-2.5 font-medium">Developer</th>
                            <th class="px-3 py-2.5 font-medium">Amount</th>
                            <th class="px-3 py-2.5 font-medium">Created</th>
                            <th class="px-3 py-2.5 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->pendingPayments as $payment)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                <td class="px-3 py-2.5 font-mono text-xs">#{{ $payment->id }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium">{{ $payment->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $payment->user?->email }}</div>
                                </td>
                                <td class="px-3 py-2.5 font-semibold tabular-nums">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                                <td class="px-3 py-2.5 text-zinc-500">{{ $payment->created_at->toDateString() }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="flex justify-end gap-1.5">
                                        <flux:button size="sm" variant="primary" wire:click="confirmPayment({{ $payment->id }})">Confirm</flux:button>
                                        <flux:button size="sm" variant="subtle" wire:click="cancelPayment({{ $payment->id }})">Cancel</flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Subscribers</flux:heading>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap gap-1 rounded-md bg-zinc-100 p-1 dark:bg-zinc-900">
                    @foreach ([
                        'all' => 'All',
                        'active' => 'Active',
                        'expired' => 'Expired',
                    ] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('status', '{{ $value }}')"
                            class="rounded-md px-2.5 py-1 text-xs font-medium {{ $this->status === $value ? 'bg-white text-zinc-900 shadow dark:bg-zinc-700 dark:text-white' : 'text-zinc-500' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="w-64">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search developer…" />
                </div>
                @if (count($this->selectedIds) > 0)
                    <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                    <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="document-arrow-down" variant="micro" />
                        PDF
                    </button>
                    <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                        <flux:icon name="table-cells" variant="micro" />
                        Excel
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="w-8 px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->subscribers->count() && $this->subscribers->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">Developer</th>
                        <th class="px-3 py-2.5 font-medium">Status</th>
                        <th class="px-3 py-2.5 font-medium">Active until</th>
                        <th class="px-3 py-2.5 font-medium">Last scan</th>
                        <th class="px-3 py-2.5 font-medium">Queue</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->subscribers as $user)
                        @php
                            $queued = $user->autoScanUrls->where('status', 'queued')->count();
                            $failed = $user->autoScanUrls->where('status', 'failed')->count();
                            $active = $user->autoScanActive();
                        @endphp
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($user->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $user->id }})" {{ in_array($user->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-3">
                                    <flux:avatar :src="$user->avatarUrl()" :alt="$user->name" circle class="size-8" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate font-medium">{{ $user->name }}</span>
                                            @if ($user->isVerified())
                                                <flux:icon name="check-badge" variant="micro" class="size-4 text-emerald-500" />
                                            @endif
                                        </div>
                                        <div class="truncate text-xs text-zinc-500">{{ $user->email }} · {{ $user->handle() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <flux:badge size="sm" inset="top bottom" color="{{ $active ? 'emerald' : 'amber' }}">
                                    {{ $active ? 'Active' : 'Off / expired' }}
                                </flux:badge>
                            </td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $user->auto_scan_active_until?->toDateString() ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $user->last_auto_scan_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex gap-1">
                                    @if ($queued > 0)
                                        <flux:badge size="sm" inset="top bottom" color="amber">{{ $queued }} queued</flux:badge>
                                    @endif
                                    @if ($failed > 0)
                                        <flux:badge size="sm" inset="top bottom" color="rose">{{ $failed }} failed</flux:badge>
                                    @endif
                                    @if ($queued === 0 && $failed === 0)
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5">
                                    <flux:button size="sm" variant="subtle" wire:click="showHistory({{ $user->id }})">
                                        History{{ $user->auto_scan_runs_count > 0 ? ' · '.number_format($user->auto_scan_runs_count) : '' }}
                                    </flux:button>
                                    <flux:button size="sm" variant="subtle" wire:click="scanNow({{ $user->id }})">
                                        Scan now
                                    </flux:button>
                                    <flux:button size="sm" variant="subtle" :href="route('devid', $user->handle())" target="_blank">
                                        DevID
                                    </flux:button>
                                    <flux:button size="sm" variant="subtle" wire:click="toggleAutoScan({{ $user->id }})">
                                        {{ $user->auto_scan_enabled ? 'Turn off' : 'Turn on' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No auto-scan subscribers match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->subscribers->hasPages())
            <div class="mt-4">
                {{ $this->subscribers->links() }}
            </div>
        @endif
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">URL scan queue</flux:heading>
            <flux:text>Every repository URL queued for AI scanning, across all developers.</flux:text>
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">Repository</th>
                        <th class="px-3 py-2.5 font-medium">Developer</th>
                        <th class="px-3 py-2.5 font-medium">Status</th>
                        <th class="px-3 py-2.5 font-medium">Last scanned</th>
                        <th class="px-3 py-2.5 font-medium">Queued</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->urls as $url)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="folder-git-2" variant="micro" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="max-w-64 truncate font-mono text-xs" title="{{ $url->url }}">{{ $url->url }}</span>
                                </div>
                                @if ($url->last_error)
                                    <div class="mt-1 truncate text-xs text-rose-500" title="{{ $url->last_error }}">{{ $url->last_error }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium">{{ $url->user?->name ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $url->user?->email }}</div>
                            </td>
                            <td class="px-3 py-2.5">
                                <flux:badge size="sm" inset="top bottom" :color="$this->urlColor($url->status)">
                                    {{ ucfirst($url->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $url->last_scanned_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $url->created_at->diffForHumans() }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5">
                                    @if ($url->status === 'failed')
                                        <flux:button size="sm" variant="subtle" wire:click="retryUrl({{ $url->id }})">Retry</flux:button>
                                    @endif
                                    <flux:button size="sm" variant="subtle" wire:click="removeUrl({{ $url->id }})">Remove</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No URLs in the scan queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->urls->hasPages())
            <div class="mt-4">
                {{ $this->urls->links() }}
            </div>
        @endif
    </div>

    <flux:modal name="scan-history" wire:model="showHistoryModal" class="w-full max-w-2xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Scan history</flux:heading>
                <flux:text>
                    Past auto-scan runs for
                    <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $this->historyUser?->name ?? 'this developer' }}</span>
                    and the XP each run awarded.
                </flux:text>
            </div>
            <flux:modal.close variant="ghost">Close</flux:modal.close>
        </div>

        <div class="mt-4 max-h-[60vh] overflow-y-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-zinc-800">
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
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
                    @forelse ($this->historyRuns as $run)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-2.5 whitespace-nowrap text-zinc-500" title="{{ $run->created_at->toDateTimeString() }}">{{ $run->created_at->diffForHumans() }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->scanned) }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_evidence) }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_projects) }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ number_format($run->new_journal) }}</td>
                            <td class="px-3 py-2.5">
                                @if ($run->xp > 0)
                                    <flux:badge size="sm" inset="top bottom" color="emerald">+{{ number_format($run->xp) }} XP</flux:badge>
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
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No scans have run for this developer yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $totalXp = $this->historyRuns->sum('xp');
        @endphp
        @if ($totalXp > 0)
            <div class="mt-3 flex items-center justify-between rounded-md bg-emerald-50 px-3 py-2 text-sm dark:bg-emerald-500/10">
                <span class="text-zinc-600 dark:text-zinc-300">Total XP awarded by auto-scan</span>
                <span class="font-bold text-emerald-600">+{{ number_format($totalXp) }} XP</span>
            </div>
        @endif
    </flux:modal>
</div>
