<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

/**
 * Intercepts every outgoing email and adds the platform admin as a BCC
 * recipient so the team retains a parallel audit trail of invoices,
 * receipts, notifications and other transactional messages.
 *
 * The handler is wrapped in a try-catch so a mail issue never breaks
 * the HTTP request (e.g. a Livewire update that doesn't need email).
 */
class AddAdminBccToOutgoingEmails
{
    public function handle(MessageSending $event): void
    {
        try {
            $adminEmail = config('mail.admin_bcc');

            if (! $adminEmail || $adminEmail === '') {
                return;
            }

            $message = $event->message;

            $existingTos = array_map(fn (Address $a) => $a->getAddress(), $message->getTo() ?? []);
            $existingBccs = array_map(fn (Address $a) => $a->getAddress(), $message->getBcc() ?? []);

            if (in_array($adminEmail, $existingTos, true) || in_array($adminEmail, $existingBccs, true)) {
                return;
            }

            $message->bcc($adminEmail);
        } catch (\Throwable) {
            // Silently ignore — never let a mail issue break the HTTP response.
        }
    }
}
