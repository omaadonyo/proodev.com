<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DiscoverService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters = [], int $perPage = 18): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['skills'])
            ->withCount(['projects as published_projects' => fn ($q) => $q->where('status', 'published')])
            ->withCount(['evidence as evidence_count' => fn ($q) => $q->ready()])
            ->visibleToPublic()
            ->where(function ($q) {
                $q->where('public_passport', true);
            });

        $this->applySearch($query, $filters['query'] ?? null);
        $this->applySkills($query, $filters['skills'] ?? []);
        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('reputation_score')
            ->orderByDesc('experience_points')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('headline', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        });
    }

    /**
     * @param  array<int, string>  $skillNames
     */
    private function applySkills(Builder $query, array $skillNames): void
    {
        if ($skillNames === []) {
            return;
        }

        $query->whereHas('skills', fn ($q) => $q->whereIn('skills.slug', $skillNames));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $level = (int) ($filters['level'] ?? 0);
        $minXp = (int) ($filters['min_xp'] ?? 0);

        if ($level > 1) {
            $threshold = app(LevelService::class)->thresholdForLevel($level);
            $nextThreshold = app(LevelService::class)->thresholdForLevel($level + 1);
            $query->whereBetween('experience_points', [$threshold, max($threshold, $nextThreshold - 1)]);
        } elseif ($minXp > 0) {
            $query->where('experience_points', '>=', $minXp);
        }

        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (! empty($filters['timezone'])) {
            $query->where('timezone', $filters['timezone']);
        }

        if (($filters['verified_only'] ?? false) === true) {
            $query->whereHas('verificationRequests', fn ($q) => $q->where('status', 'approved'));
        }

        if (! empty($filters['category'])) {
            $query->whereHas('projects', fn ($q) => $q->whereIn('tech_stack', [$filters['category']]));
        }
    }

    /**
     * @return array<int, string>
     */
    public function popularSkills(int $limit = 12): array
    {
        return Skill::query()
            ->withCount('users')
            ->orderByDesc('users_count')
            ->limit($limit)
            ->pluck('name')
            ->all();
    }
}
