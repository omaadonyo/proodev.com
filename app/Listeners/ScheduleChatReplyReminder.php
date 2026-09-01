<?php

namespace App\Listeners;

use App\Jobs\SendChatReplyReminderJob;
use App\Notifications\NewMessageNotification;
use Wirechat\Wirechat\Events\MessageCreated;

/**
 * When a private chat message is sent, notify the recipient in-app and
 * schedule a reminder email in case they don't reply within a few minutes.
 */
class ScheduleChatReplyReminder
{
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;

        if (! $conversation || ! $conversation->isPrivate() || $conversation->isSelf()) {
            return;
        }

        $sender = $message->user;

        if (! $sender) {
            return;
        }

        $recipient = $conversation->participants()
            ->with('participantable')
            ->get()
            ->map(fn ($participant) => $participant->participantable)
            ->first(fn ($user) => $user !== null && (int) $user->getKey() !== (int) $sender->getKey());

        if (! $recipient?->email) {
            return;
        }

        $recipient->notify(new NewMessageNotification(
            sender: $sender,
            conversation: $conversation,
            preview: (string) $message->body,
        ));

        SendChatReplyReminderJob::dispatch(
            conversationId: $conversation->getKey(),
            senderId: $sender->getKey(),
            recipientId: $recipient->getKey(),
            senderParticipantId: $message->participant_id,
            triggerMessageId: $message->getKey(),
        )->delay(now()->addMinutes(5));
    }
}
