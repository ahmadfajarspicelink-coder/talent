<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

/**
 * DevicePolicy — otorisasi per-aksi pada network Device.
 *
 * Aturan:
 *  - viewAny/view/poll: semua role (module:network middleware filter)
 *  - create/update/delete: admin only
 */
class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Device $device): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Custom action: manual SNMP poll.
     */
    public function poll(User $user, Device $device): bool
    {
        return true; // staff + admin
    }
}
