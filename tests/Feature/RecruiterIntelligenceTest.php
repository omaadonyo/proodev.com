<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Mail\InterviewInvitationMail;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\EvidenceAnalysis;
use App\Models\RecruiterInterview;
use App\Models\RecruiterNote;
use App\Models\ResumeValidation;
use App\Models\Skill;
use App\Models\TalentAlert;
use App\Models\TalentPool;
use App\Models\TalentPoolMember;
use App\Models\User;
use App\Services\Recruiter\AgencyWorkspaceService;
use App\Services\Recruiter\CandidateComparisonService;
use App\Services\Recruiter\CandidateIntelligenceService;
use App\Services\Recruiter\EvidenceSearchService;
use App\Services\Recruiter\ExecutiveReportService;
use App\Services\Recruiter\InterviewGeneratorService;
use App\Services\Recruiter\JobMatchService;
use App\Services\Recruiter\RankingService;
use App\Services\Recruiter\RecruiterAccessService;
use App\Services\Recruiter\ResumeValidationService;
use App\Services\Recruiter\RiskAssessmentService;
use App\Services\Recruiter\TalentAlertService;
use App\Services\Recruiter\TeamFitService;
use App\Services\Recruiter\VerifiedExpertService;
use App\Support\CalendarInvite;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function recruiterUser(): User
{
    return User::factory()->create(['role' => UserRole::Recruiter]);
}

function developerWithEvidence(array $overrides = []): User
{
    $dev = User::factory()->create(array_merge([
        'public_passport' => true,
        'is_verified' => true,
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

function intelligenceCompany(User $owner): Company
{
    return Company::factory()->create([
        'owner_id' => $owner->id,
        'plan' => CompanyPlan::Intelligence,
        'status' => 'approved',
    ]);
}

test('a recruiter-role user has intelligence access', function () {
    $recruiter = recruiterUser();

    expect(app(RecruiterAccessService::class)->canAccess($recruiter))->toBeTrue();
});

test('a developer user without a paid plan has no intelligence access', function () {
    $dev = User::factory()->create(['role' => UserRole::Developer]);

    expect(app(RecruiterAccessService::class)->canAccess($dev))->toBeFalse();
});

test('a company owner on the intelligence plan has access', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    intelligenceCompany($owner);

    expect(app(RecruiterAccessService::class)->canAccess($owner))->toBeTrue();
});

test('a company owner on the free plan has intelligence access but not workspace access', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'status' => 'approved']);

    expect(app(RecruiterAccessService::class)->canAccess($owner))->toBeTrue()
        ->and($owner->hasWorkspaceAccess())->toBeFalse();
});

test('a company owner on a paid plan has intelligence and workspace access', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Intelligence, 'status' => 'approved']);

    expect(app(RecruiterAccessService::class)->canAccess($owner))->toBeTrue()
        ->and($owner->hasWorkspaceAccess())->toBeTrue();
});

test('guests and developers are blocked from the recruiter suite', function () {
    $dev = User::factory()->create();

    $this->get(route('recruiter.index'))->assertRedirect(route('login'));
    $this->actingAs($dev)->get(route('recruiter.index'))->assertForbidden();
});

test('recruiters can access the intelligence hub', function () {
    $this->actingAs(recruiterUser())
        ->get(route('recruiter.index'))
        ->assertOk();
});

test('the candidate intelligence report is evidence-backed and explainable', function () {
    $dev = developerWithEvidence();

    $report = app(CandidateIntelligenceService::class)->report($dev);

    expect($report['developer']['name'])->toBe($dev->name)
        ->and($report['magnitude']['total'])->toBeInt()
        ->and($report['magnitude']['factors'])->not->toBeEmpty()
        ->and($report['evidence']['count'])->toBe(1)
        ->and($report['evidence']['technologies'])->toContain('laravel')
        ->and($report['evidence']['engineering_areas'])->toContain('Backend Engineering')
        ->and($report['verification']['verified'])->toBeTrue()
        ->and($report['generated_by'])->toBe('evidence-engine')
        ->and($report['suggested_roles'])->not->toBeEmpty();
});

