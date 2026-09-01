<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Wirechat\Wirechat\Models\Conversation;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $sender,
        public Conversation $conversation,
        public string $preview,
    ) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable->wantsNotification('chats')) {
            return [];
        }

        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new-message',
            'title' => "New message from {$this->sender->name}",
            'body' => Str::limit($this->preview, 120),
            'icon' => 'chat-bubble-oval-left-ellipsis',
            'url' => route('wirechat.chats.chat', $this->conversation),
        ];
    }
}
