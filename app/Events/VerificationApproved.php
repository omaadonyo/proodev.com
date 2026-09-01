<?php

namespace App\Events;

use App\Models\VerificationRequest;
use Illuminate\Foundation\Events\Dispatchable;

class VerificationApproved
{
    use Dispatchable;

    public function __construct(public VerificationRequest $request) {}
}