test('the candidate intelligence report can be cached per recruiter', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $first = app(CandidateIntelligenceService::class)->report($dev, ['recruiter' => $recruiter]);
    $second = app(CandidateIntelligenceService::class)->report($dev, ['recruiter' => $recruiter]);

    expect($recruiter->candidateIntelligenceReports()->count())->toBe(1)
        ->and($first['magnitude']['total'])->toBe($second['magnitude']['total']);
});

test('candidate comparison recommends a winner across two candidates', function () {
    $strong = developerWithEvidence(['name' => 'Strong Dev']);

    // Give Strong Dev a second, higher-scoring evidence source so it clearly leads.
    $extra = Evidence::create([
        'user_id' => $strong->id,
        'type' => 'technical-article',
        'title' => 'Distributed Systems Playbook',
        'url' => 'https://example.com/distributed',
        'source' => 'web',
        'status' => 'ready',
        'ai_score' => 95,
        'analyzed_at' => now(),
    ]);
    EvidenceAnalysis::create([
        'evidence_id' => $extra->id,
        'summary' => 'A deep article on distributed systems and consensus algorithms.',
        'technologies' => ['redis', 'kubernetes', 'kafka'],
        'engineering_areas' => ['Software Architecture', 'Distributed Systems'],
        'complexity' => 'advanced',
        'knowledge_domains' => ['Distributed Systems'],
        'highlights' => ['Raft consensus', 'Horizontal scaling'],
        'strengths' => ['Deep systems thinking'],
        'references' => [],
        'generated_by' => 'rule-based-fallback',
    ]);

    $weak = developerWithEvidence(['name' => 'Weak Dev']);

    $result = app(CandidateComparisonService::class)->compare([$weak, $strong]);

    expect($result['winner']['name'])->toBe('Strong Dev')
        ->and($result['candidates'])->toHaveCount(2)
        ->and($result['matrix'])->not->toBeEmpty();
});

test('comparison requires at least two candidates', function () {
    $dev = developerWithEvidence();

    expect(fn () => app(CandidateComparisonService::class)->compare([$dev]))
        ->toThrow(InvalidArgumentException::class);
});

test('evidence search finds engineers by technology inside analyzed work', function () {
    developerWithEvidence(['name' => 'Redis Engineer']);

    $results = app(EvidenceSearchService::class)->byTechnology('redis');

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Redis Engineer');
});

test('evidence search finds engineers by engineering area', function () {
    developerWithEvidence(['name' => 'Backend Pro']);

    $results = app(EvidenceSearchService::class)->byEngineeringArea('API Engineering');

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Backend Pro');
});

test('rankings order candidates by engineering magnitude', function () {
    $a = developerWithEvidence(['name' => 'Rank A']);
    $b = developerWithEvidence(['name' => 'Rank B']);

    $ranked = app(RankingService::class)->rankCollection(collect([$b, $a]));

    expect($ranked)->toHaveCount(2)
        ->and($ranked[0]['rank'])->toBe(1)
        ->and($ranked[0]['magnitude'])->toBeGreaterThanOrEqual($ranked[1]['magnitude']);
});

test('verified network only surfaces verified engineers', function () {
    developerWithEvidence(['name' => 'Verified Pro', 'is_verified' => true]);
    User::factory()->create(['public_passport' => true, 'is_verified' => false]);

    $experts = app(VerifiedExpertService::class)->verified();

    expect($experts)->toHaveCount(1)
        ->and($experts->first()['developer']['name'])->toBe('Verified Pro');
});

test('resume validation compares resume claims against evidence', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $result = app(ResumeValidationService::class)->validate(
        $recruiter,
        $dev,
        'Senior Laravel developer with 8 years of PHP and API engineering experience. Expert in Redis and Docker.',
    );

    expect($result['proven_claims'])->toContain('laravel')
        ->and($result['unproven_claims'])->not->toBeEmpty()
        ->and($result['confidence'])->toBeInt()
        ->and($result['generated_by'])->toBe('evidence-engine')
        ->and(ResumeValidation::where('recruiter_id', $recruiter->id)->exists())->toBeTrue();
});

