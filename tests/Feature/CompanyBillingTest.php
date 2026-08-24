<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Skill;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function billingAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function grantHiringVerification(Company $company): void
{
    app(BillingService::class)->createCompanyVerificationPayment($company);

    Payment::where('company_id', $company->id)
        ->where('purpose', 'verification')
        ->update(['status' => 'paid', 'paid_at' => now()]);
}

test('a company can be registered and is active on the free plan immediately', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::companies.create')
        ->set('name', 'Acme Inc')
        ->call('create')
        ->assertRedirect();

    $company = Company::where('name', 'Acme Inc')->first();

    expect($company)->not->toBeNull()
        ->and($company->status)->toBe(CompanyStatus::Approved)
        ->and($company->approved_at)->not->toBeNull()
        ->and($company->canPostJobs())->toBeFalse() // requires $299 hiring verification
        ->and((function () use ($company) { grantHiringVerification($company); return $company->refresh()->canPostJobs(); })())->toBeTrue()
        ->and($company->members()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('company onboarding completes profile and publishes the first job', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);
    grantHiringVerification($company);

    Livewire::actingAs($owner)
        ->test('pages::companies.onboarding', ['company' => $company])
        ->set('industry', 'SaaS')
        ->set('location', 'Remote')
        ->set('website', 'https://acme.com')
        ->call('saveDetails')
        ->assertSet('phase', 'job')
        ->set('title', 'Senior Backend Engineer')
        ->call('saveJob');

    $company->refresh();

    expect($company->industry)->toBe('SaaS')
        ->and($company->location)->toBe('Remote')
        ->and($company->website)->toBe('https://acme.com')
        ->and($company->jobs()->where('title', 'Senior Backend Engineer')->where('status', JobStatus::Open)->exists())->toBeTrue();
});

test('company onboarding can skip the job step', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::companies.onboarding', ['company' => $company])
        ->call('skipJob')
        ->assertRedirect();

    expect($company->jobs()->count())->toBe(0);
});

test('trial companies are limited to a single active job', function () {
    $company = Company::factory()->create(['plan' => CompanyPlan::Trial, 'status' => CompanyStatus::Approved]);

    Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    expect($company->canPostJobs())->toBeFalse();

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Draft]);

    expect($job->status)->toBe(JobStatus::Draft);
});

test('paid companies can post unlimited active jobs', function () {
    $company = Company::factory()->recruiter()->create(['status' => CompanyStatus::Approved]);
    grantHiringVerification($company);

    Job::factory()->count(3)->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    expect($company->canPostJobs())->toBeTrue()
        ->and($company->openJobsCount())->toBe(3)
        ->and($company->planLimitReached())->toBeFalse();

    Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    expect($company->refresh()->canPostJobs())->toBeTrue();
});

test('trial companies are limited by their job post credits', function () {
    $company = Company::factory()->create(['plan' => CompanyPlan::Trial, 'job_post_credits' => 2, 'status' => CompanyStatus::Approved]);
    grantHiringVerification($company);

    Job::factory()->count(2)->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    expect($company->canPostJobs())->toBeFalse()
        ->and($company->remainingJobPosts())->toBe(0);

    // Closing a job frees a credit, so posting resumes.
    $company->jobs()->first()->update(['status' => JobStatus::Closed]);

    expect($company->refresh()->canPostJobs())->toBeTrue()
        ->and($company->remainingJobPosts())->toBe(1);
});

test('job post credits are tracked per company and grantable', function () {
    $company = Company::factory()->create(['plan' => CompanyPlan::Trial, 'job_post_credits' => 1, 'status' => CompanyStatus::Approved]);

    expect($company->jobPostCredits())->toBe(1);

    $company->grantJobPosts(3);

    expect($company->jobPostCredits())->toBe(4);
});

test('buying a single job post bundle is priced at $299 and grants one credit on confirmation', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'job_post_credits' => 1]);

    $payment = app(BillingService::class)->createJobPostsPayment($company, 1);

    expect((float) $payment->amount)->toBe(299.0)
        ->and($payment->metadata['job_posts'])->toBe(1)
        ->and($payment->purpose->value)->toBe('job-posts');

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $company->refresh();

    expect($company->jobPostCredits())->toBe(2);
});

