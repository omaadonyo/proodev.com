<?php

use App\Enums\UserRole;
use App\Mail\CandidateShortlistMail;
use App\Models\Evidence;
use App\Models\EvidenceAnalysis;
use App\Models\RecruiterMatch;
use App\Models\Skill;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\EvidenceScoutService;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\JobMatchService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function jobMatchRecruiter(): User
{
    return User::factory()->create(['role' => UserRole::Recruiter]);
}

function jobMatchCandidate(array $overrides = []): User
{
    $dev = User::factory()->create(array_merge([
        'public_passport' => true,
        'is_verified' => true,
        'reputation_score' => 0,
        'experience_points' => 0,
    ], $overrides));

    $evidence = Evidence::create([
        'user_id' => $dev->id,
        'type' => 'github-repository',
        'title' => 'Payment Gateway Service',
        'url' => 'https://github.com/'.$dev->username.'/gateway',
        'source' => 'github',
        'status' => 'ready',
        'ai_score' => 82,
        'analyzed_at' => now(),
    ]);

    EvidenceAnalysis::create([
        'evidence_id' => $evidence->id,
        'summary' => 'A payment gateway with idempotency, webhooks, and a reconciliation worker.',
        'technologies' => ['laravel', 'redis', 'docker'],
        'engineering_areas' => ['Backend Engineering', 'API Engineering'],
        'complexity' => 'complex',
        'knowledge_domains' => ['Payments', 'Distributed Systems'],
        'highlights' => ['Idempotent API design', 'Webhook delivery with retries'],
        'strengths' => ['Robust payment handling'],
        'references' => [['claim' => 'Payment gateway', 'reference' => 'Gateway repo']],
        'generated_by' => 'rule-based-fallback',
    ]);

    return $dev;
}

function seedJobMatchSkills(): void
{
    Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    Skill::create(['name' => 'Redis', 'slug' => 'redis', 'category' => 'data']);
}

test('the matcher extracts keywords from pasted text and ranks verified engineers first', function () {
    seedJobMatchSkills();

    $verified = jobMatchCandidate(['name' => 'Verified Match']);
    $unverified = jobMatchCandidate(['name' => 'Unverified Match', 'is_verified' => false]);

    // Both declare Laravel as a skill so keyword matching fires on skills too.
    $verified->skills()->attach(Skill::where('slug', 'laravel')->first()->id);
    $unverified->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobText', 'We are hiring a Senior Backend Engineer strong in Laravel and Redis. 5+ years of PHP required.')
        ->call('runJobMatch');

    $component->assertSet('matchRan', true)
        ->assertSet('matchedSource', 'text')
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis']);

    $ids = $component->get('matchedIds');

    expect($ids)->toContain($verified->id)
        ->and($ids)->toContain($unverified->id)
        ->and(array_search($verified->id, $ids, true))->toBeLessThan(array_search($unverified->id, $ids, true));
});

test('the matcher fetches a job posting URL and matches engineers from its text', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'URL Match']);

    $this->mock(EvidenceScoutService::class, function ($mock) {
        $mock->shouldReceive('fetch')
            ->once()
            ->with('https://careers.acme.com/senior-backend')
            ->andReturn([
                'title' => 'Senior Backend Engineer',
                'description' => 'We need a Laravel and Redis expert to own our checkout platform.',
                'content' => 'Responsibilities: build APIs with Laravel, run Redis clusters, deploy with Docker.',
            ]);
    });

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobUrl', 'https://careers.acme.com/senior-backend')
        ->call('runJobMatch')
        ->assertSet('matchRan', true)
        ->assertSet('matchedSource', 'url')
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis'])
        ->assertDispatched('toast');

    expect($candidate->is_verified)->toBeTrue();
});

test('directory results show an evidence-match percentage badge after a match runs', function () {
    seedJobMatchSkills();

    $full = jobMatchCandidate(['name' => 'Badge Full Match']);
    $half = jobMatchCandidate(['name' => 'Badge Half Match']);
    $full->skills()->attach(Skill::where('slug', 'laravel')->first()->id);
    $full->skills()->attach(Skill::where('slug', 'redis')->first()->id);
    $half->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('query', 'Badge')
        ->set('jobText', 'We are hiring an engineer with Laravel and Redis skills.')
        ->call('runJobMatch')
        ->assertSet('matchRan', true)
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis'])
        ->assertSee('Badge Full Match')
        ->assertSee('Badge Half Match')
        ->assertSee('100%')
        ->assertSee('50%')
        ->assertSee('Perfect match — covers all selected skills');

    $instance = $component->instance();

    expect($instance->matchPct($full))->toBe(100)
        ->and($instance->matchPct($half))->toBe(50);
});

