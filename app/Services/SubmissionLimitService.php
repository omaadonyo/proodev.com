<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Models\User;
use App\Support\FeatureFlags;

class SubmissionLimitService
{
    /**
     * How many free evidence submissions remain today for the user.
     */
    public function remainingFree(User $user): int
    {
        $free = (int) config('billing.developer.daily_free_submissions', 3);

        if ($user->daily_evidence_date !== now()->toDateString()) {
            return $free;
        }

        return max(0, $free - (int) $user->daily_evidence_count);
    }

    /**
     * Whether the user can submit another link right now (free slot or credits).
     */
    public function canSubmit(User $user): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($this->remainingFree($user) > 0) {
            return true;
        }

        return app(CreditService::class)->has($user, (int) config('billing.developer.submission_credit_cost', 1));
    }

    /**
     * Record a submission. Free daily slots are consumed first; anything beyond
     * the free allowance is paid for with credits.
     */
    public function recordSubmission(User $user): void
    {
        if (! $this->enabled()) {
            return;
        }

        $free = (int) config('billing.developer.daily_free_submissions', 3);

        if ($user->daily_evidence_date !== now()->toDateString()) {
            $user->forceFill([
                'daily_evidence_date' => now()->toDateString(),
                'daily_evidence_count' => 0,
            ])->save();
        }

        if ((int) $user->daily_evidence_count >= $free) {
            app(CreditService::class)->spend(
                $user,
                (int) config('billing.developer.submission_credit_cost', 1),
                CreditTransactionType::Submission,
                'Extra evidence submission beyond the free daily allowance.',
            );
        } else {
            $user->increment('daily_evidence_count');
        }
    }

    public function enabled(): bool
    {
        return FeatureFlags::active('credits');
    }
}
