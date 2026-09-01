<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum VouchStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public const LABELS = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
}
