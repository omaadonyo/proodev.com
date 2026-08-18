<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CompanyPlan: string
{
    use HasLabels;

    case Trial = 'trial';
    case Recruiter = 'recruiter';
    case Intelligence = 'intelligence';

    public const LABELS = [
        'trial' => 'Free',
        'recruiter' => 'Recruiter',
        'intelligence' => 'Recruiter Intelligence Suite',
    ];

    public function isPaid(): bool
    {
        return $this !== self::Trial;
    }

    public function monthlyPrice(): int
    {
        return match ($this) {
            self::Recruiter => (int) config('billing.companies.recruiter.price', 299),
            self::Intelligence => (int) config('billing.companies.intelligence.price', 199),
            self::Trial => 0,
        };
    }

    public function firstMonthPrice(): ?int
    {
        return match ($this) {
            self::Intelligence => (int) config('billing.companies.intelligence.first_month_price', 599),
            default => null,
        };
    }

    public function jobLimit(): ?int
    {
        return match ($this) {
            self::Trial => (int) config('billing.companies.trial.job_limit', 1),
            default => null,
        };
    }

    public function hasIntelligence(): bool
    {
        return $this === self::Intelligence || $this === self::Recruiter;
    }
}
