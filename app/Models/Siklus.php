<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siklus extends Model
{
    use SoftDeletes;

    protected $table = 'siklus';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_panen' => 'date',
            'tanggal_rechick' => 'date',
            'bobot_sak_kg' => 'decimal:2',
            'pakan_retur_sak' => 'decimal:2',
        ];
    }

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIHENTIKAN = 'dihentikan';

    public const STATUSES = [self::STATUS_BERJALAN, self::STATUS_SELESAI, self::STATUS_DIHENTIKAN];

    /** R12: siklus selesai dikunci dari penyuntingan recording. */
    public function isTerkunci(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Kandang::class);
    }

    public function standarHarian(): HasMany
    {
        return $this->hasMany(StandarHarian::class);
    }

    public function parameterUmur(): HasMany
    {
        return $this->hasMany(ParameterUmur::class);
    }

    public function jadwalVaksin(): HasMany
    {
        return $this->hasMany(JadwalVaksin::class);
    }

    public function kontrak(): HasOne
    {
        return $this->hasOne(KontrakSiklus::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(RecordingHarian::class);
    }

    public function samplings(): HasMany
    {
        return $this->hasMany(SamplingBobot::class);
    }

    public function pemberianOvk(): HasMany
    {
        return $this->hasMany(PemberianOvk::class);
    }

    public function panen(): HasMany
    {
        return $this->hasMany(Panen::class);
    }

    public function rhpp(): HasOne
    {
        return $this->hasOne(Rhpp::class);
    }
}
