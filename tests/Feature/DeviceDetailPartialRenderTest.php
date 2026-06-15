<?php

namespace Tests\Feature;

use App\Livewire\DeviceDetail;
use App\Models\Device;
use App\Models\NetworkInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifikasi bahwa hasil refactor partials tidak break Livewire render.
 * Setiap partial harus ter-include dengan benar dan tidak ada undefined
 * variable / missing @include.
 */
class DeviceDetailPartialRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_detail_component_renders_all_sections(): void
    {
        $device = Device::factory()->online()->create([
            'name' => 'TEST-SWITCH-01',
            'ip_address' => '10.0.0.1',
        ]);

        // Butuh interface supaya port map partial render (jika kosong, di-@if-skip)
        NetworkInterface::factory()->for($device)->up()->count(4)->create();

        Livewire::test(DeviceDetail::class, ['id' => $device->id])
            ->assertSuccessful()
            // Header partial
            ->assertSee('TEST-SWITCH-01')
            ->assertSee('10.0.0.1')
            ->assertSee('Online')
            ->assertSee('Poll')
            // Metrics partial — label
            ->assertSee('CPU Usage')
            ->assertSee('Memory Usage')
            ->assertSee('Uptime')
            // Port map partial
            ->assertSee('Port Map')
            // Interfaces table partial
            ->assertSee('Interfaces (')
            ->assertSee('Idx')
            ->assertSee('Name')
            ->assertSee('Status')
            ->assertSee('Errors');
    }

    public function test_device_detail_does_not_throw_with_minimal_data(): void
    {
        $device = Device::factory()->offline()->create();

        Livewire::test(DeviceDetail::class, ['id' => $device->id])
            ->assertSuccessful()
            ->assertSee('Offline');
    }

    public function test_partials_exist_as_files(): void
    {
        $partials = [
            'livewire.partials.device-detail._dd-header',
            'livewire.partials.device-detail._dd-metrics',
            'livewire.partials.device-detail._dd-port-map',
            'livewire.partials.device-detail._dd-port-slot',
            'livewire.partials.device-detail._dd-port-selector',
            'livewire.partials.device-detail._dd-history-charts',
            'livewire.partials.device-detail._dd-interfaces-table',
            'livewire.partials.device-detail._dd-traffic-chart',
        ];

        foreach ($partials as $view) {
            $this->assertTrue(
                view()->exists($view),
                "Partial view [{$view}] harus ada. Refactor device-detail."
            );
        }
    }

    public function test_main_view_size_is_small_enough(): void
    {
        $path = resource_path('views/livewire/device-detail.blade.php');
        $this->assertFileExists($path);

        $loc = count(file($path, FILE_IGNORE_NEW_LINES));
        $this->assertLessThan(
            80,
            $loc,
            "device-detail.blade.php harusnya < 80 LOC setelah refactor (saat ini: {$loc})."
        );
    }
}
