<?php

namespace App\Services;

/**
 * ModuleAccessPolicy
 *
 * Logika domain murni untuk kontrol akses berbasis role (R2).
 * Memetakan role pengguna ke daftar modul yang boleh diakses dan
 * menentukan apakah sebuah role boleh mengakses modul tertentu.
 *
 * Pemetaan tetap:
 *   admin -> [partner, order, client, finance, user_management, ticket, ...]
 *   staff -> [order, partner, ticket, ...]
 *
 * Role yang tidak dikenal diperlakukan sebagai tanpa akses (daftar kosong).
 */
class ModuleAccessPolicy
{
    /**
     * Pemetaan tetap role ke modul yang diizinkan.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED = [
        'admin' => ['partner', 'order', 'client', 'finance', 'user_management', 'package', 'network', 'ticket'],
        'staff' => ['order', 'partner', 'network', 'ticket'],
    ];

    /**
     * Kembalikan daftar modul yang boleh diakses oleh sebuah role.
     *
     * Role yang tidak dikenal menghasilkan daftar kosong (tanpa akses).
     *
     * @return array<int, string>
     */
    public function allowedModules(string $role): array
    {
        return self::ALLOWED[$role] ?? [];
    }

    /**
     * Tentukan apakah sebuah role boleh mengakses modul tertentu.
     *
     * Mengembalikan true jika dan hanya jika modul termasuk dalam
     * daftar modul yang diizinkan untuk role tersebut.
     */
    public function canAccess(string $role, string $module): bool
    {
        return in_array($module, $this->allowedModules($role), true);
    }
}
