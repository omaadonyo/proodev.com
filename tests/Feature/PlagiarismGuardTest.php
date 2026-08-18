<?php

use App\Actions\Evidence\AddEvidenceAction;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Mail\PlagiarismAlertMail;
use App\Mail\PlagiarismBanMail;
use App\Mail\PlagiarismBanOverturnedMail;
use App\Mail\PlagiarismWarningMail;
use App\Models\Evidence;
use App\Models\PlagiarismStrike;
use App\Models\User;
use App\Notifications\PlagiarismAlertNotification;
use App\Notifications\PlagiarismBanNotification;
use App\Notifications\PlagiarismWarningNotification;
use App\Services\PlagiarismDetectedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function claimedEvidence(User $user, string $repoName, string $repoOwner = 'acme'): Evidence
{
    return Evidence::create([
        'user_id' => $user->id,
        'type' => EvidenceType::GithubRepository,
        'title' => $repoName,
        'url' => "https://github.com/{$repoOwner}/{$repoName}",
        'source' => 'github',
        'status' => EvidenceStatus::Ready,
    ]);
}

test('non-repository URLs are not flagged', function () {
    Http::fake();

    $user = User::factory()->create();

    $evidence = app(AddEvidenceAction::class)->handle($user, 'https://packagist.org/packages/laravel/framework');

    expect($evidence->exists)->toBeTrue()
        ->and(PlagiarismStrike::count())->toBe(0);
});

test('a developer can claim their own GitHub repo', function () {
    Http::fake();

    $user = User::factory()->create(['github_url' => 'https://github.com/johncodes']);

    $evidence = app(AddEvidenceAction::class)->handle($user, 'https://github.com/johncodes/payments-core');

    expect($evidence->exists)->toBeTrue()
        ->and(PlagiarismStrike::count())->toBe(0);
});

test('claiming a repo already claimed by another user triggers a warning to both parties', function () {
    Mail::fake();
    Notification::fake();

    $owner = User::factory()->create();
    $offender = User::factory()->create();

    claimedEvidence($owner, 'rocket');

    expect(fn () => app(AddEvidenceAction::class)->handle($offender, 'https://github.com/acme/rocket'))
        ->toThrow(PlagiarismDetectedException::class);

    $strike = PlagiarismStrike::sole();

    expect($strike->offender_id)->toBe($offender->id)
        ->and($strike->owner_id)->toBe($owner->id)
        ->and($strike->strike_number)->toBe(1)
        ->and($strike->action)->toBe('warning')
        ->and($strike->repo_url)->toBe('https://github.com/acme/rocket')
        ->and($offender->evidence()->count())->toBe(0);

    Notification::assertSentTo($offender, PlagiarismWarningNotification::class);
    Notification::assertSentTo($owner, PlagiarismAlertNotification::class);
    Mail::assertQueued(PlagiarismWarningMail::class);
    Mail::assertQueued(PlagiarismAlertMail::class);
});

test('the passport form catches the guard and does not create the evidence', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $offender = User::factory()->create(['public_passport' => true]);

    claimedEvidence($owner, 'rocket');

    Livewire::actingAs($offender)
        ->test('pages::passport', ['user' => $offender])
        ->set('url', 'https://github.com/acme/rocket')
        ->call('addEvidence')
        ->assertOk();

    expect($offender->evidence()->count())->toBe(0)
        ->and(PlagiarismStrike::count())->toBe(1);
});

test('a second offense bans the user and suspends the account', function () {
    Mail::fake();
    Notification::fake();

    $owner = User::factory()->create();
    $offender = User::factory()->create();

    PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'owner_id' => $owner->id,
        'repo_owner' => 'acme',
        'repo_name' => 'first',
        'repo_url' => 'https://github.com/acme/first',
        'strike_number' => 1,
        'action' => 'warning',
        'reason' => 'First offense',
        'notified_at' => now(),
    ]);

    claimedEvidence($owner, 'second');

    expect(fn () => app(AddEvidenceAction::class)->handle($offender, 'https://github.com/acme/second'))
        ->toThrow(PlagiarismDetectedException::class);

    $strike = PlagiarismStrike::where('repo_name', 'second')->sole();

    expect($strike->strike_number)->toBe(2)
        ->and($strike->action)->toBe('banned')
        ->and($offender->fresh()->isSuspended())->toBeTrue();

    Notification::assertSentTo($offender, PlagiarismBanNotification::class);
    Mail::assertQueued(PlagiarismBanMail::class);
});

