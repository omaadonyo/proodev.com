<?php

namespace App\Services;

use App\Models\PassportView;
use App\Models\User;
use Illuminate\Support\Collection;

class DevIDViewService
{
    /**
     * Record a passport view, deduped so the same viewer (or guest IP) only
     * counts once per day.
     */
    public function record(User $owner, ?User $viewer, ?string $ip = null): bool
    {
        if ($viewer && $viewer->id === $owner->id) {
            return false;
        }

        if (! $owner->public_passport) {
            return false;
        }

        $duplicate = PassportView::where('passport_owner_id', $owner->id)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->where(function ($query) use ($viewer, $ip) {
                if ($viewer) {
                    $query->where('viewer_id', $viewer->id);
                } else {
                    $query->whereNull('viewer_id')->where('ip_address', $ip);
                }
            })
            ->exists();

        if ($duplicate) {
            return false;
        }

        PassportView::create([
            'passport_owner_id' => $owner->id,
            'viewer_id' => $viewer?->id,
            'ip_address' => $viewer ? null : $ip,
            'viewed_at' => now(),
        ]);

        return true;
    }

    /**
     * Total view count for a user's passport.
     */
    public function count(User $owner): int
    {
        return PassportView::where('passport_owner_id', $owner->id)->count();
    }

    /**
     * Recent distinct viewers (auth'd users only) for a user's passport.
     *
     * @return Collection<int, PassportView>
     */
    public function recentViewers(User $owner, int $limit = 12)
    {
        $viewerIds = PassportView::where('passport_owner_id', $owner->id)
            ->whereNotNull('viewer_id')
            ->select('viewer_id')
            ->groupBy('viewer_id')
            ->orderByRaw('MAX(viewed_at) DESC')
            ->limit($limit)
            ->pluck('viewer_id');

        $latestByViewer = PassportView::where('passport_owner_id', $owner->id)
            ->whereIn('viewer_id', $viewerIds)
            ->select('id', 'viewer_id', 'viewed_at')
            ->orderBy('viewed_at')
            ->get()
            ->keyBy('viewer_id');

        return $viewerIds->map(function (int $viewerId) use ($latestByViewer) {
            return $latestByViewer->get($viewerId);
        })->filter()->values();
    }

    /**
     * Number of anonymous (guest) views in a period.
     */
    public function anonymousCount(User $owner): int
    {
        return PassportView::where('passport_owner_id', $owner->id)
            ->whereNull('viewer_id')
            ->count();
    }
}
