<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotSuspended
{
    /**
     * Block suspended (non-admin) accounts from using the platform.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && $user->suspended_at !== null) {
            auth()->logout();

            return abort(403, 'Your account has been suspended.');
        }

        return $next($request);
    }
}
