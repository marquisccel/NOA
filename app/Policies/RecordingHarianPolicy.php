<?php

namespace App\Policies;

use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;

class RecordingHarianPolicy
{
    public function view(User $user, RecordingHarian $recording): bool
    {
        return $user->isPeternak() && $recording->siklus->kandang->user_id === $user->id;
    }

    /** Sunting hanya bila baris belum diparaf (R11) dan siklus belum selesai (R12). */
    public function update(User $user, RecordingHarian $recording): bool
    {
        return $this->view($user, $recording)
            && ! $recording->diparaf_ppl
            && ! $recording->siklus->isTerkunci();
    }

    public function delete(User $user, RecordingHarian $recording): bool
    {
        return $this->update($user, $recording);
    }

    /** Paraf boleh ditandai selama belum diparaf, juga setelah siklus selesai. */
    public function paraf(User $user, RecordingHarian $recording): bool
    {
        return $this->view($user, $recording) && ! $recording->diparaf_ppl;
    }

    /** Entri baru hanya pada siklus yang belum selesai (R12). */
    public function create(User $user, Siklus $siklus): bool
    {
        return $user->isPeternak()
            && $siklus->kandang->user_id === $user->id
            && ! $siklus->isTerkunci();
    }
}