test('an unknown job posts bundle is rejected', function () {
    $company = Company::factory()->create();

    app(BillingService::class)->createJobPostsPayment($company, 3);
})->throws(InvalidArgumentException::class);

test('confirming a verification payment activates the badge and short domain', function () {
    $user = User::factory()->create();

    $payment = app(BillingService::class)->createVerificationPayment($user, 'sam-dev');

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $user->refresh();

    expect($user->isVerified())->toBeTrue()
        ->and($user->short_domain)->toBe('sam-dev')
        ->and($user->verifiedBadge())->toBe('sam-dev')
        ->and($user->userVerifications()->where('status', 'approved')->exists())->toBeTrue();
});

test('confirming a credit purchase grants the purchased credits', function () {
    $user = User::factory()->create();

    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    expect($payment->metadata['credits'])->toBe(8);

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $user->refresh();

    expect($user->creditBalance())->toBe(8)
        ->and($user->creditTransactions()->where('type', 'purchase')->count())->toBe(1);
});

test('an intelligence suite subscription charges the first month price and upgrades the company', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Pending]);

    $payment = app(BillingService::class)->createSubscriptionPayment($company, CompanyPlan::Intelligence);

    expect((float) $payment->amount)->toBe(599.0)
        ->and($payment->metadata['first_month'])->toBeTrue();

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $company->refresh();

    expect($company->plan)->toBe(CompanyPlan::Intelligence)
        ->and($company->status)->toBe(CompanyStatus::Approved);
});

test('a recruiter plan subscription is priced at the monthly rate', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id]);

    $payment = app(BillingService::class)->createSubscriptionPayment($company, CompanyPlan::Recruiter);

    expect((float) $payment->amount)->toBe(299.0);
});

test('confirming a subscription sets the plan renewal date a month out', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Pending]);

    $payment = app(BillingService::class)->createSubscriptionPayment($company, CompanyPlan::Intelligence);

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $company->refresh();

    expect($company->plan_renews_at)->not->toBeNull()
        ->and($company->plan_renews_at->gt(now()->addDays(28)))->toBeTrue()
        ->and($company->plan_renews_at->lt(now()->addDays(32)))->toBeTrue();
});

test('the company manage page shows the current plan summary with renewal date for paid companies', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create([
        'owner_id' => $owner->id,
        'plan' => CompanyPlan::Intelligence,
        'job_post_credits' => 3,
        'status' => CompanyStatus::Approved,
        'plan_renews_at' => now()->addMonth(),
    ]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::companies.manage', ['company' => $company])
        ->assertSee('Current plan')
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('Active')
        ->assertSee('Unlimited')
        ->assertSee('Plan renews')
        ->assertSee($company->plan_renews_at->format('M j, Y'))
        ->assertSee('$199/month');
});

test('a company verification payment is $299 and approves the company on confirmation', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->pending()->create(['owner_id' => $owner->id]);

    $payment = app(BillingService::class)->createCompanyVerificationPayment($company);

    expect((float) $payment->amount)->toBe(299.0)
        ->and($payment->purpose->value)->toBe('verification')
        ->and($payment->metadata['company_verification'])->toBeTrue();

    app(BillingService::class)->markPaid($payment, billingAdmin());

    $company->refresh();

    expect($company->status)->toBe(CompanyStatus::Approved);
});

test('the discover developers page has been removed', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $this->actingAs($owner)
        ->get('/discover')
        ->assertNotFound();
});

test('a developer can apply to an open job', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::jobs.apply', ['company' => $company, 'job' => $job])
        ->call('submit');

    $application = $user->applications()->where('job_id', $job->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->cover_letter)->toBeNull();
});

test('a developer can apply with a PDF resume', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $user = User::factory()->create();

    Storage::fake('local');

    Livewire::actingAs($user)
        ->test('pages::jobs.apply', ['company' => $company, 'job' => $job])
        ->set('resume', UploadedFile::fake()->create('resume.pdf', 200))
        ->call('submit')
        ->assertHasNoErrors();

    $application = $user->applications()->where('job_id', $job->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->resume_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($application->resume_path))->toBeTrue();
});

