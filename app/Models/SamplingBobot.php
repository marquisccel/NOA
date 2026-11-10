<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SamplingBobot extends Model
{
    use SoftDeletes;

    protected $table = 'sampling_bobot';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_bobot_kg' => 'decimal:3',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
