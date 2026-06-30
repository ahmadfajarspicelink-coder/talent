<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceStat;
use App\Models\InterfaceStat;
use App\Models\NetworkInterface;
use Illuminate\Support\Facades\Log;

class SnmpService
{
    // ── IF-MIB ──
    private const OID_IF_INDEX        = '1.3.6.1.2.1.2.2.1.1';
    private const OID_IF_DESCR        = '1.3.6.1.2.1.2.2.1.2';
    private const OID_IF_TYPE         = '1.3.6.1.2.1.2.2.1.3';
    private const OID_IF_SPEED        = '1.3.6.1.2.1.2.2.1.5';
    private const OID_IF_HIGH_SPEED   = '1.3.6.1.2.1.31.1.1.1.15';  // ifHighSpeed (Mbps)
    private const OID_IF_ADMIN_STATUS = '1.3.6.1.2.1.2.2.1.7';
    private const OID_IF_OPER_STATUS  = '1.3.6.1.2.1.2.2.1.8';
    private const OID_IF_IN_OCTETS    = '1.3.6.1.2.1.2.2.1.10';
    private const OID_IF_IN_ERRORS    = '1.3.6.1.2.1.2.2.1.14';
    private const OID_IF_OUT_OCTETS   = '1.3.6.1.2.1.2.2.1.16';
    private const OID_IF_OUT_ERRORS   = '1.3.6.1.2.1.2.2.1.20';
    private const OID_IF_ALIAS        = '1.3.6.1.2.1.31.1.1.1.18';

    // ── System ──
    private const OID_SYS_DESCR  = '1.3.6.1.2.1.1.1.0';
    private const OID_SYS_UPTIME = '1.3.6.1.2.1.1.3.0';
    private const OID_SYS_NAME   = '1.3.6.1.2.1.1.5.0';

    // ── HOST-RESOURCES-MIB (CPU) ──
    private const OID_HR_PROCESSOR_LOAD = '1.3.6.1.2.1.25.3.3.1.2';

    // ── HOST-RESOURCES-MIB (Memory/Storage) ──
    private const OID_HR_STORAGE_TYPE  = '1.3.6.1.2.1.25.2.3.1.2';
    private const OID_HR_STORAGE_DESCR = '1.3.6.1.2.1.25.2.3.1.3';
    private const OID_HR_STORAGE_SIZE  = '1.3.6.1.2.1.25.2.3.1.5';
    private const OID_HR_STORAGE_USED  = '1.3.6.1.2.1.25.2.3.1.6';
    private const OID_HR_STORAGE_ALLOC = '1.3.6.1.2.1.25.2.3.1.4';

    // ── Vendor-specific ──
    private const OID_MK_CPU_LOAD    = '1.3.6.1.4.1.14988.1.1.3.11.0';
    private const OID_MK_MEM_USED    = '1.3.6.1.4.1.14988.1.1.7.8.0';
    private const OID_MK_MEM_TOTAL   = '1.3.6.1.4.1.14988.1.1.7.7.0';
    private const OID_CISCO_CPU_5MIN = '1.3.6.1.4.1.9.9.109.1.1.1.1.8';
    private const OID_HW_MEM_USED    = '1.3.6.1.4.1.2011.5.25.31.1.1.1.1.7';
    private const OID_HW_MEM_TOTAL   = '1.3.6.1.4.1.2011.5.25.31.1.1.1.1.5';

    public function __construct(
        private readonly TelegramService $telegram = new TelegramService(),
    ) {}

    // ──────────────────────────────────────
    //  Core SNMP helpers
    // ──────────────────────────────────────

    public function testConnection(string $ip, string $community): bool
    {
        $val = @snmp2_get($ip, $community, self::OID_SYS_DESCR, 2000000);
        return $val !== false && !empty($this->cleanSnmpValue($val));
    }

