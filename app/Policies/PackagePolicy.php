<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

/**
 * PackagePolicy — otorisasi per-aksi pada Package (paket internet).
 *
 * Aturan:
 *  - viewAny/view: semua role (module:package middleware filter)
 *  - create/update/delete: admin only
 */
class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Package $package): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Package $package): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->role === 'admin';
    }
}
