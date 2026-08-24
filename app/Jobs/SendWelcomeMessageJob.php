<?php

namespace App\Jobs;

use App\Mail\MessagesWaitingMail;
use App\Models\User;
use App\Services\WelcomeMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers the admin welcome messages ~8 minutes after a user registers,
 * then emails them that messages are waiting — without revealing who sent
 * them or what they say.
 */
class SendWelcomeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(WelcomeMessageService $welcome): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $sent = $welcome->sendTo($user);

        if ($sent) {
            Mail::to($user->email)->send(new MessagesWaitingMail($user));
        }
    }
}
