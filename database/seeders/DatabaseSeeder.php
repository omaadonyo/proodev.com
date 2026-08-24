<?php

namespace Database\Seeders;

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Models\Skill;
use App\Models\User;
use App\Services\ExperienceService;
use App\Services\ReputationService;
use App\Services\TimelineService;
use Database\Seeders\Concerns\SeedsDeveloperContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use SeedsDeveloperContent;

    public function run(): void
    {
        $this->call([
            SkillSeeder::class,
            AchievementSeeder::class,
            UserAvatarSeeder::class,
        ]);

        $skills = Skill::pluck('id', 'slug')->all();

        $admin = User::firstOrCreate(
            ['email' => config('platform.admin_email')],
            [
                'name' => 'ProoDev Admin',
                'username' => 'proodev-admin',
                'password' => Hash::make(config('platform.admin_password')),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        // Keep a single, stable platform admin — legacy demo admins are demoted
        // so the admin account is never duplicated or visible as a regular user.
        User::where('is_admin', true)->where('id', '!=', $admin->id)->update(['is_admin' => false]);

        $demo = User::firstOrCreate(
            ['email' => config('platform.demo_email')],
            [
                'name' => 'Demo Engineer',
                'username' => 'demo-engineer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'headline' => 'Full-stack engineer building with Laravel + Livewire',
                'bio' => 'I like shipping thoughtful software and sharing what I learn.',
                'location' => 'Berlin, Germany',
                'timezone' => 'Europe/Berlin',
                'github_url' => 'https://github.com/demo',
                'website_url' => 'https://example.com',
                'linkedin_url' => 'https://linkedin.com/in/demo',
                'public_passport' => true,
            ],
        );

        $demo->skills()->syncWithPivotValues(
            array_map(fn ($slug) => $skills[$slug] ?? null, ['php', 'laravel', 'livewire', 'mysql', 'tailwind-css']),
            ['level' => 3, 'verified_at' => now(), 'times_used' => 20],
        );

        app(ExperienceService::class)->award($demo, 620, 'Seeded growth');

        $demo->forceFill(['streak_count' => 6, 'longest_streak' => 12, 'last_activity_at' => now()])->saveQuietly();

        app(TimelineService::class)->record(
            $demo,
            TimelineEventType::Joined,
            'Joined ProoDev',
            'Welcome to the platform where growth is public.',
            [],
            null,
            Visibility::Public,
            now()->subDays(30),
        );

        app(TimelineService::class)->record(
            $admin,
            TimelineEventType::Joined,
            'Joined ProoDev',
            null,
            [],
            null,
            Visibility::Public,
            now()->subDays(45),
        );

        $this->seedNews($admin);

        app(ReputationService::class)->recalculate($admin);
    }
}
