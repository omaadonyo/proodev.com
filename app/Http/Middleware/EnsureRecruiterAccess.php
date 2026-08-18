<?php

namespace App\Http\Middleware;

use App\Services\Recruiter\RecruiterAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecruiterAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! app(RecruiterAccessService::class)->canAccess($user)) {
            abort(403, 'The Recruiter Intelligence Suite requires the Recruiter Intelligence plan.');
        }

        return $next($request);
    }
}
