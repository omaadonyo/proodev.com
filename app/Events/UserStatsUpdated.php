<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UserStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public User $user) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->user->id)];
    }

    public function broadcastAs(): string
    {
        return 'user-stats-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'experience_points' => $this->user->experience_points,
            'level' => $this->user->level(),
            'level_title' => $this->user->levelTitle(),
            'streak_count' => $this->user->streak_count,
            'reputation_score' => $this->user->reputation_score,
        ];
    }
}
