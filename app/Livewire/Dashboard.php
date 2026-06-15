<?php

namespace App\Livewire;

use App\Models\Device;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Livewire layout for this component.
     *
     * Using a property instead of calling ->layout() on the view to avoid
     * undefined method errors from static analysis or older/livewire versions.
     */
    protected $layout = 'layouts.app';
    public $devices;
    public $totalUp = 0;
    public $totalDown = 0;
    public $totalDevices = 0;
    public $avgCpu = 0;
    public $alerts = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->devices = Device::with('interfaces')->get();
        $this->totalDevices = $this->devices->count();
        $this->totalUp = $this->devices->where('status', 'online')->count();
        $this->totalDown = $this->devices->where('status', 'offline')->count();

        $onlineDevices = $this->devices->where('status', 'online')->whereNotNull('cpu_usage');
        $this->avgCpu = $onlineDevices->count() > 0
            ? (int)round($onlineDevices->avg('cpu_usage'))
            : 0;
    }

    public function pollNow()
    {
        $snmp = new \App\Services\SnmpService();
        $results = $snmp->pollAll();

        $this->alerts = [];
        foreach ($results as $deviceName => $result) {
            if (isset($result['alerts'])) {
                $this->alerts = array_merge($this->alerts, $result['alerts']);
            }
        }

        $this->loadData();
        $this->dispatch('pollComplete');
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