test('the match badge metric can include evidence technologies via the toggle', function () {
    seedJobMatchSkills();

    // jobMatchCandidate seeds ready evidence with technologies ['laravel', 'redis', 'docker'].
    $candidate = jobMatchCandidate(['name' => 'Tech Metric Candidate']);
    $candidate->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('query', 'Tech Metric')
        ->set('jobText', 'We are hiring a Laravel engineer with Redis and Docker experience.')
        ->call('runJobMatch')
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis'])
        ->assertSee('Tech Metric Candidate');

    // Skills only: laravel covered out of [laravel, redis] -> 50%.
    expect($component->instance()->matchPct($candidate))->toBe(50);

    // With evidence technologies: skills 1/2 + technologies ['laravel','redis','docker'] 3/3 -> 4/5 -> 80%.
    $component->set('includeTechnologies', true)
        ->assertSee('80%')
        ->assertSee('skills & technologies');

    expect($component->instance()->matchPct($candidate))->toBe(80);

    // Toggling back off restores the skills-only percentage.
    $component->set('includeTechnologies', false)
        ->assertSee('50%');

    expect($component->instance()->matchPct($candidate))->toBe(50);
});

test('directory results sort by match percentage descending after a match runs', function () {
    seedJobMatchSkills();

    $full = jobMatchCandidate(['name' => 'Sort Full Match', 'reputation_score' => 10]);
    $half = jobMatchCandidate(['name' => 'Sort Half Match', 'reputation_score' => 20]);
    $none = jobMatchCandidate(['name' => 'Sort None Match', 'reputation_score' => 30]);
    $full->skills()->attach(Skill::where('slug', 'laravel')->first()->id);
    $full->skills()->attach(Skill::where('slug', 'redis')->first()->id);
    $half->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('query', 'Sort');

    // Without a match, the original reputation order is kept.
    expect($component->instance()->directoryEngineers()->pluck('name')->all())
        ->toBe(['Sort None Match', 'Sort Half Match', 'Sort Full Match']);

    // After a match, best-fit engineers surface first regardless of reputation.
    $component->set('jobText', 'We are hiring an engineer with Laravel and Redis skills.')
        ->call('runJobMatch')
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis']);

    expect($component->instance()->directoryEngineers()->pluck('name')->all())
        ->toBe(['Sort Full Match', 'Sort Half Match', 'Sort None Match']);

    $component->assertSeeInOrder(['Sort Full Match', 'Sort Half Match', 'Sort None Match']);
});

test('the active match context is persisted per recruiter and clears on reset', function () {
    seedJobMatchSkills();
    jobMatchCandidate(['name' => 'Context Candidate']);
    $recruiter = jobMatchRecruiter();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->set('jobText', 'We are hiring a Laravel and Redis engineer.')
        ->call('runJobMatch')
        ->assertSet('matchRan', true);

    $record = RecruiterMatch::activeFor($recruiter);

    expect($record)->not->toBeNull()
        ->and($record->skills)->toBe(['laravel', 'redis'])
        ->and($record->include_technologies)->toBeFalse();

    $component = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->set('includeTechnologies', true);

    expect(RecruiterMatch::activeFor($recruiter)->include_technologies)->toBeTrue();

    $component->call('resetJobMatch');

    expect(RecruiterMatch::activeFor($recruiter))->toBeNull();
});

test('the search page restores a persisted match on mount so badges survive restarts', function () {
    seedJobMatchSkills();
    $recruiter = jobMatchRecruiter();
    $candidate = jobMatchCandidate(['name' => 'Restored Match Candidate']);
    $candidate->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    RecruiterMatch::setFor($recruiter, ['skills' => ['laravel'], 'technologies' => []], [$candidate->id], false);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->assertSet('matchRan', true)
        ->assertSet('matchedIds', [$candidate->id])
        ->assertSee('Restored Match Candidate')
        ->assertSee('100%', false);
});

