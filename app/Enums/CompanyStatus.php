<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CompanyStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';

    public const LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'suspended' => 'Suspended',
    ];
}
