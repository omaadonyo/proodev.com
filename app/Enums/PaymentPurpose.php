<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentPurpose: string
{
    use HasLabels;

    case Verification = 'verification';
    case Credits = 'credits';
    case Subscription = 'subscription';
    case AutoScan = 'auto-scan';
    case JobPosts = 'job-posts';

    public const LABELS = [
        'verification' => 'Developer Verification',
        'credits' => 'Credit Purchase',
        'subscription' => 'Company Subscription',
        'auto-scan' => 'Repo Auto-Scan',
        'job-posts' => 'Job Post Credits',
    ];
}
