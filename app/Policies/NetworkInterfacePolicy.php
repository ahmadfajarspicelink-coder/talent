<?php

namespace App\Policies;

use App\Models\NetworkInterface;
use App\Models\User;

/**
 * NetworkInterfacePolicy — otorisasi per-aksi pada NetworkInterface.
 *
 * Aturan: sama dengan DevicePolicy.
 *  - view: semua role (module:network middleware filter)
 *  - create/update/delete: admin only
 */
class NetworkInterfacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NetworkInterface $networkInterface): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, NetworkInterface $networkInterface): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, NetworkInterface $networkInterface): bool
    {
        return $user->role === 'admin';
    }
}
