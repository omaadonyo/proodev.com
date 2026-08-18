<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Support\Collection;

class TrendingService
{
    /**
     * @return Collection<int, array{name: string, count: int}>
     */
    public function trendingTechnologies(int $limit = 6): Collection
    {
        return Project::published()
            ->whereNotNull('tech_stack')
            ->get('tech_stack')
            ->flatMap(fn ($project) => (array) $project->tech_stack)
            ->filter()
            ->countBy(fn (string $tech) => strtolower(trim($tech)))
            ->sortDesc()
            ->take($limit)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values();
    }

    /**
     * @return Collection<int, Project>
     */
    public function trendingProjects(int $limit = 5): Collection
    {
        return Project::published()
            ->with('user')
            ->orderByDesc('recognition_count')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function topEngineers(int $limit = 5): Collection
    {
        return User::query()
            ->visibleToPublic()
            ->where('experience_points', '>', 0)
            ->orderByDesc('reputation_score')
            ->orderByDesc('experience_points')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, VerificationRequest>
     */
    public function recentVerifications(int $limit = 5): Collection
    {
        return VerificationRequest::query()
            ->where('status', 'approved')
            ->with('user')
            ->orderByDesc('reviewed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, TimelineEvent>
     */
    public function liveActivity(int $limit = 8): Collection
    {
        return TimelineEvent::public()
            ->with('user')
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }
}
