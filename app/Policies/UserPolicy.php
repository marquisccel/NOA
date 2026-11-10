<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Peternak hanya boleh mengelola akun ABK miliknya sendiri. */
    public function kelolaAbk(User $user, User $target): bool
    {
        return $user->isPeternak() && $target->isAbk() && $target->peternak_id === $user->id;
    }
}
