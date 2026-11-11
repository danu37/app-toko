<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\User;

class PelangganPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
