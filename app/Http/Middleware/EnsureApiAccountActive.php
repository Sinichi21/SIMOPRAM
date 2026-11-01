<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccountActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_active) {
            $request->user()?->currentAccessToken()?->delete();

            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        return $next($request);
    }
}
