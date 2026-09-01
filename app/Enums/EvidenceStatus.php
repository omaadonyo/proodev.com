<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum EvidenceStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Extracting = 'extracting';
    case Analyzing = 'analyzing';
    case Ready = 'ready';
    case Failed = 'failed';

    public const LABELS = [
        'pending' => 'Pending',
        'extracting' => 'Extracting',
        'analyzing' => 'Analyzing',
        'ready' => 'Ready',
        'failed' => 'Failed',
    ];
}
