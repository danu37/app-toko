<?php

namespace App\Policies;

use App\Models\Kategori;
use App\Models\User;

class KategoriPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function view(User $user, Kategori $kategori): bool
    {
        return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, Kategori $kategori): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, Kategori $kategori): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
