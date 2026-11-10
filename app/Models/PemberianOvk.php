<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemberianOvk extends Model
{
    use SoftDeletes;

    protected $table = 'pemberian_ovk';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'biaya' => 'decimal:2',
        ];
    }

    public const JENIS = ['obat', 'vaksin', 'kimia'];

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
