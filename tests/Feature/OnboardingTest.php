<?php

use App\Models\Evidence;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\ProfileScoutService;
use Illuminate\Support\Facades\Http;

test('guests are redirected from the onboarding page', function () {
    $this->get(route('onboarding'))->assertRedirect(route('login'));
});

test('users who finished onboarding are sent straight to the feed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertRedirect(route('home'));
});

test('the onboarding page renders for a new user', function () {
    $user = User::factory()->withoutOnboarding()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertSeeLivewire('pages::onboarding');
});

test('the onboarding nudge appears when scanning a GitHub profile without a linked account', function () {
    $user = User::factory()->withoutOnboarding()->create(['github_url' => null]);

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/MrPunyapal')
        ->assertSee('GitHub account yet')
        ->assertSee('plagiarism guard can verify');
});

test('the onboarding nudge hides once a GitHub account is linked', function () {
    $user = User::factory()->withoutOnboarding()->create(['github_url' => 'https://github.com/jane']);

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/MrPunyapal')
        ->assertDontSee('GitHub account yet');
});

test('the onboarding nudge hides for non-GitHub profile URLs', function () {
    $user = User::factory()->withoutOnboarding()->create(['github_url' => null]);

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://linkedin.com/in/someone')
        ->assertDontSee('GitHub account yet');
});

test('skipping onboarding completes it and goes to the feed', function () {
    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->call('skip')
        ->assertRedirect(route('home'));

    expect($user->fresh()->hasCompletedOnboarding())->toBeTrue();
});

test('an unsupported profile URL shows an error', function () {
    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://example.com/not-a-profile')
        ->call('begin')
        ->assertSet('phase', 'input')
        ->assertNotSet('error', null);
});

test('a github url without a username shows an error', function () {
    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/')
        ->call('begin')
        ->assertSet('phase', 'input')
        ->assertSet('error', 'We could not find a username in that GitHub URL.');
});

