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

    /** Lihat siklus lengkap (kontrak, biaya, rekonsiliasi): hanya peternak pemilik; tidak untuk ABK (R13). */
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

    /**
     * Boleh melihat dan mengisi recording harian: peternak pemilik, atau ABK aktif yang
     * ditugaskan ke siklus ini dan dimiliki peternak yang sama (F8.2).
     */
    public function catat(User $user, Siklus $siklus): bool
    {
        if ($user->isPeternak()) {
            return $siklus->kandang->user_id === $user->id;
        }

        return $user->isAbk()
            && $user->aktif
            && $user->siklus_id === $siklus->id
            && $user->peternak_id === $siklus->kandang->user_id;
    }
}
