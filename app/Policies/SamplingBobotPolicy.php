<?php

namespace App\Policies;

use App\Models\SamplingBobot;
use App\Models\User;

class SamplingBobotPolicy
{
    public function update(User $user, SamplingBobot $sampling): bool
    {
        return $user->isPeternak() && $sampling->siklus->kandang->user_id === $user->id;
    }

    public function delete(User $user, SamplingBobot $sampling): bool
    {
        return $this->update($user, $sampling);
    }
}
