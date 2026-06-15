<?php

namespace Tests\Feature;

use App\Livewire\TopTraffic;
use App\Models\Device;
use App\Models\InterfaceStat;
use App\Models\NetworkInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test untuk N+1 query fix di TopTraffic Livewire component.
 *
 * Sebelumnya: 1 query untuk list interfaces + N query (satu per interface)
 * untuk ambil 2 stats terakhir = O(N+1).
 *
 * Sekarang: 1 query untuk list interfaces + 1 query batch untuk semua stats
 * = total 2 query, tidak peduli berapa jumlah interface.
 *
 * Feature: N+1 query optimization, TopTraffic calculateTopTraffic
 */
class TopTrafficQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_top_traffic_does_not_trigger_n_plus_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Setup: 5 devices, masing-masing 2 interface = 10 interface aktif
        $devices = Device::factory()->count(5)->create();
        $interfaces = collect();
        foreach ($devices as $device) {
            for ($i = 1; $i <= 2; $i++) {
                $interfaces->push(NetworkInterface::create([
                    'device_id' => $device->id,
                    'if_index' => $i,
                    'if_name' => "eth{$i}",
                    'if_alias' => "Port {$i}",
                    'if_oper_status' => 'up',
                    'if_in_octets' => 1000000 * $i,
                    'if_out_octets' => 500000 * $i,
                    'if_speed' => 1000000000,
                    'last_polled_at' => now(),
                ]));
            }
        }

        // 3 stats per interface (cukup untuk test "ambil 2 terakhir")
        foreach ($interfaces as $iface) {
            for ($t = 3; $t >= 1; $t--) {
                InterfaceStat::create([
                    'interface_id' => $iface->id,
                    'polled_at' => now()->subMinutes($t * 5),
                    'in_octets' => $iface->if_in_octets * $t,
                    'out_octets' => $iface->if_out_octets * $t,
                ]);
            }
        }

        // Act: jalankan calculateTopTraffic sambil hitung query
        $component = new TopTraffic();
        $component->selectedDevice = 'all';
        $component->topCount = 10;
        $component->sortBy = 'total';

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->calculateTopTraffic();
        $queries = DB::getQueryLog();

        // Assert: max 3 query (interfaces+join, stats batch, count devices saat mount).
        // Sebelumnya: 10 interface = 11 query. Sekarang harus <= 3.
        $this->assertLessThanOrEqual(
            3,
            count($queries),
            'N+1 regression: calculateTopTraffic seharusnya <= 3 query, dapat '.count($queries).'. Queries: '.PHP_EOL.
            collect($queries)->pluck('query')->implode(PHP_EOL),
        );

        // Sanity: hasil harus berisi semua 10 interface
        $this->assertCount(10, $component->topInterfaces);

        // Setiap result harus punya if_name (join device berhasil)
        foreach ($component->topInterfaces as $result) {
            $this->assertNotEmpty($result['if_name']);
            $this->assertNotEmpty($result['device_name']);
        }
    }

    public function test_empty_interfaces_returns_empty_without_queries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Tidak ada device/interface sama sekali
        $component = new TopTraffic();
        $component->selectedDevice = 'all';
        $component->topCount = 10;
        $component->sortBy = 'total';

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->calculateTopTraffic();
        $queries = DB::getQueryLog();

        // Optimasi: kalau kosong, jangan query stats sama sekali.
        // Hanya 1 query (interfaces+join) yang jalan.
        $this->assertLessThanOrEqual(
            1,
            count($queries),
            'Empty case seharusnya tidak query stats, dapat '.count($queries).' query.',
        );

        $this->assertSame([], $component->topInterfaces);
    }

    public function test_single_interface_rate_calculation_still_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $device = Device::factory()->create();
        $iface = NetworkInterface::create([
            'device_id' => $device->id,
            'if_index' => 1,
            'if_name' => 'eth0',
            'if_oper_status' => 'up',
            'if_in_octets' => 1000000,
            'if_out_octets' => 500000,
            'if_speed' => 1000000000,
            'last_polled_at' => now(),
        ]);

        // 2 stats dengan delta 100MB dalam 60 detik = ~1.6 MB/s
        InterfaceStat::create([
            'interface_id' => $iface->id,
            'polled_at' => now()->subMinutes(1),
            'in_octets' => 0,
            'out_octets' => 0,
        ]);
        InterfaceStat::create([
            'interface_id' => $iface->id,
            'polled_at' => now(),
            'in_octets' => 100 * 1024 * 1024,
            'out_octets' => 50 * 1024 * 1024,
        ]);

        $component = new TopTraffic();
        $component->selectedDevice = 'all';
        $component->topCount = 10;
        $component->sortBy = 'total';
        $component->calculateTopTraffic();

        $this->assertCount(1, $component->topInterfaces);
        $result = $component->topInterfaces[0];

        // Debug: cek apakah stats terambil dengan benar
        $statsCount = InterfaceStat::where('interface_id', $iface->id)->count();
        $this->assertSame(2, $statsCount, 'Should have 2 InterfaceStat rows');

        // 100 MiB (104,857,600 bytes) dalam 60s = 1,747,626.66 bytes/sec
        $this->assertEqualsWithDelta(1747626.66, $result['rate_in'], 1);
        $this->assertEqualsWithDelta(873813.33, $result['rate_out'], 1);
    }
}