    public function getSystemInfo(string $ip, string $community): array
    {
        return [
            'sysDescr'  => $this->cleanSnmpValue(@snmp2_get($ip, $community, self::OID_SYS_DESCR, 2000000) ?: 'unknown'),
            'sysUptime' => (int)$this->cleanSnmpValue(@snmp2_get($ip, $community, self::OID_SYS_UPTIME, 2000000) ?: '0'),
            'sysName'   => $this->cleanSnmpValue(@snmp2_get($ip, $community, self::OID_SYS_NAME, 2000000) ?: 'unknown'),
        ];
    }

    private function walkTable(string $ip, string $community, string $oid): array
    {
        $result = [];
        $data = @snmp2_real_walk($ip, $community, $oid, 2000000, 1);

        if ($data === false) return [];

        foreach ($data as $key => $value) {
            $index = substr($key, strrpos($key, '.') + 1);
            $result[(int)$index] = $this->cleanSnmpValue($value);
        }

        return $result;
    }

    /**
     * Strip SNMP type prefix from value (Windows SNMP returns "INTEGER: 1", "STRING: ether1", etc.)
     */
    private function cleanSnmpValue(mixed $value): string
    {
        $value = trim((string)$value, '" ');

        // Strip type prefix: "INTEGER: 1" → "1", "STRING: ether1" → "ether1"
        if (preg_match('/^(?:INTEGER|STRING|Gauge32|Counter32|Counter64|Timeticks|OID|IpAddress|NULL|BITS):\s*(.*)/i', $value, $m)) {
            $value = trim($m[1], '" ');
        }

        // Timeticks: "(12345) 0:02:03.45" → extract raw number
        if (preg_match('/^\((\d+)\)/', $value, $m)) {
            $value = $m[1];
        }

        return $value;
    }

    private function statusLabel(int|string|null $code): string
    {
        $code = is_numeric($code) ? (int)$code : 0;
        return match ($code) {
            1 => 'up',
            2 => 'down',
            3 => 'testing',
            default => 'unknown',
        };
    }

    // ──────────────────────────────────────
    //  CPU/Memory polling (vendor-aware)
    // ──────────────────────────────────────

    /**
     * Detect vendor from sysDescr
     */
    private function detectVendor(string $ip, string $community, ?string $hint = null): string
    {
        if ($hint) return strtolower($hint);

        $descr = strtolower($this->cleanSnmpValue(@snmp2_get($ip, $community, self::OID_SYS_DESCR, 2000000) ?: ''));

        if (str_contains($descr, 'mikrotik') || str_contains($descr, 'routeros')) return 'mikrotik';
        if (str_contains($descr, 'cisco')) return 'cisco';
        if (str_contains($descr, 'huawei') || str_contains($descr, 'hios')) return 'huawei';

        return 'generic';
    }

    /**
     * Poll CPU usage — returns percentage (0-100) or null
     */
    public function pollCpu(string $ip, string $community, ?string $vendor = null): ?int
    {
        $vendor = $this->detectVendor($ip, $community, $vendor);

        // Try vendor-specific first
        switch ($vendor) {
            case 'mikrotik':
                $val = @snmp2_get($ip, $community, self::OID_MK_CPU_LOAD, 2000000);
                if ($val !== false) return (int)$this->cleanSnmpValue($val);
                break;

            case 'cisco':
                // Walk cpmCPUTotal5min table (index varies per platform)
                $cpus = $this->walkTable($ip, $community, '1.3.6.1.4.1.9.9.109.1.1.1.1.8');
                if (!empty($cpus)) {
                    // Average all CPU entries
                    return (int)round(array_sum($cpus) / count($cpus));
                }
                break;
        }

        // Fallback: generic hrProcessorLoad
        $cpus = $this->walkTable($ip, $community, self::OID_HR_PROCESSOR_LOAD);
        if (!empty($cpus)) {
            $total = array_sum($cpus);
            return (int)round($total / count($cpus));
        }

        return null;
    }

