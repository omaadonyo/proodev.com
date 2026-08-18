<?php

namespace App\Listeners;

use App\Events\VouchCreated;
use App\Notifications\VouchReceivedNotification;
use App\Services\NotificationService;

class NotifyVouchRecipient
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(VouchCreated $event): void
    {
        $vouch = $event->vouch->load(['voucher', 'vouchee']);

        $vouch->vouchee->notify(new VouchReceivedNotification($vouch));

        $this->notifications->vouchReceived($vouch);
    }
}
