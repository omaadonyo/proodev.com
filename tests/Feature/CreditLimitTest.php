<?php

use App\Actions\Evidence\AddEvidenceAction;
use App\Enums\CreditTransactionType;
use App\Models\Evidence;
use App\Models\User;
use App\Services\CreditService;
use App\Services\InsufficientCreditsException;
use App\Services\SubmissionLimitService;
use Illuminate\Support\Facades\Queue;

test('a user gets three free evidence submissions per day', function () {
    Queue::fake();
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        $evidence = app(AddEvidenceAction::class)->handle($user, "https://github.com/example/repo-{$i}");
        expect($evidence)->toBeInstanceOf(Evidence::class);
    }

    $user->refresh();
    expect($user->daily_evidence_count)->toBe(3);
    expect(app(SubmissionLimitService::class)->remainingFree($user))->toBe(0);
    expect(app(SubmissionLimitService::class)->canSubmit($user))->toBeFalse();
});

test('the free allowance resets the following day', function () {
    $user = User::factory()->create(['daily_evidence_count' => 3, 'daily_evidence_date' => now()->subDay()->toDateString()]);

    $limit = app(SubmissionLimitService::class);

    expect($limit->remainingFree($user))->toBe(3);
    expect($limit->canSubmit($user))->toBeTrue();
});

test('submitting beyond the free limit spends a credit', function () {
    Queue::fake();
    $user = User::factory()->create();
    app(CreditService::class)->grant($user, 5, CreditTransactionType::Grant);

    foreach (range(1, 4) as $i) {
        app(AddEvidenceAction::class)->handle($user, "https://github.com/example/repo-{$i}");
    }

    $user->refresh();

    expect($user->credit_balance)->toBe(4);
    expect($user->creditTransactions()->where('type', CreditTransactionType::Submission)->count())->toBe(1);
});

test('submitting beyond the free limit without credits throws', function () {
    Queue::fake();
    $user = User::factory()->create(); // zero credits

    foreach (range(1, 3) as $i) {
        app(AddEvidenceAction::class)->handle($user, "https://github.com/example/repo-{$i}");
    }

    app(AddEvidenceAction::class)->handle($user, 'https://github.com/example/repo-4');
})->throws(InsufficientCreditsException::class);

test('re-adding an existing url does not consume an additional submission', function () {
    Queue::fake();
    $user = User::factory()->create();

    app(AddEvidenceAction::class)->handle($user, 'https://github.com/laravel/framework');
    app(AddEvidenceAction::class)->handle($user, 'https://github.com/laravel/framework');

    $user->refresh();
    expect($user->daily_evidence_count)->toBe(1);
});