    /**
     * Poll memory — returns ['used' => bytes, 'total' => bytes] or null
     */
    public function pollMemory(string $ip, string $community, ?string $vendor = null): ?array
    {
        $vendor = $this->detectVendor($ip, $community, $vendor);

        // Try vendor-specific first
        switch ($vendor) {
            case 'mikrotik':
                $used  = @snmp2_get($ip, $community, self::OID_MK_MEM_USED, 2000000);
                $total = @snmp2_get($ip, $community, self::OID_MK_MEM_TOTAL, 2000000);
                if ($used !== false && $total !== false) {
                    $usedClean = (int)$this->cleanSnmpValue($used);
                    $totalClean = (int)$this->cleanSnmpValue($total);
                    if ($usedClean > 0 && $totalClean > 0) {
                        return ['used' => $usedClean, 'total' => $totalClean];
                    }
                }
                break;

            case 'cisco':
                // Walk cpmCPUMemoryUsed/Free (index varies per platform)
                $usedArr  = $this->walkTable($ip, $community, '1.3.6.1.4.1.9.9.109.1.1.1.1.12');
                $freeArr  = $this->walkTable($ip, $community, '1.3.6.1.4.1.9.9.109.1.1.1.1.13');
                if (!empty($usedArr) && !empty($freeArr)) {
                    $usedKB  = array_sum($usedArr);
                    $totalKB = $usedKB + array_sum($freeArr);
                    return ['used' => (int)($usedKB * 1024), 'total' => (int)($totalKB * 1024)];
                }
                break;

            case 'huawei':
                $used  = @snmp2_get($ip, $community, self::OID_HW_MEM_USED, 2000000);
                $total = @snmp2_get($ip, $community, self::OID_HW_MEM_TOTAL, 2000000);
                if ($used !== false && $total !== false) {
                    $usedClean = (int)$this->cleanSnmpValue($used);
                    $totalClean = (int)$this->cleanSnmpValue($total);
                    if ($usedClean > 0 && $totalClean > 0) {
                        return ['used' => $usedClean, 'total' => $totalClean];
                    }
                }
                break;
        }

        // Fallback: generic hrStorageTable — find RAM/Physical Memory entry
        $types = $this->walkTable($ip, $community, self::OID_HR_STORAGE_TYPE);
        $sizes = $this->walkTable($ip, $community, self::OID_HR_STORAGE_SIZE);
        $used  = $this->walkTable($ip, $community, self::OID_HR_STORAGE_USED);
        $alloc = $this->walkTable($ip, $community, self::OID_HR_STORAGE_ALLOC);

        foreach ($types as $idx => $type) {
            // hrStorageType: .1.3.6.1.2.1.25.2.1.2 = RAM
            if (str_contains($type, '25.2.1.2') || str_contains($type, 'hrStorageRam')) {
                $allocBytes = (int)($alloc[$idx] ?? 1);
                return [
                    'used'  => (int)($used[$idx] ?? 0) * $allocBytes,
                    'total' => (int)($sizes[$idx] ?? 0) * $allocBytes,
                ];
            }
        }

        return null;
    }

    /**
     * Poll uptime timeticks
     */
    public function pollUptime(string $ip, string $community): ?int
    {
        $val = @snmp2_get($ip, $community, self::OID_SYS_UPTIME, 2000000);
        return $val !== false ? (int)$this->cleanSnmpValue($val) : null;
    }

    // ──────────────────────────────────────
    /**
     * Resolve interface speed — use ifHighSpeed (Mbps) when ifSpeed is saturated (>4.29G)
     * Returns speed in bps for storage consistency
     */
    private function resolveSpeed(int $ifSpeed, int $ifHighSpeed): int
    {
        // ifSpeed max uint32 = saturated (speed > 4.29 Gbps)
        if ($ifSpeed >= 4294967295 && $ifHighSpeed > 0) {
            return $ifHighSpeed * 1000000;  // Mbps → bps
        }
        return $ifSpeed > 0 ? $ifSpeed : ($ifHighSpeed > 0 ? $ifHighSpeed * 1000000 : 0);
    }

    //  Device polling (full)
    // ──────────────────────────────────────

