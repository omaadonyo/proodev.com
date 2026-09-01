<?php

namespace App\Services\Recruiter;

use App\Models\User;
use App\Services\EngineeringMagnitudeService;
use Illuminate\Support\Collection;

/**
 * Engineering magnitude rankings. Ranks developers by their explainable
 * 0-1000 Engineering Magnitude score, with per-candidate factor breakdowns
 * so every ranking position is defensible.
 */
class RankingService
{
    public function __construct(private CandidateIntelligenceService $intelligence) {}

    /**
     * Rank developers by magnitude score.
     *
     * @param  string|null  $area  optional engineering area to filter by
     * @return array<int, array<string, mixed>>
     */
    public function rankings(?string $area = null, int $limit = 50): array
    {
        $query = User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()]);

        if ($area) {
            $query->whereHas('evidence', function ($q) use ($area) {
                $q->ready()->whereHas('analysis', fn ($a) => $a->where('engineering_areas', 'like', '%'.$area.'%'));
            });
        }

        $developers = $query
            ->orderByDesc('reputation_score')
            ->limit($limit)
            ->get();

        $ranked = $developers
            ->map(function (User $developer) {
                $breakdown = app(EngineeringMagnitudeService::class)->breakdown($developer);

                return [
                    'rank' => 0,
                    'developer' => [
                        'id' => $developer->id,
                        'name' => $developer->name,
                        'handle' => $developer->handle(),
                        'headline' => $developer->headline,
                        'location' => $developer->location,
                        'avatar' => $developer->avatarUrl(),
                        'passport_url' => route('devid', $developer->handle()),
                        'evidence_count' => $developer->evidence_count,
                    ],
                    'magnitude' => $breakdown['total'],
                    'label' => app(EngineeringMagnitudeService::class)->labelFor($breakdown['total']),
                    'percentile' => app(EngineeringMagnitudeService::class)->percentile($breakdown['total']),
                    'factors' => $breakdown['factors'],
                    'top_areas' => collect($breakdown['factors'])
                        ->filter(fn ($f) => ($f['points'] / max(1, $f['max'])) >= 0.7)
                        ->keys()
                        ->take(3)
                        ->all(),
                ];
            })
            ->sortByDesc(fn ($entry) => $entry['magnitude'])
            ->values()
            ->map(function ($entry, $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            });

        return $ranked->all();
    }

    /**
     * @param  Collection<int, User>  $developers
     * @return array<int, array<string, mixed>>
     */
    public function rankCollection(Collection $developers): array
    {
        $ranked = $developers
            ->map(function (User $developer) {
                $breakdown = app(EngineeringMagnitudeService::class)->breakdown($developer);

                return [
                    'rank' => 0,
                    'developer' => [
                        'id' => $developer->id,
                        'name' => $developer->name,
                        'handle' => $developer->handle(),
                        'avatar' => $developer->avatarUrl(),
                        'headline' => $developer->headline,
                        'location' => $developer->location,
                        'passport_url' => route('devid', $developer->handle()),
                    ],
                    'magnitude' => $breakdown['total'],
                    'label' => app(EngineeringMagnitudeService::class)->labelFor($breakdown['total']),
                ];
            })
            ->sortByDesc('magnitude')
            ->values()
            ->map(function ($entry, $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            });

        return $ranked->all();
    }
}
