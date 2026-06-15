<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi middleware SetCacheHeaders mengirim Cache-Control yang benar.
 * Quick Win #8 — HTML response caching policy.
 */
class SetCacheHeadersTest extends TestCase
{
    public function test_html_get_response_has_revalidate_cache(): void
    {
        $response = $this->get('/login');

        // HTML: browser harus revalidate (no-cache) setiap reload
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control', ''));
    }

    public function test_post_response_has_no_store_cache(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $package = \App\Models\Package::factory()->create();
        $response = $this->post(route('packages.destroy', $package));

        // Mutating requests: no-store + no-cache
        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_put_response_has_no_store_cache(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $package = \App\Models\Package::factory()->create();
        $response = $this->put(route('packages.update', $package), [
            'name' => 'Updated Package',
            'bandwidth_mbps' => 100,
            'monthly_price' => 500000,
        ]);

        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_delete_response_has_no_store_cache(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $order = \App\Models\Order::factory()->create();
        $response = $this->delete(route('orders.destroy', $order));

        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_middleware_class_exists_and_registered(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\SetCacheHeaders::class));

        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('SetCacheHeaders', $bootstrap, 'Middleware harus di-append di bootstrap/app.php');
    }
}
