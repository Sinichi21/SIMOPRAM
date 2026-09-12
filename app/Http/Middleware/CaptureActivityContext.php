<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CaptureActivityContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Context::forgetHidden(['activity_actor', 'activity_school_id']);
        Context::addHidden('activity_request_id', 'req_'.Str::uuid());
        $logger = app(ActivityLogger::class);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $logger->failure($exception);
            throw $exception;
        }

        $response->headers->set('X-Request-ID', $logger->requestId());
        if ($response->getStatusCode() >= 400 && ! $request->attributes->get('activity_failure_logged')) {
            $security = in_array($response->getStatusCode(), [401, 403, 419, 429], true);
            $logger->record($security ? 'users' : 'settings', 'failed', new: ['http_status' => $response->getStatusCode()], status: 'failed',
                type: $security ? 'security' : 'system', description: $security ? 'Akses ditolak' : 'Permintaan aplikasi gagal');
        }
        if ($response->isSuccessful() && $response->headers->has('Content-Disposition') && ! $request->attributes->get('activity_download_logged')) {
            $route = $request->route()?->getName() ?? '';
            $module = str_starts_with($route, 'reports.') ? 'reports' : (str_starts_with($route, 'letters.') ? 'letters' : 'archives');
            $target = collect($request->route()?->parameters() ?? [])->first(fn (mixed $value): bool => $value instanceof Model);
            $logger->record($module, $module === 'reports' && ! str_contains($route, 'download') ? 'exported' : 'downloaded', $target);
        }

        return $response;
    }
}
