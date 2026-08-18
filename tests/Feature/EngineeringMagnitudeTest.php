<?php

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Enums\ProjectStatus;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\User;
use App\Models\Vouch;
use App\Services\EngineeringMagnitudeService;

test('engineering magnitude returns an explainable factor breakdown', function () {
    $user = User::factory()->create(['streak_count' => 10, 'longest_streak' => 20]);

    $evidence = Evidence::create([
        'user_id' => $user->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'Inventory OS',
        'url' => 'https://github.com/acme/inventory-os',
        'source' => 'github',
        'status' => EvidenceStatus::Ready,
        'ai_score' => 78,
    ]);

    $evidence->analysis()->create([
        'summary' => 'A Laravel inventory system.',
        'technologies' => ['Laravel', 'Redis', 'Postgres'],
        'engineering_areas' => ['Backend Engineering', 'Software Architecture', 'Performance Engineering'],
        'complexity' => 'complex',
        'architecture_observations' => 'Service-oriented design.',
        'skills' => [['name' => 'Laravel', 'confidence' => 85]],
        'knowledge_domains' => ['Backend'],
        'highlights' => ['Highlights'],
        'strengths' => ['Clean architecture'],
        'references' => [['claim' => 'Uses Laravel', 'reference' => 'README']],
    ]);

    $project = Project::create([
        'user_id' => $user->id,
        'title' => 'Inventory OS',
        'slug' => 'inventory-os',
        'problem' => 'Problem',
        'solution' => 'Solution',
        'status' => ProjectStatus::Published,
        'published_at' => now(),
        'tech_stack' => ['Laravel'],
        'engineering_decisions' => ['Used queues', 'Cached queries'],
        'recognition_count' => 3,
    ]);

    Vouch::create([
        'voucher_id' => User::factory()->create()->id,
        'vouchee_id' => $user->id,
        'type' => 'architecture',
        'status' => 'approved',
        'weight' => 2,
    ]);

    $result = app(EngineeringMagnitudeService::class)->breakdown($user);

    expect($result)->toHaveKey('total')
        ->and($result['total'])->toBeBetween(0, 1000)
        ->and($result['factors'])->toHaveKeys([
            'evidence_quality',
            'technical_depth',
            'knowledge_sharing',
            'breadth_of_expertise',
            'consistency',
            'community_trust',
            'verification',
            'contribution_history',
        ]);

    foreach ($result['factors'] as $factor) {
        expect($factor['label'])->not->toBeEmpty()
            ->and($factor['points'])->toBeGreaterThanOrEqual(0)
            ->and($factor['points'])->toBeLessThanOrEqual($factor['max'])
            ->and($factor['description'])->not->toBeEmpty();
    }

    expect($result['factors']['evidence_quality']['evidence'])->toContain('Inventory OS');
});

test('engineering magnitude labels are ordered and explainable', function () {
    $service = app(EngineeringMagnitudeService::class);

    expect($service->labelFor(0))->toBe('Emerging')
        ->and($service->labelFor(200))->toBe('Building')
        ->and($service->labelFor(400))->toBe('Established')
        ->and($service->labelFor(500))->toBe('Proven')
        ->and($service->labelFor(700))->toBe('Distinguished')
        ->and($service->labelFor(900))->toBe('Exceptional');
});
