<?php

namespace App\Services;

use App\Enums\TimelineEventType;
use App\Models\User;
use App\Notifications\LevelUpNotification;

class ExperienceService
{
    public const XP_PROJECT_PUBLISHED = 100;

    public const XP_PROJECT_RECOGNIZED = 10;

    public const XP_VOUCH_APPROVED = 25;

    public const XP_VOUCH_GIVEN = 10;

    public const XP_SKILL_VERIFIED = 40;

    public const XP_VERIFICATION_APPROVED = 75;

    public const XP_JOURNAL_PUBLIC = 10;

    public const XP_COMMUNITY_CONTRIBUTION = 5;

    public function __construct(
        private LevelService $levels,
        private StreakService $streaks,
        private TimelineService $timeline,
    ) {}

    public function award(User $user, int $xp, string $reason, array $data = []): void
    {
        if ($xp <= 0) {
            return;
        }

        $before = $user->level();

        $user->experience_points += $xp;
        $user->saveQuietly();

        $this->streaks->registerActivity($user);

        $after = $user->level();

        if ($after > $before) {
            for ($level = $before + 1; $level <= $after; $level++) {
                $title = $this->levels->titleForLevel($level);

                $this->timeline->record(
                    $user,
                    TimelineEventType::LevelUp,
                    "Reached {$title} level",
                    null,
                    ['level' => $level, 'title' => $title],
                );

                $user->notify(new LevelUpNotification($level, $title));
            }
        }
    }
}
