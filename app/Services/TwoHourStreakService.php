<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class TwoHourStreakService
{
    // 0 streak by default, earn after 2 hours on site, 1 streak = 1 chat
    public const REWARD_INTERVAL_MINUTES = 120;
    public const CHAT_STREAK_LIMIT = 1;

    public function canEarn(User $user): bool
    {
        if ($user->isVerified()) {
            return true;
        }

        return $user->canEarnTwoHourReward();
    }

    public function timeUntilNextReward(User $user): int
    {
        return $user->minutesUntilNextTwoHourReward();
    }

    public function progressPercent(User $user): int
    {
        return $user->twoHourProgressPercent();
    }

    public function canChat(User $user): bool
    {
        if ($user->isVerified()) {
            return true;
        }

        return $user->canUseFreeChat();
    }

    public function consumeChatStreak(User $user, ?Carbon $now = null): bool
    {
        $now ??= now();

        if ($user->isVerified()) {
            return true;
        }

        if (! $this->canChat($user)) {
            return false;
        }

        // Consume the 1 streak after chat — reset to 0 and require another 2 hours
        $user->two_hour_streak_count = max(0, $user->two_hour_streak_count - 1);
        // Keep last reward time for progress tracking, but streak is now 0
        $user->saveQuietly();

        return true;
    }

    /**
     * Attempt to earn streak after 2 hours on site. No XP — streak gates chat.
     */
    public function tryAward(User $user, ?Carbon $now = null): int
    {
        $now ??= now();

        if (! $this->canEarn($user)) {
            return 0;
        }

        // Earn 1 streak after 2 hours
        $user->two_hour_streak_count = 1;
        $user->last_two_hour_reward_at = $now;
        $user->saveQuietly();

        return 1;
    }

    public function snapshot(User $user): array
    {
        return [
            'streak' => $user->two_hour_streak_count,
            'earned_xp' => $user->two_hour_earned_xp,
            'can_earn' => $this->canEarn($user),
            'can_chat' => $this->canChat($user),
            'has_active' => $user->hasActiveFreeChatStreak(),
            'minutes_until_next' => $this->timeUntilNextReward($user),
            'progress' => $this->progressPercent($user),
            'xp_per_reward' => 0,
            'next_xp' => 0,
            'expires_at' => $user->freeChatExpiresAt(),
        ];
    }
}
