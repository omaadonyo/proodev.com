<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\Payments\Data\GatewayInitiation;

/**
 * Manual settlement via WorldRemit to a mobile-money account (MTN Mobile Money).
 * The buyer sends money, then an admin confirms the payment like bank transfer.
 */
class WorldRemitGateway extends AbstractGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::WorldRemit;
    }

    protected function isConfigured(array $settings): bool
    {
        return filled($settings['mobile_money_number'] ?? null);
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
                'pay_to' => (string) ($settings['payout_country'] ?? 'Uganda'),
                'mobile_money_provider' => (string) ($settings['mobile_money_provider'] ?? 'MTN Mobile Money'),
                'mobile_money_number' => (string) ($settings['mobile_money_number'] ?? ''),
                'account_name' => (string) ($settings['account_name'] ?? ''),
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
