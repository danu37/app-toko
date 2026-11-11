<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->is_active && $user->isAdmin() && $user->id !== $target->id;
    }
}
