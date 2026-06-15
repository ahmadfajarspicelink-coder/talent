<?php

namespace App\Livewire;

use App\Models\Device;
use App\Services\SnmpService;
use Livewire\Component;

class DeviceManager extends Component
{
    public $devices;
    public $showForm = false;
    public $editId = null;

    // Form fields
    public $name = '';
    public $ip_address = '';
    public $snmp_community = 'public';
    public $snmp_version = '2c';
    public $vendor = '';
    public $model = '';
    public $location = '';

    // Test result
    public $testResult = null;

    protected $rules = [
        'name'           => 'required|string|max:255',
        'ip_address'     => 'required|ip',
        'snmp_community' => 'required|string|max:100',
        'snmp_version'   => 'required|in:1,2c,3',
        'vendor'         => 'nullable|string|max:100',
        'model'          => 'nullable|string|max:100',
        'location'       => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->loadDevices();
    }

    public function loadDevices()
    {
        $this->devices = Device::withCount('interfaces')->orderBy('name')->get();
    }

    public function showAddForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editDevice($id)
    {
        $device = Device::findOrFail($id);
        $this->editId = $id;
        $this->name = $device->name;
        $this->ip_address = $device->ip_address;
        $this->snmp_community = $device->snmp_community;
        $this->snmp_version = $device->snmp_version;
        $this->vendor = $device->vendor ?? '';
        $this->model = $device->model ?? '';
        $this->location = $device->location ?? '';
        $this->showForm = true;
        $this->testResult = null;
    }

    public function saveDevice()
    {
        $this->validate();

        if ($this->editId) {
            $device = Device::findOrFail($this->editId);
            $device->update([
                'name'           => $this->name,
                'ip_address'     => $this->ip_address,
                'snmp_community' => $this->snmp_community,
                'snmp_version'   => $this->snmp_version,
                'vendor'         => $this->vendor ?: null,
                'model'          => $this->model ?: null,
                'location'       => $this->location ?: null,
            ]);
            session()->flash('message', "Device '{$this->name}' updated.");
        } else {
            // Check duplicate IP
            if (Device::where('ip_address', $this->ip_address)->exists()) {
                $this->addError('ip_address', 'IP sudah terdaftar.');
                return;
            }

            Device::create([
                'name'           => $this->name,
                'ip_address'     => $this->ip_address,
                'snmp_community' => $this->snmp_community,
                'snmp_version'   => $this->snmp_version,
                'vendor'         => $this->vendor ?: null,
                'model'          => $this->model ?: null,
                'location'       => $this->location ?: null,
                'status'         => 'unknown',
            ]);
            session()->flash('message', "Device '{$this->name}' ditambahkan.");
        }

        $this->resetForm();
        $this->loadDevices();
    }

    public function deleteDevice($id)
    {
        $device = Device::findOrFail($id);
        $name = $device->name;
        $device->delete();
        $this->loadDevices();
        session()->flash('message', "Device '{$name}' dihapus.");
    }

    public function testConnection()
    {
        $this->validate(['ip_address' => 'required|ip', 'snmp_community' => 'required|string']);

        $snmp = new SnmpService();
        $ok = $snmp->testConnection($this->ip_address, $this->snmp_community);

        if ($ok) {
            $info = $snmp->getSystemInfo($this->ip_address, $this->snmp_community);
            $this->testResult = [
                'success' => true,
                'sysDescr' => $info['sysDescr'],
                'sysName' => $info['sysName'],
            ];

            // Auto-fill name if empty
            if (empty($this->name) && $info['sysName'] !== 'unknown') {
                $this->name = $info['sysName'];
            }

            // Auto-detect vendor
            if (empty($this->vendor)) {
                $this->vendor = $this->detectVendor($info['sysDescr']);
            }
        } else {
            $this->testResult = [
                'success' => false,
                'message' => 'SNMP connection failed. Check IP and community string.',
            ];
        }
    }

    private function detectVendor(string $sysDescr): string
    {
        $lower = strtolower($sysDescr);
        $vendors = config('vendors.vendors', []);

        foreach ($vendors as $key => $vendor) {
            if ($key === 'generic') continue;
            foreach ($vendor['detect'] as $keyword) {
                if (str_contains($lower, strtolower($keyword))) {
                    return $key;
                }
            }
        }

        return 'generic';
    }

    public function cancelForm()
    {
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->showForm = false;
        $this->editId = null;
        $this->name = '';
        $this->ip_address = '';
        $this->snmp_community = 'public';
        $this->snmp_version = '2c';
        $this->vendor = '';
        $this->model = '';
        $this->location = '';
        $this->testResult = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.device-manager')->layout('layouts.app');
    }
}
