<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi SecurityHeaders middleware menambahkan HTTP security headers
 * ke semua response (HTML, JSON, redirect).
 *
 * QW #5 — security hardening: H-01 (no security headers) dari audit report.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_login_page_has_x_frame_options_header(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_login_page_has_x_content_type_options_nosniff(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_login_page_has_referrer_policy_header(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_page_has_permissions_policy_header(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=(), usb=()');
    }

    public function test_login_page_has_content_security_policy_header(): void
    {
        $response = $this->get('/login');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_security_headers_present_on_json_response(): void
    {
        // /up adalah health endpoint yang return JSON (atau HTML 'ok').
        $response = $this->get('/up');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_x_powered_by_header_is_removed(): void
    {
        $response = $this->get('/login');

        $this->assertNull(
            $response->headers->get('X-Powered-By'),
            'X-Powered-By harus di-strip (H-02: PHP version disclosure).'
        );
    }

    public function test_hsts_header_set_only_on_secure_request(): void
    {
        // HTTP (default test env) → HSTS TIDAK di-set (invalid di HTTP).
        $response = $this->get('/login');
        $this->assertNull($response->headers->get('Strict-Transport-Security'));

        // HTTPS (simulated) → HSTS di-set. Cara: buat Request dengan
        // HTTPS on, lalu invoke middleware via direct call.
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create('/login', 'GET');
        $request->server->set('HTTPS', 'on');
        $request->server->set('SERVER_PORT', 443);

        // Pakai handler yang return empty Response.
        $middleware = new \App\Http\Middleware\SecurityHeaders();
        $result = $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('test', 200);
        });
        $hsts = $result->headers->get('Strict-Transport-Security');
        $this->assertNotNull($hsts, 'HSTS harus di-set saat HTTPS.');
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }
}
