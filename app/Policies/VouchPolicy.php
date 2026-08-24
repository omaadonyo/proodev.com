<?php

namespace App\Policies;

use App\Enums\VouchStatus;
use App\Models\User;
use App\Models\Vouch;

class VouchPolicy
{
    public function create(User $user): bool
    {
        return $user->isVerified() && $user->vouch_credits > 0;
    }

    public function approve(User $user, ?Vouch $vouch = null): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Vouch $vouch): bool
    {
        return $user->isAdmin()
            || $vouch->status === VouchStatus::Approved
            || $vouch->voucher_id === $user->id
            || $vouch->vouchee_id === $user->id;
    }
}
