<?php

namespace App\Policies;

use App\Models\Kandang;
use App\Models\User;

class KandangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPeternak();
    }

    public function create(User $user): bool
    {
        return $user->isPeternak();
    }

    public function view(User $user, Kandang $kandang): bool
    {
        return $user->isPeternak() && $kandang->user_id === $user->id;
    }

    public function update(User $user, Kandang $kandang): bool
    {
        return $this->view($user, $kandang);
    }

    public function delete(User $user, Kandang $kandang): bool
    {
        return $this->view($user, $kandang);
    }
}
