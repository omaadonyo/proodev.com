<?php

namespace App\Jobs;

use App\Mail\ChatReminderMail;
use App\Models\ChatReminderMute;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * Runs ~5 minutes after a chat message is sent and emails the recipient
 * if they still haven't replied to that message.
 */
class SendChatReplyReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $senderId,
        public int $recipientId,
        public int $senderParticipantId,
        public int $triggerMessageId,
    ) {}

    public function handle(): void
    {
        $conversationModel = Wirechat::conversationModelClass();

        $conversation = $conversationModel::find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $latest = $conversation->messages()->latest('id')->first();

        // No messages, the recipient replied, or the trigger is no longer the
        // latest message (a newer reminder is scheduled for it) — skip.
        if (! $latest || $latest->participant_id !== $this->senderParticipantId || $latest->id !== $this->triggerMessageId) {
            return;
        }

        $recipient = User::find($this->recipientId);
        $sender = User::find($this->senderId);

        if (! $recipient?->email || ! $sender) {
            return;
        }

        // The recipient muted reminders for this conversation or opted out
        // of chat emails entirely.
        if (! $recipient->wantsEmail('new_chats')) {
            return;
        }

        if (ChatReminderMute::where('user_id', $recipient->id)
            ->where('conversation_id', $conversation->id)
            ->exists()) {
            return;
        }

        Mail::to($recipient)->send(new ChatReminderMail(
            recipient: $recipient,
            sender: $sender,
            conversation: $conversation,
            preview: (string) $latest->body,
        ));
    }
}
