<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Akun dinonaktifkan. Hubungi admin untuk mengaktifkan kembali.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Akun dinonaktifkan. Hubungi admin untuk mengaktifkan kembali.']);
        }

        return $next($request);
    }
}
