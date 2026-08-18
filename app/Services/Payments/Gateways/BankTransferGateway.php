<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\Payments\Data\GatewayInitiation;

class BankTransferGateway extends AbstractGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Bank;
    }

    protected function isConfigured(array $settings): bool
    {
        return filled($settings['account_number'] ?? null);
    }

    protected function buildRedirect(Payment $payment, array $settings): string
    {
        return route('payments.checkout', $payment);
    }

    public function initiate(Payment $payment): GatewayInitiation
    {
        $settings = $this->settings->for($this->method());
        $reference = $this->generateReference($payment);

        $payment->update(['gateway_reference' => $reference]);

        return new GatewayInitiation(
            payment: $payment,
            redirectUrl: null,
            instructions: [
                'bank_name' => (string) ($settings['bank_name'] ?? ''),
                'account_name' => (string) ($settings['account_name'] ?? ''),
                'account_number' => (string) ($settings['account_number'] ?? ''),
                'bank_code' => (string) ($settings['bank_code'] ?? ''),
                'reference' => $reference,
            ],
            gatewayReference: $reference,
        );
    }

    public function verifyNotification(Payment $payment, array $payload): bool
    {
        return false;
    }
}
