<?php

namespace App\Policies;

use App\Models\Transaksi;
use App\Models\User;

class TransaksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function view(User $user, Transaksi $transaksi): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function update(User $user, Transaksi $transaksi): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, Transaksi $transaksi): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
