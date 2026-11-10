<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KontrakSiklus extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_siklus';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'harga_doc' => 'decimal:2',
            'harga_pakan_sak' => 'decimal:2',
            'harga_ovk' => 'decimal:2',
            'harga_kontrak_ayam' => 'decimal:2',
            'bonus_fcr' => 'boolean',
            'bonus_deplesi' => 'boolean',
            'bonus_ip' => 'boolean',
            'bonus_harga_pasar' => 'boolean',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
