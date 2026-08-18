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
    Livewire::test('landing-scout')
        ->assertSet('phase', 'input')
        ->assertSet('url', 'https://github.com/MrPunyapal/proodev')
        ->set('url', '')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('demo', true)
        ->assertSet('url', 'https://github.com/MrPunyapal/proodev')
        ->assertSee('proodev · scout')
        ->assertSee('Passport build')
        ->assertSee('Profile fetch')
        ->assertSee('Level & magnitude')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'done')
        ->assertSet('score', 912)
        ->assertSet('material.title', 'ProoDev')
        ->assertSet('draft.problem', 'Engineers scatter their work across projects, journals and separate tools, making it hard to prove their skills with evidence. Reputation is self-reported, so claims carry little signal and are easy to fake.')
        ->assertSee('Passport ready');
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
        ->assertSet('url', 'https://github.com/MrPunyapal/proodev');
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

    Livewire::test('landing-scout')
        ->set('url', 'https://github.com/MrPunyapal/demo-app')
        ->call('begin')
        ->assertSet('phase', 'scouting')
        ->assertSet('demo', false)
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->call('tick')
        ->assertSet('phase', 'done')
        ->assertSet('score', 100);
});
