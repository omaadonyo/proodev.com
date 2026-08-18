<?php

namespace App\Events;

use App\Models\Vouch;
use Illuminate\Foundation\Events\Dispatchable;

class VouchApproved
{
    use Dispatchable;

    public function __construct(public Vouch $vouch) {}
}
