<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Data\GatewayInitiation;
use App\Services\Payments\PaymentMethodSettings;

abstract class AbstractGateway implements PaymentGateway
{
    public function __construct(
        protected PaymentMethodSettings $settings,
    ) {}

    abstract protected function isConfigured(array $settings): bool;

    /**
     * Build the external redirect URL for a configured gateway.
     */
    abstract protected function buildRedirect(Payment $payment, array $settings): string;

    public function initiate(Payment $payment): GatewayInitiation
    {
        $settings = $this->settings->for($this->method());

        $reference = $this->generateReference($payment);

        $payment->update(['gateway_reference' => $reference]);

        if (! $this->isConfigured($settings)) {
            return new GatewayInitiation(
                payment: $payment,
                redirectUrl: route('payments.checkout', $payment),
                gatewayReference: $reference,
            );
        }

        return new GatewayInitiation(
            payment: $payment,
            redirectUrl: $this->buildRedirect($payment, $settings),
            gatewayReference: $reference,
        );
    }

    /**
     * Short 6-character alpha reference (letters only) shown to the payer so
     * manual payments can be matched by an admin.
     */
    protected function generateReference(Payment $payment): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return implode('', array_map(fn () => $alphabet[random_int(0, 25)], range(1, 6)));
    }

    protected function returnUrl(Payment $payment): string
    {
        return match ($payment->purpose) {
            PaymentPurpose::Verification => route('verify'),
            PaymentPurpose::Subscription, PaymentPurpose::JobPosts => $payment->company
                ? route('companies.manage', $payment->company)
                : route('subscription'),
            default => route('credits'),
        };
    }
}
