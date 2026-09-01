<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVerificationStatus;
use App\Enums\RecognitionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $tagline
 * @property string $problem
 * @property string $solution
 * @property string|null $architecture
 * @property array<int, string>|null $tech_stack
 * @property array<int, string>|null $screenshots
 * @property string|null $lessons_learned
 * @property array<int, string>|null $engineering_decisions
 * @property string|null $demo_url
 * @property string|null $repository_url
 * @property ProjectStatus $status
 * @property Carbon|null $published_at
 * @property string|null $ai_summary
 * @property int|null $ai_score
 * @property ProjectVerificationStatus $verification_status
 * @property Carbon|null $verified_at
 * @property int $views_count
 * @property int $recognition_count
 */
class Project extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'tagline',
        'problem',
        'solution',
        'architecture',
        'tech_stack',
        'screenshots',
        'lessons_learned',
        'engineering_decisions',
        'demo_url',
        'repository_url',
        'status',
        'published_at',
        'ai_summary',
        'ai_score',
        'verification_status',
        'verified_at',
        'views_count',
        'recognition_count',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'screenshots' => 'array',
            'engineering_decisions' => 'array',
            'status' => ProjectStatus::class,
            'verification_status' => ProjectVerificationStatus::class,
            'published_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recognitions(): HasMany
    {
        return $this->hasMany(ProjectRecognition::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(TimelineEvent::class, 'target');
    }

    public function scopePublished($query)
    {
        return $query->where('status', ProjectStatus::Published)->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->status === ProjectStatus::Published;
    }

    public function recognitionBreakdown(): array
    {
        return $this->recognitions()
            ->get()
            ->groupBy('type')
            ->map->count()
            ->toArray();
    }

    public function recognitionTypes(): array
    {
        $types = [];

        foreach (RecognitionType::cases() as $type) {
            $types[$type->value] = [
                'label' => $type->label(),
                'count' => 0,
            ];
        }

        foreach ($this->recognitionBreakdown() as $type => $count) {
            if (isset($types[$type])) {
                $types[$type]['count'] = $count;
            }
        }

        return $types;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Project $project) {
            if ($project->isDirty('title') || ! $project->slug) {
                $project->slug = $project->slug ?: (Str::slug($project->title).'-'.Str::lower(Str::random(6)));
            }
        });
    }
}
