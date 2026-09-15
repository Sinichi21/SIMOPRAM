<?php

namespace App\Http\Middleware;

use App\Services\FilePreviewContent;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PreviewFileResponse
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $response->isSuccessful() || $request->boolean('download')) {
            return $response;
        }
        if ($request->hasHeader('X-Livewire') && $response instanceof JsonResponse) {
            $data = $response->getData(true);
            foreach ($data['components'] ?? [] as $index => $component) {
                if (isset($component['effects']['download'])) {
                    $file = $component['effects']['download'];
                    $file['previewText'] = app(FilePreviewContent::class)->text(base64_decode($file['content']), $file['name']);
                    $data['components'][$index]['effects']['dispatches'][] = [
                        'name' => 'file-preview', 'params' => $file,
                    ];
                    unset($data['components'][$index]['effects']['download']);
                }
            }
            $response->setData($data);

            return $response;
        }
        $disposition = $response->headers->get('Content-Disposition');
        if (! $request->isMethod('GET') || ! $disposition) {
            return $response;
        }
        $mime = strtolower(explode(';', $response->headers->get('Content-Type', ''))[0]);
        if (in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'text/plain'], true)) {
            $response->headers->set('Content-Disposition', preg_replace('/^attachment/i', 'inline', $disposition));
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            return $response;
        }

        preg_match('/filename=(?:"([^"]+)"|([^;\\s]+))/i', $disposition, $matches);
        $filename = ($matches[1] ?? '') ?: ($matches[2] ?? 'Berkas');
        if (preg_match("/filename\\*=utf-8''([^;]+)/i", $disposition, $encodedName)) {
            $filename = rawurldecode($encodedName[1]);
        }
        $previewText = null;
        if ($response instanceof BinaryFileResponse && $response->getFile()->getSize() <= 15 * 1024 * 1024) {
            $previewText = app(FilePreviewContent::class)->text(file_get_contents($response->getFile()->getPathname()), $filename);
        }

        return response()->view('partials.file-preview', [
            'downloadUrl' => $request->fullUrlWithQuery(['download' => 1]),
            'filename' => $filename, 'previewText' => $previewText,
        ])->withHeaders(['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
