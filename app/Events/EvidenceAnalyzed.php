<?php

namespace App\Events;

use App\Models\Evidence;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class EvidenceAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public Evidence $evidence) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('feed'),
            new Channel('user.'.$this->evidence->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'evidence-analyzed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->evidence->id,
            'user_id' => $this->evidence->user_id,
            'title' => $this->evidence->title,
            'type' => $this->evidence->type->value,
            'complexity' => $this->evidence->analysis?->complexity,
            'score' => $this->evidence->ai_score,
            'analyzed_at' => $this->evidence->analyzed_at?->toISOString(),
        ];
    }
}
