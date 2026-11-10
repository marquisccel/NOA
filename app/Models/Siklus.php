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
            'ditutup_pada' => 'date',
            'bobot_sak_kg' => 'decimal:2',
            'pakan_retur_sak' => 'decimal:2',
            'ambang_rekonsiliasi' => 'array',
        ];
    }

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_PANEN_SELESAI = 'panen_selesai';

    public const STATUS_MENUNGGU_RHPP = 'menunggu_rhpp';

    public const STATUS_REKONSILIASI = 'rekonsiliasi';

    /** Nilai 'selesai' dipertahankan dari Fase 1-6 dan tampil sebagai "Ditutup". */
    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITUTUP_TANPA_RHPP = 'ditutup_tanpa_rhpp';

    public const STATUS_DIHENTIKAN = 'dihentikan';

    /** Label dan variasi badge tiap status (desain: 7 status). */
    public const STATUS = [
        self::STATUS_BERJALAN => ['Berjalan', 'success'],
        self::STATUS_PANEN_SELESAI => ['Panen selesai', 'info'],
        self::STATUS_MENUNGGU_RHPP => ['Menunggu RHPP', 'warning'],
        self::STATUS_REKONSILIASI => ['Rekonsiliasi', 'info'],
        self::STATUS_SELESAI => ['Ditutup', 'neutral'],
        self::STATUS_DITUTUP_TANPA_RHPP => ['Ditutup tanpa RHPP', 'neutral'],
        self::STATUS_DIHENTIKAN => ['Dihentikan', 'danger'],
    ];

    public const STATUSES = [
        self::STATUS_BERJALAN, self::STATUS_PANEN_SELESAI, self::STATUS_MENUNGGU_RHPP, self::STATUS_REKONSILIASI,
        self::STATUS_SELESAI, self::STATUS_DITUTUP_TANPA_RHPP, self::STATUS_DIHENTIKAN,
    ];

    /** Siklus terbuka menahan kandangnya dari siklus baru (R2), termasuk saat menunggu RHPP. */
    public const TERBUKA = [self::STATUS_BERJALAN, self::STATUS_PANEN_SELESAI, self::STATUS_MENUNGGU_RHPP, self::STATUS_REKONSILIASI];

    /** Siklus tertutup: data hanya dapat dilihat (recording dan pengaturan terkunci). */
    public const TERTUTUP = [self::STATUS_SELESAI, self::STATUS_DITUTUP_TANPA_RHPP, self::STATUS_DIHENTIKAN];

    public function labelStatus(): string
    {
        return self::STATUS[$this->status][0] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function varianStatus(): string
    {
        return self::STATUS[$this->status][1] ?? 'neutral';
    }

    public function isTerbuka(): bool
    {
        return in_array($this->status, self::TERBUKA, true);
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

    /** R12: siklus yang sudah ditutup (atau dihentikan) dikunci dari penyuntingan. */
    public function isTerkunci(): bool
    {
        return in_array($this->status, self::TERTUTUP, true);
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
