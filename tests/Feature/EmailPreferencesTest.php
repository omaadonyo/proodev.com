<?php

use App\Enums\CompanyStatus;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Enums\UserRole;
use App\Events\EvidenceAdded;
use App\Events\EvidenceAnalyzed;
use App\Mail\EvidenceActivityMail;
use App\Mail\NewJobPostedMail;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\Job;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Notifications\MentionNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\WeeklyReportNotification;
use App\Services\Recruiter\JobMatchService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Models\Message;

test('the profile settings page shows email preference toggles', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->assertSee('Email preferences')
        ->assertSee('New job offers')
        ->assertSee('New chats')
        ->assertSee('Scans & evidence')
        ->assertSee('Transactions & payments');
});

test('email preferences can be saved from the profile settings page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->assertSet('email_job_offers', true)
        ->set('email_job_offers', false)
        ->set('email_new_chats', false)
        ->set('email_transactions', false)
        ->call('updateEmailPreferences')
        ->assertHasNoErrors();

    expect($user->refresh()->preferences['email_job_offers'])->toBeFalse()
        ->and($user->preferences['email_new_chats'])->toBeFalse()
        ->and($user->preferences['email_scans_evidence'])->toBeTrue()
        ->and($user->preferences['email_transactions'])->toBeFalse();
});

test('wantsEmail defaults to true for every category', function () {
    $user = User::factory()->create();

    expect($user->wantsEmail('job_offers'))->toBeTrue()
        ->and($user->wantsEmail('new_chats'))->toBeTrue()
        ->and($user->wantsEmail('scans_evidence'))->toBeTrue()
        ->and($user->wantsEmail('transactions'))->toBeTrue();
});

test('publishing a job emails opted-in developers whose skills match, not opted-out ones', function () {
    Mail::fake();

    $laravel = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid(), 'category' => 'backend']);

    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $optedIn = User::factory()->create(['role' => UserRole::Developer]);
    $optedIn->skills()->attach($laravel, ['level' => 8]);

    $optedOut = User::factory()->create(['role' => UserRole::Developer]);
    $optedOut->skills()->attach($laravel, ['level' => 8]);
    $optedOut->forceFill(['preferences' => array_merge($optedOut->preferences ?? [], ['email_job_offers' => false])])->save();

    Livewire::actingAs($owner)
        ->test('pages::companies.jobs.create', ['company' => $company])
        ->set('title', 'Senior Laravel Engineer')
        ->set('description', 'Build payment rails with Laravel for a fast-growing fintech.')
        ->call('create');

    Mail::assertQueued(NewJobPostedMail::class, fn (NewJobPostedMail $mail) => $mail->hasTo($optedIn->email));
    Mail::assertNotQueued(NewJobPostedMail::class, fn (NewJobPostedMail $mail) => $mail->hasTo($optedOut->email));
});

test('publishing a job does not email developers whose skills do not match', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $reactDev = User::factory()->create(['role' => UserRole::Developer]);
    $react = Skill::create(['name' => 'React', 'slug' => 'react-'.uniqid(), 'category' => 'frontend']);
    $reactDev->skills()->attach($react, ['level' => 9]);

    Livewire::actingAs($owner)
        ->test('pages::companies.jobs.create', ['company' => $company])
        ->set('title', 'Go Backend Engineer')
        ->set('description', 'Build event-driven systems in Go and Postgres.')
        ->call('create');

    Mail::assertNotQueued(NewJobPostedMail::class, fn (NewJobPostedMail $mail) => $mail->hasTo($reactDev->email));
});

test('AI drafts a job posting for review before publishing', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $brief = "We're hiring a senior Laravel engineer to own our payments platform. They'll build REST APIs with Laravel and Postgres, containerize services with Docker, and ship iteratively with a small team.";

    $component = Livewire::actingAs($owner)
        ->test('pages::companies.jobs.create', ['company' => $company])
        ->assertSee('Draft with AI')
        ->set('jobBrief', $brief)
        ->call('draftWithAi')
        ->assertHasNoErrors();

    expect($component->get('title'))->not->toBeEmpty()
        ->and($component->get('description'))->toContain($brief)
        ->and($component->get('description'))->toContain('About the company: '.$company->name)
        ->and($component->get('requirements'))->toContain('Strong experience with Laravel')
        ->and($component->get('is_remote'))->toBeTrue()
        ->and($component->get('employment_type'))->toBe('full-time')
        ->and($component->get('currency'))->toBe('USD');
});

