<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rhpp extends Model
{
    use SoftDeletes;

    protected $table = 'rhpp';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'total_pakan_kg' => 'decimal:2',
            'total_tonase' => 'decimal:2',
            'abw' => 'decimal:3',
            'umur_panen' => 'decimal:2',
            'deplesi_persen' => 'decimal:2',
            'fcr' => 'decimal:3',
            'ip' => 'decimal:2',
            'pendapatan' => 'decimal:2',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
