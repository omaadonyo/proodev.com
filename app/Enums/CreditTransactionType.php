<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CreditTransactionType: string
{
    use HasLabels;

    case Purchase = 'purchase';
    case Submission = 'submission';
    case Analysis = 'analysis';
    case Grant = 'grant';
    case Refund = 'refund';

    public const LABELS = [
        'purchase' => 'Purchase',
        'submission' => 'Evidence submission',
        'analysis' => 'AI analysis',
        'grant' => 'Grant',
        'refund' => 'Refund',
    ];
}
