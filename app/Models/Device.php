<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'ip_address', 'snmp_community', 'snmp_version',
        'vendor', 'model', 'location', 'status',
        'cpu_usage', 'memory_used', 'memory_total', 'uptime_ticks',
        'last_polled_at', 'port_map_filter',
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
        'port_map_filter' => 'array',
    ];

    public function interfaces(): HasMany
    {
        return $this->hasMany(NetworkInterface::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(DeviceStat::class);
    }

    /**
     * Get memory usage percentage
     */
    public function getMemoryPercentAttribute(): ?float
    {
        if ($this->memory_total && $this->memory_total > 0 && $this->memory_used !== null) {
            return round(($this->memory_used / $this->memory_total) * 100, 1);
        }
        return null;
    }

    /**
     * Format uptime ticks to human readable
     */
    public function getUptimeFormattedAttribute(): string
    {
        if (!$this->uptime_ticks) return '-';

        $seconds = (int)($this->uptime_ticks / 100);
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) return "{$days}d {$hours}h {$minutes}m";
        if ($hours > 0) return "{$hours}h {$minutes}m";
        return "{$minutes}m";
    }

    /**
     * Format bytes to human readable
     */
    public static function formatBytes(?int $bytes): string
    {
        if ($bytes === null) return '-';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    public static function formatSpeed(?int $bps): string
    {
        if ($bps === null || $bps <= 0) return '-';
        $mbps = $bps / 1000000;
        if ($mbps >= 1000) {
            $gbps = $mbps / 1000;
            return ($gbps == floor($gbps) ? number_format($gbps, 0) : number_format($gbps, 1)) . 'G';
        }
        return number_format($mbps, 0) . 'Mb';
    }
}
