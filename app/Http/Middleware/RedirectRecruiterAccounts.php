<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * /credits and /verify are developer monetization pages. Recruiters and
 * companies are redirected to their subscription management instead.
 */
class RedirectRecruiterAccounts
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isRecruiterOrCompanyAccount()) {
            return redirect()->route('subscription');
        }

        return $next($request);
    }
}