test('AI drafts a refreshed posting on the job edit page for review', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $job = Job::factory()->create(['company_id' => $company->id, 'title' => 'Old Role Title']);

    $brief = 'The role now also owns our mobile checkout — add React Native, Stripe and on-call expectations. Keep it senior and remote-friendly with Laravel.';

    $component = Livewire::actingAs($owner)
        ->test('pages::companies.jobs.edit', ['company' => $company, 'job' => $job])
        ->assertSee('Refresh with AI')
        ->assertSet('title', 'Old Role Title')
        ->set('jobBrief', $brief)
        ->call('draftWithAi')
        ->assertHasNoErrors();

    expect($component->get('title'))->not->toBeEmpty()
        ->and($component->get('description'))->toContain($brief)
        ->and($component->get('description'))->toContain('About the company: '.$company->name)
        ->and($component->get('requirements'))->toContain('Strong experience with Laravel');
});

test('the AI draft requires a brief before generating', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    Livewire::actingAs($owner)
        ->test('pages::companies.jobs.create', ['company' => $company])
        ->set('jobBrief', '')
        ->call('draftWithAi')
        ->assertHasErrors(['jobBrief']);
});

test('matching developers for a job reuses the keyword extraction and ranks verified first', function () {
    $laravel = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid(), 'category' => 'backend']);

    $verified = User::factory()->create(['role' => UserRole::Developer, 'is_verified' => true, 'verified_at' => now()]);
    $verified->skills()->attach($laravel, ['level' => 8]);

    $plain = User::factory()->create(['role' => UserRole::Developer]);
    $plain->skills()->attach($laravel, ['level' => 8]);

    $recruiter = User::factory()->create(['role' => UserRole::Recruiter]);
    $recruiter->skills()->attach($laravel, ['level' => 8]);

    $job = Job::factory()->create([
        'title' => 'Senior Laravel Engineer',
        'description' => 'Ship features with Laravel every day.',
    ]);

    $matches = app(JobMatchService::class)->matchingDevelopersFor($job);

    expect($matches->pluck('id'))->toContain($verified->id, $plain->id)
        ->not->toContain($recruiter->id)
        ->and($matches->first()->id)->toBe($verified->id);
});

test('evidence activity emails respect the scans & evidence preference', function () {
    Mail::fake();

    $optedIn = User::factory()->create();
    $optedOut = User::factory()->create();
    $optedOut->forceFill(['preferences' => array_merge($optedOut->preferences ?? [], ['email_scans_evidence' => false])])->save();

    $added = Evidence::create([
        'user_id' => $optedIn->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'proodev/core',
        'url' => 'https://github.com/proodev/core',
        'status' => EvidenceStatus::Pending,
    ]);

    $addedOut = Evidence::create([
        'user_id' => $optedOut->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'proodev/out',
        'url' => 'https://github.com/proodev/out',
        'status' => EvidenceStatus::Pending,
    ]);

    event(new EvidenceAdded($added));
    event(new EvidenceAdded($addedOut));

    Mail::assertQueued(EvidenceActivityMail::class, fn (EvidenceActivityMail $mail) => $mail->hasTo($optedIn->email));
    Mail::assertNotQueued(EvidenceActivityMail::class, fn (EvidenceActivityMail $mail) => $mail->hasTo($optedOut->email));

    // Analysis-complete email goes to opted-in owners only.
    $analyzed = $added->fresh();
    $analyzed->update(['status' => EvidenceStatus::Ready, 'ai_score' => 92]);

    Mail::fake();

    event(new EvidenceAnalyzed($analyzed));

    Mail::assertQueued(EvidenceActivityMail::class, fn (EvidenceActivityMail $mail) => $mail->hasTo($optedIn->email) && $mail->analyzed);
    Mail::assertNotQueued(EvidenceActivityMail::class, fn (EvidenceActivityMail $mail) => $mail->hasTo($optedOut->email));
});