test('resume validation flags seniority contradictions', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $result = app(ResumeValidationService::class)->validate(
        $recruiter,
        $dev,
        'Staff Principal engineer with 20 years of experience leading huge teams.',
    );

    expect($result['contradictions'])->not->toBeEmpty();
});

test('risk assessment flags thin evidence on a new candidate', function () {
    $dev = User::factory()->create(['public_passport' => true]);

    $risk = app(RiskAssessmentService::class)->assess($dev);

    expect($risk['overall_risk'])->toBe('high')
        ->and($risk['risks'])->not->toBeEmpty()
        ->and($risk['recommendation'])->toBeString();
});

test('interview generator produces evidence-grounded questions', function () {
    $dev = developerWithEvidence();

    $guide = app(InterviewGeneratorService::class)->generate($dev);

    expect($guide['sections']['technical'])->not->toBeEmpty()
        ->and($guide['sections']['behavioural'])->not->toBeEmpty()
        ->and($guide['sections']['probing'])->not->toBeEmpty()
        ->and($guide['probe_note'])->toBeString();
});

test('executive report exports a complete brief', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $brief = app(ExecutiveReportService::class)->build($dev, $recruiter);

    expect($brief['meta']['candidate'])->toBe($dev->name)
        ->and($brief['executive_summary'])->toBeString()
        ->and($brief['magnitude_factors'])->not->toBeEmpty()
        ->and($brief['strengths'])->not->toBeEmpty()
        ->and($brief['disclaimer'])->toBeString();
});

test('agency workspace creates pools and tracks candidate status', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    $result = app(AgencyWorkspaceService::class)->addCandidate($recruiter, $dev);

    expect($result['pool']->id)->toBe($pool->id)
        ->and($pool->members()->where('candidate_id', $dev->id)->exists())->toBeTrue();

    app(AgencyWorkspaceService::class)->setStatus($result['member'], ['status' => 'shortlisted']);

    expect($result['member']->fresh()->status)->toBe('shortlisted');

    app(AgencyWorkspaceService::class)->addNote($recruiter, $dev, 'Strong backend signals.');

    expect(RecruiterNote::where('recruiter_id', $recruiter->id)->exists())->toBeTrue();
});

test('agency workspace overview aggregates pipeline stats', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    app(AgencyWorkspaceService::class)->addCandidate($recruiter, $dev);
    RecruiterInterview::create([
        'recruiter_id' => $recruiter->id,
        'candidate_id' => $dev->id,
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'mode' => 'video',
    ]);

    $overview = app(AgencyWorkspaceService::class)->overview($recruiter);

    expect($overview['total_candidates'])->toBe(1)
        ->and($overview['active_interviews'])->toHaveCount(1)
        ->and($overview['pools'])->not->toBeEmpty();
});

test('talent alerts find matching candidates', function () {
    $recruiter = recruiterUser();
    developerWithEvidence(['name' => 'Alert Match']);

    $alert = TalentAlert::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Laravel devs',
        'criteria' => ['min_magnitude' => 100, 'verified_only' => true],
        'frequency' => 'daily',
    ]);

    $matches = app(TalentAlertService::class)->runAlert($alert);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['name'])->toBe('Alert Match')
        ->and($alert->fresh()->last_run_at)->not->toBeNull();
});

test('talent alert keywords are extracted from a pasted job description', function () {
    Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    Skill::create(['name' => 'Redis', 'slug' => 'redis', 'category' => 'data']);

    $job = 'Senior Backend Engineer\nWe are looking for a Laravel developer who loves Redis and Docker.\nRequirements: 5+ years of PHP, REST APIs, queue workers.';

    $service = app(JobMatchService::class);
    $keywords = $service->extractKeywords($service->resolveText($job)['text']);

    expect($keywords['skills'])->toContain('laravel')
        ->and($keywords['skills'])->toContain('redis');
});

