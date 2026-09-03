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

    public string $plan = 'lifetime';

    public function mount(): void
    {
        $this->shortName = auth()->user()->handle();
    }

    public function purchase(): void
    {
        $validated = $this->validate([
            'shortName' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-_]+$/', 'min:3', 'max:30'],
            'plan' => ['required', 'in:lifetime,monthly'],
        ]);

        $payment = app(BillingService::class)->createVerificationPayment(auth()->user(), strtolower($validated['shortName']), $validated['plan']);

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    #[Computed]
    public function isVerified(): bool
    {
        return auth()->user()->isVerified();
    }

    #[Computed]
    public function lifetimePrice(): float
    {
        return (float) config('billing.developer.verification.lifetime_price', 17);
    }

    #[Computed]
    public function monthlyPrice(): float
    {
        return (float) config('billing.developer.verification.monthly_price', 8);
    }

    #[Computed]
    public function price(): float
    {
        return $this->plan === 'monthly' ? $this->monthlyPrice : $this->lifetimePrice;
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
            ['label' => 'Skills on your DevID', 'done' => $user->skills()->exists()],
            ['label' => 'Shipped projects', 'done' => $user->projects()->whereNotNull('published_at')->exists()],
            ['label' => 'Analyzed evidence', 'done' => $user->evidence()->where('status', EvidenceStatus::Ready)->exists()],
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

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-8">
        {{-- Professional header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-white via-zinc-50/50 to-white p-6 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900 sm:p-8">
            <div class="pointer-events-none absolute -right-12 -top-12 size-64 rounded-full bg-[#3750eb]/5 blur-3xl dark:bg-[#3750eb]/10" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[#3750eb]/15 bg-[#3750eb]/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-[#3750eb] dark:border-[#3750eb]/20 dark:bg-[#3750eb]/10 dark:text-[#8f9dff]">
                        <flux:icon name="shield-check" variant="micro" class="size-3.5" />
                        Identity verification
                    </div>
                    <flux:heading size="xl" class="mt-3 !text-[1.75rem] font-extrabold tracking-tight sm:!text-3xl">Developer Verification</flux:heading>
                    <flux:text class="mt-2 max-w-xl text-sm leading-relaxed sm:text-[15px]">
                        Prove you're a real person behind your work. Get a verified badge, a short <span class="font-semibold text-zinc-900 dark:text-white">{{ $this->shortDomain }}/your-name</span> link, and stronger trust with recruiters. Lifetime <span class="font-semibold text-zinc-900 dark:text-white">${{ number_format($this->lifetimePrice, 0) }} once</span> or <span class="font-semibold text-zinc-900 dark:text-white">${{ number_format($this->monthlyPrice, 0) }}/month</span>.
                    </flux:text>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] font-medium text-zinc-500">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 dark:bg-white/5"><flux:icon name="lock-closed" variant="micro" class="size-3.5 text-emerald-500" /> Manual checkout, admin confirmed</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 dark:bg-white/5"><flux:icon name="shield-check" variant="micro" class="size-3.5 text-[#3750eb]" /> Verified badge on DevID & feed</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 dark:bg-white/5"><flux:icon name="link" variant="micro" class="size-3.5 text-zinc-500" /> Short link included</span>
                    </div>
                </div>
                <div class="hidden shrink-0 items-center gap-3 sm:flex">
                    <div class="flex -space-x-2">
                        <span class="flex size-9 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-zinc-900 dark:ring-zinc-900">✓</span>
                        <span class="flex size-9 items-center justify-center rounded-full bg-[#3750eb] text-xs font-bold text-white ring-2 ring-white dark:ring-zinc-900">ID</span>
                    </div>
                    <div class="text-xs leading-tight">
                        <div class="font-semibold text-zinc-900 dark:text-white">Trusted verification</div>
                        <div class="text-zinc-500">Badge • Link • Trust signal</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($this->isVerified)
            <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50/50 via-white to-white shadow-sm dark:border-emerald-900/30 dark:from-emerald-950/20 dark:via-zinc-900 dark:to-zinc-900">
                <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="text-center sm:text-left">
                        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 sm:mx-0">
                            <flux:icon name="check-badge" class="size-8" />
                        </div>
                        <flux:heading class="mt-4 !text-2xl font-extrabold tracking-tight">You're verified</flux:heading>
                        <flux:text class="mt-2 max-w-md">
                            Your badge is live on your DevID, feed entries, and search. Your short shareable link is ready.
                        </flux:text>
                        <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white"><flux:icon name="check" variant="micro" class="size-3.5" /> Verified badge active</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-white/5 dark:text-zinc-300">ProoDev | info@proodev.com</span>
                        </div>
                        <div class="mt-6 max-w-md">
                            @php($short = auth()->user()->shortLink())
                            <div class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500">Your short link</div>
                            <div
                                x-data="{ copied: false }"
                                class="mt-2 flex items-center gap-2 rounded-xl bg-zinc-100 p-2 dark:bg-white/5"
                            >
                                <span class="flex size-8 items-center justify-center rounded-lg bg-white text-[#3750eb] shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700"><flux:icon name="link" variant="micro" class="size-4" /></span>
                                <a
                                    href="{{ $short ?: route('devid', auth()->user()->handle()) }}"
                                    wire:navigate
                                    class="min-w-0 flex-1 truncate text-sm font-semibold text-zinc-900 hover:underline dark:text-white"
                                >
                                    {{ $short ?: route('devid', auth()->user()->handle()) }}
                                </a>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $short ?: route('devid', auth()->user()->handle()) }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-md transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                                >
                                    <flux:icon name="clipboard-document" variant="micro" class="size-3.5" />
                                    <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">Share this link anywhere. It opens your public DevID. Works on résumés, GitHub, LinkedIn.</p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">DevID strength</div>
                            <span class="rounded-full bg-zinc-900 px-2.5 py-1 text-xs font-bold text-white dark:bg-white dark:text-zinc-900">{{ $this->completion }}%</span>
                        </div>
                        <div class="mt-3">
                            <flux:progress :value="$this->completion" color="emerald" />
                            <p class="mt-2 text-xs leading-relaxed text-zinc-500">A complete DevID makes your verified badge more meaningful to recruiters. Add projects, evidence, and vouches to grow this score.</p>
                        </div>
                        <div class="mt-4 grid gap-2">
                            @foreach ([
                                ['icon' => 'check-badge', 'title' => 'Verified badge', 'desc' => 'Visible on your DevID, feed, and search'],
                                ['icon' => 'link', 'title' => 'Short link', 'desc' => $this->shortDomain.'/your-name ready to share'],
                                ['icon' => 'shield-check', 'title' => 'Trust signal', 'desc' => 'Recruiters see you as a real, human-verified engineer'],
                            ] as $perk)
                                <div class="flex items-center gap-3 rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"><flux:icon name="{{ $perk['icon'] }}" variant="micro" class="size-4" /></span>
                                    <div>
                                        <div class="text-xs font-semibold text-zinc-900 dark:text-white">{{ $perk['title'] }}</div>
                                        <div class="text-[11px] text-zinc-500">{{ $perk['desc'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('devid', auth()->user()->handle()) }}" wire:navigate class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">View your public DevID <flux:icon name="arrow-top-right-on-square" variant="micro" class="size-4" /></a>
                    </div>
                </div>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl bg-zinc-100 shadow-sm dark:bg-white/5">
                <div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="p-6 sm:p-7">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Choose your plan</div>
                            <span class="hidden items-center gap-1.5 rounded-full bg-zinc-900 px-2.5 py-1 text-[10px] font-semibold text-white dark:bg-white dark:text-zinc-900 sm:inline-flex">ProoDev | info@proodev.com</span>
                        </div>

                        <div class="mt-4 grid gap-3" role="radiogroup" aria-label="Verification plan">
                            {{-- Lifetime - $17 once --}}
                            <button
                                type="button"
                                wire:click="$set('plan', 'lifetime')"
                                class="group flex items-center justify-between gap-4 rounded-xl border p-4 text-left transition-all {{ $this->plan === 'lifetime' ? 'border-[#3750eb] bg-[#3750eb]/5 shadow-md ring-2 ring-[#3750eb]/20' : 'bg-zinc-100 hover:shadow-sm dark:bg-white/5' }}"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-zinc-900 dark:text-white">Lifetime</span>
                                        <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Best value</span>
                                         <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-white/5">One-time</span>
                                    </div>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-500">One payment, verified forever. No renewals. Perfect for long-term trust.</p>
                                    <div class="mt-2 hidden items-center gap-2 text-[11px] text-zinc-500 sm:flex"><flux:icon name="check" variant="micro" class="size-3.5 text-emerald-500" /> Badge + short link forever</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="flex items-baseline justify-end gap-1">
                                        <span class="text-2xl font-extrabold tabular-nums tracking-tight text-zinc-900 dark:text-white">${{ number_format($this->lifetimePrice, 0) }}</span>
                                    </div>
                                    <div class="text-[11px] font-semibold text-zinc-500">once · forever</div>
                                    <div class="mt-1 hidden text-[10px] font-medium text-emerald-600 dark:text-emerald-400 sm:block">Save vs monthly</div>
                                </div>
                            </button>

                            {{-- Monthly - $8/month --}}
                            <button
                                type="button"
                                wire:click="$set('plan', 'monthly')"
                                class="group flex items-center justify-between gap-4 rounded-xl border p-4 text-left transition-all {{ $this->plan === 'monthly' ? 'border-[#3750eb] bg-[#3750eb]/5 shadow-md ring-2 ring-[#3750eb]/20' : 'bg-zinc-100 hover:shadow-sm dark:bg-white/5' }}"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-zinc-900 dark:text-white">Monthly</span>
                                        <span class="rounded-full bg-sky-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Flexible</span>
                                        <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[10px] font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">30 days</span>
                                    </div>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-500">Renew each month. Cancel anytime by not renewing. Great to try first.</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="flex items-baseline justify-end gap-1">
                                        <span class="text-2xl font-extrabold tabular-nums tracking-tight text-zinc-900 dark:text-white">${{ number_format($this->monthlyPrice, 0) }}</span>
                                        <span class="text-xs font-semibold text-zinc-500">/mo</span>
                                    </div>
                                    <div class="text-[11px] font-medium text-zinc-500">recurring · 30 days</div>
                                </div>
                            </button>
                        </div>

                        <flux:error name="plan" class="mt-2" />

                        <div class="mt-6 grid gap-3">
                            <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">What you get</div>
                            @foreach ([
                                ['icon' => 'check-badge', 'title' => 'Verified badge', 'text' => 'Check badge on your DevID, profile, and every feed entry.'],
                                ['icon' => 'link', 'title' => 'Your own short link', 'text' => 'Reserve '.$this->shortDomain.'/<your-name> and link it anywhere.'],
                                ['icon' => 'shield-check', 'title' => 'Trust signal for recruiters', 'text' => 'Recruiters and the community see you\'re human at a glance.'],
                            ] as $benefit)
                                <div class="flex items-start gap-3 rounded-xl bg-zinc-100 p-3.5 transition hover:bg-white dark:bg-white/5 dark:hover:bg-zinc-800">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-[#3750eb] shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-[#8f9dff] dark:ring-zinc-700">
                                        <flux:icon name="{{ $benefit['icon'] }}" variant="micro" class="size-4" />
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $benefit['title'] }}</div>
                                        <div class="text-xs leading-relaxed text-zinc-500">{{ $benefit['text'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-lg bg-zinc-100 px-3 py-2.5 text-xs leading-relaxed text-zinc-500 dark:bg-white/5">
                            Questions? <a href="mailto:info@proodev.com" class="font-semibold text-[#3750eb] hover:underline dark:text-[#8f9dff]">info@proodev.com</a> · Invoices & receipts include full billing details.
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:p-7 lg:border-l lg:border-t-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <flux:heading size="sm" class="!text-base font-bold">Checkout</flux:heading>
                                <flux:text class="mt-1 text-xs">Manual checkout. An admin confirms your payment. Badge activates instantly after confirmation.</flux:text>
                            </div>
                            <span class="hidden shrink-0 rounded-full bg-emerald-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white sm:inline-flex">Secure</span>
                        </div>

                        <x-secure-checkout-notice class="mt-4 bg-white dark:bg-zinc-800/80" />

                        <form wire:submit="purchase" class="mt-5 grid gap-4">
                            <div>
                                <flux:field>
                                    <flux:label class="!text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ $this->shortDomain }}/<span class="text-zinc-400">your-name</span> <span class="font-normal normal-case tracking-normal text-zinc-400">, 3-30 chars, letters/numbers/-/_</span></flux:label>
                                    <flux:input wire:model="shortName" placeholder="your-name" class="!rounded-xl" />
                                    <flux:error name="shortName" />
                                    <flux:description class="mt-1 text-[11px]">This becomes your shareable ProoDev link. You can change it later via support.</flux:description>
                                </flux:field>
                            </div>
                            <div class="rounded-xl bg-zinc-100 p-3 dark:bg-white/5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-500">Due today</span>
                                    <span class="text-lg font-extrabold tabular-nums text-zinc-900 dark:text-white">${{ number_format($this->price, 0) }} <span class="text-xs font-semibold text-zinc-500">{{ $this->plan === 'monthly' ? '/ month' : 'once' }}</span></span>
                                </div>
                                <div class="mt-1 text-[11px] text-zinc-500">{{ $this->plan === 'monthly' ? 'Billed monthly · Cancel by not renewing' : 'One-time payment · Verified forever' }} · ProoDev | info@proodev.com</div>
                            </div>
                            <flux:button type="submit" variant="primary" class="w-full !rounded-xl !py-3 text-sm font-semibold" wire:loading.attr="disabled">
                                <flux:icon name="shield-check" variant="micro" />
                                <span wire:loading.remove wire:target="purchase">@if ($this->plan === 'monthly') Subscribe · ${{ number_format($this->monthlyPrice, 0) }}/month @else Purchase verification · ${{ number_format($this->lifetimePrice, 0) }} @endif</span>
                                <span wire:loading wire:target="purchase">Processing…</span>
                            </flux:button>
                            <p class="text-center text-[11px] text-zinc-500">By continuing you agree to ProoDev verification terms. Need help? <a href="mailto:info@proodev.com" class="font-semibold text-zinc-900 hover:underline dark:text-white">Contact support</a>.</p>
                        </form>

                        @if ($this->pendingPayment)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-relaxed text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                                <div class="flex items-center gap-2 font-semibold"><flux:icon name="clock" variant="micro" class="size-4" /> Checkout #{{ $this->pendingPayment->id }} awaiting confirmation</div>
                                <div class="mt-1">Your badge activates as soon as an admin confirms the payment. You’ll get an email at <span class="font-semibold">{{ auth()->user()->email }}</span>.</div>
                            </div>
                        @endif
                        <div class="mt-4 flex items-center justify-center gap-2 text-[11px] text-zinc-400">
                            <flux:icon name="lock-closed" variant="micro" class="size-3.5" /> Secure manual verification · Tax included
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-zinc-100 p-6 dark:bg-white/5 sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-500 dark:bg-white/5">
                            <flux:icon name="chart-bar" variant="micro" class="size-3.5 text-[#3750eb]" /> Verification readiness
                        </div>
                        <flux:heading size="sm" class="mt-3 !text-base font-bold">Make your badge count more</flux:heading>
                        <flux:text class="mt-1 max-w-xl text-xs leading-relaxed">Strengthen your DevID before you verify so the badge carries maximum trust with recruiters and the community. This is optional. You can verify now and improve later.</flux:text>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-[#3750eb] px-3 py-1.5 text-xs font-bold text-white shadow-sm dark:bg-[#3750eb]">
                        <flux:icon name="sparkles" variant="micro" class="size-3.5" />
                        {{ $this->readinessPercent }}% ready
                    </span>
                </div>

                <div class="mt-6">
                    <div class="mb-1.5 flex items-center justify-between text-[11px] font-medium text-zinc-500">
                        <span>Overall readiness</span>
                        <span class="tabular-nums font-semibold text-zinc-900 dark:text-white">{{ $this->readinessPercent }}%</span>
                    </div>
                    <flux:progress :value="$this->readinessPercent" color="{{ $this->readinessPercent >= 80 ? 'emerald' : ($this->readinessPercent >= 40 ? 'amber' : 'rose') }}" />
                    <div class="mt-2 flex items-center justify-between text-[11px] text-zinc-400">
                        <span>0%</span><span>50%</span><span>100%</span>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($this->readiness as $item)
                        <div class="flex items-center gap-3 rounded-xl border p-3.5 text-sm transition {{ $item['done'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/30 dark:bg-emerald-950/20' : 'bg-zinc-100 dark:bg-white/5' }}">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $item['done'] ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-zinc-400 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-zinc-700' }}">
                                <flux:icon name="{{ $item['done'] ? 'check' : 'minus' }}" variant="micro" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $item['label'] }}</div>
                                <div class="text-xs {{ $item['done'] ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'text-zinc-500' }}">{{ $item['done'] ? 'Complete, great signal for recruiters' : 'Incomplete, add this to strengthen trust' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex items-start gap-3 rounded-xl border border-[#3750eb]/15 bg-[#3750eb]/5 p-3.5 dark:border-[#3750eb]/20 dark:bg-[#3750eb]/10">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white text-[#3750eb] shadow-sm ring-1 ring-[#3750eb]/10 dark:bg-zinc-900"><flux:icon name="information-circle" variant="micro" class="size-4" /></span>
                    <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-300"><span class="font-semibold text-zinc-900 dark:text-white">Doesn’t block verification.</span> You can verify now even at 0% readiness. The badge is about identity, not completeness. A stronger DevID just makes the trust signal more meaningful.</p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('devid', auth()->user()->handle()) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:bg-white/5 dark:text-zinc-200">View DevID <flux:icon name="arrow-top-right-on-square" variant="micro" class="size-3.5" /></a>
                    <a href="{{ route('projects.create') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900">Add evidence <flux:icon name="plus" variant="micro" class="size-3.5" /></a>
                </div>
            </div>
        @endif
    </div>
</div>