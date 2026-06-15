<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\NetworkInterface;
use App\Models\InterfaceStat;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TopTraffic extends Component
{
    public $devices;
    public $selectedDevice = 'all';
    public $topCount = 10;
    public $sortBy = 'total'; // total, inbound, outbound, rate
    public $topInterfaces = [];

    public function mount()
    {
        $this->devices = Device::orderBy('name')->get();
        $this->calculateTopTraffic();
    }

    public function updatedSelectedDevice()
    {
        $this->calculateTopTraffic();
    }

    public function updatedTopCount()
    {
        $this->calculateTopTraffic();
    }

    public function updatedSortBy()
    {
        $this->calculateTopTraffic();
    }

    public function calculateTopTraffic()
    {
        // Get the 2 most recent polled_at timestamps per interface to calculate rate
        $query = NetworkInterface::query()
            ->join('devices', 'devices.id', '=', 'interfaces.device_id')
            ->where('interfaces.if_oper_status', 'up')
            ->where('interfaces.if_in_octets', '>', 0);

        if ($this->selectedDevice !== 'all') {
            $query->where('interfaces.device_id', $this->selectedDevice);
        }

        $interfaces = $query->select(
            'interfaces.id',
            'interfaces.if_name',
            'interfaces.if_alias',
            'interfaces.if_index',
            'interfaces.if_in_octets',
            'interfaces.if_out_octets',
            'interfaces.if_speed',
            'interfaces.last_polled_at',
            'devices.name as device_name',
            'devices.ip_address',
        )->get();

        if ($interfaces->isEmpty()) {
            $this->topInterfaces = [];
            return;
        }

        // N+1 fix: ambil 2 stats terakhir untuk SEMUA interface dalam 1 query,
        // bukan 1 query per interface di dalam loop. Sebelumnya: O(N+1) queries.
        $interfaceIds = $interfaces->pluck('id');

        $latestStats = InterfaceStat::query()
            ->select('interface_id', 'polled_at', 'in_octets', 'out_octets')
            ->whereIn('interface_id', $interfaceIds)
            ->orderBy('interface_id')
            ->orderByDesc('polled_at')
            ->get()
            ->groupBy('interface_id')
            ->map(fn ($group) => $group->take(2)->values());

        $results = [];

        foreach ($interfaces as $iface) {
            $stats = $latestStats->get($iface->id, collect());

            $rateIn = 0;
            $rateOut = 0;

            if ($stats->count() >= 2) {
                $current = $stats[0];
                $previous = $stats[1];

                // Carbon 3 (Laravel 12) mengubah default diffInSeconds menjadi signed
                // — bisa negatif tergantung urutan operand. Pakai abs() untuk konsisten.
                $timeDiff = abs($current->polled_at->diffInSeconds($previous->polled_at));

                if ($timeDiff > 0) {
                    // Handle counter wrap (32-bit counter overflow)
                    $deltaIn = $current->in_octets >= $previous->in_octets
                        ? $current->in_octets - $previous->in_octets
                        : (0xFFFFFFFF - $previous->in_octets + $current->in_octets);

                    $deltaOut = $current->out_octets >= $previous->out_octets
                        ? $current->out_octets - $previous->out_octets
                        : (0xFFFFFFFF - $previous->out_octets + $current->out_octets);

                    $rateIn = $deltaIn / $timeDiff;   // bytes/sec
                    $rateOut = $deltaOut / $timeDiff;
                }
            }

            $totalBytes = $iface->if_in_octets + $iface->if_out_octets;

            $results[] = [
                'id'            => $iface->id,
                'device_name'   => $iface->device_name,
                'ip'            => $iface->ip_address,
                'if_name'       => $iface->if_name,
                'if_alias'      => $iface->if_alias,
                'if_index'      => $iface->if_index,
                'in_octets'     => $iface->if_in_octets,
                'out_octets'    => $iface->if_out_octets,
                'total_octets'  => $totalBytes,
                'rate_in'       => $rateIn,       // bytes/sec
                'rate_out'      => $rateOut,       // bytes/sec
                'rate_total'    => $rateIn + $rateOut,
                'speed'         => $iface->if_speed,
                'last_polled'   => $iface->last_polled_at,
            ];
        }

        // Sort
        $sortKey = match ($this->sortBy) {
            'inbound'  => 'rate_in',
            'outbound' => 'rate_out',
            'rate'     => 'rate_total',
            default    => 'total_octets',
        };

        usort($results, fn($a, $b) => $b[$sortKey] <=> $a[$sortKey]);

        $this->topInterfaces = array_slice($results, 0, $this->topCount);
    }

    /**
     * Format bytes/sec to human readable
     */
    public static function formatRate(float $bytesPerSec): string
    {
        if ($bytesPerSec < 1024) return round($bytesPerSec, 1) . ' B/s';
        if ($bytesPerSec < 1048576) return round($bytesPerSec / 1024, 1) . ' KB/s';
        if ($bytesPerSec < 1073741824) return round($bytesPerSec / 1048576, 2) . ' MB/s';
        return round($bytesPerSec / 1073741824, 2) . ' GB/s';
    }

    /**
     * Format bytes to human readable
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes < 1099511627776) return round($bytes / 1073741824, 2) . ' GB';
        return round($bytes / 1099511627776, 2) . ' TB';
    }

    /**
     * Calculate utilization % against interface speed
     */
    public static function utilization(float $rate, int $speed): float
    {
        if ($speed <= 0) return 0;
        return round(($rate * 8 / $speed) * 100, 1); // bits/sec vs speed in bps
    }

    public function render()
    {
        return view('livewire.top-traffic')->layout('layouts.app');
    }
}
