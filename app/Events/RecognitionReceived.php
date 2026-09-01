<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class RecognitionReceived
{
    use Dispatchable;

    public function __construct(
        public Project $project,
        public User $recognizer,
        public string $type,
    ) {}
}
