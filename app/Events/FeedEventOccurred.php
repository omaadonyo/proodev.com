<?php

namespace App\Events;

use App\Models\TimelineEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class FeedEventOccurred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public TimelineEvent $timelineEvent) {}

    public function broadcastOn(): array
    {
        return [new Channel('feed')];
    }

    public function broadcastAs(): string
    {
        return 'feed-event';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->timelineEvent->id,
            'type' => $this->timelineEvent->type->value,
            'user_id' => $this->timelineEvent->user_id,
            'title' => $this->timelineEvent->title,
            'occurred_at' => $this->timelineEvent->occurred_at->toISOString(),
        ];
    }
}
