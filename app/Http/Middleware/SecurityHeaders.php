<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers middleware — global protection layer.
 *
 * Menambahkan HTTP security headers yang hilang dari response default Laravel
 * (audit H-01). Dipasang global via bootstrap/app.php agar konsisten di
 * semua route (HTML, JSON, redirect, file download).
 *
 * Headers:
 *  - X-Frame-Options: SAMEORIGIN        → block clickjacking (iframe attack)
 *  - X-Content-Type-Options: nosniff    → block MIME confusion
 *  - Referrer-Policy                    → limit URL leak ke external
 *  - Permissions-Policy                 → disable unused browser features
 *  - Strict-Transport-Security          → enforce HTTPS (1 tahun + subdomain)
 *  - Content-Security-Policy            → limit script/style/frame source
 *
 * Catatan CSP: Livewire 4 + Alpine emit inline <script> + eval(), sehingga
 * 'unsafe-inline' + 'unsafe-eval' WAJIB sampai migrasi ke CSP nonce.
 * Bunnynet (Tailwind 3 default font) butuh allowlist domain.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip untuk response yang tidak punya property `headers` (binary
        // stream, exception response, atau response kustom tanpa HeaderBag).
        // `headers` adalah public property di Symfony Response — gunakan
        // property_exists, BUKAN method_exists (yang selalu return false).
        if (property_exists($response, 'headers') === false) {
            return $response;
        }

        $headers = $response->headers;

        // Clickjacking protection
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME confusion: browser tidak boleh tebak content type
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer leak: kirim origin only ke external, full URL ke same-origin
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable unused browser features (defense in depth)
        $headers->set(
            'Permissions-Policy',
            'geolocation=(), camera=(), microphone=(), payment=(), usb=()'
        );

        // HSTS: paksa HTTPS selama 1 tahun, include subdomain. Hanya set
        // jika koneksi HTTPS (HSTS hanya valid via HTTPS, abaikan di HTTP).
        if ($request->isSecure()) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // CSP: allow self + Bunnynet (Tailwind default) + inline script (Livewire/Alpine)
        // TODO: migrasi ke nonce-based CSP saat Livewire 4 support nonce.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "font-src 'self' https://fonts.bunny.net data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $headers->set('Content-Security-Policy', $csp);

        // Strip X-Powered-By: PHP version disclosure (H-02). Apache config
        // juga di-strip via `Header unset X-Powered-By` di vhost.
        $headers->remove('X-Powered-By');
        $headers->remove('Server');

        return $response;
    }
}
