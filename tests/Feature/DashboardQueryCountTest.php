<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Device;
use App\Models\NetworkInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifikasi Dashboard Livewire tidak punya N+1.
 * Quick Win #5 (lanjutan) — regression guard untuk eager loading.
 */
class DashboardQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loadData_does_not_trigger_n_plus_one(): void
    {
        // Setup: 10 devices dengan 3 interfaces each
        foreach (range(1, 10) as $i) {
            $d = Device::factory()->online()->create();
            NetworkInterface::factory()->for($d)->up()->count(3)->create();
        }

        // Hitung query untuk 10 device pertama
        DB::flushQueryLog();
        DB::enableQueryLog();

        $component = Livewire::test(Dashboard::class);
        $component->call('loadData');

        $queryCount = count(DB::getQueryLog());

        // 10 devices × 3 interfaces = 30 interface rows
        // Query budget: 1 devices query + 1 eager interfaces query = 2 queries
        // Allow 5 untuk overhead (session, csrf, dll)
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "Dashboard loadData() pakai {$queryCount} queries — N+1 detected (max 5 allowed)"
        );
    }

    public function test_dashboard_loadData_counts_correctly(): void
    {
        // Setup: 5 online + 2 offline
        foreach (range(1, 5) as $i) {
            Device::factory()->online()->create();
        }
        foreach (range(1, 2) as $i) {
            Device::factory()->offline()->create();
        }

        $component = Livewire::test(Dashboard::class);
        $component->assertSet('totalDevices', 7)
            ->assertSet('totalUp', 5)
            ->assertSet('totalDown', 2);
    }

    public function test_dashboard_handles_zero_devices(): void
    {
        $component = Livewire::test(Dashboard::class);

        $component->assertSet('totalDevices', 0)
            ->assertSet('totalUp', 0)
            ->assertSet('totalDown', 0)
            ->assertSet('avgCpu', 0);
    }
}
