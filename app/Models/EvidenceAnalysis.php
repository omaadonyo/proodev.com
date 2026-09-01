<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evidence_id
 * @property string $summary
 * @property array<int, string>|null $technologies
 * @property array<int, string>|null $engineering_areas
 * @property string $complexity
 * @property string|null $architecture_observations
 * @property array<int, array<string, mixed>>|null $skills
 * @property array<int, string>|null $knowledge_domains
 * @property array<int, string>|null $highlights
 * @property array<int, string>|null $strengths
 * @property array<int, array<string, mixed>>|null $references
 * @property string $generated_by
 */
class EvidenceAnalysis extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'evidence_id',
        'summary',
        'technologies',
        'engineering_areas',
        'complexity',
        'architecture_observations',
        'skills',
        'knowledge_domains',
        'highlights',
        'strengths',
        'references',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'engineering_areas' => 'array',
            'skills' => 'array',
            'knowledge_domains' => 'array',
            'highlights' => 'array',
            'strengths' => 'array',
            'references' => 'array',
        ];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }
}
