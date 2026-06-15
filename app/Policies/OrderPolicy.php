<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * OrderPolicy — otorisasi per-aksi pada Order.
 *
 * Aturan (H-04 — QW #10):
 *  - viewAny: semua role yang lolos module:order middleware
 *  - view: admin SELALU boleh, staff HANYA order yang dia create
 *  - create: semua role (staff bisa buat order)
 *  - update: admin SELALU boleh, staff HANYA order yang dia create
 *  - delete: admin only
 *
 * Order lama (sebelum H-04 fix) tanpa `created_by` bisa diakses SEMUA staff
 * (graceful fallback). Tidak throw error — degradasi ke behavior lama untuk
 * data legacy.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // module:order middleware sudah filter role
    }

    public function view(User $user, Order $order): bool
    {
        return $this->adminOrOwner($user, $order);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $this->adminOrOwner($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Admin → always allow. Staff → only if user created the order.
     * Legacy orders (created_by null) → allow all staff (graceful).
     */
    private function adminOrOwner(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Legacy data tanpa created_by: degradasi ke behavior lama.
        if ($order->created_by === null) {
            return true;
        }

        return (int) $order->created_by === (int) $user->id;
    }
}
