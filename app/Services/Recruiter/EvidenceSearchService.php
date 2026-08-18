<?php

namespace App\Services\Recruiter;

use App\Models\User;
use App\Services\DiscoverService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Evidence-based talent search. Unlike keyword matching over resumes, this
 * search runs against analyzed evidence: technologies, engineering areas,
 * knowledge domains, and complexity found inside actual engineering work.
 */
class EvidenceSearchService
{
    public function __construct(private DiscoverService $discover) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters = [], int $perPage = 18): LengthAwarePaginator
    {
        $query = $this->discover->search($filters, $perPage);

        return $query;
    }

    /**
     * Candidates who have analyzed evidence containing a given technology.
     *
     * @return Collection<int, User>
     */
    public function byTechnology(string $technology)
    {
        return User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->whereHas('evidence', function ($q) use ($technology) {
                $q->ready()->whereHas('analysis', function ($a) use ($technology) {
                    $a->where('technologies', 'like', '%'.$technology.'%');
                });
            })
            ->orderByDesc('reputation_score')
            ->get();
    }

    /**
     * Candidates who have analyzed evidence covering a given engineering area.
     *
     * @return Collection<int, User>
     */
    public function byEngineeringArea(string $area)
    {
        return User::query()
            ->visibleToPublic()
            ->where('public_passport', true)
            ->whereHas('evidence', function ($q) use ($area) {
                $q->ready()->whereHas('analysis', function ($a) use ($area) {
                    $a->where('engineering_areas', 'like', '%'.$area.'%');
                });
            })
            ->orderByDesc('reputation_score')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function facetSummary(array $filters = []): array
    {
        return [
            'total' => $this->discover->search($filters, 1)->total(),
        ];
    }
}
