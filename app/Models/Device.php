<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'ip_address', 'snmp_community', 'snmp_version',
        'vendor', 'model', 'location', 'status',
        'cpu_usage', 'memory_used', 'memory_total', 'uptime_ticks',
        'last_polled_at', 'port_map_filter',
    ];

    protected $hidden = [
        'snmp_community',  // QW #9: M-04 — sembunyikan saat serialization
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
        'port_map_filter' => 'array',
    ];

    /**
     * Encrypt snmp_community at-rest (M-04 — QW #9).
     *
     * Field di database tetap `snmp_community` (varchar), tapi nilai yang
     * disimpan adalah `Crypt::encryptString($value)`. Akses via property
     * `$device->snmp_community` otomatis decrypt. SnmpService pakai
     * accessor transparan — tidak perlu ubah service code.
     */
    public function setSnmpCommunityAttribute(string $value): void
    {
        // Jika sudah ter-encrypt (mis. reload dari DB), jangan double-encrypt.
        // Crypt::encryptString menghasilkan base64 dengan prefix `eyJ` (JSON-encoded
        // dari `{"iv":"...","value":"...","mac":"...","tag":"..."}`).
        if (str_starts_with($value, 'eyJ') && str_contains($value, '"iv"')) {
            $this->attributes['snmp_community'] = $value;

            return;
        }

        $this->attributes['snmp_community'] = Crypt::encryptString($value);
    }

    public function getSnmpCommunityAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Legacy/plaintext value (data migrasi lama): kembalikan apa adanya.
            // Logger untuk audit agar bisa migrasi plaintext → encrypted nanti.
            \Illuminate\Support\Facades\Log::warning("Device snmp_community decrypt failed (likely legacy plaintext): " . $e->getMessage());

            return $value;
        }
    }

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
