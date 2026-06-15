<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * OrderPolicy — otorisasi per-aksi pada Order.
 *
 * Aturan:
 *  - viewAny/view: semua role yang lolos module:order middleware
 *  - create: semua role (staff bisa buat order)
 *  - update/advanceStatus: semua role (staff bisa proses order)
 *  - delete: admin only
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // module:order middleware sudah filter role
    }

    public function view(User $user, Order $order): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return true;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}
