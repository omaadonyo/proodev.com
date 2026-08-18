<?php

namespace App\Services;

use App\Models\User;

class ProfileCompletionService
{
    /**
     * Estimate how complete a user's public profile is, as a 0–100 score.
     */
    public function percentage(User $user): int
    {
        $checks = [
            'headline' => filled($user->headline),
            'bio' => filled($user->bio),
            'location' => filled($user->location),
            'github_url' => filled($user->github_url),
            'skills' => $user->skills()->exists(),
            'projects' => $user->projects()->exists(),
        ];

        $completed = collect($checks)->filter()->count();

        return (int) round(($completed / max(1, count($checks))) * 100);
    }
}
