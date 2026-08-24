<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WelcomeMessageService;
use Illuminate\Console\Command;

class SendWelcomeMessagesCommand extends Command
{
    protected $signature = 'os:send-welcome-messages';

    protected $description = 'Send the default admin welcome message to every user who has not received it yet';

    public function handle(WelcomeMessageService $welcome): int
    {
        $count = 0;
        $total = User::where('email', '!=', config('platform.admin_email'))->count();

        User::query()
            ->where('email', '!=', config('platform.admin_email'))
            ->chunkById(100, function ($users) use ($welcome, &$count) {
                foreach ($users as $user) {
                    if ($welcome->sendTo($user)) {
                        $count++;
                    }
                }
            });

        $this->info("Welcome message sent to {$count} of {$total} user(s).");

        return self::SUCCESS;
    }
}
