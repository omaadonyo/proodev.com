<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum ApplicationStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Hired = 'hired';

    public const LABELS = [
        'pending' => 'Pending',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Rejected',
        'hired' => 'Hired',
    ];
}
