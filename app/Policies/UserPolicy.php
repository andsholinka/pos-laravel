<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Admins can delete other users, never themselves. */
    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $user->is($target);
    }

    /** Admins can (de)activate other users, never themselves. */
    public function toggleActive(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $user->is($target);
    }
}
