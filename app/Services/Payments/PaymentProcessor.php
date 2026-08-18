<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\Payments\Data\GatewayInitiation;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates checkout for a pending payment and fulfils it once a gateway
 * confirms success.
 */
class PaymentProcessor
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentMethodSettings $settings,
        private readonly BillingService $billing,
    ) {}

    /**
     * Start a checkout for a pending payment using the given method.
     */
    public function initiate(Payment $payment, PaymentMethod $method): GatewayInitiation
    {
        if (! $this->settings->isEnabled($method)) {
            throw new \InvalidArgumentException("Payment method [{$method->value}] is disabled.");
        }

        $payment->update([
            'payment_method' => $method,
            'provider' => $method->value,
        ]);

        return $this->gateways->driver($method)->initiate($payment);
    }

    /**
     * Handle a gateway callback/notification. Returns true when the payment was
     * confirmed as a result of this call.
     */
    public function handleNotification(Payment $payment, array $payload): bool
    {
        if ($payment->status === PaymentStatus::Paid) {
            return false;
        }

        if (! $payment->payment_method) {
            return false;
        }

        $gateway = $this->gateways->driver($payment->payment_method);

        if (! $gateway->verifyNotification($payment, $payload)) {
            return false;
        }

        DB::transaction(function () use ($payment, $payload) {
            $payment->update([
                'gateway_data' => $payload,
                'gateway_reference' => $payment->gateway_reference
                    ?? (string) data_get($payload, 'data.tx_ref', data_get($payload, 'transaction_reference', '')),
            ]);

            $this->billing->markPaid($payment);
        });

        return true;
    }
}
