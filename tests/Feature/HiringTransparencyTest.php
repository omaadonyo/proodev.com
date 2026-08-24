<?php

use App\Enums\ApplicationStatus;
use App\Enums\CompanyStatus;
use App\Enums\FeedbackCategory;
use App\Enums\HiringStage;
use App\Enums\JobStatus;
use App\Events\ApplicationStageChanged;
use App\Mail\ApplicationStageUpdateMail;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Services\HiringTransparencyService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function hiringSetup(): array
{
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    return [$owner, $company, $job];
}

test('applying to a job creates an application-received timeline event', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $developer = User::factory()->create();

    $application = Application::create([
        'job_id' => $job->id,
        'user_id' => $developer->id,
    ]);

    app(HiringTransparencyService::class)->recordStage($application, HiringStage::ApplicationReceived);

    expect($application->timeline())->toHaveCount(1)
        ->and($application->latestStage())->toBe(HiringStage::ApplicationReceived)
        ->and($application->events->first()->created_at)->not->toBeNull();
});

test('verified developers see their application timeline', function () {
    [$owner, $company, $job] = hiringSetup();
    $developer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $developer->id]);
    app(HiringTransparencyService::class)->recordStage($application, HiringStage::Shortlisted);

    Livewire::actingAs($developer)
        ->test('pages::applications.index')
        ->assertOk()
        ->assertSee('Application timeline')
        ->assertSee(HiringStage::Shortlisted->label());
});

test('unverified developers see the locked preview but not real events', function () {
    [$owner, $company, $job] = hiringSetup();
    $developer = User::factory()->create(['is_verified' => false]);

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $developer->id]);
    app(HiringTransparencyService::class)->recordStage($application, HiringStage::NotSelected);

    Livewire::actingAs($developer)
        ->test('pages::applications.index')
        ->assertOk()
        ->assertSee('Unlock Hiring Transparency')
        ->assertSee('Verify My DevID')
        ->assertDontSee(HiringStage::NotSelected->label());
});

test('recruiters can move candidates through stages and events are auditable', function () {
    Mail::fake();
    Event::fake([ApplicationStageChanged::class]);

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    Livewire::actingAs($owner)
        ->test('pages::companies.applicants', ['company' => $company])
        ->call('selectJob', $job->id)
        ->call('setStage', $application->id, 'shortlisted');

    $application->refresh();

    expect($application->status->value)->toBe(ApplicationStatus::Shortlisted->value)
        ->and($application->latestStage())->toBe(HiringStage::Shortlisted)
        ->and($application->events()->count())->toBeGreaterThanOrEqual(1);

    Event::assertDispatched(ApplicationStageChanged::class);
});

test('rejection without feedback shows no invented reason', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    Livewire::actingAs($owner)
        ->test('pages::companies.applicants', ['company' => $company])
        ->call('selectJob', $job->id)
        ->call('confirmReject', $application->id);

    $event = $application->refresh()->timeline()->last();

    expect($event->stage())->toBe(HiringStage::NotSelected)
        ->and($event->feedback_category)->toBeNull()
        ->and($application->status->value)->toBe('rejected');
});

test('structured feedback is attached when provided', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    Livewire::actingAs($owner)
        ->test('pages::companies.applicants', ['company' => $company])
        ->call('selectJob', $job->id)
        ->set('provideFeedback', true)
        ->set('feedbackCategory', FeedbackCategory::StrongerCandidate->value)
        ->set('feedbackNote', "Distributed systems\nProduction-scale infrastructure")
        ->call('confirmReject', $application->id);

    $event = $application->refresh()->timeline()->last();

    expect($event->feedbackCategory())->toBe(FeedbackCategory::StrongerCandidate)
        ->and($event->feedback_note)->toContain('Production-scale');
});

test('companies can suppress candidate notifications per stage', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    // Suppress shortlist notifications.
    $company->update(['hiring_settings' => ['notify_shortlisted' => false]]);

    app(HiringTransparencyService::class)->recordStage($application, HiringStage::Shortlisted);

    Mail::assertNothingSent(ApplicationStageUpdateMail::class);
});

test('stage notifications are emailed when transparency allows it', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    app(HiringTransparencyService::class)->recordStage($application, HiringStage::Interview);

    Mail::assertQueued(ApplicationStageUpdateMail::class, fn ($mail) => $mail->hasTo($candidate->email));
});

test('developers cannot see other developers application events', function () {
    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create(['is_verified' => true]);
    $outsider = User::factory()->create(['is_verified' => true]);

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);
    app(HiringTransparencyService::class)->recordStage($application, HiringStage::Offer);

    Livewire::actingAs($outsider)
        ->test('pages::applications.index')
        ->assertOk()
        ->assertDontSee($application->job->title)
        ->assertDontSee(HiringStage::Offer->label());

    expect(Application::where('user_id', $outsider->id)->count())->toBe(0);
});

test('role paused is not treated as a rejection', function () {
    Mail::fake();

    [$owner, $company, $job] = hiringSetup();
    $candidate = User::factory()->create();

    $application = Application::factory()->create(['job_id' => $job->id, 'user_id' => $candidate->id]);

    app(HiringTransparencyService::class)->recordStage($application, HiringStage::RolePaused);

    expect($application->refresh()->status->value)->toBe('pending')
        ->and($application->latestStage())->toBe(HiringStage::RolePaused)
        ->and($application->latestStage()->isClosedState())->toBeFalse();
});