test('the search page keeps the active match in the URL query string', function () {
    seedJobMatchSkills();
    $recruiter = jobMatchRecruiter();
    $candidate = jobMatchCandidate(['name' => 'URL Match Candidate']);
    $candidate->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs($recruiter)->test('pages::recruiter.search');

    // The mount render registers the URL mapping (?match) that the frontend
    // applies whenever the match state changes.
    $mountEffects = $component->effects;

    expect($mountEffects['url']['matchRan'] ?? null)->not->toBeNull()
        ->and($mountEffects['url']['matchRan']['as'])->toBe('match')
        ->and($mountEffects['url']['matchRan']['except'])->toBeFalse();

    // Running and clearing the match toggles the URL-backed state.
    $component->set('jobText', 'We are hiring a Laravel engineer.')
        ->call('runJobMatch')
        ->assertSet('matchRan', true)
        ->call('resetJobMatch')
        ->assertSet('matchRan', false);
});

test('loading the search page with ?match=1 restores the matched Directory view', function () {
    seedJobMatchSkills();
    $recruiter = jobMatchRecruiter();
    $candidate = jobMatchCandidate(['name' => 'URL Restore Candidate']);
    $candidate->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    RecruiterMatch::setFor($recruiter, ['skills' => ['laravel'], 'technologies' => []], [$candidate->id], false);

    Livewire::withQueryParams(['match' => '1'])
        ->actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->assertSet('matchRan', true)
        ->assertSee('URL Restore Candidate')
        ->assertSee('ranked by fit')
        ->assertSee('100%', false);
});

test('the candidate report header shows the match badge and best-match ring', function () {
    seedJobMatchSkills();

    $full = jobMatchCandidate(['name' => 'Report Full Match']);
    $half = jobMatchCandidate(['name' => 'Report Half Match']);
    $full->skills()->attach(Skill::where('slug', 'laravel')->first()->id);
    $full->skills()->attach(Skill::where('slug', 'redis')->first()->id);
    $half->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $recruiter = jobMatchRecruiter();
    RecruiterMatch::setFor($recruiter, ['skills' => ['laravel', 'redis'], 'technologies' => []], [$full->id, $half->id], false);

    $fullComponent = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $full]);

    $fullComponent
        ->assertSee('Perfect match — covers all selected skills', false)
        ->assertSee('#34d399, #14b8a6', false);

    expect($fullComponent->instance()->matchPct())->toBe(100);

    $halfComponent = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $half]);

    $halfComponent->assertSee('50% of the posting', false);

    expect($halfComponent->instance()->matchPct())->toBe(50);
});

test('the candidate report shows no match badge without an active match', function () {
    seedJobMatchSkills();
    $dev = jobMatchCandidate(['name' => 'Report No Match']);

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->assertSee('Report No Match')
        ->assertDontSee('Perfect match — covers all selected skills')
        ->assertDontSee('% of the posting');
});

test('job match results populate the Directory instead of a list below the form', function () {
    seedJobMatchSkills();

    $matched = jobMatchCandidate(['name' => 'Directory Matched Engineer']);
    $unmatched = User::factory()->create([
        'name' => 'Directory Unmatched Engineer',
        'public_passport' => true,
    ]);
    $matched->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobText', 'We are hiring an engineer with Laravel and Redis skills.')
        ->call('runJobMatch')
        ->assertSet('matchRan', true)
        ->assertSet('matchedKeywords.skills', ['laravel', 'redis']);

    // The matched engineer fills the Directory, which is labelled as match results...
    $component->assertSee('Directory Matched Engineer')
        ->assertSee('ranked by fit');

    // ...and the unmatched engineer is not part of the match results.
    $component->assertDontSee('Directory Unmatched Engineer');

    expect($component->instance()->matchedEngineers()->pluck('name')->all())
        ->toBe(['Directory Matched Engineer']);
});

