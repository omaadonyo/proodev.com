<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum ReportStatus: string
{
    use HasLabels;

    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public const LABELS = [
        'open' => 'Open',
        'resolved' => 'Resolved',
        'dismissed' => 'Dismissed',
    ];
}
