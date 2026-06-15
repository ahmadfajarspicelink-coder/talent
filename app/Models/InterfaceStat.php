<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterfaceStat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'interface_id', 'in_octets', 'out_octets',
        'in_errors', 'out_errors', 'oper_status', 'polled_at',
    ];

    protected $casts = [
        'polled_at' => 'datetime',
    ];

    public function interface(): BelongsTo
    {
        return $this->belongsTo(NetworkInterface::class, 'interface_id');
    }
}
