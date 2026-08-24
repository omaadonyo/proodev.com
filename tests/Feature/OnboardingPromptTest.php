<?php

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Support\Str;

function onboardingReadyProfile(array $overrides = []): User
{
    return User::factory()->withoutOnboarding()->create(array_merge([
        'headline' => 'Senior Laravel engineer',
        'bio' => 'Building thoughtful software.',
        'location' => 'Berlin, Germany',
        'github_url' => 'https://github.com/jane',
    ], $overrides));
}

test('developers with an incomplete profile see the profile progress prompt', function () {
    $user = User::factory()->withoutOnboarding()->create(); // empty profile

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertSee('Add your project links and GitHub repo')
        ->assertSee('Finish setup');
});

test('the onboarding prompt appears on the home feed', function () {
    $user = User::factory()->withoutOnboarding()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Add your project links and GitHub repo')
        ->assertSee('Finish setup');
});

test('the prompt renders once, inside the desktop header, so the sidebar layout stays intact', function () {
    $user = User::factory()->withoutOnboarding()->create();

    $html = $this->actingAs($user)->get(route('home'))->assertOk()->getContent();

    // The prompt must not sit between the sidebar and the mobile header — that
    // breaks Flux's `[data-flux-sidebar]+[data-flux-header]` grid rule and makes
    // the header span the full page width.
    expect(substr_count($html, 'Add your project links and GitHub repo'))->toBe(1)
        ->and(strpos($html, 'data-flux-sidebar'))->not->toBeFalse()
        ->and(strpos($html, 'data-flux-header'))->not->toBeFalse()
        ->and(strpos($html, 'Add your project links and GitHub repo'))->toBeGreaterThan(strpos($html, 'data-flux-header'));
});

test('users who skipped onboarding still see the prompt until their profile is complete', function () {
    $user = User::factory()->create(); // onboarding_completed_at = now (e.g. skipped), empty profile

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertSee('Add your project links and GitHub repo')
        ->assertDontSee('Finish setup')
        ->assertSee('Complete profile');
});

test('the prompt is hidden once the profile is complete', function () {
    $user = onboardingReadyProfile();

    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    $user->skills()->attach($skill);

    Project::create([
        'user_id' => $user->id,
        'title' => 'Payment Gateway',
        'slug' => 'payment-gateway-'.Str::lower(Str::random(6)),
        'problem' => 'Billing was manual.',
        'solution' => 'Built an automated gateway.',
    ]);

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertDontSee('Add your project links and GitHub repo');
});

test('admins never see the onboarding prompt, even without onboarding', function () {
    $admin = User::factory()->withoutOnboarding()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('devid', $admin->handle()))
        ->assertOk()
        ->assertDontSee('Add your project links and GitHub repo');
});

test('recruiter and company accounts never see the onboarding prompt, even without onboarding', function () {
    $company = User::factory()->company()->withoutOnboarding()->create();

    $this->actingAs($company)
        ->get(route('devid', $company->handle()))
        ->assertOk()
        ->assertDontSee('Add your project links and GitHub repo');
});

test('the prompt shows the profile completion percentage in the header', function () {
    $user = onboardingReadyProfile(); // 4 of 6 checks → 67%

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertSee('Add your project links and GitHub repo')
        ->assertSee('67%');
});

test('the prompt disappears once the profile exceeds 75%', function () {
    $user = onboardingReadyProfile(); // 4 of 6 checks → 67%

    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    $user->skills()->attach($skill); // 5 of 6 → 83%

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertDontSee('Add your project links and GitHub repo');
});

test('the profile completion percentage reflects profile signals', function () {
    $service = app(ProfileCompletionService::class);

    $bare = User::factory()->create();

    expect($service->percentage($bare))->toBe(0);

    $partial = User::factory()->create([
        'headline' => 'Senior Laravel engineer',
        'bio' => 'Building thoughtful software.',
        'location' => 'Berlin, Germany',
        'github_url' => 'https://github.com/jane',
    ]);

    expect($service->percentage($partial))->toBe(67); // 4 of 6 checks

    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    $partial->skills()->attach($skill);

    expect($service->percentage($partial->fresh()))->toBe(83); // 5 of 6

    Project::create([
        'user_id' => $partial->id,
        'title' => 'Payment Gateway',
        'slug' => 'payment-gateway-'.Str::lower(Str::random(6)),
        'problem' => 'Billing was manual.',
        'solution' => 'Built an automated gateway.',
    ]);

    expect($service->percentage($partial->fresh()))->toBe(100); // all 6
});
