<?php

namespace App\Listeners;

use App\Enums\TimelineEventType;
use App\Enums\VouchType;
use App\Events\VouchApproved;
use App\Notifications\VouchReceivedNotification;
use App\Services\AchievementService;
use App\Services\ExperienceService;
use App\Services\NotificationService;
use App\Services\ReputationService;
use App\Services\TimelineService;

class ApproveVouchEffects
{
    public function __construct(
        private ExperienceService $experience,
        private AchievementService $achievements,
        private TimelineService $timeline,
        private ReputationService $reputation,
        private NotificationService $notifications,
    ) {}

    public function handle(VouchApproved $event): void
    {
        $vouch = $event->vouch->load(['voucher', 'vouchee', 'skill']);

        $this->experience->award($vouch->vouchee, ExperienceService::XP_VOUCH_APPROVED * $vouch->weight, 'Received an approved vouch');

        if ($vouch->type === VouchType::Skill && $vouch->skill) {
            $userSkill = $vouch->vouchee->skills()->where('skill_id', $vouch->skill_id)->first();

            if ($userSkill) {
                $userSkill->pivot->verified_at = now();
                $userSkill->pivot->save();
            } else {
                $vouch->vouchee->skills()->attach($vouch->skill_id, ['verified_at' => now()]);
            }

            $this->timeline->record(
                $vouch->vouchee,
                TimelineEventType::SkillVerified,
                "Skill verified: {$vouch->skill->name}",
                "{$vouch->voucher->name} vouched for this skill.",
                ['skill_id' => $vouch->skill_id, 'skill_name' => $vouch->skill->name],
            );
        }

        $this->timeline->record(
            $vouch->vouchee,
            TimelineEventType::VouchReceived,
            "Received a {$vouch->type->label()} from {$vouch->voucher->name}",
            $vouch->message,
            ['vouch_id' => $vouch->id, 'type' => $vouch->type->value],
        );

        $this->achievements->track($vouch->vouchee, 'vouched', 1);

        $vouch->vouchee->notify(new VouchReceivedNotification($vouch));

        $this->notifications->vouchReceived($vouch);

        $this->reputation->recalculate($vouch->vouchee);
    }
}
