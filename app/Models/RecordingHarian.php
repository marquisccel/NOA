<?php

namespace App\Models;

use App\Exceptions\RecordingTerkunci;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecordingHarian extends Model
{
    use SoftDeletes;

    public const SEBAB_AFKIR = ['sakit', 'cacat', 'kerdil', 'lainnya'];

    /** Kolom yang boleh berubah saat baris ditandai diparaf. */
    private const KOLOM_PARAF = ['diparaf_ppl', 'tanggal_paraf', 'updated_at'];

    protected $table = 'recording_harian';

    protected $guarded = [];

    /**
     * Penjaga di tingkat model (bukan hanya antarmuka):
     * R11 baris yang sudah diparaf PPL tidak dapat disunting atau dihapus;
     * R12 siklus selesai dikunci dari penyuntingan recording (paraf tetap boleh).
     */
    protected static function booted(): void
    {
        static::saving(function (self $recording) {
            $selesai = Siklus::withTrashed()->whereKey($recording->siklus_id)->value('status') === Siklus::STATUS_SELESAI;

            if ($selesai && ! $recording->hanyaMengubahParaf()) {
                throw RecordingTerkunci::siklusSelesai();
            }
        });

        static::updating(function (self $recording) {
            if ($recording->getOriginal('diparaf_ppl')) {
                throw RecordingTerkunci::diparaf();
            }
        });

        static::deleting(function (self $recording) {
            if ($recording->diparaf_ppl) {
                throw RecordingTerkunci::diparaf();
            }

            if (Siklus::withTrashed()->whereKey($recording->siklus_id)->value('status') === Siklus::STATUS_SELESAI) {
                throw RecordingTerkunci::siklusSelesai();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_paraf' => 'date',
            'diparaf_ppl' => 'boolean',
            'pakan_masuk_sak' => 'decimal:2',
            'pakan_pakai_sak' => 'decimal:2',
            'pakan_sisa_sak' => 'decimal:2',
            'bobot_aktual_gram' => 'decimal:2',
            'suhu_pagi' => 'decimal:1',
            'suhu_sore' => 'decimal:1',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }

    /** True bila baris sudah ada dan satu-satunya perubahan adalah penandaan paraf. */
    private function hanyaMengubahParaf(): bool
    {
        return $this->exists
            && array_diff(array_keys($this->getDirty()), self::KOLOM_PARAF) === [];
    }
}