test('a repo owned by a different GitHub account is flagged when GitHub confirms no association', function () {
    Mail::fake();
    Notification::fake();

    Http::fake([
        'api.github.com/repos/other/repo' => Http::response(['owner' => ['login' => 'other']], 200),
        'api.github.com/repos/other/repo/contributors*' => Http::response([['login' => 'someone-else']], 200),
    ]);

    $user = User::factory()->create(['github_url' => 'https://github.com/johncodes']);

    expect(fn () => app(AddEvidenceAction::class)->handle($user, 'https://github.com/other/repo'))
        ->toThrow(PlagiarismDetectedException::class);

    $strike = PlagiarismStrike::sole();

    expect($strike->repo_owner)->toBe('other')
        ->and($strike->repo_name)->toBe('repo')
        ->and($strike->action)->toBe('warning')
        ->and($user->evidence()->count())->toBe(0);

    Notification::assertSentTo($user, PlagiarismWarningNotification::class);
});

test('the guard fails open when GitHub is unavailable', function () {
    Http::fake(['api.github.com/*' => Http::response([], 500)]);

    $user = User::factory()->create(['github_url' => 'https://github.com/johncodes']);

    $evidence = app(AddEvidenceAction::class)->handle($user, 'https://github.com/other/repo');

    expect($evidence->exists)->toBeTrue()
        ->and(PlagiarismStrike::count())->toBe(0);
});

test('the guard accepts a repo the user contributes to on GitHub', function () {
    Http::fake([
        'api.github.com/repos/acme/tool' => Http::response(['owner' => ['login' => 'acme']], 200),
        'api.github.com/repos/acme/tool/contributors*' => Http::response([['login' => 'johncodes']], 200),
    ]);

    $user = User::factory()->create(['github_url' => 'https://github.com/johncodes']);

    $evidence = app(AddEvidenceAction::class)->handle($user, 'https://github.com/acme/tool');

    expect($evidence->exists)->toBeTrue()
        ->and(PlagiarismStrike::count())->toBe(0);
});

test('a banned user shows a public plagiarism notice on their passport', function () {
    $user = User::factory()->create(['public_passport' => true]);

    PlagiarismStrike::create([
        'offender_id' => $user->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Second offense',
        'notified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->assertOk()
        ->assertSee('This account has been banned for plagiarism')
        ->assertSee('Banned');
});

test('the admin panel lists strikes with offenders and repositories', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create(['name' => 'Copy Kat']);
    $owner = User::factory()->create();

    $strike = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'owner_id' => $owner->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Repeated plagiarism',
        'notified_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->assertOk()
        ->assertSee('Copy Kat')
        ->assertSee('rocket')
        ->assertSee('Ban');

    expect($strike->fresh()->exists)->toBeTrue();
});

test('an admin can overturn a ban and the public notice disappears', function () {
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create(['public_passport' => true]);

    $strike = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Second offense',
        'notified_at' => now(),
    ]);

    $offender->suspend();

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->call('overturnBan', $strike->id)
        ->assertHasNoErrors();

    expect($strike->fresh()->isOverturned())->toBeTrue()
        ->and($strike->fresh()->overturned_by)->toBe($admin->id)
        ->and($offender->fresh()->isSuspended())->toBeFalse();

    Mail::assertQueued(PlagiarismBanOverturnedMail::class, fn (PlagiarismBanOverturnedMail $mail) => $mail->hasTo($offender->email));

    Livewire::actingAs($offender)
        ->test('pages::passport', ['user' => $offender])
        ->assertOk()
        ->assertDontSee('This account has been banned for plagiarism');
});

