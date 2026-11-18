<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kandang extends Model
{
    use SoftDeletes;

    protected $table = 'kandang';

    public const TIPE_OPEN_HOUSE = 'open_house';

    public const TIPE_CLOSED_HOUSE = 'closed_house';

    /** Tipe kandang yang dipilih di formulir: kunci => [label, keterangan]. */
    public const TIPE = [
        self::TIPE_OPEN_HOUSE => ['Kandang terbuka', 'Dinding terbuka atau bertirai; udara mengalir alami.'],
        self::TIPE_CLOSED_HOUSE => ['Kandang tertutup', 'Tertutup rapat; suhu dan udara diatur kipas dan pendingin.'],
    ];

    protected $guarded = [];

    public function labelTipe(): ?string
    {
        return self::TIPE[$this->tipe][0] ?? null;
    }

    /** Siklus yang belum ditutup, termasuk yang menunggu RHPP (R2: paling banyak satu per kandang). */
    public function siklusBerjalan(): HasOne
    {
        return $this->hasOne(Siklus::class)->whereIn('status', Siklus::TERBUKA)->latestOfMany('tanggal_masuk');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siklus(): HasMany
    {
        return $this->hasMany(Siklus::class);
    }
}
