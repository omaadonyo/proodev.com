<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Carbon;

class WeeklyReportService
{
    public function __construct(private Ai\AiService $ai) {}

    public function generate(User $user, ?Carbon $weekStart = null): WeeklyReport
    {
        $weekStart ??= now()->startOfWeek();

        $start = $weekStart->copy()->startOfDay();
        $end = $weekStart->copy()->addWeek()->startOfDay();

        $events = TimelineEvent::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get();

        $projectsPublished = Project::where('user_id', $user->id)
            ->whereBetween('published_at', [$start, $end])
            ->count();

        $recognitionsReceived = $user->recognitionsGiven()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $skillsImproved = $user->skills()
            ->wherePivot('updated_at', '>=', $start)
            ->wherePivot('updated_at', '<', $end)
            ->count();

        $xpGained = $user->experience_points - ($user->weeklyReports()
            ->where('week_started', '<', $weekStart->toDateString())
            ->orderByDesc('week_started')
            ->first()?->data['xp'] ?? $user->experience_points);

        $xpGained = max(0, $xpGained);

        $highlights = $events->take(8)->map(fn (TimelineEvent $event) => [
            'type' => $event->type->value,
            'title' => $event->title,
            'occurred_at' => $event->occurred_at->toISOString(),
        ])->all();

        $journalEntries = $user->journalEntries()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $data = [
            'week_started' => $weekStart->toDateString(),
            'projects_published' => $projectsPublished,
            'skills_improved' => $skillsImproved,
            'xp_gained' => $xpGained,
            'growth_percentage' => $this->growthPercentage($user, $weekStart),
            'recognition_received' => $recognitionsReceived,
            'reputation_score' => $user->reputation_score,
            'journal_entries' => $journalEntries,
            'activity_count' => $events->count(),
            'highlights' => $highlights,
        ];

        $data['insights'] = $this->ai->weeklyInsights([
            'week_started' => $data['week_started'],
            'highlights' => $highlights,
            'growth_percentage' => $data['growth_percentage'],
        ]);

        return WeeklyReport::updateOrCreate(
            ['user_id' => $user->id, 'week_started' => $weekStart->toDateString()],
            ['data' => $data, 'generated_at' => now()],
        );
    }

    private function growthPercentage(User $user, Carbon $weekStart): int
    {
        $previous = $user->weeklyReports()
            ->where('week_started', $weekStart->copy()->subWeek()->toDateString())
            ->first();

        if (! $previous) {
            return $user->experience_points > 0 ? 100 : 0;
        }

        $prevXp = $previous->data['xp'] ?? 0;
        $nowXp = $user->experience_points;

        if ($prevXp <= 0) {
            return $nowXp > 0 ? 100 : 0;
        }

        return (int) round((($nowXp - $prevXp) / $prevXp) * 100);
    }
}
