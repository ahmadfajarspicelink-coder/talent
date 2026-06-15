<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * ClientPolicy — otorisasi per-aksi pada Client.
 *
 * Aturan:
 *  - viewAny/view: semua role (module:client middleware filter)
 *  - upgrade/dismantle: semua role (staff bisa proses client)
 *  - create/update/delete: admin only (client auto-created dari order)
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Custom action: upgrade client ke order baru.
     */
    public function upgrade(User $user, Client $client): bool
    {
        return true; // staff + admin
    }

    /**
     * Custom action: dismantle (bongkar) layanan client.
     */
    public function dismantle(User $user, Client $client): bool
    {
        return true; // staff + admin
    }
}
