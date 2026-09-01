<?php

namespace App\Services\Payments\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\Payments\Data\GatewayInitiation;

interface PaymentGateway
{
    public function method(): PaymentMethod;

    /**
     * Start a checkout for the given pending payment.
     */
    public function initiate(Payment $payment): GatewayInitiation;

    /**
     * Verify an incoming webhook/callback payload for the payment.
     */
    public function verifyNotification(Payment $payment, array $payload): bool;
}
