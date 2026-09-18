<?php

use App\Http\Middleware\CaptureActivityContext;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\PreviewFileResponse;
use App\Http\Middleware\RequireCurrentSchool;
use App\Http\Middleware\SetCurrentSchool;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CaptureActivityContext::class);
        $middleware->append(PreviewFileResponse::class);
        $middleware->web(append: [EnsureAccountActive::class]);
        $middleware->alias([
            'school' => SetCurrentSchool::class,
            'school.required' => RequireCurrentSchool::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            SetCurrentSchool::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request): ?RedirectResponse {
            if ($exception->getPrevious() instanceof TokenMismatchException
                && $request->isMethod('POST')
                && $request->routeIs('activity-access.enter')
                && ! $request->expectsJson()) {
                return new RedirectResponse(route('activity-access.open', [
                    'token' => $request->route('token'),
                    'session_refreshed' => 1,
                ], absolute: false), 303, [
                    'Cache-Control' => 'private, no-store',
                    'Referrer-Policy' => 'no-referrer',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ]);
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
