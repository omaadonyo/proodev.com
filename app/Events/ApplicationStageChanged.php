<?php

namespace App\Events;

use App\Models\ApplicationEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the candidate over the existing Reverb connection whenever a
 * candidate-visible hiring stage changes.
 */
class ApplicationStageChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public int $candidateId,
        public ApplicationEvent $event,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->candidateId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'application-stage-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'application_id' => $this->event->application_id,
            'stage' => $this->event->stage,
            'created_at' => $this->event->created_at?->toIso8601String(),
        ];
    }
}