test('the resume is downloadable by the applicant, the company and recruiters only', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $applicant = User::factory()->create();
    $recruiter = User::factory()->create(['role' => UserRole::Recruiter]);
    $stranger = User::factory()->create();

    Storage::fake('local');
    Storage::disk('local')->put('resumes/test.pdf', 'pdf-bytes');

    $application = Application::factory()->create([
        'job_id' => $job->id,
        'user_id' => $applicant->id,
        'resume_path' => 'resumes/test.pdf',
    ]);

    $this->actingAs($applicant)->get(route('applications.resume', $application))->assertOk();
    $this->actingAs($owner)->get(route('applications.resume', $application))->assertOk();
    $this->actingAs($recruiter)->get(route('applications.resume', $application))->assertOk();

    $this->actingAs($stranger)->get(route('applications.resume', $application))->assertForbidden();
    $this->actingAs($applicant)->get(route('applications.resume', Application::factory()->create(['job_id' => $job->id])))->assertNotFound();
});

test('a developer cannot apply to the same job twice', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $user = User::factory()->create();

    Application::factory()->create(['job_id' => $job->id, 'user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('pages::jobs.apply', ['company' => $company, 'job' => $job])
        ->assertStatus(409);
});

test('the company manage page shows job post bundles and a credits progress bar', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'job_post_credits' => 1, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::companies.manage', ['company' => $company])
        ->assertSee('Buy job post credits')
        ->assertSee('1 job post')
        ->assertSee('$299')
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('$599')
        ->assertSee('$199/month')
        ->assertSee('0 / 1');
});

test('an admin can grant job post credits to a company from the admin panel', function () {
    $admin = billingAdmin();
    $company = Company::factory()->create(['job_post_credits' => 1]);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies')
        ->call('grantJobPosts', $company->id, 3);

    $company->refresh();

    expect($company->jobPostCredits())->toBe(4);
});

test('a recruiter can shortlist an applicant and save a note', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    Livewire::actingAs($owner)
        ->test('pages::companies.manage', ['company' => $company])
        ->call('selectJob', $job->id)
        ->call('setApplicationStatus', $application->id, 'shortlisted')
        ->call('saveNote', $application->id);

    $application->refresh();

    expect($application->status->value)->toBe('shortlisted')
        ->and($application->reviewed_at)->not->toBeNull();
});

test('a recruiter can see the developers who applied to a job opening on the applicants page', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);
    $applicant = User::factory()->create();
    Application::factory()->create(['job_id' => $job->id, 'user_id' => $applicant->id]);

    Livewire::actingAs($owner)
        ->test('pages::companies.applicants', ['company' => $company])
        ->assertSee($job->title)
        ->assertSee($applicant->name)
        ->call('setStage', $applicant->applications()->first()->id, 'shortlisted');

    expect($applicant->applications()->first()->status->value)->toBe('shortlisted');
});

test('the applicants list shows the company logo next to each job', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create([
        'owner_id' => $owner->id,
        'status' => CompanyStatus::Approved,
        'logo_path' => 'company-logos/acme.png',
    ]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'status' => JobStatus::Open]);

    Livewire::actingAs($owner)
        ->test('pages::companies.applicants', ['company' => $company])
        ->assertOk()
        ->assertSee('storage/company-logos/acme.png', false)
        ->assertDontSee('bg-accent/10 font-bold text-accent');
});

test('the subscription page renders for a company owner and shows both tiers', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::subscription')
        ->assertOk()
        ->assertSee('Subscription')
        ->assertSee('Recruiter')
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('$299')
        ->assertSee('$599');
});

test('the subscription page creates a company verification payment', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->pending()->create(['owner_id' => $owner->id]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::subscription')
        ->call('verifyCompany')
        ->assertRedirect();

    $payment = Payment::where('company_id', $company->id)
        ->where('purpose', 'verification')
        ->where('status', 'pending')
        ->first();

    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(299.0);
});

