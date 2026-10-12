<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /** Admins see every sale; a kasir only sees their own. */
    public function view(User $user, Sale $sale): bool
    {
        return $user->isAdmin() || $sale->user_id === $user->id;
    }

    /** Only admins can void, and only sales that are not already void. */
    public function void(User $user, Sale $sale): bool
    {
        return $user->isAdmin() && ! $sale->isVoid();
    }
}
