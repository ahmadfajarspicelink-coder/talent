<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetCacheHeaders — tambah header Cache-Control untuk response HTML.
 *
 * Aturan:
 *  - HTML responses (text/html): `no-cache, private, must-revalidate`
 *    Browser harus revalidate setiap reload (pakai ETag/Last-Modified
 *    untuk cek apakah konten berubah) — memastikan user selalu dapat
 *    data terbaru tapi hemat bandwidth kalau konten tidak berubah.
 *  - JSON responses (application/json): `no-store` — tidak boleh di-cache
 *    sama sekali (data real-time/dashboard).
 *  - Method POST/PUT/PATCH/DELETE: `no-store` (mutating requests, no cache)
 *
 * Quick Win #8 — melengkapi static asset caching dengan HTML caching
 * yang proper (sebelumnya HTML hanya punya default Laravel headers).
 */
class SetCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Mutating requests: no caching
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');

        // JSON: no cache (real-time data)
        if (str_contains($contentType, 'application/json')) {
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        }

        // HTML: revalidate each time (default 200 OK, but check ETag)
        if (str_contains($contentType, 'text/html') || $contentType === '') {
            // Hanya set kalau belum ada (biar tidak override custom policy)
            if (! $response->headers->has('Cache-Control')) {
                $response->headers->set('Cache-Control', 'no-cache, private, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            }
        }

        return $response;
    }
}