test('the sidebar hides developer profile items and shows subscription for company accounts', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::subscription')
        ->assertOk()
        ->assertDontSee('My Passport')
        ->assertDontSee('Journal')
        ->assertSee('Subscription');
});

test('a free company account sees the full intelligence suite but an upgrade prompt for workspaces', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Trial, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('subscription'))
        ->assertOk()
        ->assertSee('Recruiter Intelligence')
        ->assertSee('Evidence Search')
        ->assertSee('Upgrade for Workspaces')
        ->assertDontSee('Manage Workspaces');
});

test('a company owner on a paid plan sees the full intelligence suite and workspace management', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'plan' => CompanyPlan::Intelligence, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('subscription'))
        ->assertOk()
        ->assertSee('Recruiter Intelligence')
        ->assertSee('Evidence Search')
        ->assertSee('Manage Workspaces')
        ->assertDontSee('Upgrade for Workspaces');
});

test('an admin can approve a pending company', function () {
    $admin = billingAdmin();
    $company = Company::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.companies')
        ->call('approve', $company->id);

    $company->refresh();
    expect($company->status)->toBe(CompanyStatus::Approved);
});

test('an admin can confirm a pending payment', function () {
    $admin = billingAdmin();
    $payment = Payment::factory()->verification()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.payments')
        ->call('markPaid', $payment->id);

    $payment->refresh();
    expect($payment->status->value)->toBe('paid')
        ->and($payment->confirmed_by)->toBe($admin->id);
});

test('the credits page renders the balance and bundles', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Credit balance')
        ->assertSee('Purchase credits');
});

test('the verify page renders for an unverified user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::verify')
        ->assertOk()
        ->assertSee('Developer Verification');
});

test('the jobs index only lists open roles from approved companies', function () {
    $approved = Company::factory()->create(['status' => CompanyStatus::Approved]);
    Job::factory()->create(['company_id' => $approved->id, 'title' => 'Open Senior Role', 'status' => JobStatus::Open]);
    Job::factory()->create(['company_id' => $approved->id, 'title' => 'Closed Role', 'status' => JobStatus::Closed]);

    $pending = Company::factory()->pending()->create();
    Job::factory()->create(['company_id' => $pending->id, 'title' => 'Pending Company Role', 'status' => JobStatus::Open]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSee('Open Senior Role')
        ->assertDontSee('Closed Role')
        ->assertDontSee('Pending Company Role');
});

test('the jobs index shows posted time, deadline and skill tags with logos', function () {
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid(), 'category' => 'backend']);

    $approved = Company::factory()->create(['status' => CompanyStatus::Approved]);
    $job = Job::factory()->create([
        'company_id' => $approved->id,
        'title' => 'Laravel Platform Engineer',
        'description' => 'Build APIs with Laravel for a fast-growing fintech.',
        'status' => JobStatus::Open,
        'published_at' => now()->subDays(2),
        'deadline' => now()->addWeeks(3),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::jobs.index')
        ->assertOk()
        ->assertSee('Posted 2 days ago')
        ->assertSee('Deadline '.$job->deadline->format('M j, Y'))
        ->assertSee('Laravel')
        ->assertSee('viewBox="0 0 24 24"', false);
});

test('a company can upload and remove its logo from onboarding', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::companies.onboarding', ['company' => $company])
        ->set('logo', UploadedFile::fake()->image('acme.png'))
        ->call('saveLogo')
        ->assertHasNoErrors();

    $company->refresh();

    expect($company->logo_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($company->logo_path))->toBeTrue();
    expect($company->logoUrl())->toContain('storage/'.$company->logo_path);

    Livewire::actingAs($owner)
        ->test('pages::companies.onboarding', ['company' => $company])
        ->call('removeLogo')
        ->assertHasNoErrors();

    expect($company->refresh()->logo_path)->toBeNull();
});

test('the company logo component renders the uploaded logo instead of initials', function () {
    $company = Company::factory()->create(['logo_path' => 'company-logos/acme.png']);

    $this->blade('<x-company-logo :company="$company" />', ['company' => $company])
        ->assertSee('storage/company-logos/acme.png', false)
        ->assertDontSee('bg-accent/10 font-bold text-accent');
});
