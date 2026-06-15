<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'cpu_usage', 'memory_used',
        'memory_total', 'uptime_ticks', 'polled_at',
    ];

    protected $casts = [
        'polled_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
