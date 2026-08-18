<?php

namespace App\Actions\Verification;

use App\Events\VerificationApproved;
use App\Models\VerificationRequest;
use Illuminate\Support\Carbon;

class ApproveVerificationAction
{
    public function handle(VerificationRequest $request, bool $approve = true, ?Carbon $expiresAt = null): VerificationRequest
    {
        $request->update([
            'status' => $approve ? 'approved' : 'rejected',
            'reviewed_at' => now(),
            'expires_at' => $approve ? ($expiresAt ?? now()->addYears(2)) : null,
        ]);

        if ($approve) {
            VerificationApproved::dispatch($request);
        }

        return $request;
    }
}
