<?php

use App\Enums\EvidenceStatus;
use App\Services\BillingService;
use App\Services\ProfileCompletionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Verify Your Identity')] class extends Component
{
    public string $shortName = '';

    public function mount(): void
    {
        $this->shortName = auth()->user()->handle();
    }

    public function purchase(): void
    {
        $this->validate(['shortName' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-_]+$/', 'min:3', 'max:30']]);

        $payment = app(BillingService::class)->createVerificationPayment(auth()->user(), strtolower($this->shortName));

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    #[Computed]
    public function isVerified(): bool
    {
        return auth()->user()->isVerified();
    }

    #[Computed]
    public function price(): float
    {
        return (float) config('billing.developer.verification.price', 8);
    }

    #[Computed]
    public function shortDomain(): string
    {
        return (string) config('billing.developer.verification.short_domain', 'proo.dev');
    }

    #[Computed]
    public function pendingPayment()
    {
        return auth()->user()
            ->payments()
            ->where('purpose', 'verification')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    #[Computed]
    public function completion(): int
    {
        return app(ProfileCompletionService::class)->percentage(auth()->user());
    }

    #[Computed]
    public function readiness(): array
    {
        $user = auth()->user();

        return [
            ['label' => 'Profile & bio filled out', 'done' => filled($user->headline) && filled($user->bio)],
            ['label' => 'Skills on your passport', 'done' => $user->skills()->exists()],
            ['label' => 'Shipped projects', 'done' => $user->projects()->whereNotNull('published_at')->exists()],
            ['label' => 'Analyzed evidence', 'done' => $user->evidence()->where('status', EvidenceStatus::Ready)->exists()],
            ['label' => 'Community vouches', 'done' => $user->approvedVouchesReceived()->exists()],
        ];
    }

    #[Computed]
    public function readinessPercent(): int
    {
        $done = collect($this->readiness)->where('done', true)->count();

        return (int) round($done / max(1, count($this->readiness)) * 100);
    }
}
?>

<div class="mx-auto w-full max-w-4xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Developer Verification</flux:heading>
            <flux:text>
                A one-time {{ number_format($this->price, 0) }} purchase that proves you're a real person behind your work.
            </flux:text>
        </div>

        @if ($this->isVerified)
            <div class="overflow-hidden rounded-2xl border border-emerald-300/50 bg-white p-8 text-center dark:border-emerald-400/20 dark:bg-zinc-800">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-400/10 text-emerald-500">
                    <flux:icon name="check-badge" class="size-8" />
                </div>
                <flux:heading class="mt-4">You're verified</flux:heading>
                <flux:text class="mt-2">
                    Your badge is live on your passport, and your short shareable link is ready.
                </flux:text>
                <div class="mx-auto mt-4 max-w-md">
                    @php($short = auth()->user()->shortLink())
                    <div
                        x-data="{ copied: false }"
                        class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <flux:icon name="link" variant="micro" class="size-4 shrink-0 text-accent" />
                        <a
                            href="{{ $short ?: route('passport', auth()->user()->handle()) }}"
                            wire:navigate
                            class="min-w-0 flex-1 truncate text-sm font-semibold text-accent hover:underline"
                        >
                            {{ $short ?: route('passport', auth()->user()->handle()) }}
                        </a>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $short ?: route('passport', auth()->user()->handle()) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                            class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <flux:icon name="{{ $short ? 'clipboard' : 'link' }}" variant="micro" class="size-3.5" />
                            <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">Share this link anywhere — it opens your public passport.</p>
                </div>

                <div class="mx-auto mt-8 max-w-md">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Passport strength</span>
                        <span class="tabular-nums">{{ $this->completion }}% complete</span>
                    </div>
                    <flux:progress :value="$this->completion" color="emerald" />
                    <p class="mt-2 text-xs text-zinc-500">A complete passport helps recruiters trust your verified badge even more.</p>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="grid gap-8 p-6 lg:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">One-time purchase</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-5xl font-bold tabular-nums text-zinc-900 dark:text-white">${{ number_format($this->price, 0) }}</span>
                            <span class="text-sm text-zinc-500">once · forever</span>
                        </div>

                        <div class="mt-6 grid gap-3">
                            @foreach ([
                                ['icon' => 'check-badge', 'title' => 'Verified badge', 'text' => 'A check badge on your passport, profile, and feed entries.'],
                                ['icon' => 'link', 'title' => 'Your own short link', 'text' => 'Reserve '.$this->shortDomain.'/<your-name> and link it anywhere.'],
                                ['icon' => 'shield-check', 'title' => 'Trust signal', 'text' => 'Recruiters and the community can see you\'re human at a glance.'],
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
                        <flux:heading size="sm">Checkout</flux:heading>
                        <flux:text>This build uses manual checkout — an admin confirms your payment.</flux:text>

                        <x-secure-checkout-notice class="mt-4 bg-white dark:bg-zinc-800/80" />

                        <form wire:submit="purchase" class="mt-5 grid gap-4">
                            <div>
                                <flux:field>
                                    <flux:label>{{ $this->shortDomain }}/<span class="text-zinc-400">your-name</span></flux:label>
                                    <flux:input wire:model="shortName" placeholder="your-name" />
                                    <flux:error name="shortName" />
                                </flux:field>
                            </div>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                <flux:icon name="shield-check" variant="micro" />
                                Purchase verification — ${{ number_format($this->price, 0) }}
                            </flux:button>
                        </form>

                        @if ($this->pendingPayment)
                            <div class="mt-4 rounded-lg border border-amber-300/40 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                                <div class="font-semibold">Checkout #{{ $this->pendingPayment->id }} awaiting confirmation</div>
                                <div class="mt-1">Your badge activates as soon as an admin confirms the payment.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="sm">Verification readiness</flux:heading>
                        <flux:text>Strengthen your passport before you verify so the badge carries maximum trust.</flux:text>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-3 py-1 text-sm font-bold text-accent">
                        <flux:icon name="chart-bar" variant="micro" />
                        {{ $this->readinessPercent }}% ready
                    </span>
                </div>

                <div class="mt-5">
                    <div class="mb-1 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Readiness</span>
                        <span class="tabular-nums">{{ $this->readinessPercent }}%</span>
                    </div>
                    <flux:progress :value="$this->readinessPercent" color="{{ $this->readinessPercent >= 80 ? 'emerald' : ($this->readinessPercent >= 40 ? 'amber' : 'rose') }}" />
                </div>

                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    @foreach ($this->readiness as $item)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full {{ $item['done'] ? 'bg-emerald-400/10 text-emerald-500' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">
                                <flux:icon name="{{ $item['done'] ? 'check' : 'minus' }}" variant="micro" />
                            </span>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $item['label'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $item['done'] ? 'Complete' : 'Incomplete' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-2 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-900">
                    <flux:icon name="information-circle" variant="micro" class="shrink-0" />
                    Readiness doesn't block verification — it just makes the badge more meaningful to recruiters.
                </div>
            </div>
        @endif
    </div>
</div>