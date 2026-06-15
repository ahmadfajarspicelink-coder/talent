<?php

namespace App\Http\Middleware;

use App\Services\ModuleAccessPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModuleAccess
 *
 * Middleware kontrol akses berbasis role (R2). Untuk setiap route yang
 * dilekati middleware ini dengan parameter modul, middleware:
 *
 *  1. Memeriksa apakah modul sedang tidak tersedia (R2.6). Jika ya, akses
 *     ditolak dengan HTTP 503 + pesan "layanan tidak tersedia". Pengecekan
 *     ketersediaan didahulukan agar modul yang down memang benar-benar
 *     tertutup untuk semua role.
 *  2. Mengonsultasikan ModuleAccessPolicy berdasarkan role pengguna yang
 *     sedang login. Jika role tidak diizinkan mengakses modul, akses ditolak
 *     dengan HTTP 403 + pesan "akses ditolak" (R2.4, R2.5).
 *
 * Karena setiap route dilekati middleware dengan parameter modulnya
 * masing-masing, permintaan yang mencakup beberapa modul ditangani per-route:
 * modul yang diizinkan tetap jalan sementara modul terlarang diblokir
 * diam-diam dengan pesan akses ditolak (R2.5).
 *
 * Mekanisme "modul tidak tersedia" dibaca dari config `modules.unavailable`
 * (lihat config/modules.php), yaitu array nama modul yang sedang down. Ini
 * memudahkan simulasi modul down pada feature test (task 8.3) lewat
 * `config(['modules.unavailable' => ['order']])`.
 */
class EnsureModuleAccess
{
    public function __construct(
        private readonly ModuleAccessPolicy $policy,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // R2.6: modul yang sedang tidak tersedia ditolak untuk semua role.
        if ($this->isModuleUnavailable($module)) {
            abort(503, 'layanan tidak tersedia');
        }

        // Tentukan role pengguna yang sedang login.
        $role = (string) ($request->user()->role ?? '');

        // R2.4, R2.5: blokir modul yang tidak diizinkan untuk role ini.
        if (! $this->policy->canAccess($role, $module)) {
            abort(403, 'akses ditolak');
        }

        return $next($request);
    }

    /**
     * Tentukan apakah sebuah modul sedang ditandai tidak tersedia.
     */
    private function isModuleUnavailable(string $module): bool
    {
        $unavailable = (array) config('modules.unavailable', []);

        return in_array($module, $unavailable, true);
    }
}
