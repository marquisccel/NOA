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
            'ambang_rekonsiliasi' => 'array',
        ];
    }

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIHENTIKAN = 'dihentikan';

    public const STATUSES = [self::STATUS_BERJALAN, self::STATUS_SELESAI, self::STATUS_DIHENTIKAN];

    /** Label dan warna badge status. "Selesai" tampil sebagai "Ditutup" seperti di desain. */
    public function labelStatus(): string
    {
        return ['berjalan' => 'Berjalan', 'selesai' => 'Ditutup', 'dihentikan' => 'Dihentikan'][$this->status] ?? ucfirst($this->status);
    }

    public function varianStatus(): string
    {
        return ['berjalan' => 'success', 'selesai' => 'neutral', 'dihentikan' => 'danger'][$this->status] ?? 'neutral';
    }

    /** Hari umur ayam pada tanggal tertentu (tanggal masuk = hari ke-1), minimal 1. */
    public function umurHari(?\Carbon\CarbonInterface $pada = null): int
    {
        $pada = ($pada ?? now())->copy()->startOfDay();

        return max(1, (int) $this->tanggal_masuk->copy()->startOfDay()->diffInDays($pada) + 1);
    }

    /** Populasi kini = populasi awal dikurangi seluruh deplesi (mati dan afkir) tercatat. */
    public function populasiKini(): int
    {
        return $this->populasi - (int) $this->recordings()->sum('deplesi_harian');
    }

    /**
     * Nomor siklus di kandangnya ("Siklus 7"): urutan menurut tanggal masuk, dihitung bukan disimpan.
     *
     * @param  iterable<int>  $kandangIds
     * @return array<int, int> id siklus => nomor
     */
    public static function nomorMap(iterable $kandangIds): array
    {
        $peta = [];

        $perKandang = static::whereIn('kandang_id', collect($kandangIds)->all())
            ->orderBy('kandang_id')->orderBy('tanggal_masuk')->orderBy('id')
            ->get(['id', 'kandang_id'])
            ->groupBy('kandang_id');

        // foreach (bukan arrow function): arrow function menangkap $peta per nilai sehingga isinya hilang.
        foreach ($perKandang as $daftar) {
            foreach ($daftar->values() as $i => $s) {
                $peta[$s->id] = $i + 1;
            }
        }

        return $peta;
    }

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