test('a talent alert can be created from a job posting on the alerts page', function () {
    Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);

    $recruiter = recruiterUser();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.alerts')
        ->set('name', 'Senior Laravel')
        ->set('jobText', 'We are hiring a Senior Laravel engineer to own our checkout platform.')
        ->call('createAlert');

    $alert = TalentAlert::where('recruiter_id', $recruiter->id)->first();

    expect($alert)->not->toBeNull()
        ->and($alert->criteria['skills'])->toContain('laravel')
        ->and($alert->criteria['source'])->toBe('job');
});

test('talent alerts match candidates by extracted technologies in evidence', function () {
    developerWithEvidence(['name' => 'Redis Engineer']);

    $matches = app(TalentAlertService::class)->findMatches([
        'skills' => [],
        'technologies' => ['redis'],
    ]);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['name'])->toBe('Redis Engineer');
});

test('team fit assesses a candidate against team gaps', function () {
    $owner = User::factory()->create(['role' => UserRole::Company]);
    $company = intelligenceCompany($owner);
    $dev = developerWithEvidence();

    $team = $company->teamProfiles()->create([
        'name' => 'Backend Team',
        'strengths' => ['Frontend Engineering'],
        'gaps' => ['Backend Engineering', 'API Engineering'],
        'desired_expertise' => ['laravel', 'redis'],
    ]);

    $fit = app(TeamFitService::class)->assess($team, $dev);

    expect($fit['fit_score'])->toBeGreaterThanOrEqual(50)
        ->and($fit['gap_coverage']['covered'])->not->toBeEmpty()
        ->and($fit['verdict'])->toBeString();
});

test('recruiter routes are reachable for a recruiter user', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $this->actingAs($recruiter);

    foreach ([
        route('recruiter.compare'),
        route('recruiter.search'),
        route('recruiter.rankings'),
        route('recruiter.validate'),
        route('recruiter.interviews'),
        route('recruiter.workspace'),
        route('recruiter.alerts'),
        route('recruiter.candidates.show', $dev->id),
        route('recruiter.exports', $dev->id),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('recruiter sidebar groups the intelligence suite into hiring steps', function () {
    $recruiter = recruiterUser();

    $this->actingAs($recruiter)
        ->get(route('recruiter.index'))
        ->assertOk()
        ->assertSee('Recruiter Intelligence')
        ->assertSee('Hiring journey')
        ->assertSee('Discover')
        ->assertSee('Evidence Search')
        ->assertSee('Magnitude Rankings')
        ->assertSee('Talent Alerts')
        ->assertSee('Evaluate')
        ->assertSee('Compare Candidates')
        ->assertSee('Resume Validation')
        ->assertSee('Interview Builder')
        ->assertSee('Hire')
        ->assertSee('Agency Workspace');
});

test('recruiters can create talent pools and save candidates from evidence search', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->assertSee('New pool name')
        ->set('newPoolName', 'Q3 Backend')
        ->call('createPool')
        ->assertHasNoErrors();

    $pool = TalentPool::where('recruiter_id', $recruiter->id)->first();

    expect($pool)->not->toBeNull()
        ->and($pool->name)->toBe('Q3 Backend');

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->call('saveToPool', $dev->id, $pool->id)
        ->assertDispatched('toast');

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->exists())->toBeTrue();
});

test('evidence search supports view toggles and avatar-click selection', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();
    developerWithEvidence(['username' => 'view-two-'.uniqid()]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->assertSet('view', 'grid')
        ->call('setView', 'avatars')
        ->assertSet('view', 'avatars')
        ->call('setView', 'detailed')
        ->assertSet('view', 'detailed')
        ->call('setView', 'not-a-view')
        ->assertSet('view', 'grid')
        ->call('toggleSelect', $dev->id)
        ->assertSet('selected', [$dev->id])
        ->call('toggleSelect', $dev->id)
        ->assertSet('selected', []);
});

test('bulk saving selected candidates saves them and removes them from results', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();
    $dev2 = developerWithEvidence(['username' => 'bulk-dev-'.uniqid()]);

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Bulk pool',
        'slug' => 'bulk-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->call('toggleSelect', $dev->id)
        ->call('toggleSelect', $dev2->id)
        ->call('bulkSaveToPool', $pool->id)
        ->assertDispatched('toast')
        ->assertSet('savedIds', [$dev->id, $dev2->id])
        ->assertSet('selected', []);

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)->count())->toBe(2);
});

