<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi static asset cache headers ter-setup.
 * Quick Win #5 — production-ready caching.
 *
 * Vite hashed assets (public/build/assets/*): 1 year immutable
 * Non-hashed static (favicon, images):        7 days
 *
 * NOTE: Cache-Control headers di-serve oleh Apache .htaccess,
 * bukan oleh Laravel. Test verifikasi file .htaccess ada dan
 * isinya benar (bukan test HTTP response).
 */
class StaticAssetCacheTest extends TestCase
{
    public function test_build_htaccess_has_immutable_cache(): void
    {
        $htaccess = public_path('build/.htaccess');
        $this->assertFileExists($htaccess, 'public/build/.htaccess tidak ada — harus dibuat untuk Vite hashed assets');

        $content = file_get_contents($htaccess);

        $this->assertStringContainsString('max-age=31536000', $content, 'Vite hashed assets harus 1 year cache');
        $this->assertStringContainsString('immutable', $content, 'Vite hashed assets harus immutable');
        $this->assertStringContainsString('mod_headers', $content, 'Harus guard dengan IfModule mod_headers');
    }

    public function test_public_htaccess_has_static_cache(): void
    {
        $htaccess = public_path('.htaccess');
        $this->assertFileExists($htaccess, 'public/.htaccess tidak ada');

        $content = file_get_contents($htaccess);

        $this->assertStringContainsString('max-age=604800', $content, 'Static assets non-hashed harus 7 day cache');
        $this->assertStringContainsString('ico|png|jpg', $content, 'Rule harus cover common static types (ico, png, jpg)');
    }

    public function test_vite_build_has_hashed_filenames(): void
    {
        $manifest = public_path('build/manifest.json');
        $this->assertFileExists($manifest, 'Vite manifest.json tidak ada — run npm run build');

        $manifestData = json_decode(file_get_contents($manifest), true);
        $this->assertNotEmpty($manifestData, 'Vite manifest kosong');

        // Verify CSS file punya hash di nama
        $cssFile = array_key_first($manifestData['resources/css/app.css'] ?? []);
        // Manifest format: { "resources/css/app.css": { "file": "assets/app-HASH.css", ... } }
        $builtCss = $manifestData['resources/css/app.css']['file'] ?? '';
        $this->assertMatchesRegularExpression('/assets\/app-[a-zA-Z0-9]+\.css$/', $builtCss, 'Vite CSS filename harus punya hash');
    }

    public function test_build_directory_contains_assets(): void
    {
        $assets = glob(public_path('build/assets/*'));
        $this->assertNotEmpty($assets, 'public/build/assets/ kosong — run npm run build');

        $jsFiles = array_filter($assets, fn ($f) => str_ends_with($f, '.js'));
        $cssFiles = array_filter($assets, fn ($f) => str_ends_with($f, '.css'));

        $this->assertNotEmpty($jsFiles, 'Tidak ada JS file di build/assets/');
        $this->assertNotEmpty($cssFiles, 'Tidak ada CSS file di build/assets/');
    }
}
