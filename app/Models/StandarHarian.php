<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandarHarian extends Model
{
    protected $table = 'standar_harian';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pakan_std_sak' => 'decimal:2',
            'bobot_std_gram' => 'decimal:2',
            'fcr_std' => 'decimal:3',
            'feed_intake_std_gram' => 'decimal:2',
        ];
    }

    public function siklus(): BelongsTo
    {
        return $this->belongsTo(Siklus::class);
    }
}
