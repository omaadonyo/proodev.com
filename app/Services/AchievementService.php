<?php

namespace App\Services;

use App\Enums\TimelineEventType;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Notifications\AchievementEarnedNotification;

class AchievementService
{
    public function __construct(
        private ExperienceService $experience,
        private TimelineService $timeline,
    ) {}

    public function award(User $user, string $key, array $data = []): ?UserAchievement
    {
        $achievement = Achievement::where('key', $key)->first();

        if (! $achievement) {
            return null;
        }

        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($existing && $existing->awarded_at) {
            return $existing;
        }

        $record = $existing ?? new UserAchievement([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'progress' => 0,
        ]);

        $record->progress = max($record->progress, $achievement->threshold ?? 1);
        $record->awarded_at = $record->awarded_at ?? now();
        $record->data = $data;
        $record->save();

        $this->experience->award($user, $achievement->points, "Earned achievement: {$achievement->name}", $data);

        $this->timeline->record(
            $user,
            TimelineEventType::BadgeEarned,
            "Earned badge: {$achievement->name}",
            $achievement->description,
            ['achievement_key' => $achievement->key, 'achievement_id' => $achievement->id, 'icon' => $achievement->icon],
        );

        $user->notify(new AchievementEarnedNotification($achievement));

        return $record;
    }

    public function track(User $user, string $key, int $increment = 1, array $data = []): ?UserAchievement
    {
        $achievement = Achievement::where('key', $key)->first();

        if (! $achievement) {
            return null;
        }

        $record = UserAchievement::firstOrNew([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        $record->progress += $increment;
        $record->save();

        if ($achievement->threshold && $record->progress >= $achievement->threshold && ! $record->awarded_at) {
            return $this->award($user, $key, $data);
        }

        return $record;
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function countAwarded(User $user, array $keys = []): int
    {
        $query = UserAchievement::where('user_id', $user->id)->whereNotNull('awarded_at');

        if ($keys !== []) {
            $query->whereHas('achievement', fn ($q) => $q->whereIn('key', $keys));
        }

        return $query->count();
    }
}
