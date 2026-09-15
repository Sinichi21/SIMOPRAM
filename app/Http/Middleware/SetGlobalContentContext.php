<?php

namespace App\Http\Middleware;

use App\Services\GlobalActivityAccess;
use App\Support\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGlobalContentContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && app(GlobalActivityAccess::class)->canEnter($request->user()), 403);
        app(SchoolContext::class)->clear();

        return $next($request);
    }
}
