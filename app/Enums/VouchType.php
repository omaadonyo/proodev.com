<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum VouchType: string
{
    use HasLabels;

    case Skill = 'skill';
    case Architecture = 'architecture';
    case Project = 'project';
    case Mentorship = 'mentorship';
    case CodeReview = 'code-review';
    case Collaboration = 'collaboration';

    public const LABELS = [
        'skill' => 'Skill Vouch',
        'architecture' => 'Architecture Vouch',
        'project' => 'Project Vouch',
        'mentorship' => 'Mentorship Vouch',
        'code-review' => 'Code Review Vouch',
        'collaboration' => 'Collaboration Vouch',
    ];
}
