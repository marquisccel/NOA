<?php

namespace App\Policies;

use App\Models\PemberianOvk;
use App\Models\User;

class PemberianOvkPolicy
{
    public function update(User $user, PemberianOvk $ovk): bool
    {
        return $user->isPeternak() && $ovk->siklus->kandang->user_id === $user->id;
    }

    public function delete(User $user, PemberianOvk $ovk): bool
    {
        return $this->update($user, $ovk);
    }
}
