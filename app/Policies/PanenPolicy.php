<?php

namespace App\Policies;

use App\Models\Panen;
use App\Models\User;

class PanenPolicy
{
    public function view(User $user, Panen $panen): bool
    {
        return $user->isPeternak() && $panen->siklus->kandang->user_id === $user->id;
    }

    public function update(User $user, Panen $panen): bool
    {
        return $this->view($user, $panen);
    }

    public function delete(User $user, Panen $panen): bool
    {
        return $this->view($user, $panen);
    }
}
