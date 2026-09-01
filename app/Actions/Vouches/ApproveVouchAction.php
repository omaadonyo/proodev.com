<?php

namespace App\Actions\Vouches;

use App\Enums\VouchStatus;
use App\Events\VouchApproved;
use App\Models\Vouch;

class ApproveVouchAction
{
    public function handle(Vouch $vouch, bool $approve = true): Vouch
    {
        if ($approve) {
            $vouch->update(['status' => VouchStatus::Approved]);

            VouchApproved::dispatch($vouch);
        } else {
            $vouch->update(['status' => VouchStatus::Rejected]);

            $vouch->voucher()->increment('vouch_credits');
        }

        return $vouch;
    }
}
