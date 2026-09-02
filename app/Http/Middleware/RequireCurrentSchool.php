<?php

namespace App\Http\Middleware;

use App\Support\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCurrentSchool
{
    public function __construct(
        protected SchoolContext $schoolContext
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        abort_unless(
            $this->schoolContext->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        return $next($request);
    }
}