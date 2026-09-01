<?php

namespace App\Services;

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Events\FeedEventOccurred;
use App\Models\TimelineEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class TimelineService
{
    public function record(
        User $user,
        TimelineEventType $type,
        string $title,
        ?string $description = null,
        array $data = [],
        ?Model $target = null,
        Visibility $visibility = Visibility::Public,
        ?CarbonInterface $occurredAt = null,
    ): TimelineEvent {
        $event = TimelineEvent::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'visibility' => $visibility,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        if ($visibility === Visibility::Public) {
            broadcast(new FeedEventOccurred($event));
        }

        return $event;
    }
}
