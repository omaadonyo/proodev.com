<?php

use App\Jobs\SendChatReplyReminderJob;
use App\Mail\ChatReminderMail;
use App\Models\ChatReminderMute;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;

function chatParticipants(): array
{
    $sender = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $recipient = User::factory()->create();

    $conversation = $sender->createConversationWith($recipient);

    $senderParticipant = $conversation->participants()->where('participantable_id', $sender->id)->firstOrFail();
    $recipientParticipant = $conversation->participants()->where('participantable_id', $recipient->id)->firstOrFail();

    return [$sender, $recipient, $conversation, $senderParticipant, $recipientParticipant];
}

function sendMessage(Conversation $conversation, $participant, string $body = 'Hey, are you around?'): Message
{
    return Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $participant->id,
        'body' => $body,
        'type' => MessageType::TEXT,
    ]);
}

test('a private chat message schedules a delayed reply reminder for the recipient', function () {
    Queue::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    $message = sendMessage($conversation, $senderParticipant);

    event(new MessageCreated($message));

    Queue::assertPushed(SendChatReplyReminderJob::class, function ($job) use ($conversation, $sender, $recipient, $senderParticipant, $message) {
        return $job->conversationId === $conversation->id
            && $job->senderId === $sender->id
            && $job->recipientId === $recipient->id
            && $job->senderParticipantId === $senderParticipant->id
            && $job->triggerMessageId === $message->id;
    });
});

test('the reminder email is sent when the recipient has not replied after 5 minutes', function () {
    Mail::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    $message = sendMessage($conversation, $senderParticipant);

    (new SendChatReplyReminderJob(
        conversationId: $conversation->id,
        senderId: $sender->id,
        recipientId: $recipient->id,
        senderParticipantId: $senderParticipant->id,
        triggerMessageId: $message->id,
    ))->handle();

    Mail::assertQueued(ChatReminderMail::class, function (ChatReminderMail $mail) use ($recipient, $sender, $conversation) {
        return $mail->hasTo($recipient->email)
            && $mail->sender->is($sender)
            && $mail->conversation->is($conversation);
    });
});

test('the reminder email is skipped once the recipient has replied', function () {
    Mail::fake();

    [$sender, $recipient, $conversation, $senderParticipant, $recipientParticipant] = chatParticipants();
    $message = sendMessage($conversation, $senderParticipant);
    sendMessage($conversation, $recipientParticipant, 'Hey! Just saw this.');

    (new SendChatReplyReminderJob(
        conversationId: $conversation->id,
        senderId: $sender->id,
        recipientId: $recipient->id,
        senderParticipantId: $senderParticipant->id,
        triggerMessageId: $message->id,
    ))->handle();

    Mail::assertNothingQueued();
});

test('the reminder email is skipped when the recipient muted the conversation', function () {
    Mail::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    ChatReminderMute::create(['user_id' => $recipient->id, 'conversation_id' => $conversation->id]);
    $message = sendMessage($conversation, $senderParticipant);

    (new SendChatReplyReminderJob(
        conversationId: $conversation->id,
        senderId: $sender->id,
        recipientId: $recipient->id,
        senderParticipantId: $senderParticipant->id,
        triggerMessageId: $message->id,
    ))->handle();

    Mail::assertNothingQueued();
});

test('the reminder email is skipped when the recipient opted out of chat emails', function () {
    Mail::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    $recipient->forceFill(['preferences' => array_merge($recipient->preferences ?? [], ['email_new_chats' => false])])->save();
    $message = sendMessage($conversation, $senderParticipant);

    (new SendChatReplyReminderJob(
        conversationId: $conversation->id,
        senderId: $sender->id,
        recipientId: $recipient->id,
        senderParticipantId: $senderParticipant->id,
        triggerMessageId: $message->id,
    ))->handle();

    Mail::assertNothingQueued();
});

test('a new chat message creates an in-app notification for the recipient', function () {
    Notification::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    $message = sendMessage($conversation, $senderParticipant, 'Hi! Checking in about the role.');

    event(new MessageCreated($message));

    Notification::assertSentTo(
        $recipient,
        NewMessageNotification::class,
        fn (NewMessageNotification $notification) => $notification->sender->is($sender)
            && $notification->conversation->is($conversation)
            && str_contains($notification->preview, 'Checking in'),
    );

    Notification::assertNotSentTo($sender, NewMessageNotification::class);
});

test('the mute toggle starts on and can be switched off', function () {
    $recipient = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $peer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $conversation = $peer->createConversationWith($recipient);
    ChatReminderMute::create(['user_id' => $recipient->id, 'conversation_id' => $conversation->id]);

    Livewire::actingAs($recipient)
        ->test('chat-reminder-mute', ['conversationId' => $conversation->id])
        ->assertSet('muted', true)
        ->call('toggle')
        ->assertSet('muted', false);

    expect(ChatReminderMute::count())->toBe(0);
});

test('the mute toggle can be switched on', function () {
    $recipient = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $peer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $conversation = $peer->createConversationWith($recipient);

    Livewire::actingAs($recipient)
        ->test('chat-reminder-mute', ['conversationId' => $conversation->id])
        ->assertSet('muted', false)
        ->call('toggle')
        ->assertSet('muted', true);

    expect(ChatReminderMute::where('user_id', $recipient->id)->where('conversation_id', $conversation->id)->exists())->toBeTrue();
});

test('the chat page shows the reminder mute toggle', function () {
    $recipient = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $peer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $conversation = $peer->createConversationWith($recipient);

    $this->actingAs($recipient)
        ->get(route('wirechat.chats.chat', $conversation))
        ->assertOk()
        ->assertSeeLivewire('chat-reminder-mute')
        ->assertSee('Reminders on');
});

test('the reminder email is skipped when a newer message supersedes the trigger', function () {
    Mail::fake();

    [$sender, $recipient, $conversation, $senderParticipant] = chatParticipants();
    $message = sendMessage($conversation, $senderParticipant);
    sendMessage($conversation, $senderParticipant, 'Also, bumping this.');

    (new SendChatReplyReminderJob(
        conversationId: $conversation->id,
        senderId: $sender->id,
        recipientId: $recipient->id,
        senderParticipantId: $senderParticipant->id,
        triggerMessageId: $message->id,
    ))->handle();

    Mail::assertNothingQueued();
});