test('the profile settings page shows in-app notification preference toggles', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->assertSee('Notification preferences')
        ->assertSee('New chat messages')
        ->assertSee('Mentions')
        ->assertSee('Weekly reports');
});

test('notification preferences can be saved from the profile settings page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->assertSet('notify_chats', true)
        ->set('notify_chats', false)
        ->set('notify_mentions', false)
        ->call('updateNotificationPreferences')
        ->assertHasNoErrors();

    expect($user->refresh()->preferences['notify_chats'])->toBeFalse()
        ->and($user->preferences['notify_mentions'])->toBeFalse()
        ->and($user->preferences['notify_weekly_reports'])->toBeTrue();
});

test('wantsNotification defaults to true for every category', function () {
    $user = User::factory()->create();

    expect($user->wantsNotification('chats'))->toBeTrue()
        ->and($user->wantsNotification('mentions'))->toBeTrue()
        ->and($user->wantsNotification('weekly_reports'))->toBeTrue();
});

test('opted-out users receive no in-app chat notification', function () {
    Notification::fake();

    $sender = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $recipient = User::factory()->create(['preferences' => ['notify_chats' => false]]);

    $conversation = $sender->createConversationWith($recipient);
    $senderParticipant = $conversation->participants()->where('participantable_id', $sender->id)->firstOrFail();

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $senderParticipant->id,
        'body' => 'Hello there',
    ]);

    event(new MessageCreated($message));

    Notification::assertNotSentTo($recipient, NewMessageNotification::class);
});

test('opted-out users receive no mention or weekly report notification', function () {
    Notification::fake();

    $optedOut = User::factory()->create(['preferences' => ['notify_mentions' => false, 'notify_weekly_reports' => false]]);
    $optedIn = User::factory()->create();

    $author = User::factory()->create();
    $comment = Comment::create([
        'commentable_type' => Project::class,
        'commentable_id' => 1,
        'user_id' => $author->id,
        'body' => 'Great work!',
    ]);
    $report = WeeklyReport::create([
        'user_id' => $optedOut->id,
        'week_started' => now()->startOfWeek(),
        'data' => ['projects_published' => 2, 'activity_count' => 9, 'xp_gained' => 120, 'growth_percentage' => 14],
        'generated_at' => now(),
    ]);

    $optedOut->notify(new MentionNotification($comment));
    $optedOut->notify(new WeeklyReportNotification($report));

    $optedIn->notify(new MentionNotification($comment));
    $optedIn->notify(new WeeklyReportNotification($report));

    Notification::assertNotSentTo($optedOut, MentionNotification::class);
    Notification::assertNotSentTo($optedOut, WeeklyReportNotification::class);
    Notification::assertSentTo($optedIn, MentionNotification::class);
    Notification::assertSentTo($optedIn, WeeklyReportNotification::class);
});

test('the unread messages badge shows the unread count', function () {
    $user = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $peer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $conversation = $peer->createConversationWith($user);
    $peerParticipant = $conversation->participants()->where('participantable_id', $peer->id)->firstOrFail();

    Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $peerParticipant->id,
        'body' => 'Hello there',
    ]);

    expect($user->unreadMessageCount())->toBe(1);

    Livewire::actingAs($user)
        ->test('unread-messages-badge')
        ->assertSee('1');
});

test('the unread messages badge subscribes to the wirechat participant channel', function () {
    $user = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $component = Livewire::actingAs($user)->test('unread-messages-badge');

    $expected = 'echo-private:chats.participant.'.bin2hex($user->getMorphClass()).'.'.$user->id.',.Wirechat\Wirechat\Events\NotifyParticipant';

    expect(array_keys($component->instance()->getListeners()))->toContain($expected);
});