test('an admin note is saved with an overturn and shown when reviewing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create();

    $strike = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Second offense',
        'notified_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->call('review', $strike->id)
        ->set('reviewNote', 'Legitimate contributor — banned by mistake.')
        ->call('overturnBan', $strike->id)
        ->assertHasNoErrors();

    expect($strike->fresh()->review_note)->toBe('Legitimate contributor — banned by mistake.')
        ->and($strike->fresh()->isOverturned())->toBeTrue();

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->call('review', $strike->id)
        ->assertSet('reviewNote', 'Legitimate contributor — banned by mistake.')
        ->assertSee('Legitimate contributor');
});

test('an admin can save a note without overturning', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create();

    $strike = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 1,
        'action' => 'warning',
        'reason' => 'First offense',
        'notified_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->call('review', $strike->id)
        ->set('reviewNote', 'Monitoring — user linked GitHub and is cooperating.')
        ->call('saveNote', $strike->id)
        ->assertHasNoErrors();

    expect($strike->fresh()->review_note)->toBe('Monitoring — user linked GitHub and is cooperating.')
        ->and($strike->fresh()->isOverturned())->toBeFalse()
        ->and($offender->fresh()->isSuspended())->toBeFalse();
});

test('an admin can reinstate a suspended account from the panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create();

    $strike = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 1,
        'action' => 'warning',
        'reason' => 'First offense',
        'notified_at' => now(),
    ]);

    $offender->suspend();

    Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->call('reinstate', $offender->id)
        ->assertHasNoErrors();

    expect($offender->fresh()->isSuspended())->toBeFalse()
        ->and($strike->fresh()->isOverturned())->toBeFalse();
});

test('the admin panel filters to active bans only', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $offender = User::factory()->create();

    $banned = PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'one',
        'repo_url' => 'https://github.com/acme/one',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Second offense',
        'notified_at' => now(),
    ]);

    PlagiarismStrike::create([
        'offender_id' => $offender->id,
        'repo_owner' => 'acme',
        'repo_name' => 'two',
        'repo_url' => 'https://github.com/acme/two',
        'strike_number' => 2,
        'action' => 'banned',
        'reason' => 'Overturned',
        'overturned_at' => now(),
        'overturned_by' => $admin->id,
        'notified_at' => now(),
    ]);

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.plagiarism')
        ->set('filter', 'bans');

    $rows = $component->get('rows');

    expect($rows->pluck('id'))->toContain($banned->id)
        ->and($rows->count())->toBe(1);
});

test('the passport nudges users to link GitHub when typing a repo URL without one', function () {
    $user = User::factory()->create(['public_passport' => true, 'github_url' => null]);

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->set('url', 'https://github.com/acme/rocket')
        ->assertSee('Link your GitHub URL');
});

test('no GitHub nudge when the user already linked a GitHub account', function () {
    $user = User::factory()->create(['public_passport' => true, 'github_url' => 'https://github.com/johncodes']);

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->set('url', 'https://github.com/johncodes/repo')
        ->assertDontSee('Link your GitHub URL');
});

test('no GitHub nudge for non-repository URLs', function () {
    $user = User::factory()->create(['public_passport' => true, 'github_url' => null]);

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->set('url', 'https://packagist.org/packages/laravel/framework')
        ->assertDontSee('Link your GitHub URL');
});

test('the persistent link hint shows when repo evidence exists without a linked GitHub', function () {
    $user = User::factory()->create(['public_passport' => true, 'github_url' => null]);

    claimedEvidence($user, 'rocket');

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->assertSee('Link your GitHub account');
});

test('the persistent link hint hides once a GitHub account is linked', function () {
    $user = User::factory()->create(['public_passport' => true, 'github_url' => 'https://github.com/johncodes']);

    claimedEvidence($user, 'rocket');

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->assertDontSee('Link your GitHub account');
});

test('a warning-only user shows no ban notice on their passport', function () {
    $user = User::factory()->create(['public_passport' => true]);

    PlagiarismStrike::create([
        'offender_id' => $user->id,
        'repo_owner' => 'acme',
        'repo_name' => 'rocket',
        'repo_url' => 'https://github.com/acme/rocket',
        'strike_number' => 1,
        'action' => 'warning',
        'reason' => 'First offense',
        'notified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::passport', ['user' => $user])
        ->assertOk()
        ->assertDontSee('This account has been banned for plagiarism');
});
