<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpIsNotBlocked
{
    /**
     * Reject requests coming from an IP address an admin has blocked.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip && BlockedIp::where('ip_address', $ip)->exists()) {
            return abort(403, 'This IP address has been blocked.');
        }

        return $next($request);
    }
}