test('compare selected from search redirects with the candidate ids', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();
    $dev2 = developerWithEvidence(['username' => 'cmp-redirect-'.uniqid()]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->call('toggleSelect', $dev->id)
        ->call('toggleSelect', $dev2->id)
        ->call('compareSelected')
        ->assertRedirect(route('recruiter.compare', ['ids' => $dev->id.','.$dev2->id]));
});

test('rankings open candidate details and export a candidate pdf', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $component = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.rankings')
        ->call('openCandidate', $dev->id)
        ->assertSet('activeCandidateId', $dev->id)
        ->assertSee('Export details (PDF)')
        ->assertSee($dev->name);

    $component->call('exportCandidatePdf')->assertDispatched('download');
});

test('compare prefills selected candidates from ids and adds pool members', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();
    $dev2 = developerWithEvidence(['username' => 'pool-compare-'.uniqid()]);

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Compare pool',
        'slug' => 'compare-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev2->id, 'status' => 'saved']);

    // Pool-member add first (before any query params are introduced).
    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.compare')
        ->set('activePoolId', (string) $pool->id)
        ->assertSee('Add from talent pool')
        ->assertSee($dev2->name)
        ->call('addFromPool', $dev2->id)
        ->assertSet('selected', [$dev2->id]);

    // Then verify the ids query param prefills the comparison from the search page.
    Livewire::actingAs($recruiter)
        ->withQueryParams(['ids' => $dev->id.','.$dev2->id])
        ->test('pages::recruiter.compare')
        ->assertSet('selected', [$dev->id, $dev2->id]);
});

test('candidates can be saved to a pool with a status from search results', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Status pool',
        'slug' => 'status-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->assertSee('Shortlisted')
        ->assertSee('Interviewing')
        ->assertSee('Offered')
        ->call('setCandidateStatus', $dev->id, $pool->id, 'shortlisted')
        ->assertDispatched('toast')
        ->assertSet('savedIds', [$dev->id]);

    $member = TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->first();

    expect($member)->not->toBeNull()
        ->and($member->status)->toBe('shortlisted');
});

test('candidate status updates in place and removing restores them to results', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Update pool',
        'slug' => 'update-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    $component = Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->call('setCandidateStatus', $dev->id, $pool->id, 'shortlisted')
        ->call('setCandidateStatus', $dev->id, $pool->id, 'interviewing');

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->count())->toBe(1)
        ->and(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->first()->status)->toBe('interviewing');

    $component
        ->call('removeFromPool', $dev->id, $pool->id)
        ->assertDispatched('toast')
        ->assertSet('savedIds', []);

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->exists())->toBeFalse();
});

test('candidate reports let recruiters stack candidates for comparison', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->assertSee('Compare');
});

test('the compare tray stacks candidates in the session and opens the comparison', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $component = Livewire::actingAs($recruiter)
        ->test('compare-tray', ['candidateId' => $dev->id])
        ->call('add')
        ->assertDispatched('toast');

    expect(session('recruiter_compare_ids'))->toBe([$dev->id]);

    $component
        ->assertSee($dev->name)
        ->call('compare')
        ->assertRedirect(route('recruiter.compare', ['ids' => (string) $dev->id]));
});

test('the compare tray caps the stack at three and dedupes', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();
    $dev2 = developerWithEvidence(['username' => 'stack-two-'.uniqid()]);
    $dev3 = developerWithEvidence(['username' => 'stack-three-'.uniqid()]);
    $dev4 = developerWithEvidence(['username' => 'stack-four-'.uniqid()]);

    session()->put('recruiter_compare_ids', [$dev->id, $dev2->id, $dev3->id]);

    Livewire::actingAs($recruiter)
        ->test('compare-tray', ['candidateId' => $dev->id])
        ->call('add')
        ->assertDispatched('toast');

    expect(session('recruiter_compare_ids'))->toBe([$dev->id, $dev2->id, $dev3->id])
        ->and(count(session('recruiter_compare_ids')))->toBe(3);
});

