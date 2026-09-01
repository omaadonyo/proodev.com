<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum UserRole: string
{
    use HasLabels;

    case Developer = 'developer';
    case Company = 'company';
    case Recruiter = 'recruiter';

    public const LABELS = [
        'developer' => 'Developer',
        'company' => 'Company',
        'recruiter' => 'Recruiter',
    ];

    public function isCompany(): bool
    {
        return $this === self::Company;
    }

    public function isDeveloper(): bool
    {
        return $this === self::Developer;
    }

    public function isRecruiter(): bool
    {
        return $this === self::Recruiter;
    }

    public function isRecruiterOrCompany(): bool
    {
        return $this === self::Recruiter || $this === self::Company;
    }
}
