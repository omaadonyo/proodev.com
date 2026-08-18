<?php

namespace App\Services\Recruiter;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The verified network. Surfaces developers who have passed official
 * verification (approved verification requests or verified skills), so
 * recruiters can engage with the community's proven members first.
 */
class VerifiedExpertService
{
    /**
     * Verified developers, ordered by reputation.
     *
     * @param  string|null  $skill  optional skill name to filter by
     * @return Collection<int, array<string, mixed>>
     */
    public function verified(?string $skill = null, int $limit = 60): Collection
    {
        $query = User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->where(function ($q) {
                $q->where('is_verified', true)
                    ->orWhereHas('verificationRequests', fn ($r) => $r->where('status', 'approved'))
                    ->orWhereHas('skills', fn ($s) => $s->wherePivotNotNull('verified_at'));
            });

        if ($skill) {
            $query->whereHas('skills', fn ($s) => $s->where('skills.name', $skill));
        }

        return $query
            ->with(['skills'])
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()])
            ->orderByDesc('reputation_score')
            ->orderByDesc('experience_points')
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => [
                'developer' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'handle' => $user->handle(),
                    'headline' => $user->headline,
                    'location' => $user->location,
                    'avatar' => $user->avatarUrl(),
                    'passport_url' => route('passport', $user->handle()),
                    'evidence_count' => $user->evidence_count,
                    'is_verified' => $user->isVerified(),
                ],
                'verified_skills' => $user->skills
                    ->filter(fn ($s) => $s->pivot->verified_at !== null)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'reputation' => (int) $user->reputation_score,
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function verifiedSkillNames(): array
    {
        return Skill::query()
            ->whereHas('users', fn ($q) => $q->wherePivotNotNull('verified_at'))
            ->orderBy('name')
            ->limit(40)
            ->pluck('name')
            ->all();
    }
}
