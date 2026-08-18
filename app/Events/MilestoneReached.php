<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MilestoneReached
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public string $type,
        public int $value,
    ) {}
}
