<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recruiter's active job-description match, persisted so the match badges
 * and Directory ranking survive browser restarts. One row per recruiter.
 */
class RecruiterMatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'skills' => 'array',
        'technologies' => 'array',
        'matched_ids' => 'array',
        'include_technologies' => 'boolean',
    ];

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    /**
     * The recruiter's persisted active match, if any.
     */
    public static function activeFor(User $recruiter): ?self
    {
        return static::where('recruiter_id', $recruiter->id)->first();
    }

    /**
     * Persist (or replace) the recruiter's active match.
     *
     * @param  array{skills: array<int, string>, technologies: array<int, string>}  $keywords
     * @param  array<int, int>  $matchedIds
     */
    public static function setFor(User $recruiter, array $keywords, array $matchedIds, bool $includeTechnologies): self
    {
        return static::updateOrCreate(
            ['recruiter_id' => $recruiter->id],
            [
                'skills' => $keywords['skills'] ?? [],
                'technologies' => $keywords['technologies'] ?? [],
                'matched_ids' => $matchedIds,
                'include_technologies' => $includeTechnologies,
            ],
        );
    }

    /**
     * Forget the recruiter's active match.
     */
    public static function clearFor(User $recruiter): void
    {
        static::where('recruiter_id', $recruiter->id)->delete();
    }

    /**
     * The recruiter's active match in the same shape the search page and the
     * report/workspace consumers expect, or null when no match is active.
     *
     * @return array{skills: array<int, string>, technologies: array<int, string>, include_technologies: bool}|null
     */
    public static function contextFor(User $recruiter): ?array
    {
        $record = static::activeFor($recruiter);

        if (! $record || ($record->skills ?? []) === []) {
            return null;
        }

        return [
            'skills' => $record->skills ?? [],
            'technologies' => $record->technologies ?? [],
            'include_technologies' => (bool) $record->include_technologies,
        ];
    }
}
