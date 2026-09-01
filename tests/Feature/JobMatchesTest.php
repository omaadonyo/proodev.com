<?php

use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobMatch;
use App\Models\Skill;
use App\Models\User;
use App\Services\JobMatchService;
use Livewire\Livewire;

test('guests can browse open roles without match scoring', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    Livewire::test('pages::jobs.index')
        ->assertOk()
        ->assertSee($job->title)
        ->assertSet('canMatch', false)
        ->assertDontSee('Analyze with AI')
        ->assertDontSee('Quick estimate');
});

test('authenticated developers see match scoring on the jobs page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    Livewire::actingAs($user)
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSet('canMatch', true)
        ->assertSee($job->title)
        ->assertSee('Quick estimate')
        ->assertSee('/100');
});

test('only open jobs from approved companies appear', function () {
    $user = User::factory()->create();

    $approved = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $open = Job::factory()->create(['company_id' => $approved->id, 'status' => JobStatus::Open]);
    $draft = Job::factory()->draft()->create(['company_id' => $approved->id]);
    $closed = Job::factory()->closed()->create(['company_id' => $approved->id]);
    $pendingCompany = Company::factory()->pending()->create();
    $hidden = Job::factory()->create(['company_id' => $pendingCompany->id, 'status' => JobStatus::Open]);

    Livewire::actingAs($user)
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSee($open->title)
        ->assertDontSee($draft->title)
        ->assertDontSee($closed->title)
        ->assertDontSee($hidden->title);
});

test('the jobs page shows a quick rule-based estimate without persisting', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $user->skills()->attach($skill->id, ['level' => 5, 'verified_at' => now()]);

    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create([
        'company_id' => $company->id,
        'status' => JobStatus::Open,
        'title' => 'Senior Laravel Developer',
        'description' => 'We are hiring a Laravel engineer.',
        'requirements' => ['Expert in Laravel', 'Experience with MySQL'],
    ]);

    Livewire::actingAs($user)
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSee('Quick estimate')
        ->assertSee('Senior Laravel Developer');

    expect(JobMatch::where('user_id', $user->id)->where('job_id', $job->id)->exists())->toBeFalse();
});

test('analyze persists a cached match and displays it', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $user->skills()->attach($skill->id, ['level' => 5, 'verified_at' => now()]);

    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create([
        'company_id' => $company->id,
        'status' => JobStatus::Open,
        'title' => 'Senior Laravel Developer',
        'description' => 'We are hiring a Laravel engineer.',
        'requirements' => ['Expert in Laravel'],
    ]);

    Livewire::actingAs($user)
        ->test('pages::jobs.index')
        ->call('analyze', $job->id)
        ->assertOk()
        ->assertDispatched('toast-show');

    $match = JobMatch::where('user_id', $user->id)->where('job_id', $job->id)->first();

    expect($match)->not->toBeNull()
        ->and($match->score)->toBeInt()
        ->and($match->generated_by)->toBe('rule-based-fallback')
        ->and($match->matched_skills)->toContain('Laravel');
});

test('a cached match is reused instead of re-analyzed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    JobMatch::create([
        'user_id' => $user->id,
        'job_id' => $job->id,
        'score' => 92,
        'recommendation' => 'strong_match',
        'summary' => 'Cached analysis.',
        'matched_skills' => ['Laravel'],
        'missing_skills' => [],
        'generated_by' => 'rule-based-fallback',
        'analyzed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSee('Cached analysis.')
        ->assertSee('92/100')
        ->assertSee('Scouted');
});

test('quick score reflects profile overlap', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $user->skills()->attach($skill->id, ['level' => 5, 'verified_at' => now()]);

    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);

    $matching = Job::factory()->create([
        'company_id' => $company->id,
        'status' => JobStatus::Open,
        'title' => 'Laravel Developer',
        'description' => 'Laravel, MySQL.',
        'requirements' => ['Laravel'],
    ]);

    $unrelated = Job::factory()->create([
        'company_id' => $company->id,
        'status' => JobStatus::Open,
        'title' => 'Rust Systems Engineer',
        'description' => 'Rust, Kubernetes.',
        'requirements' => ['Rust'],
    ]);

    $service = app(JobMatchService::class);
    $profileKeywords = $service->profileKeywords($user);
    $matchingScore = $service->quickScoreWithProfile($profileKeywords, $matching);
    $unrelatedScore = $service->quickScoreWithProfile($profileKeywords, $unrelated);

    expect($matchingScore['score'])->toBeGreaterThan($unrelatedScore['score'])
        ->and($matchingScore['matched_skills'])->toContain('Laravel')
        ->and($unrelatedScore['missing_skills'])->not->toBeEmpty();
});
