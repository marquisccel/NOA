<?php

namespace App\Policies;

use App\Models\Siklus;
use App\Models\User;

class SiklusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPeternak();
    }

    public function create(User $user): bool
    {
        return $user->isPeternak();
    }

    public function view(User $user, Siklus $siklus): bool
    {
        return $user->isPeternak() && $siklus->kandang->user_id === $user->id;
    }

    public function update(User $user, Siklus $siklus): bool
    {
        return $this->view($user, $siklus);
    }

    public function delete(User $user, Siklus $siklus): bool
    {
        return $this->view($user, $siklus);
    }
}
