<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi bahwa semua module views (business-critical) memiliki dark: variants.
 * Quick Win #4 — coverage dark mode 7/49 → 24/49 views.
 *
 * Pattern: NovaSpark design system
 * - text-gray-{500..900} → + dark:text-slate-{100..500}
 * - bg-{color}-50/100   → + dark:bg-{color}-950/40..60
 * - text-{color}-600..800 → + dark:text-{color}-300..400
 * - border-{color}-200/300 → + dark:border-{color}-700/800
 */
class DarkModeCoverageTest extends TestCase
{
    /**
     * List module views yang HARUS punya dark: variants.
     * Setiap view akan di-cek dengan regex yang sesuai konteksnya.
     */
    public static function moduleViewsProvider(): array
    {
        return [
            'clients.index'      => ['clients.index', ['resources/views/clients/index.blade.php']],
            'partners.index'     => ['partners.index', ['resources/views/partners/index.blade.php']],
            'packages.index'     => ['packages.index', ['resources/views/packages/index.blade.php']],
            'users.index'        => ['users.index', ['resources/views/users/index.blade.php']],
            'finance.orders'     => ['finance.orders', ['resources/views/finance/orders.blade.php']],
            'finance.clients'    => ['finance.clients', ['resources/views/finance/clients.blade.php']],
            'livewire.dashboard' => ['livewire.dashboard', ['resources/views/livewire/dashboard.blade.php']],
            'livewire.device-manager' => ['livewire.device-manager', ['resources/views/livewire/device-manager.blade.php']],
            'livewire.top-traffic' => ['livewire.top-traffic', ['resources/views/livewire/top-traffic.blade.php']],
            'orders.index'       => ['orders.index', ['resources/views/orders/index.blade.php']],
            'orders.create'      => ['orders.create', ['resources/views/orders/create.blade.php']],
            'orders.document'    => ['orders.document', ['resources/views/orders/document.blade.php']],
            'dashboard'          => ['dashboard', ['resources/views/dashboard.blade.php']],
        ];
    }

    /**
     * @dataProvider moduleViewsProvider
     */
    public function test_module_view_has_dark_variants(string $name, array $pathArr): void
    {
        $path = base_path($pathArr[0]);
        $this->assertFileExists($path, "View {$name} tidak ada di {$path}");

        $content = file_get_contents($path);
        $darkCount = substr_count($content, 'dark:');

        $this->assertGreaterThan(
            5,
            $darkCount,
            "View {$name} punya {$darkCount} dark: variant — seharusnya > 5 (Quick Win #4 incomplete)."
        );
    }

    public function test_dark_coverage_above_30_views(): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'))
        );
        $views = [];
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $views[] = $file->getPathname();
            }
        }
        $withDark = 0;
        foreach ($views as $v) {
            if (str_contains(file_get_contents($v), 'dark:')) {
                $withDark++;
            }
        }

        $total = count($views);
        $this->assertGreaterThanOrEqual(
            30,
            $withDark,
            "Coverage dark mode {$withDark}/{$total} views — target minimal 30. Quick Win #4 incomplete."
        );
    }

    public function test_no_unrenderable_dark_classes(): void
    {
        // Setiap dark: variant harus punya light counterpart-nya di class yang sama
        $views = glob(base_path('resources/views/**/*.blade.php'));
        $violations = [];

        foreach ($views as $v) {
            $content = file_get_contents($v);
            if (! str_contains($content, 'dark:')) {
                continue;
            }
            // Cari semua class="..." yang punya dark: tapi tidak punya non-dark class
            preg_match_all('/class="([^"]*)"/', $content, $matches);
            foreach ($matches[1] as $classValue) {
                if (! preg_match('/\bdark:/', $classValue)) {
                    continue;
                }
                // Ada dark: di class — pasti ada class lain juga? minimal 2 total
                $classes = preg_split('/\s+/', trim($classValue));
                $lightCount = 0;
                foreach ($classes as $c) {
                    if (! str_starts_with($c, 'dark:')
                        && ! str_starts_with($c, 'aria-')
                        && ! str_starts_with($c, 'peer-')
                        && ! str_starts_with($c, 'group-')
                        && $c !== '') {
                        $lightCount++;
                    }
                }
                if ($lightCount === 0) {
                    $violations[] = basename($v) . ': class="' . $classValue . '"';
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Ada class dengan dark: tanpa light counterpart (akan selalu di-dark mode): '
                . implode('; ', array_slice($violations, 0, 5))
        );
    }
}
