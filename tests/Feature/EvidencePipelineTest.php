<?php

use App\Actions\Evidence\AddEvidenceAction;
use App\Enums\EvidenceStatus;
use App\Jobs\AnalyzeEvidenceJob;
use App\Models\Evidence;
use App\Models\User;
use App\Services\Ai\RuleBasedFallbackProvider;
use App\Services\EvidenceScoutService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->scout = app(EvidenceScoutService::class);
});

test('classify detects github repositories', function () {
    $c = $this->scout->classify('https://github.com/laravel/framework');

    expect($c['type']->value)->toBe('github-repository')
        ->and($c['source'])->toBe('github')
        ->and($c['handle'])->toBe('laravel')
        ->and($c['repo'])->toBe('framework');
});

test('classify detects packages, articles and videos', function () {
    expect($this->scout->classify('https://www.npmjs.com/package/axios')['type']->value)->toBe('package');
    expect($this->scout->classify('https://dev.to/laravel/awesome')['type']->value)->toBe('technical-article');
    expect($this->scout->classify('https://www.youtube.com/watch?v=x')['type']->value)->toBe('technical-video');
    expect($this->scout->classify('https://example.com')['type']->value)->toBe('personal-website');
});

test('rule-based fallback produces an evidence report with references', function () {
    $provider = new RuleBasedFallbackProvider;
    $report = $provider->structured(
        'You are an expert engineering intelligence analyst.',
        '...',
        ['content' => 'Built a Laravel API with queues, Redis caching, Docker, and a Postgres database. Used Pest for testing and a modular service layer.'],
    );

    expect($report)->toHaveKeys([
        'summary', 'technologies', 'engineering_areas', 'complexity',
        'architecture_observations', 'skills', 'knowledge_domains',
        'highlights', 'strengths', 'references',
    ])->and($report['technologies'])->toContain('Laravel')
        ->and($report['references'])->not->toBeEmpty();
});

test('adding evidence creates a record, timeline event, and dispatches the job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $evidence = app(AddEvidenceAction::class)->handle($user, 'https://github.com/laravel/framework');

    expect($evidence)->toBeInstanceOf(Evidence::class)
        ->and($evidence->status)->toBe(EvidenceStatus::Pending);

    $this->assertDatabaseHas('timeline_events', [
        'user_id' => $user->id,
        'target_type' => Evidence::class,
        'target_id' => $evidence->id,
    ]);

    Queue::assertPushed(AnalyzeEvidenceJob::class, fn ($job) => $job->evidence->id === $evidence->id);
});