test('talent pool rows show the match badge from the session match context', function () {
    seedJobMatchSkills();
    $recruiter = jobMatchRecruiter();

    $full = jobMatchCandidate(['name' => 'Pool Badge Full']);
    $half = jobMatchCandidate(['name' => 'Pool Badge Half']);
    $full->skills()->attach(Skill::where('slug', 'laravel')->first()->id);
    $full->skills()->attach(Skill::where('slug', 'redis')->first()->id);
    $half->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $full->id, 'status' => 'shortlisted']);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $half->id, 'status' => 'saved']);

    RecruiterMatch::setFor($recruiter, ['skills' => ['laravel', 'redis'], 'technologies' => []], [$full->id, $half->id], false);

    $component = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->assertSee('Pool Badge Full')
        ->assertSee('Pool Badge Half')
        ->assertSee('100%', false)
        ->assertSee('50%', false)
        ->assertSee('Perfect match — covers all selected skills', false);

    $instance = $component->instance();

    expect($instance->matchPctFor($full))->toBe(100)
        ->and($instance->matchPctFor($half))->toBe(50);
});

test('talent pool rows show no match badge without an active match', function () {
    seedJobMatchSkills();
    $recruiter = jobMatchRecruiter();
    $candidate = jobMatchCandidate(['name' => 'Pool Badge None']);
    $candidate->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $candidate->id, 'status' => 'saved']);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->assertSee('Pool Badge None')
        ->assertDontSee('Perfect match — covers all selected skills')
        ->assertDontSee('% of the posting');
});

test('the directory offers a results-per-page selector', function () {
    seedJobMatchSkills();
    jobMatchCandidate(['name' => 'Per Page Candidate']);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->assertSee('18 / page')
        ->assertSee('72 / page')
        ->set('perPage', 36)
        ->assertSet('perPage', 36);

    expect($component->instance()->results()->perPage())->toBe(36);
});

test('bulk actions export and email the selected engineers', function () {
    Mail::fake();
    seedJobMatchSkills();

    $first = jobMatchCandidate(['name' => 'Bulk First Candidate']);
    $second = jobMatchCandidate(['name' => 'Bulk Second Candidate']);
    $recruiter = jobMatchRecruiter();

    $component = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->set('query', 'Bulk')
        ->call('toggleSelect', $first->id)
        ->call('toggleSelect', $second->id)
        ->assertSee('2 selected');

    $component->call('exportSelectedPdf')
        ->assertDispatched('download', function ($name, $params) use ($first, $second) {
            $payload = $params[0] ?? [];
            $pdf = base64_decode($payload['content'] ?? '', true);

            return $name === 'download'
                && ($payload['filename'] ?? '') === 'selected-candidates-'.now()->format('Y-m-d').'.pdf'
                && ($payload['mime'] ?? '') === 'application/pdf'
                && is_string($pdf) && str_starts_with($pdf, '%PDF-')
                && str_contains($pdf, $first->name) && str_contains($pdf, $second->name);
        });

    $component->call('exportSelectedExcel')
        ->assertDispatched('download', function ($name, $params) use ($first, $second) {
            $payload = $params[0] ?? [];

            return $name === 'download'
                && ($payload['filename'] ?? '') === 'selected-candidates-'.now()->format('Y-m-d').'.csv'
                && ($payload['mime'] ?? '') === 'text/csv;charset=utf-8'
                && str_contains($payload['content'], $first->name)
                && str_contains($payload['content'], $second->name);
        });

    $component->call('emailSelected')
        ->assertHasNoErrors();

    Mail::assertQueued(CandidateShortlistMail::class, function ($mail) use ($recruiter, $first, $second) {
        return $mail->hasTo($recruiter->email)
            && collect($mail->rows)->contains(fn ($row) => $row['name'] === $first->name)
            && collect($mail->rows)->contains(fn ($row) => $row['name'] === $second->name);
    });
});

test('clearing the job match restores the full filter-based directory', function () {
    seedJobMatchSkills();

    $matched = jobMatchCandidate(['name' => 'Match Cleared Candidate']);
    $other = User::factory()->create([
        'name' => 'Match Cleared Other',
        'public_passport' => true,
        'reputation_score' => 50,
    ]);
    $matched->skills()->attach(Skill::where('slug', 'laravel')->first()->id);

    $component = Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobText', 'We are hiring an engineer with Laravel and Redis skills.')
        ->call('runJobMatch')
        ->assertDontSee('Match Cleared Other');

    $component->call('resetJobMatch')
        ->assertSee('Match Cleared Other')
        ->assertDontSee('ranked by fit');

    expect($component->instance()->matchedEngineers()->isEmpty())->toBeTrue();
});

