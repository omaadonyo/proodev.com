<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum JobStatus: string
{
    use HasLabels;

    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public const LABELS = [
        'draft' => 'Draft',
        'open' => 'Open',
        'closed' => 'Closed',
    ];
}