    public function pollInterfaces(string $ip, string $community): array
    {
        $ifIndexes   = $this->walkTable($ip, $community, self::OID_IF_INDEX);
        $ifDescrs    = $this->walkTable($ip, $community, self::OID_IF_DESCR);
        $ifAliases   = $this->walkTable($ip, $community, self::OID_IF_ALIAS);
        $ifSpeeds    = $this->walkTable($ip, $community, self::OID_IF_SPEED);
        $ifHighSpeed = $this->walkTable($ip, $community, self::OID_IF_HIGH_SPEED);
        $ifTypes     = $this->walkTable($ip, $community, self::OID_IF_TYPE);
        $ifAdmin     = $this->walkTable($ip, $community, self::OID_IF_ADMIN_STATUS);
        $ifOper      = $this->walkTable($ip, $community, self::OID_IF_OPER_STATUS);
        $ifInOctets  = $this->walkTable($ip, $community, self::OID_IF_IN_OCTETS);
        $ifOutOctets = $this->walkTable($ip, $community, self::OID_IF_OUT_OCTETS);
        $ifInErrors  = $this->walkTable($ip, $community, self::OID_IF_IN_ERRORS);
        $ifOutErrors = $this->walkTable($ip, $community, self::OID_IF_OUT_ERRORS);

        $interfaces = [];

        foreach ($ifIndexes as $idx => $ifIndex) {
            $interfaces[$idx] = [
                'if_index'        => $ifIndex,
                'if_name'         => $ifDescrs[$idx] ?? "port{$idx}",
                'if_descr'        => $ifDescrs[$idx] ?? null,
                'if_alias'        => $ifAliases[$idx] ?? null,
                'if_speed'        => $this->resolveSpeed((int)($ifSpeeds[$idx] ?? 0), (int)($ifHighSpeed[$idx] ?? 0)),
                'if_type'         => (int)($ifTypes[$idx] ?? 0),
                'if_admin_status' => (int)($ifAdmin[$idx] ?? 0),
                'if_oper_status'  => (int)($ifOper[$idx] ?? 0),
                'if_in_octets'    => (int)($ifInOctets[$idx] ?? 0),
                'if_out_octets'   => (int)($ifOutOctets[$idx] ?? 0),
                'if_in_errors'    => (int)($ifInErrors[$idx] ?? 0),
                'if_out_errors'   => (int)($ifOutErrors[$idx] ?? 0),
            ];
        }

        return $interfaces;
    }

