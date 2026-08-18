<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActivity
{
    /**
     * Stamp the authenticated user's `last_activity_at` at most once per
     * minute so the presence feature has fresh data without hammering the DB.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->last_activity_at === null || $user->last_activity_at->isBefore(now()->subMinute()))) {
            $user->forceFill(['last_activity_at' => now()])->save();
        }

        return $next($request);
    }
}
