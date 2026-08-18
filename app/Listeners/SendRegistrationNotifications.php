<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;

/**
 * Sends the welcome email + admin registration alert whenever a new user
 * registers (covers both Fortify and Socialite registration paths).
 */
class SendRegistrationNotifications
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(Registered $event): void
    {
        $this->notifications->newRegistration($event->user);
    }
}
