<?php

namespace App\Listeners;

use App\Events\RecognitionReceived;
use App\Notifications\RecognitionReceivedNotification;
use App\Services\AchievementService;
use App\Services\ExperienceService;
use App\Services\ReputationService;

class HandleRecognitionReceived
{
    public function __construct(
        private ExperienceService $experience,
        private AchievementService $achievements,
        private ReputationService $reputation,
    ) {}

    public function handle(RecognitionReceived $event): void
    {
        $owner = $event->project->user;

        $this->experience->award($owner, ExperienceService::XP_PROJECT_RECOGNIZED, "{$event->project->title} earned recognition");

        $owner->notify(new RecognitionReceivedNotification($event->project, $event->type));

        $total = $event->project->recognitions()->count();

        if ($total >= 10) {
            $this->achievements->award($owner, 'recognized');
        }

        $this->reputation->recalculate($owner);
    }
}
