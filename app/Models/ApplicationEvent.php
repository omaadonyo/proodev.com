<?php

namespace App\Models;

use App\Enums\FeedbackCategory;
use App\Enums\HiringStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An auditable hiring-timeline event attached to an application.
 * Internal-only events (candidate_visible = false) never reach the candidate.
 */
class ApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'actor_id',
        'stage',
        'candidate_visible',
        'feedback_category',
        'feedback_note',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'candidate_visible' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function stage(): HiringStage
    {
        return HiringStage::from($this->stage);
    }

    public function feedbackCategory(): ?FeedbackCategory
    {
        return $this->feedback_category !== null ? FeedbackCategory::tryFrom($this->feedback_category) : null;
    }
}