test('scanning a github profile builds evidence, projects, journal and a level from the repos', function () {
    Http::fake([
        'api.github.com/users/MrPunyapal' => Http::response([
            'login' => 'MrPunyapal',
            'name' => 'Punyapal Shah',
            'bio' => 'Full-stack engineer',
            'company' => 'Acme Corp',
            'location' => 'India',
            'followers' => 120,
            'public_repos' => 4,
            'created_at' => '2015-01-01T00:00:00Z',
        ], 200),
        'api.github.com/users/MrPunyapal/repos*' => Http::response([
            [
                'name' => 'framework',
                'full_name' => 'MrPunyapal/framework',
                'description' => 'A PHP framework for fast APIs',
                'language' => 'PHP',
                'stargazers_count' => 100,
                'forks_count' => 40,
                'topics' => ['framework', 'php'],
                'homepage' => 'https://framework.dev',
                'html_url' => 'https://github.com/MrPunyapal/framework',
                'size' => 5000,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2020-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-06-01T00:00:00Z',
            ],
            [
                'name' => 'laravel-app',
                'full_name' => 'MrPunyapal/laravel-app',
                'description' => 'A Laravel application',
                'language' => 'PHP',
                'stargazers_count' => 50,
                'forks_count' => 5,
                'topics' => ['laravel'],
                'homepage' => null,
                'html_url' => 'https://github.com/MrPunyapal/laravel-app',
                'size' => 2000,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2021-03-01T00:00:00Z',
                'updated_at' => '2023-05-01T00:00:00Z',
                'pushed_at' => '2023-05-01T00:00:00Z',
            ],
            [
                'name' => 'notes',
                'full_name' => 'MrPunyapal/notes',
                'description' => 'My personal engineering notes',
                'language' => 'JavaScript',
                'stargazers_count' => 30,
                'forks_count' => 1,
                'topics' => ['notes'],
                'homepage' => null,
                'html_url' => 'https://github.com/MrPunyapal/notes',
                'size' => 500,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2022-05-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z',
                'pushed_at' => '2023-01-01T00:00:00Z',
            ],
            [
                'name' => 'forked-thing',
                'full_name' => 'someone/forked-thing',
                'description' => 'A fork of someone elses work',
                'language' => 'Rust',
                'stargazers_count' => 0,
                'forks_count' => 0,
                'topics' => [],
                'homepage' => null,
                'html_url' => 'https://github.com/someone/forked-thing',
                'size' => 100,
                'fork' => true,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-02-01T00:00:00Z',
                'pushed_at' => '2023-02-01T00:00:00Z',
            ],
        ], 200),
        'api.github.com/repos/MrPunyapal/*/readme' => Http::response([
            'content' => base64_encode("# Project\n\nA documented project built with PHP and queues."),
        ], 200),
    ]);

    $user = User::factory()->withoutOnboarding()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/MrPunyapal')
        ->call('begin')
        ->assertSet('phase', 'scouting');

    for ($i = 0; $i < 40 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done');

    $user = $user->fresh();

    expect($user->github_url)->toBe('https://github.com/MrPunyapal')
        ->and($user->name)->toBe('Punyapal Shah')
        ->and($user->headline)->toBe('Acme Corp')
        ->and($user->location)->toBe('India')
        ->and($user->bio)->not->toBeNull()
        ->and($user->bio)->toContain('public repositories')
        ->and($user->hasCompletedOnboarding())->toBeTrue()
        ->and($user->skills->pluck('name'))->toContain('PHP')
        ->and($user->skills->pluck('name'))->toContain('JavaScript');

    // Every real repo becomes evidence; the fork is skipped.
    expect(Evidence::where('user_id', $user->id)->count())->toBe(3)
        ->and(Evidence::where('user_id', $user->id)->pluck('url'))
        ->not->toContain('https://github.com/someone/forked-thing');

    // The strongest repos become published projects, dated from repo history.
    expect(Project::where('user_id', $user->id)->where('status', 'published')->count())->toBe(3);

    $framework = Project::where('user_id', $user->id)->where('repository_url', 'https://github.com/MrPunyapal/framework')->first();

    expect($framework)->not->toBeNull()
        ->and($framework->published_at->toDateString())->toBe('2024-06-01')
        ->and($framework->tech_stack)->toContain('PHP')
        ->and($framework->repository_url)->toBe('https://github.com/MrPunyapal/framework');

    // Journal entries are dated from repo creation.
    expect(JournalEntry::where('user_id', $user->id)->count())->toBe(3);

    $oldest = JournalEntry::where('user_id', $user->id)->orderBy('published_at')->first();

    expect($oldest->title)->toBe('Started framework')
        ->and($oldest->published_at->toDateString())->toBe('2020-01-01')
        ->and($oldest->isPublic())->toBeTrue();

    // Level and XP are derived entirely from what the scan found.
    expect($user->experience_points)->toBeGreaterThanOrEqual(600)
        ->and($user->level())->toBeGreaterThanOrEqual(3);
});

test('scanning a profile with no public repositories still completes onboarding', function () {
    Http::fake([
        'api.github.com/users/ghost' => Http::response([
            'login' => 'ghost',
            'name' => 'Ghost User',
            'followers' => 1,
            'public_repos' => 0,
        ], 200),
        'api.github.com/users/ghost/repos*' => Http::response([], 200),
    ]);

    $user = User::factory()->withoutOnboarding()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/ghost')
        ->call('begin');

    for ($i = 0; $i < 10 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done');

    expect($user->fresh()->hasCompletedOnboarding())->toBeTrue()
        ->and(Evidence::where('user_id', $user->id)->count())->toBe(0)
        ->and(Project::where('user_id', $user->id)->count())->toBe(0);
});

test('scouting a linkedin url saves the link and completes onboarding', function () {
    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://www.linkedin.com/in/punyapal')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'done');

    expect($user->fresh()->linkedin_url)->toBe('https://www.linkedin.com/in/punyapal')
        ->and($user->fresh()->hasCompletedOnboarding())->toBeTrue();
});

test('finishing onboarding redirects to the feed', function () {
    Http::fake(['api.github.com/*' => Http::response(['login' => 'dev', 'followers' => 1, 'public_repos' => 1], 200)]);

    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/dev')
        ->call('begin')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('finish')
        ->assertRedirect(route('home'));
});

test('a failed github fetch returns to the input phase with an error', function () {
    Http::fake([
        'api.github.com/users/ghost' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::onboarding')
        ->set('url', 'https://github.com/ghost')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->call('tick')
        ->assertSet('phase', 'input')
        ->assertNotSet('error', null);
});

test('the profile scout service rejects unsupported sources', function () {
    expect(fn () => app(ProfileScoutService::class)->scout('https://example.com/x'))
        ->toThrow(InvalidArgumentException::class);
});
