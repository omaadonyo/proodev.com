<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum ProjectVerificationStatus: string
{
    use HasLabels;

    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public const LABELS = [
        'unverified' => 'Unverified',
        'pending' => 'Pending Review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];
}
