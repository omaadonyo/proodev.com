<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public const LABELS = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
        'cancelled' => 'Cancelled',
    ];
}
