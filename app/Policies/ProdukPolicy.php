<?php

namespace App\Policies;

use App\Models\Produk;
use App\Models\User;

class ProdukPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function view(User $user, Produk $produk): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function update(User $user, Produk $produk): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, Produk $produk): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function restore(User $user, Produk $produk): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function forceDelete(User $user, Produk $produk): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
