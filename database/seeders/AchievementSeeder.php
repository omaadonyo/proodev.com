<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['key' => 'first-project', 'name' => 'First Project', 'description' => 'Publish your first project.', 'icon' => 'rocket-launch', 'category' => 'projects', 'points' => 25, 'type' => 'count', 'threshold' => 1],
            ['key' => 'projects-5', 'name' => 'Builder', 'description' => 'Publish 5 projects.', 'icon' => 'cube', 'category' => 'projects', 'points' => 50, 'type' => 'count', 'threshold' => 5],
            ['key' => 'projects-10', 'name' => 'Architect', 'description' => 'Publish 10 projects.', 'icon' => 'building-library', 'category' => 'projects', 'points' => 100, 'type' => 'count', 'threshold' => 10],
            ['key' => 'streak-7', 'name' => 'Week Streak', 'description' => 'Log activity 7 days in a row.', 'icon' => 'calendar-days', 'category' => 'streaks', 'points' => 25, 'type' => 'streak', 'threshold' => 7],
            ['key' => 'streak-30', 'name' => 'Month Streak', 'description' => 'Log activity 30 days in a row.', 'icon' => 'fire', 'category' => 'streaks', 'points' => 100, 'type' => 'streak', 'threshold' => 30],
            ['key' => 'level-3', 'name' => 'Engineer', 'description' => 'Reach level 3 (Engineer).', 'icon' => 'arrow-trending-up', 'category' => 'levels', 'points' => 50, 'type' => 'level', 'threshold' => 3],
            ['key' => 'level-5', 'name' => 'Architect', 'description' => 'Reach level 5 (Architect).', 'icon' => 'cog', 'category' => 'levels', 'points' => 100, 'type' => 'level', 'threshold' => 5],
            ['key' => 'first-vouch', 'name' => 'Trusted', 'description' => 'Receive your first approved vouch.', 'icon' => 'shield-check', 'category' => 'community', 'points' => 25, 'type' => 'count', 'threshold' => 1],
            ['key' => 'vouches-5', 'name' => 'Reputable', 'description' => 'Receive 5 approved vouches.', 'icon' => 'finger-print', 'category' => 'community', 'points' => 50, 'type' => 'count', 'threshold' => 5],
            ['key' => 'first-journal', 'name' => 'Reflective', 'description' => 'Publish your first journal entry.', 'icon' => 'book-open-text', 'category' => 'journal', 'points' => 10, 'type' => 'count', 'threshold' => 1],
            ['key' => 'first-recognition', 'name' => 'Recognized', 'description' => 'Get recognized on a project.', 'icon' => 'star', 'category' => 'projects', 'points' => 10, 'type' => 'count', 'threshold' => 1],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
