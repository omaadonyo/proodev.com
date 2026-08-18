<?php

namespace App\Services\Recruiter;

use App\Enums\EvidenceStatus;
use App\Models\TalentAlert;
use App\Models\User;
use App\Notifications\TalentAlertNotification;
use App\Services\EngineeringMagnitudeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Talent discovery alerts. Watches the talent pool for candidates matching
 * a recruiter's criteria (skills, location, verified-only, min magnitude)
 * and notifies when new matches surface.
 */
class TalentAlertService
{
    public function __construct(private EngineeringMagnitudeService $magnitude) {}

    /**
     * Run a single alert and return the newly matched candidates.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function runAlert(TalentAlert $alert): Collection
    {
        $criteria = $alert->criteria ?? [];
        $newCandidates = $this->findMatches($criteria);

        $seenKey = 'talent-alert:'.$alert->id.':seen';
        $seen = Cache::get($seenKey, []);

        $fresh = $newCandidates->filter(fn ($candidate) => ! in_array($candidate['id'], $seen, true))->values();

        if ($fresh->isNotEmpty()) {
            $alert->recruiter->notify(new TalentAlertNotification($alert->name, $fresh->first(), $alert->id));
        }

        $alert->update(['last_run_at' => now()]);

        Cache::put($seenKey, $newCandidates->pluck('id')->merge($seen)->unique()->all(), now()->addDays(30));

        return $fresh;
    }

    /**
     * Run all active alerts for a recruiter.
     *
     * @return array<string, int>
     */
    public function runAllFor(User $recruiter): array
    {
        $results = [];

        foreach ($recruiter->talentAlerts()->where('is_active', true)->get() as $alert) {
            $results[$alert->id] = $this->runAlert($alert)->count();
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    public function findMatches(array $criteria): Collection
    {
        $query = User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()]);

        if (! empty($criteria['skills'])) {
            $skills = (array) $criteria['skills'];
            $query->whereHas('skills', fn ($q) => $q->whereIn('skills.slug', $skills));
        }

        if (! empty($criteria['technologies'])) {
            $technologies = array_values(array_filter(array_map('trim', (array) $criteria['technologies'])));

            if ($technologies !== []) {
                $query->whereHas('evidence', function ($e) use ($technologies) {
                    $e->where('status', EvidenceStatus::Ready)->whereHas('analysis', function ($a) use ($technologies) {
                        foreach (array_slice($technologies, 0, 15) as $tech) {
                            $a->orWhere('technologies', 'like', '%'.$tech.'%');
                        }
                    });
                });
            }
        }

        if (! empty($criteria['location'])) {
            $query->where('location', 'like', '%'.$criteria['location'].'%');
        }

        if (! empty($criteria['timezone'])) {
            $query->where('timezone', $criteria['timezone']);
        }

        if (! empty($criteria['verified_only'])) {
            $query->where(function ($q) {
                $q->where('is_verified', true)
                    ->orWhereHas('verificationRequests', fn ($r) => $r->where('status', 'approved'));
            });
        }

        if (! empty($criteria['min_evidence'])) {
            $query->having('evidence_count', '>=', (int) $criteria['min_evidence']);
        }

        $developers = $query
            ->orderByDesc('reputation_score')
            ->limit(50)
            ->get();

        return $developers
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'handle' => $user->handle(),
                'headline' => $user->headline,
                'location' => $user->location,
                'avatar' => $user->avatarUrl(),
                'passport_url' => route('passport', $user->handle()),
                'evidence_count' => $user->evidence_count,
                'reputation' => (int) $user->reputation_score,
                'magnitude' => $this->magnitude->breakdown($user)['total'],
            ])
            ->filter(fn ($entry) => empty($criteria['min_magnitude']) || $entry['magnitude'] >= (int) $criteria['min_magnitude'])
            ->sortByDesc('magnitude')
            ->values();
    }
}
