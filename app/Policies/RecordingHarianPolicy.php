<?php

namespace App\Policies;

use App\Models\RecordingHarian;
use App\Models\Siklus;
use App\Models\User;

class RecordingHarianPolicy
{
    /** Peternak pemilik dan ABK miliknya boleh melihat dan mengisi (F8.2). */
    public function view(User $user, RecordingHarian $recording): bool
    {
        return ($user->isPeternak() || $user->isAbk())
            && $user->idPemilikData() === $recording->siklus->kandang->user_id;
    }

    /** Sunting hanya bila baris belum diparaf (R11) dan siklus belum selesai (R12). */
    public function update(User $user, RecordingHarian $recording): bool
    {
        return $this->view($user, $recording)
            && ! $recording->diparaf_ppl
            && ! $recording->siklus->isTerkunci();
    }

    /** Hapus entri hanya untuk peternak; ABK hanya mengisi dan memperbaiki. */
    public function delete(User $user, RecordingHarian $recording): bool
    {
        return $user->isPeternak() && $this->update($user, $recording);
    }

    /** Menandai paraf PPL adalah urusan peternak; selama belum diparaf, juga setelah siklus selesai. */
    public function paraf(User $user, RecordingHarian $recording): bool
    {
        return $user->isPeternak() && $this->view($user, $recording) && ! $recording->diparaf_ppl;
    }

    /** Entri baru hanya pada siklus yang belum selesai (R12). */
    public function create(User $user, Siklus $siklus): bool
    {
        return ($user->isPeternak() || $user->isAbk())
            && $user->idPemilikData() === $siklus->kandang->user_id
            && ! $siklus->isTerkunci();
    }
}
