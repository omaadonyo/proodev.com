<?php

use App\Models\Evidence;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('the feed embeds the scout runner in place of the composer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('scout-runner')
        ->assertDontSee('createProjectFromComposer');
});

test('scouting a github profile builds evidence, projects and journal live', function () {
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

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('scout-runner')
        ->set('url', 'https://github.com/MrPunyapal')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('mode', 'profile');

    for ($i = 0; $i < 40 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done');

    expect(Evidence::where('user_id', $user->id)->count())->toBe(3)
        ->and(Project::where('user_id', $user->id)->where('status', 'published')->count())->toBe(3)
        ->and(JournalEntry::where('user_id', $user->id)->count())->toBe(3)
        ->and($user->fresh()->experience_points)->toBeGreaterThanOrEqual(354);
});

test('scouting a single repository url imports it as evidence and a project', function () {
    Http::fake([
        'api.github.com/repos/MrPunyapal/framework' => Http::response([
            'full_name' => 'MrPunyapal/framework',
            'name' => 'framework',
            'description' => 'A PHP framework for fast APIs',
            'language' => 'PHP',
            'stargazers_count' => 100,
            'forks_count' => 40,
            'topics' => ['framework', 'php'],
            'homepage' => 'https://framework.dev',
            'html_url' => 'https://github.com/MrPunyapal/framework',
        ], 200),
        'api.github.com/repos/MrPunyapal/framework/readme' => Http::response([
            'content' => base64_encode("# Framework\n\nA documented framework built with PHP."),
        ], 200),
    ]);

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('scout-runner')
        ->set('url', 'https://github.com/MrPunyapal/framework')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('mode', 'single');

    for ($i = 0; $i < 20 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done');

    expect(Evidence::where('user_id', $user->id)->count())->toBe(1)
        ->and(Project::where('user_id', $user->id)->where('status', 'published')->count())->toBe(1)
        ->and($user->fresh()->experience_points)->toBe(108);
});

test('an invalid scout url stays on the input phase with a validation error', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('scout-runner')
        ->set('url', 'not-a-real-url')
        ->call('begin')
        ->assertSet('phase', 'input')
        ->assertHasErrors('url');
});

test('the scout runner resets back to the input form', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('scout-runner')
        ->set('url', 'https://github.com/MrPunyapal/framework')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->call('restart')
        ->assertSet('phase', 'input')
        ->assertSet('url', null);
});
