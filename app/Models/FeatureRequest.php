<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_INCLUDED = 'included';

    protected $fillable = [
        'title',
        'description',
        'status',
        'target_votes',
        'created_by',
        'included_at',
    ];

    protected function casts(): array
    {
        return [
            'target_votes' => 'integer',
            'included_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeatureRequestVote::class);
    }

    public function votesCount(): int
    {
        return $this->votes()->count();
    }

    public function hasVoted(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function hasReachedTarget(): bool
    {
        return $this->target_votes > 0 && $this->votesCount() >= $this->target_votes;
    }

    /**
     * Approve a pending request so it becomes publicly visible and votable.
     */
    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark the feature as developed and included once its vote target is hit.
     */
    public function markIncluded(): void
    {
        $this->update([
            'status' => self::STATUS_INCLUDED,
            'included_at' => now(),
        ]);
    }
}
