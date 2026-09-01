<?php

namespace App\Services;

use App\Models\User;

class ReputationService
{
    /**
     * @return array{
     *     total: int,
     *     components: array<string, array{label: string, points: int, weight: int, icon: string}>
     * }
     */
    public function breakdown(User $user): array
    {
        $projects = $user->projects()->published()->get();
        $recognitions = $projects->sum->recognition_count;
        $achievements = $user->achievements()->wherePivotNotNull('awarded_at')->get();
        $vouchesGiven = $user->vouchesGiven()->where('status', 'approved')->count();
        $vouchesReceived = $user->vouchesReceived()->where('status', 'approved')->with('skill')->get();
        $verifiedSkills = $user->skills()->wherePivotNotNull('verified_at')->get();
        $verifications = $user->verificationRequests()->where('status', 'approved')->count();
        $comments = $user->comments()->count();
        $recognitionsGiven = $user->recognitionsGiven()->count();
        $skillLevels = $user->skills()->get()->sum('pivot.level');

        $components = [
            'project_quality' => [
                'label' => 'Project Quality',
                'points' => $projects->count() * 10 + $recognitions * 2,
                'weight' => 25,
                'icon' => 'folder',
            ],
            'verified_achievements' => [
                'label' => 'Verified Achievements',
                'points' => $achievements->count() * 5,
                'weight' => 15,
                'icon' => 'shield-check',
            ],
            'community_contribution' => [
                'label' => 'Community Contribution',
                'points' => $vouchesGiven * 3 + $comments * 1 + $recognitionsGiven * 2,
                'weight' => 15,
                'icon' => 'users',
            ],
            'technical_trust' => [
                'label' => 'Technical Trust',
                'points' => (int) $vouchesReceived->sum(fn ($v) => $v->weight) * 4 + $verifiedSkills->count() * 5 + $verifications * 10,
                'weight' => 45,
                'icon' => 'finger-print',
            ],
            'activity_consistency' => [
                'label' => 'Activity Consistency',
                'points' => min(100, $user->streak_count * 2),
                'weight' => 0,
                'icon' => 'calendar-days',
            ],
            'learning_progress' => [
                'label' => 'Learning Progress',
                'points' => $skillLevels,
                'weight' => 0,
                'icon' => 'academic-cap',
            ],
        ];

        $total = 0;

        foreach ($components as $component) {
            if ($component['weight'] > 0) {
                $total += $component['points'];
            }
        }

        $total = min(1000, $total);

        return [
            'total' => $total,
            'components' => $components,
        ];
    }

    public function recalculate(User $user): int
    {
        $score = $this->breakdown($user)['total'];

        $user->forceFill(['reputation_score' => $score])->saveQuietly();

        return $score;
    }
}
