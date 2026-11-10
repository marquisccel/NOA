<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalVaksin extends Model
{
    protected $table = 'jadwal_vaksin';

    protected $guarded = [];

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
