<?php

namespace App\Events;

use App\Models\Vouch;
use Illuminate\Foundation\Events\Dispatchable;

class VouchCreated
{
    use Dispatchable;

    public function __construct(public Vouch $vouch) {}
}
