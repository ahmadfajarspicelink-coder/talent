<?php

namespace Tests\Unit;

use App\Services\ModuleAccessPolicy;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based test untuk kebijakan akses modul (ModuleAccessPolicy).
 *
 * Logika murni tanpa I/O atau database, sehingga mewarisi PHPUnit\Framework\TestCase
 * dan TIDAK menggunakan RefreshDatabase.
 */
class ModuleAccessPolicyPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Pemetaan referensi role -> modul yang diizinkan (sumber kebenaran independen
     * dari implementasi, untuk membandingkan hasil policy).
     *
     * @var array<string, array<int, string>>
     */
    private const EXPECTED_ALLOWED = [
        'admin' => ['partner', 'order', 'client', 'finance', 'user_management', 'package'],
        'staff' => ['order', 'partner'],
    ];

    /**
     * Semesta modul yang bisa diminta. Mencakup seluruh modul nyata ditambah
     * beberapa modul yang tidak pernah diizinkan untuk role manapun, agar
     * properti "modul lain selalu diblokir" benar-benar teruji.
     *
     * @var array<int, string>
     */
    private const REQUEST_UNIVERSE = [
        'partner',
        'order',
        'client',
        'finance',
        'user_management',
        'package',
        'dashboard',
        'settings',
        'reports',
        'unknown_module',
    ];

    /**
     * Feature: talent-secreet-isp-broker, Property 1: Kebijakan akses modul konsisten dengan role
     *
     * For any role pengguna (admin/staff) dan for any himpunan modul yang diminta,
     * modul yang diizinkan tepat sama dengan irisan himpunan yang diminta dengan
     * daftar modul yang boleh diakses role tersebut (admin = semua modul; staff =
     * hanya order & partner), dan modul lain selalu diblokir.
     *
     * Validates: Requirements 2.2, 2.3, 2.4, 2.5
     */
    public function test_allowed_modules_equal_intersection_of_request_and_role_policy(): void
    {
        $policy = new ModuleAccessPolicy();

        $this->limitTo(100)
            ->forAll(
                Generator\elements('admin', 'staff'),
                Generator\subset(self::REQUEST_UNIVERSE)
            )
            ->then(function (string $role, array $requested) use ($policy): void {
                $roleAllowed = self::EXPECTED_ALLOWED[$role];

                // Modul yang diloloskan policy dari himpunan yang diminta.
                $grantedByPolicy = array_values(array_filter(
                    $requested,
                    static fn (string $module): bool => $policy->canAccess($role, $module)
                ));

                // Irisan yang diharapkan: modul yang diminta DAN diizinkan untuk role.
                $expectedIntersection = array_values(array_intersect($requested, $roleAllowed));

                // Bandingkan sebagai himpunan (urutan tidak relevan).
                sort($grantedByPolicy);
                sort($expectedIntersection);

                $this->assertSame(
                    $expectedIntersection,
                    $grantedByPolicy,
                    sprintf(
                        'Akses untuk role "%s" atas modul {%s} harus sama dengan irisan dengan modul role-nya.',
                        $role,
                        implode(', ', $requested)
                    )
                );

                // Properti komplementer: setiap modul di luar daftar role SELALU diblokir,
                // termasuk modul yang tidak dikenal.
                foreach ($requested as $module) {
                    if (!in_array($module, $roleAllowed, true)) {
                        $this->assertFalse(
                            $policy->canAccess($role, $module),
                            sprintf('Role "%s" tidak boleh mengakses modul terlarang "%s".', $role, $module)
                        );
                    }
                }
            });
    }
}
