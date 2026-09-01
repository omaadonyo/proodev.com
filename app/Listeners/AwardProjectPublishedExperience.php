<?php

namespace App\Listeners;

use App\Events\ProjectPublished;
use App\Services\AchievementService;
use App\Services\ExperienceService;

class AwardProjectPublishedExperience
{
    public function __construct(
        private ExperienceService $experience,
        private AchievementService $achievements,
    ) {}

    public function handle(ProjectPublished $event): void
    {
        $user = $event->project->user;

        $this->experience->award($user, ExperienceService::XP_PROJECT_PUBLISHED, "Published {$event->project->title}");

        $count = $user->projects()->where('status', 'published')->count();

        foreach ([1 => 'first-project', 3 => 'three-projects', 10 => 'ten-projects'] as $threshold => $key) {
            if ($count >= $threshold) {
                $this->achievements->award($user, $key);
            }
        }
    }
}
