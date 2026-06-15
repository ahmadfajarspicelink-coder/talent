<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\SnmpService;
use Illuminate\Console\Command;

class PollDevices extends Command
{
    protected $signature = 'snmp:poll {--device= : Poll specific device ID}';
    protected $description = 'Poll all (or specific) SNMP devices';

    public function handle(SnmpService $snmp): int
    {
        $query = Device::query();

        if ($this->option('device')) {
            $query->where('id', $this->option('device'));
        }

        $devices = $query->get();
        $this->info("Polling {$devices->count()} device(s)...");

        foreach ($devices as $device) {
            $this->info("Polling {$device->name} ({$device->ip_address})...");

            try {
                $result = $snmp->pollDevice($device);

                // Show alerts
                foreach ($result['alerts'] ?? [] as $alert) {
                    $this->warn("  ⚠ {$alert}");
                }

                // Show interfaces
                $ifaces = $result['interfaces'] ?? [];
                if (empty($ifaces)) {
                    $this->warn("  Device OFFLINE or no interfaces");
                } else {
                    foreach ($ifaces as $iface) {
                        $status = ($iface['status'] ?? 'unknown') === 'up' ? '✅' : '❌';
                        $this->line("  {$status} {$iface['name']} — IN: {$iface['in']} B / OUT: {$iface['out']} B");
                    }
                    $this->info("Done. " . count($ifaces) . " interfaces polled.");
                }
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
