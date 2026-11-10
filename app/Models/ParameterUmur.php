<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParameterUmur extends Model
{
    protected $table = 'parameter_umur';

    protected $guarded = [];

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