test('the interview builder lists saved pool candidates without search and marks them compared', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Interview pool',
        'slug' => 'interview-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev->id, 'status' => 'saved']);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.interviews')
        ->assertSee('Your saved candidates')
        ->assertSee($dev->name)
        ->assertSee('Interview pool')
        ->call('selectCandidate', $dev->id)
        ->assertSee('Mark as compared')
        ->call('markCompared', $dev->id)
        ->assertDispatched('toast')
        ->assertSee('Marked as compared')
        ->assertSee('Save the approved candidate');

    expect(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->first()->status)->toBe('interviewing');
});

test('the agency workspace supports view toggles for pool members', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev->id, 'status' => 'shortlisted']);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->assertSet('view', 'grid')
        ->assertSee($dev->name)
        ->call('setView', 'avatars')
        ->assertSet('view', 'avatars')
        ->call('setView', 'detailed')
        ->assertSet('view', 'detailed')
        ->assertSee('Change status')
        ->call('setView', 'list')
        ->assertSet('view', 'list');
});

test('the agency workspace filters pool members by search', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence(['name' => 'UniqueName Alice']);
    $other = developerWithEvidence(['name' => 'Bob SomethingElse']);

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev->id, 'status' => 'shortlisted']);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $other->id, 'status' => 'shortlisted']);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->assertSee($dev->name)
        ->assertSee($other->name)
        ->set('search', 'UniqueName')
        ->assertSee($dev->name)
        ->assertDontSee($other->name);
});

test('the interview builder schedules an interview and moves the candidate to interviewing', function () {
    Mail::fake();

    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev->id, 'status' => 'saved']);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.interviews')
        ->call('selectCandidate', $dev->id)
        ->assertSee('Schedule interview')
        ->set('scheduledAt', now()->addDays(2)->format('Y-m-d H:i'))
        ->set('interviewMode', 'video')
        ->call('scheduleInterview')
        ->assertDispatched('toast')
        ->assertSee('Upcoming interviews')
        ->assertSee($dev->name);

    $interview = RecruiterInterview::where('recruiter_id', $recruiter->id)->first();

    expect($interview)->not->toBeNull()
        ->and($interview->candidate_id)->toBe($dev->id)
        ->and($interview->status)->toBe('scheduled')
        ->and($interview->mode)->toBe('video')
        ->and(TalentPoolMember::where('talent_pool_id', $pool->id)->where('candidate_id', $dev->id)->first()->status)->toBe('interviewing');

    Mail::assertQueued(InterviewInvitationMail::class, fn (InterviewInvitationMail $mail) => $mail->hasTo($dev->email));

    $ics = CalendarInvite::for($interview->fresh(), $recruiter);

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->toContain('END:VCALENDAR')
        ->toContain($dev->email)
        ->toContain('METHOD:REQUEST')
        ->toContain('DTSTART:');
});

