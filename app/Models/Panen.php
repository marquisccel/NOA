<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Panen extends Model
{
    use SoftDeletes;

    protected $table = 'panen';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tonase_kg' => 'decimal:2',
            'harga_per_kg' => 'decimal:2',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
