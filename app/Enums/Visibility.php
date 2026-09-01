<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum Visibility: string
{
    use HasLabels;

    case Private = 'private';
    case Team = 'team';
    case Public = 'public';

    public const LABELS = [
        'private' => 'Private',
        'team' => 'Team',
        'public' => 'Public',
    ];
}
