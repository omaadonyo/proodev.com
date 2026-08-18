<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum VerificationStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Approved = 'approved';

    public const LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
    ];
}
