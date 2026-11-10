<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Akses hanya-baca Pengawas Inti pada satu siklus, diberikan dan dicabut oleh peternak pemilik. */
class AksesPengawas extends Model
{
    protected $table = 'akses_pengawas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['dicabut_pada' => 'datetime'];
    }

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }

    public function peternak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peternak_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('dicabut_pada');
    }

    public function masihAktif(): bool
    {
        return $this->dicabut_pada === null;
    }
}
