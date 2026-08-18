<?php

namespace App\Actions\Vouches;

use App\Data\VouchData;
use App\Events\VouchCreated;
use App\Models\User;
use App\Models\Vouch;

class CreateVouchAction
{
    public function handle(User $voucher, VouchData $data): Vouch
    {
        if ($voucher->vouch_credits < 1) {
            throw new \DomainException('You do not have enough vouch credits.');
        }

        if ($voucher->id === $data->voucheeId) {
            throw new \DomainException('You cannot vouch for yourself.');
        }

        $vouch = Vouch::create([
            'voucher_id' => $voucher->id,
            'vouchee_id' => $data->voucheeId,
            'type' => $data->type,
            'skill_id' => $data->skillId,
            'message' => $data->message,
            'status' => 'pending',
            'weight' => 1,
        ]);

        $voucher->decrement('vouch_credits');

        VouchCreated::dispatch($vouch);

        return $vouch;
    }
}
