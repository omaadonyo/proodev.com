<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\Payments\PaymentMethodSettings;
use App\Services\Payments\PaymentProcessor;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component
{
    public Payment $payment;

    public ?string $method = null;

    public ?string $redirectUrl = null;

    public array $instructions = [];

    public ?string $gatewayReference = null;

    public function mount(Payment $payment): void
    {
        abort_unless($payment->user_id === auth()->id(), 403);

        // Once an admin has already marked the payment paid (or refunded),
        // there is nothing left to do here - send the customer to the receipt.
        if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
            $this->payment = $payment;
            $this->redirect(route('invoices.show', $payment), navigate: true);

            return;
        }

        abort_unless($payment->status === PaymentStatus::Pending, 404);

        $this->payment = $payment;

        $settings = app(PaymentMethodSettings::class);
        $usable = collect($settings->usableMethods());

        $this->method = $payment->payment_method?->value
            ?? $usable->first()?->value;

        if ($this->method && $payment->payment_method) {
            $this->initiateCheckout(PaymentMethod::tryFrom($this->method), false);
        }
    }

    public function selectMethod(string $method): void
    {
        $this->method = $method;

        $this->initiateCheckout(PaymentMethod::tryFrom($method));
    }

    #[Computed]
    public function methods(): array
    {
        return app(PaymentMethodSettings::class)->usableMethods();
    }

    #[Computed]
    public function returnUrl(): string
    {
        return match ($this->payment->purpose) {
            PaymentPurpose::Verification => route('verify'),
            PaymentPurpose::Subscription => route('subscription'),
            default => route('credits'),
        };
    }

    #[Computed]
    public function isSimulated(): bool
    {
        return $this->redirectUrl !== null
            && str_contains($this->redirectUrl, route('payments.checkout', $this->payment, false));
    }

    public function goToGateway(): void
    {
        if ($this->redirectUrl) {
            $this->redirect($this->redirectUrl);
        }
    }

    /**
     * The customer says they have sent a manual payment (WorldRemit / bank
     * transfer). Record the submission and alert admins to verify it.
     */
    public function confirmPayment(): void
    {
        $this->payment->refresh();

        // The admin already marked this paid while the customer had the page
        // open - hide the confirm action and take them straight to the receipt.
        if (in_array($this->payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
            $this->redirect(route('invoices.show', $this->payment), navigate: true);

            return;
        }

        if ($this->payment->status !== PaymentStatus::Pending) {
            abort(404);
        }

        $method = $this->payment->payment_method;

        if (! $method || ! $method->isManual()) {
            abort(403);
        }

        if ($this->payment->confirmedByCustomer()) {
            Flux::toast(variant: 'warning', text: 'Payment already submitted, awaiting admin confirmation.');

            return;
        }

        $this->payment->update(['customer_confirmed_at' => now()]);
        $this->payment->refresh();

        app(NotificationService::class)->paymentSubmittedByCustomer($this->payment);

        Flux::toast(variant: 'success', text: 'Payment submitted. We will confirm it shortly.');
    }

    private function initiateCheckout(?PaymentMethod $method, bool $toast = true): void
    {
        if (! $method) {
            $this->redirectUrl = null;
            $this->instructions = [];

            return;
        }

        try {
            $initiation = app(PaymentProcessor::class)->initiate($this->payment, $method);

            $this->redirectUrl = $initiation->redirectUrl;
            $this->instructions = $initiation->instructions;
            $this->gatewayReference = $initiation->gatewayReference;

            if ($toast) {
                Flux::toast(variant: 'success', text: 'Checkout ready. Continue below to pay.');
            }
        } catch (InvalidArgumentException $e) {
            $this->redirectUrl = null;
            $this->instructions = [];

            if ($toast) {
                Flux::toast(variant: 'danger', text: $e->getMessage());
            }
        }
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="grid gap-6">
        <div>
            <flux:heading size="xl">Checkout</flux:heading>
            <flux:text>Confirm your payment to unlock your purchase.</flux:text>
        </div>

        <div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ $this->payment->purpose->label() }}</flux:heading>
                    <flux:text>Reference #{{ $this->payment->id }}</flux:text>
                </div>
                <span class="text-2xl font-bold tabular-nums">
                    {{ number_format((float) $this->payment->amount, 2) }} {{ $this->payment->currency }}
                </span>
            </div>

            <div class="mt-6">
                <flux:heading size="sm">Choose a payment method</flux:heading>
                <div class="mt-3 grid gap-2">
                    @foreach ($this->methods as $option)
                        <button
                            type="button"
                            wire:click="selectMethod('{{ $option->value }}')"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-left text-sm transition {{ $this->method === $option->value ? 'border-accent bg-accent/5' : 'border-zinc-200 hover:border-accent dark:border-zinc-700' }}"
                        >
                            <span class="flex size-5 items-center justify-center rounded-full border {{ $this->method === $option->value ? 'border-accent' : 'border-zinc-300 dark:border-zinc-600' }}">
                                @if ($this->method === $option->value)
                                    <span class="size-2.5 rounded-full bg-accent"></span>
                                @endif
                            </span>
                            <x-payment-method-logo :method="$option" class="shrink-0" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 font-medium">
                                    {{ $option->label() }}
                                    @if ($this->method === $option->value)
                                        <span class="text-xs text-accent">Selected</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-xs text-zinc-500">{{ $option->description() }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <x-secure-checkout-notice class="mt-3 bg-zinc-50 dark:bg-zinc-900/50" />
            </div>

            @if ($this->instructions && $this->payment->payment_method?->isManual())
                <div class="mt-6 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="font-semibold">{{ $this->payment->payment_method->label() }} instructions</div>
                        @if ($this->payment->payment_method === \App\Enums\PaymentMethod::WorldRemit)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ffcb05] px-2.5 py-1 text-[11px] font-bold text-black">
                                MTN Mobile Money
                            </span>
                        @endif
                    </div>
                    <div class="mt-3 grid gap-2 text-sm">
                        @foreach ($this->instructions as $label => $value)
                            @if ($value)
                                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-1.5 dark:border-zinc-700">
                                    <span class="text-zinc-500">{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                                    <span class="font-mono font-medium">{{ $value }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-zinc-500">
                        Include the reference above with your payment so we can match it. An admin confirms the payment once it arrives.
                    </p>

                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        @if ($this->payment->confirmedByCustomer())
                            <div class="flex items-center gap-2 rounded-lg bg-emerald-400/10 px-3 py-2.5 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                <flux:icon name="check-circle" variant="micro" />
                                Payment submitted {{ $this->payment->customer_confirmed_at?->diffForHumans() }}, awaiting admin confirmation.
                            </div>
                        @else
                            <div>
                                <flux:button variant="primary" wire:click="confirmPayment">
                                    <flux:icon name="check" variant="micro" />
                                    I've sent the payment, confirm
                                </flux:button>
                                <p class="mt-2 text-xs text-zinc-500">
                                    This only notifies our team to verify the transfer. No invoice is generated by confirming.
                                </p>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
                                <flux:icon name="document-text" variant="micro" />
                                Prefer to pay later?
                                <a href="{{ route('invoices.show', $this->payment) }}" target="_blank" class="font-medium text-accent hover:underline">
                                    Create a pending invoice
                                </a>
                                then pay after it's generated, and come back and confirm.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($this->redirectUrl)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <flux:button variant="primary" wire:click="goToGateway">
                        <flux:icon name="arrow-up-right" variant="micro" />
                        Continue to {{ $this->payment->payment_method?->label() }}
                    </flux:button>
                    @if ($this->isSimulated)
                        <span class="text-xs text-zinc-400">Simulated checkout (no gateway credentials configured)</span>
                    @endif
                </div>
            @endif

            <div class="mt-6">
                <flux:button variant="subtle" :href="$this->returnUrl" wire:navigate>
                    Back
                </flux:button>
            </div>
        </div>
    </div>
</div>