test('directory results show no match badges before a match runs', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'No Badge Yet']);

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('query', 'No Badge')
        ->assertSee('No Badge Yet')
        ->assertDontSee('Perfect match — covers all selected skills')
        ->assertDontSee('% of the posting');
});

test('the matcher falls back gracefully when a job posting URL cannot be fetched', function () {
    $this->mock(EvidenceScoutService::class, function ($mock) {
        $mock->shouldReceive('fetch')
            ->once()
            ->andThrow(new RuntimeException('Connection refused'));
    });

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobUrl', 'https://careers.acme.com/unreachable')
        ->call('runJobMatch')
        ->assertSet('matchRan', true)
        ->assertSet('matchedIds', []);
});

test('matched engineers can be saved into a talent pool', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'Pool Candidate']);
    $recruiter = jobMatchRecruiter();

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Backend Shortlist',
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->set('jobText', 'Senior Laravel engineer needed for our API team.')
        ->call('runJobMatch')
        ->call('saveToPool', $candidate->id, $pool->id);

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)
        ->where('candidate_id', $candidate->id)
        ->where('status', 'saved')
        ->exists())->toBeTrue();
});

test('the shortlist email renders candidate photos in a landscape layout', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'Photo Candidate']);
    $recruiter = jobMatchRecruiter();

    $rows = app(JobMatchService::class)->exportRows(collect([$candidate]));
    $mail = new CandidateShortlistMail($recruiter, $rows, 'Backend shortlist');
    $html = $mail->render();

    expect($html)
        ->toContain('container--wide')
        ->toContain('img class="avatar"')
        ->toContain($candidate->avatarUrl())
        ->toContain('Photo Candidate')
        ->toContain('Candidates');

    // The avatar is for the email only — spreadsheets stay clean.
    $csv = app(JobMatchService::class)->toCsv($rows);

    expect($csv)->not->toContain('Avatar');
});

test('the matcher exports the shortlist as an Excel-compatible CSV', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'CSV Candidate']);

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobText', 'Laravel backend role with Redis.')
        ->call('runJobMatch')
        ->call('exportExcel')
        ->assertDispatched('download', function ($name, $params) use ($candidate) {
            $payload = $params[0] ?? [];

            return $name === 'download'
                && ($payload['filename'] ?? '') === 'candidate-shortlist-'.now()->format('Y-m-d').'.csv'
                && ($payload['mime'] ?? '') === 'text/csv;charset=utf-8'
                && str_contains($payload['content'], $candidate->name)
                && str_starts_with($payload['content'], "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        });
});

test('the matcher exports the shortlist as a PDF', function () {
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'PDF Candidate']);

    Livewire::actingAs(jobMatchRecruiter())
        ->test('pages::recruiter.search')
        ->set('jobText', 'Laravel backend role with Redis.')
        ->call('runJobMatch')
        ->call('exportPdf')
        ->assertDispatched('download', function ($name, $params) use ($candidate) {
            $payload = $params[0] ?? [];
            $pdf = base64_decode($payload['content'] ?? '', true);

            return $name === 'download'
                && ($payload['filename'] ?? '') === 'candidate-shortlist-'.now()->format('Y-m-d').'.pdf'
                && ($payload['mime'] ?? '') === 'application/pdf'
                && is_string($pdf)
                && str_starts_with($pdf, '%PDF-')
                && str_contains($pdf, $candidate->name);
        });
});

test('the matcher emails the shortlist to a hiring manager', function () {
    Mail::fake();
    seedJobMatchSkills();
    $candidate = jobMatchCandidate(['name' => 'Email Candidate']);
    $recruiter = jobMatchRecruiter();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->set('jobText', 'Laravel backend role with Redis.')
        ->call('runJobMatch')
        ->call('emailShortlist')
        ->assertHasNoErrors();

    Mail::assertQueued(CandidateShortlistMail::class, function ($mail) use ($recruiter, $candidate) {
        return $mail->hasTo($recruiter->email)
            && collect($mail->rows)->contains(fn ($row) => $row['name'] === $candidate->name)
            && str_contains($mail->title, '1 engineers');
    });
});