    /**
     * Poll everything: interfaces + CPU/memory/uptime + alerts
     */
    public function pollDevice(Device $device): array
    {
        $ip = $device->ip_address;
        $community = $device->snmp_community;
        $alerts = [];

        $startTime = microtime(true);
        $interfaces = $this->pollInterfaces($ip, $community);
        $elapsed = round(microtime(true) - $startTime, 2);

        // ── Device offline ──
        if (empty($interfaces)) {
            $wasOnline = $device->status === 'online';
            $device->update(['status' => 'offline', 'last_polled_at' => now()]);

            if ($wasOnline) {
                $this->telegram->alertDeviceOffline($device->name, $ip);
                $alerts[] = "Device {$device->name} went OFFLINE";
            }

            Log::warning("SNMP poll failed for {$device->name} ({$ip})");
            return ['interfaces' => [], 'alerts' => $alerts];
        }

        // ── Device online ──
        $wasOffline = $device->status === 'offline';

        // Poll CPU / Memory / Uptime
        $cpu = $this->pollCpu($ip, $community, $device->vendor);
        $mem = $this->pollMemory($ip, $community, $device->vendor);
        $uptime = $this->pollUptime($ip, $community);

        $device->update([
            'status'        => 'online',
            'cpu_usage'     => $cpu,
            'memory_used'   => $mem['used'] ?? null,
            'memory_total'  => $mem['total'] ?? null,
            'uptime_ticks'  => $uptime,
            'last_polled_at'=> now(),
        ]);

        // Save device stat history
        DeviceStat::create([
            'device_id'    => $device->id,
            'cpu_usage'    => $cpu,
            'memory_used'  => $mem['used'] ?? null,
            'memory_total' => $mem['total'] ?? null,
            'uptime_ticks' => $uptime,
            'polled_at'    => now(),
        ]);

        if ($wasOffline) {
            $this->telegram->alertDeviceOnline($device->name, $ip);
            $alerts[] = "Device {$device->name} is back ONLINE";
        }

        // ── CPU/Memory threshold alerts ──
        $cpuThreshold = (int) config('monitoring.alert_cpu', 80);
        $memThreshold = (int) config('monitoring.alert_memory', 80);

        if ($cpu !== null && $cpu >= $cpuThreshold) {
            $this->telegram->alertHighCpu($device->name, $ip, $cpu);
            $alerts[] = "HIGH CPU: {$device->name} → {$cpu}%";
        }

        if ($mem && isset($mem['total']) && $mem['total'] > 0) {
            $memPct = round(($mem['used'] / $mem['total']) * 100, 1);
            if ($memPct >= $memThreshold) {
                $this->telegram->alertHighMemory($device->name, $ip, $memPct);
                $alerts[] = "HIGH MEMORY: {$device->name} → {$memPct}%";
            }
        }

        // ── Interface polling + alerts ──
        $now = now();
        $results = [];

        foreach ($interfaces as $data) {
            $newStatus = $this->statusLabel($data['if_oper_status']);

            $existing = NetworkInterface::where('device_id', $device->id)
                ->where('if_index', $data['if_index'])
                ->first();

            if ($existing && $existing->if_oper_status !== $newStatus) {
                $ifAlias = $data['if_alias'] ?? $existing->if_alias;

                if ($newStatus === 'down') {
                    $this->telegram->alertPortDown($device->name, $ip, $data['if_name'], $ifAlias);
                    $alerts[] = "Port DOWN: {$device->name} → {$data['if_name']}";
                } elseif ($newStatus === 'up' && $existing->if_oper_status === 'down') {
                    $this->telegram->alertPortUp($device->name, $ip, $data['if_name'], $ifAlias);
                    $alerts[] = "Port UP: {$device->name} → {$data['if_name']}";
                }
            }

            $iface = NetworkInterface::updateOrCreate(
                ['device_id' => $device->id, 'if_index' => $data['if_index']],
                [
                    'if_name'          => $data['if_name'],
                    'if_descr'         => $data['if_descr'],
                    'if_alias'         => $data['if_alias'],
                    'if_speed'         => $data['if_speed'],
                    'if_type'          => $data['if_type'],
                    'if_admin_status'  => $this->statusLabel($data['if_admin_status']),
                    'if_oper_status'   => $newStatus,
                    'if_in_octets'     => $data['if_in_octets'],
                    'if_out_octets'    => $data['if_out_octets'],
                    'if_in_errors'     => $data['if_in_errors'],
                    'if_out_errors'    => $data['if_out_errors'],
                    'last_polled_at'   => $now,
                ]
            );

            InterfaceStat::create([
                'interface_id' => $iface->id,
                'in_octets'    => $data['if_in_octets'],
                'out_octets'   => $data['if_out_octets'],
                'in_errors'    => $data['if_in_errors'],
                'out_errors'   => $data['if_out_errors'],
                'oper_status'  => $newStatus,
                'polled_at'    => $now,
            ]);

            $results[] = [
                'name'   => $data['if_name'],
                'status' => $newStatus,
                'in'     => $data['if_in_octets'],
                'out'    => $data['if_out_octets'],
            ];
        }

        Log::info("Polled {$device->name} ({$ip}): {$elapsed}s, " . count($results) . " interfaces, CPU: {$cpu}%");

        return ['interfaces' => $results, 'alerts' => $alerts];
    }

    public function pollAll(): array
    {
        $devices = Device::all();
        $results = [];

        foreach ($devices as $device) {
            try {
                $results[$device->name] = $this->pollDevice($device);
            } catch (\Throwable $e) {
                Log::error("Error polling {$device->name}: {$e->getMessage()}");
                $results[$device->name] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
