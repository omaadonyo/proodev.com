<?php

namespace App\Http\Middleware;

use App\Services\TwoHourStreakService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActivity
{
    /**
     * Stamp the authenticated user's `last_activity_at` at most once per
     * minute so the presence feature has fresh data without hammering the DB.
     * Also auto-earns 2-hour streak for chat gating.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->last_activity_at === null || $user->last_activity_at->isBefore(now()->subMinute())) {
                $user->forceFill(['last_activity_at' => now()])->save();
            }

            // Auto-earn 2-hour streak after 2 hours on site (0 → 1)
            if (! $user->isVerified() && $user->two_hour_streak_count === 0) {
                app(\App\Services\TwoHourStreakService::class)->tryAward($user->fresh());
            }
        }

        return $next($request);
    }
}
