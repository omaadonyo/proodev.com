<?php

use App\Enums\CompanyPlan;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Services\BillingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscription')] class extends Component
{
    public function upgrade(string $plan): void
    {
        $company = $this->company;

        abort_unless($company, 404);

        $planEnum = CompanyPlan::tryFrom($plan);

        abort_unless(in_array($planEnum, [CompanyPlan::Recruiter, CompanyPlan::Intelligence], true), 403);

        app(BillingService::class)->createSubscriptionPayment($company, $planEnum);

        $payment = Payment::query()
            ->where('company_id', $company->id)
            ->where('purpose', 'subscription')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    public function verifyCompany(): void
    {
        $company = $this->company;

        abort_unless($company, 404);

        app(BillingService::class)->createCompanyVerificationPayment($company);

        $payment = Payment::query()
            ->where('company_id', $company->id)
            ->where('purpose', 'verification')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $this->redirectRoute('checkout', $payment, navigate: true);
    }

    #[Computed]
    public function company(): ?Company
    {
        return auth()->user()->ownedCompany();
    }

    #[Computed]
    public function pendingPayments()
    {
        $company = $this->company;

        if (! $company) {
            return collect();
        }

        return Payment::query()
            ->where('company_id', $company->id)
            ->where('purpose', 'subscription')
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function pendingVerification()
    {
        $company = $this->company;

        if (! $company) {
            return null;
        }

        return Payment::query()
            ->where('company_id', $company->id)
            ->where('purpose', 'verification')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    #[Computed]
    public function recruiterPrice(): int
    {
        return (int) config('billing.companies.recruiter.price', 299);
    }

    #[Computed]
    public function intelligencePrice(): int
    {
        return (int) config('billing.companies.intelligence.price', 199);
    }

    #[Computed]
    public function intelligenceFirstMonth(): int
    {
        return (int) config('billing.companies.intelligence.first_month_price', 599);
    }

    #[Computed]
    public function verificationPrice(): int
    {
        return (int) config('billing.companies.verification.price', 299);
    }

    #[Computed]
    public function invoices()
    {
        $company = $this->company;

        if (! $company) {
            return collect();
        }

        return Payment::query()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Paid)
            ->latest('paid_at')
            ->limit(20)
            ->get();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Subscription</flux:heading>
            <flux:text>Manage your recruiting plan, company verification, and pending checkouts.</flux:text>
        </div>

        @if (! $this->company)
            <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">No company yet</flux:heading>
                <p class="mt-2 text-sm text-zinc-500">Create a company profile first to unlock recruiting plans and verification.</p>
                <flux:button class="mt-4" variant="primary" :href="route('companies.create')">Create your company</flux:button>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Current plan</div>
                    <div class="mt-2 text-2xl font-bold">{{ $this->company->plan->label() }}</div>
                    <div class="mt-1 text-xs text-zinc-500">{{ $this->company->plan->isPaid() ? 'Recruiting active' : 'Free plan — upgrade to recruit at scale' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Status</div>
                    <div class="mt-2 text-2xl font-bold">{{ $this->company->status->label() }}</div>
                    <div class="mt-1 text-xs text-zinc-500">Approved companies can post jobs</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Company verification</div>
                    @if ($this->company->isApproved())
                        <div class="mt-2 text-2xl font-bold text-emerald-600">Verified</div>
                        <div class="mt-1 text-xs text-zinc-500">Your company badge is active</div>
                    @else
                        <div class="mt-2 text-2xl font-bold text-amber-600">Unverified</div>
                        <div class="mt-1 text-xs text-zinc-500">Pay the ${{ number_format($this->verificationPrice) }} fee to get verified</div>
                    @endif
                </div>
            </div>

            @if ($this->pendingVerification)
                <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                    <div class="font-semibold">Company verification checkout awaiting confirmation</div>
                    <div class="mt-1 text-xs">Once an admin confirms the ${{ number_format($this->verificationPrice) }} payment, your verified badge unlocks.</div>
                </div>
            @endif

            @if ($this->pendingPayments->isNotEmpty())
                <div class="rounded-xl border border-amber-300/40 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                    <div class="font-semibold">Upgrade checkout awaiting confirmation</div>
                    <div class="mt-1 text-xs">This build uses manual checkout — your plan activates once an admin confirms the payment.</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->pendingPayments as $payment)
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium dark:bg-zinc-900">
                                #{{ $payment->id }} · {{ number_format($payment->amount, 2) }} {{ $payment->currency }} · {{ \App\Enums\CompanyPlan::tryFrom($payment->metadata['plan'] ?? '')?->label() ?? $payment->purpose->label() }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <flux:heading size="sm">Choose your tier</flux:heading>
                <flux:text>Recruiters and companies share one feature set.</flux:text>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Recruiter</span>
                            <flux:badge size="sm" inset="top bottom" color="zinc">Standard</flux:badge>
                        </div>
                        <div class="mt-2 text-3xl font-bold tabular-nums">${{ number_format($this->recruiterPrice) }}</div>
                        <div class="text-xs text-zinc-500">per month · cancel anytime</div>
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach (config('billing.companies.recruiter.features', []) as $feature)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $feature }}</span>
                            @endforeach
                        </div>
                        <flux:button class="mt-4 w-full" variant="{{ $this->company->plan === \App\Enums\CompanyPlan::Recruiter ? 'outline' : 'primary' }}" wire:click="upgrade('recruiter')" :disabled="$this->company->plan === \App\Enums\CompanyPlan::Recruiter">
                            {{ $this->company->plan === \App\Enums\CompanyPlan::Recruiter ? 'Current plan' : 'Upgrade to Recruiter' }}
                        </flux:button>
                    </div>

                    <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Recruiter Intelligence Suite</span>
                            <flux:badge size="sm" variant="success" inset="top bottom">Most powerful</flux:badge>
                        </div>
                        <div class="mt-2 text-3xl font-bold tabular-nums">${{ number_format($this->intelligenceFirstMonth) }}</div>
                        <div class="text-xs text-zinc-500">first month · then ${{ number_format($this->intelligencePrice) }}/month</div>
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach (config('billing.companies.intelligence.features', []) as $feature)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">{{ $feature }}</span>
                            @endforeach
                        </div>
                        <flux:button class="mt-4 w-full" variant="{{ $this->company->plan === \App\Enums\CompanyPlan::Intelligence ? 'outline' : 'primary' }}" wire:click="upgrade('intelligence')" :disabled="$this->company->plan === \App\Enums\CompanyPlan::Intelligence">
                            {{ $this->company->plan === \App\Enums\CompanyPlan::Intelligence ? 'Current plan' : 'Get the Intelligence Suite' }}
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold">Company verification</div>
                        <div class="text-xs text-zinc-500">A one-time ${{ number_format($this->verificationPrice) }} fee unlocks a verified badge on your company profile and priority listing in talent search.</div>
                    </div>
                    @unless ($this->company->isApproved())
                        <flux:button variant="primary" wire:click="verifyCompany">Verify company - ${{ number_format($this->verificationPrice) }}</flux:button>
                    @endunless
                </div>
            </div>

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
        @endif
    </div>
</div>