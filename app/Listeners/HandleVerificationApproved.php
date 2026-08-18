<?php

namespace App\Listeners;

use App\Enums\TimelineEventType;
use App\Events\VerificationApproved;
use App\Notifications\VerificationApprovedNotification;
use App\Services\AchievementService;
use App\Services\ExperienceService;
use App\Services\NotificationService;
use App\Services\ReputationService;
use App\Services\TimelineService;

class HandleVerificationApproved
{
    public function __construct(
        private ExperienceService $experience,
        private AchievementService $achievements,
        private TimelineService $timeline,
        private ReputationService $reputation,
        private NotificationService $notifications,
    ) {}

    public function handle(VerificationApproved $event): void
    {
        $request = $event->request->load('user');
        $user = $request->user;

        $this->experience->award($user, ExperienceService::XP_VERIFICATION_APPROVED, 'Professional verification approved');

        $this->achievements->award($user, 'verified-professional');

        $this->timeline->record(
            $user,
            TimelineEventType::AchievementVerified,
            'Verified '.($request->label ?: 'professional identity'),
            null,
            ['verification_id' => $request->id, 'label' => $request->label],
        );

        $user->notify(new VerificationApprovedNotification($request));

        $this->notifications->verificationApproved($request);

        $this->reputation->recalculate($user);
    }
}
