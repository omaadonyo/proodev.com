<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum ProjectStatus: string
{
    use HasLabels;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public const LABELS = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
}
