<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

/**
 * PartnerPolicy — otorisasi per-aksi pada Partner (provider/vendor).
 *
 * Aturan:
 *  - viewAny/view: semua role (module:partner middleware filter)
 *  - create/update/delete: admin only
 */
class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Partner $partner): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->role === 'admin';
    }
}
