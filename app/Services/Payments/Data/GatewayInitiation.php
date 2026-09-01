<?php

namespace App\Services\Payments\Data;

use App\Models\Payment;

/**
 * Result of initiating a checkout. A gateway either redirects the buyer to an
 * external checkout page, or (for bank transfer) presents payment instructions.
 */
class GatewayInitiation
{
    public function __construct(
        public readonly Payment $payment,
        public readonly ?string $redirectUrl = null,
        public readonly array $instructions = [],
        public readonly ?string $gatewayReference = null,
        public readonly ?string $checkoutId = null,
    ) {}

    public function redirects(): bool
    {
        return $this->redirectUrl !== null;
    }
}
