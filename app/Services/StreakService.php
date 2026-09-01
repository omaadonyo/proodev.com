<?php

namespace App\Services;

use App\Events\MilestoneReached;
use App\Models\User;
use Illuminate\Support\Carbon;

class StreakService
{
    public function registerActivity(User $user, ?Carbon $at = null): void
    {
        $at ??= now();
        $last = $user->last_activity_at;

        if ($last && $last->toDateString() === $at->toDateString()) {
            return;
        }

        $user->last_activity_at = $at;

        if ($last && $last->isYesterday()) {
            $user->streak_count += 1;
        } else {
            $user->streak_count = 1;
        }

        $user->longest_streak = max($user->longest_streak, $user->streak_count);

        $user->saveQuietly();

        foreach ([3, 7, 14, 30, 60, 100, 200, 365] as $milestone) {
            if ($user->streak_count === $milestone) {
                event(new MilestoneReached($user, 'streak', $milestone));
            }
        }
    }
}
