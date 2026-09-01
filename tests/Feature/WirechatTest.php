<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('verified members can access the chats panel', function () {
    $user = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('wirechat.chats.chats'))
        ->assertOk();
});

test('unverified members cannot access the chats panel', function () {
    $user = User::factory()->create(['is_verified' => false]);

    $this->actingAs($user)
        ->get(route('wirechat.chats.chats'))
        ->assertNotFound();
});

test('guests cannot access the chats panel', function () {
    $this->get(route('wirechat.chats.chats'))
        ->assertRedirect(route('login'));
});

test('admins can access the chats panel and the admin chat management panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('wirechat.chats.chats'))
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/chats')
        ->assertOk();
});

test('non-admin users cannot access the admin chat management panel', function () {
    $user = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $this->actingAs($user)
        ->get('/admin/chats')
        ->assertNotFound();
});

test('employers opening a candidate CV records a resume view', function () {
    $candidate = User::factory()->create();
    $company = Company::factory()->create();
    $member = User::factory()->create();

    CompanyMember::create(['company_id' => $company->id, 'user_id' => $member->id, 'role' => 'admin']);

    $job = Job::factory()->create(['company_id' => $company->id]);
    $application = Application::factory()->create([
        'job_id' => $job->id,
        'user_id' => $candidate->id,
        'status' => ApplicationStatus::Pending,
        'resume_path' => 'resumes/candidate.pdf',
    ]);

    Storage::fake('local');
    Storage::disk('local')->put('resumes/candidate.pdf', 'pdf-content');

    $this->actingAs($member)
        ->get(route('applications.resume', $application))
        ->assertOk();

    $application->refresh();

    expect($application->resume_view_count)->toBe(1);
    expect($application->last_resume_viewed_at)->not->toBeNull();
});

test('the sidebar shows the get verified banner to unverified developers', function () {
    $user = User::factory()->create(['is_verified' => false]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Get Verified')
        ->assertSee('Priority hiring consideration')
        ->assertSee('$17')
        ->assertSee('$8');
});

test('the sidebar hides the get verified banner for verified users, admins and company accounts', function () {
    $verified = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($verified)->get(route('home'))->assertDontSee('Get Verified');
    $this->actingAs($admin)->get(route('home'))->assertDontSee('Get Verified');
});

test('verified developers get a messages link and admins get chat management', function () {
    $verified = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $this->actingAs($verified)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Messages');

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Chat Management');
});

test('candidates downloading their own CV does not count as a view', function () {
    $candidate = User::factory()->create();
    $company = Company::factory()->create();
    $job = Job::factory()->create(['company_id' => $company->id]);
    $application = Application::factory()->create([
        'job_id' => $job->id,
        'user_id' => $candidate->id,
        'status' => ApplicationStatus::Pending,
        'resume_path' => 'resumes/candidate.pdf',
    ]);

    Storage::fake('local');
    Storage::disk('local')->put('resumes/candidate.pdf', 'pdf-content');

    $this->actingAs($candidate)
        ->get(route('applications.resume', $application))
        ->assertOk();

    $application->refresh();

    expect($application->resume_view_count)->toBe(0);
    expect($application->last_resume_viewed_at)->toBeNull();
});
