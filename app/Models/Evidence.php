<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property EvidenceType $type
 * @property string $title
 * @property string $url
 * @property string $source
 * @property string|null $description
 * @property EvidenceStatus $status
 * @property string|null $error
 * @property array<string, mixed>|null $metadata
 * @property int|null $ai_score
 * @property int|null $project_id
 * @property Carbon|null $analyzed_at
 * @property Carbon $created_at
 */
class Evidence extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'url',
        'source',
        'description',
        'status',
        'error',
        'metadata',
        'ai_score',
        'project_id',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'status' => EvidenceStatus::class,
            'metadata' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(EvidenceAnalysis::class);
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(TimelineEvent::class, 'target');
    }

    public function vouches(): HasMany
    {
        return $this->hasMany(Vouch::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isReady(): bool
    {
        return $this->status === EvidenceStatus::Ready;
    }

    public function scopeReady($query)
    {
        return $query->where('status', EvidenceStatus::Ready);
    }
}
