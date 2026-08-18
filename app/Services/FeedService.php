<?php

namespace App\Services;

use App\Enums\TimelineEventType;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedService
{
    /**
     * @return LengthAwarePaginator<int, TimelineEvent>
     */
    public function feed(User $viewer, ?TimelineEventType $type = null, int $perPage = 20): LengthAwarePaginator
    {
        return TimelineEvent::query()
            ->with([
                'user' => fn ($query) => $query->with([
                    'skills' => fn ($query) => $query->orderByPivot('level', 'desc')->take(3),
                ]),
                'target',
            ])
            ->visibleTo($viewer)
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->latestVisiblePerUser($viewer, $type)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $nonAdminEvents = fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_admin', false));

        return [
            'projects' => TimelineEvent::public()->where('type', TimelineEventType::ProjectPublished)->where($nonAdminEvents)->count(),
            'vouches' => TimelineEvent::public()->where('type', TimelineEventType::VouchReceived)->where($nonAdminEvents)->count(),
            'badges' => TimelineEvent::public()->where('type', TimelineEventType::BadgeEarned)->where($nonAdminEvents)->count(),
        ];
    }
}