test('the agency workspace remembers view and search per pool across visits', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence(['name' => 'Zed Unique']);

    $pool = app(AgencyWorkspaceService::class)->defaultPool($recruiter);
    TalentPoolMember::create(['talent_pool_id' => $pool->id, 'candidate_id' => $dev->id, 'status' => 'saved']);

    $pool2 = TalentPool::create([
        'recruiter_id' => $recruiter->id,
        'name' => 'Second pool',
        'slug' => 'second-pool-'.uniqid(),
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->call('setView', 'avatars')
        ->set('search', 'Zed')
        ->call('selectPool', $pool2->id)
        ->assertSet('view', 'grid')
        ->assertSet('search', '')
        ->call('selectPool', $pool->id)
        ->assertSet('view', 'avatars')
        ->assertSet('search', 'Zed');

    $recruiter->refresh();

    expect($recruiter->preferences['workspace_pool_state'][$pool->id])->toBe(['view' => 'avatars', 'search' => 'Zed'])
        ->and($recruiter->preferences['workspace_pool_state'][$pool2->id])->toBe(['view' => 'grid', 'search' => '']);

    // A fresh visit restores each pool's own state.
    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.workspace')
        ->assertSet('view', 'avatars')
        ->assertSet('search', 'Zed');
});

test('the interview builder shows a weekly calendar with scheduled interviews', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    RecruiterInterview::create([
        'recruiter_id' => $recruiter->id,
        'candidate_id' => $dev->id,
        'status' => 'scheduled',
        'scheduled_at' => Carbon::now()->startOfWeek()->addDays(2)->setTime(10, 30),
        'mode' => 'video',
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.interviews')
        ->assertSee('Weekly calendar')
        ->assertSee($dev->name)
        ->call('selectInterview', RecruiterInterview::first()->id)
        ->assertSee('Open report')
        ->assertSee('Cancel')
        ->call('nextWeek')
        ->assertSet('weekStart', Carbon::now()->startOfWeek()->addWeek()->toDateString())
        ->call('thisWeek')
        ->assertSet('weekStart', Carbon::now()->startOfWeek()->toDateString());
});

test('saving to a pool is scoped to the recruiter who owns it', function () {
    $recruiter = recruiterUser();
    $other = recruiterUser();
    $dev = developerWithEvidence();

    $otherPool = TalentPool::create([
        'recruiter_id' => $other->id,
        'name' => 'Private pool',
        'slug' => 'private-pool',
        'kind' => 'collection',
        'is_shared' => true,
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.search')
        ->call('saveToPool', $dev->id, $otherPool->id);

    expect(TalentPoolMember::where('talent_pool_id', $otherPool->id)->count())->toBe(0);
});

test('company sidebar merges company tools and moves subscription under settings', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('companies.dashboard', $company))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Post a Job')
        ->assertSee('Public Profile')
        ->assertSee('Applicants')
        ->assertSee('Subscription')
        ->assertDontSee('/discover');
});

test('the recruiter pipeline reflects the current hiring stage', function () {
    $recruiter = recruiterUser();

    $this->actingAs($recruiter)
        ->get(route('recruiter.search'))
        ->assertOk()
        ->assertSee('Hiring journey')
        ->assertSee('1/3');

    $this->actingAs($recruiter)
        ->get(route('recruiter.workspace'))
        ->assertOk()
        ->assertSee('3/3');
});

test('the pricing page is publicly accessible', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('$599');
});

test('saving a candidate from the report page creates a pool member', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->call('saveCandidate', 'shortlisted');

    expect(TalentPool::where('recruiter_id', $recruiter->id)->first()->members()->count())->toBe(1);
});

test('adding a note from the report page persists it', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->set('noteBody', 'Excellent backend signals.')
        ->call('addNote');

    expect(RecruiterNote::where('recruiter_id', $recruiter->id)->where('body', 'Excellent backend signals.')->exists())->toBeTrue();
});

test('the report header shows the developer social links like the passport', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence([
        'github_url' => 'https://github.com/mia-chen',
        'linkedin_url' => 'https://linkedin.com/in/mia-chen',
        'website_url' => 'https://mia.dev',
    ]);

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->assertOk()
        ->assertSee('https://github.com/mia-chen')
        ->assertSee('https://linkedin.com/in/mia-chen')
        ->assertSee('https://mia.dev')
        ->assertSee('GitHub')
        ->assertSee('LinkedIn')
        ->assertSee('Website');
});

test('the report header hides socials when none are set', function () {
    $recruiter = recruiterUser();
    $dev = developerWithEvidence();

    Livewire::actingAs($recruiter)
        ->test('pages::recruiter.candidates.show', ['candidate' => $dev])
        ->assertOk()
        ->assertDontSee('linkedin.com')
        ->assertDontSee('mia.dev');
});
