<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkInterface extends Model
{
    use HasFactory;
    protected $table = 'interfaces';

    protected $fillable = [
        'device_id', 'if_index', 'if_name', 'if_descr', 'if_alias',
        'if_speed', 'if_type', 'if_oper_status', 'if_admin_status',
        'if_in_octets', 'if_out_octets', 'if_in_errors', 'if_out_errors',
        'last_polled_at',
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(InterfaceStat::class, 'interface_id');
    }
}
