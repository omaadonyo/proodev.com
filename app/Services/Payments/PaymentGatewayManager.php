<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\BankTransferGateway;
use App\Services\Payments\Gateways\FlutterwaveGateway;
use App\Services\Payments\Gateways\PesapalGateway;
use App\Services\Payments\Gateways\WorldRemitGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const DRIVERS = [
        'bank' => BankTransferGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
        'pesapal' => PesapalGateway::class,
        'worldremit' => WorldRemitGateway::class,
    ];

    public function __construct(
        private readonly PaymentMethodSettings $settings,
    ) {}

    public function driver(PaymentMethod $method): PaymentGateway
    {
        $driver = self::DRIVERS[$method->value] ?? null;

        if (! $driver) {
            throw new InvalidArgumentException("No payment gateway for [{$method->value}].");
        }

        return app($driver);
    }
}
