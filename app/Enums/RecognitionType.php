<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum RecognitionType: string
{
    use HasLabels;

    case Useful = 'useful';
    case Elegant = 'elegant';
    case Innovative = 'innovative';
    case Scalable = 'scalable';
    case Educational = 'educational';
    case WellDocumented = 'well-documented';
    case PerformanceFocused = 'performance-focused';
    case CleanArchitecture = 'clean-architecture';

    public const LABELS = [
        'useful' => 'Useful',
        'elegant' => 'Elegant',
        'innovative' => 'Innovative',
        'scalable' => 'Scalable',
        'educational' => 'Educational',
        'well-documented' => 'Well Documented',
        'performance-focused' => 'Performance Focused',
        'clean-architecture' => 'Clean Architecture',
    ];
}
