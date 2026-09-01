<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $job_id
 * @property int $score
 * @property string $recommendation
 * @property string $summary
 * @property array<int, string>|null $matched_skills
 * @property array<int, string>|null $missing_skills
 * @property string $generated_by
 * @property Carbon $analyzed_at
 */
class JobMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'score',
        'recommendation',
        'summary',
        'matched_skills',
        'missing_skills',
        'generated_by',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'matched_skills' => 'array',
            'missing_skills' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function isStrongMatch(): bool
    {
        return $this->recommendation === 'strong_match';
    }

    public function isWeakMatch(): bool
    {
        return $this->recommendation === 'weak_match';
    }
}
