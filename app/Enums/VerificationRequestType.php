<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum VerificationRequestType: string
{
    use HasLabels;

    case Company = 'company';
    case Manual = 'manual';
    case ProfessionalDocs = 'professional-docs';
    case PublicContribution = 'public-contribution';
    case Employment = 'employment';

    public const LABELS = [
        'company' => 'Company Email Verification',
        'manual' => 'Manual Review',
        'professional-docs' => 'Professional Documentation',
        'public-contribution' => 'Public Contribution Review',
        'employment' => 'Employment Verification',
    ];
}
