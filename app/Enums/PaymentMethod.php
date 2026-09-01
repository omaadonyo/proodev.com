<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentMethod: string
{
    use HasLabels;

    case Bank = 'bank';
    case WorldRemit = 'worldremit';
    case Flutterwave = 'flutterwave';
    case Pesapal = 'pesapal';

    public const LABELS = [
        'bank' => 'Bank Transfer',
        'flutterwave' => 'Flutterwave',
        'pesapal' => 'Pesapal',
        'worldremit' => 'WorldRemit',
    ];

    public const ICONS = [
        'bank' => 'banknotes',
        'flutterwave' => 'credit-card',
        'pesapal' => 'device-phone-mobile',
        'worldremit' => 'device-phone-mobile',
    ];

    public const DESCRIPTIONS = [
        'bank' => 'Direct transfer to our bank account — admin confirms on arrival.',
        'flutterwave' => 'Pay instantly with Visa, Mastercard, Verve and mobile money.',
        'pesapal' => 'Pay with cards, mobile money, M-Pesa or bank via Pesapal.',
        'worldremit' => 'Send via WorldRemit to our MTN Mobile Money (Uganda) — admin confirms on arrival.',
    ];

    public function icon(): string
    {
        return self::ICONS[$this->value] ?? 'banknotes';
    }

    public function description(): string
    {
        return self::DESCRIPTIONS[$this->value] ?? '';
    }

    /**
     * Whether this method needs a redirect to an external gateway.
     */
    public function isGateway(): bool
    {
        return in_array($this, [self::Flutterwave, self::Pesapal], true);
    }

    /**
     * Whether this method is settled manually (instructions + admin confirmation).
     */
    public function isManual(): bool
    {
        return ! $this->isGateway();
    }
}
