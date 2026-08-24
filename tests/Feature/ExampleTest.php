<?php

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Paste evidence. Get proof.')
        ->assertSeeLivewire('landing-scout');
});

test('the landing scout runs the demo build when the url is empty', function () {
    Http::fake([
        'api.github.com/users/MrPunyapal' => Http::response([
            'login' => 'MrPunyapal',
            'name' => 'Punyapal Shah',
            'bio' => 'Full-stack engineer',
            'followers' => 120,
            'public_repos' => 2,
        ], 200),
        'api.github.com/users/MrPunyapal/repos*' => Http::response([
            [
                'name' => 'framework',
                'full_name' => 'MrPunyapal/framework',
                'description' => 'A PHP framework for fast APIs',
                'language' => 'PHP',
                'stargazers_count' => 100,
                'forks_count' => 40,
                'topics' => ['php'],
                'homepage' => null,
                'html_url' => 'https://github.com/MrPunyapal/framework',
                'size' => 5000,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2020-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-06-01T00:00:00Z',
            ],
        ], 200),
    ]);

    Livewire::test('landing-scout')
        ->assertSet('phase', 'input')
        ->assertSet('url', 'https://github.com/MrPunyapal?tab=repositories')
        ->set('url', '')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('url', 'https://github.com/MrPunyapal?tab=repositories')
        ->assertSee('proodev · scout')
        ->assertSee('DevID build')
        ->assertSee('Profile fetch')
        ->assertSee('Level & magnitude');

    $component = Livewire::test('landing-scout')
        ->set('url', '')
        ->call('begin');

    for ($i = 0; $i < 30 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done')
        ->assertSet('material.name', 'Punyapal Shah')
        ->assertSee('DevID ready');
});

test('the landing scout keeps the default demo when the url is untouched', function () {
    Livewire::test('landing-scout')
        ->call('begin')
        ->assertSet('demo', true)
        ->assertSet('phase', 'scouting');
});

test('the landing scout resets back to the input with the default url', function () {
    Livewire::test('landing-scout')
        ->call('begin')
        ->call('tryAgain')
        ->assertSet('phase', 'input')
        ->assertSet('url', 'https://github.com/MrPunyapal?tab=repositories');
});

test('the landing scout component can scout a real github repo', function () {
    Http::fake([
        'api.github.com/repos/MrPunyapal/demo-app' => Http::response([
            'full_name' => 'MrPunyapal/demo-app',
            'name' => 'demo-app',
            'description' => 'A demo application',
            'html_url' => 'https://github.com/MrPunyapal/demo-app',
            'language' => 'PHP',
            'topics' => ['laravel'],
            'homepage' => 'https://demo.example.com',
            'default_branch' => 'main',
        ], 200),
        'api.github.com/repos/MrPunyapal/demo-app/readme' => Http::response([
            'content' => base64_encode('Built with Laravel and Redis.'),
        ], 200),
    ]);

    $component = Livewire::test('landing-scout')
        ->set('url', 'https://github.com/MrPunyapal/demo-app')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('demo', false);

    for ($i = 0; $i < 30 && $component->get('phase') !== 'done'; $i++) {
        $component->call('tick');
    }

    $component->assertSet('phase', 'done')
        ->assertSet('score', 100);
});
