<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecordingHarian extends Model
{
    use SoftDeletes;

    protected $table = 'recording_harian';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_paraf' => 'date',
            'diparaf_ppl' => 'boolean',
            'pakan_masuk_sak' => 'decimal:2',
            'pakan_pakai_sak' => 'decimal:2',
            'pakan_sisa_sak' => 'decimal:2',
        ];
    }

    public const SEBAB_AFKIR = ['sakit', 'cacat', 'kerdil', 'lainnya'];

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
