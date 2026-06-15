<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\DeviceStat;
use App\Models\NetworkInterface;
use Livewire\Component;

class DeviceDetail extends Component
{
    public Device $device;
    public $interfaces;
    public $selectedInterface = null;
    public $chartData = [];
    public $cpuHistory = [];
    public $memHistory = [];
    public $portColumns = 8;
    public $showAllPorts = false;
    public $showPortSelector = false;
    public $selectedPorts = [];

    // Non-physical interface name patterns to exclude
    private static $nonPhysicalPatterns = [
        'vlan', 'loopback', 'lo', 'port-channel', 'portchannel', 'po',
        'tunnel', 'gre', 'l2tp', 'pptp', 'bridge', 'mgmt', 'management',
        'null', 'virtual', 'nve', 'overlay', 'control',
    ];

    public function togglePortFilter()
    {
        $this->showAllPorts = !$this->showAllPorts;
    }

    public function togglePortSelector()
    {
        $this->showPortSelector = !$this->showPortSelector;
    }

    public function togglePort($ifName)
    {
        $idx = array_search($ifName, $this->selectedPorts);
        if ($idx !== false) {
            unset($this->selectedPorts[$idx]);
            $this->selectedPorts = array_values($this->selectedPorts);
        } else {
            $this->selectedPorts[] = $ifName;
        }
    }

    public function savePortFilter()
    {
        $this->device->update([
            'port_map_filter' => !empty($this->selectedPorts) ? $this->selectedPorts : null,
        ]);
        $this->showPortSelector = false;
    }

    public function clearPortFilter()
    {
        $this->selectedPorts = [];
        $this->device->update(['port_map_filter' => null]);
        $this->showPortSelector = false;
    }

    public function mount($id)
    {
        $this->device = Device::findOrFail($id);
        $this->loadInterfaces();
        $this->loadDeviceHistory();

        // Load saved port filter
        $saved = $this->device->port_map_filter;
        if (!empty($saved)) {
            $this->selectedPorts = $saved;
            $this->showAllPorts = true; // when custom filter, show all toggle
        }
    }

    /**
     * Build port map grid: zigzag layout like switch front panel
     * Top row = odd indices, bottom row = even indices
     */
    public function getPortMapProperty(): array
    {
        $ifaces = $this->interfaces->values();
        if ($ifaces->isEmpty()) return [];

        // Priority 1: Custom port selection
        if (!empty($this->selectedPorts)) {
            $selected = $this->selectedPorts;
            $ifaces = $ifaces->filter(function ($iface) use ($selected) {
                return in_array($iface->if_name, $selected);
            })->values();
        }
        // Priority 2: Physical only filter
        elseif (!$this->showAllPorts) {
            $ifaces = $ifaces->filter(function ($iface) {
                $name = strtolower($iface->if_name ?? '');
                foreach (self::$nonPhysicalPatterns as $pattern) {
                    if (str_contains($name, $pattern)) {
                        return false;
                    }
                }
                return true;
            })->values();
        }

        $cols = $this->portColumns;
        $total = $ifaces->count();

        // Build grid rows: top row (odd), bottom row (even)
        $topRow = [];
        $bottomRow = [];

        for ($i = 0; $i < $total; $i++) {
            $iface = $ifaces[$i];
            $slot = [
                'id' => $iface->id,
                'index' => $iface->if_index,
                'name' => $iface->if_name ?? 'Port ' . $iface->if_index,
                'status' => $iface->if_oper_status ?? 'unknown',
                'admin' => $iface->if_admin_status ?? 'unknown',
                'speed' => $iface->if_speed,
            ];

            // Zigzag: 1=top,2=bottom,3=top,4=bottom... (physical port pairs)
            $physPort = $i + 1;
            if ($physPort % 2 === 1) {
                $topRow[] = $slot;
            } else {
                $bottomRow[] = $slot;
            }
        }

        return ['top' => $topRow, 'bottom' => $bottomRow, 'cols' => $cols];
    }

    public function loadInterfaces()
    {
        $this->interfaces = $this->device->interfaces()
            ->orderBy('if_index')
            ->get();
    }

    public function loadDeviceHistory()
    {
        $stats = DeviceStat::where('device_id', $this->device->id)
            ->orderByDesc('polled_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $this->cpuHistory = $stats->map(fn($s) => [
            'time' => $s->polled_at->format('H:i'),
            'value' => $s->cpu_usage,
        ])->toArray();

        $this->memHistory = $stats->map(fn($s) => [
            'time' => $s->polled_at->format('H:i'),
            'used' => $s->memory_used,
            'total' => $s->memory_total,
            'percent' => $s->memory_total > 0 ? round(($s->memory_used / $s->memory_total) * 100, 1) : 0,
        ])->toArray();
    }

    public function selectInterface($interfaceId)
    {
        $this->selectedInterface = $interfaceId;

        $iface = NetworkInterface::find($interfaceId);
        if ($iface) {
            $this->chartData = $iface->stats()
                ->orderByDesc('polled_at')
                ->limit(50)
                ->get()
                ->map(fn($s) => [
                    'time' => $s->polled_at->format('H:i'),
                    'in' => $s->in_octets,
                    'out' => $s->out_octets,
                ])
                ->reverse()
                ->values()
                ->toArray();
        }
    }

    public function pollDevice()
    {
        try {
            $snmp = new \App\Services\SnmpService();
            $snmp->pollDevice($this->device);
            $this->device->refresh();
            $this->loadInterfaces();
            $this->loadDeviceHistory();
        } catch (\Throwable $e) {
            session()->flash('error', 'Poll error: ' . $e->getMessage());
        }

        if ($this->selectedInterface) {
            $this->selectInterface($this->selectedInterface);
        }
    }

    public function render()
    {
        return view('livewire.device-detail')->layout('layouts.app');
    }
}
