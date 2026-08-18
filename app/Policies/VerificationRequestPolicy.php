<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, VerificationRequest $request): bool
    {
        return $request->user_id === $user->id || $user->isAdmin();
    }

    public function review(User $user, ?VerificationRequest $request = null): bool
    {
        return $user->isAdmin();
    }
}
