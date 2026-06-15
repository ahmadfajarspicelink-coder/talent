<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderListRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_order_renders_its_own_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $client = Client::factory()->create();
        $provider = Partner::factory()->provider()->create();
        $vendor = Partner::factory()->vendor()->create();

        $orders = [];
        for ($i = 0; $i < 3; $i++) {
            $orders[] = Order::factory()->create([
                'client_id' => $client->id,
                'provider_id' => $provider->id,
                'vendor_id' => $vendor->id,
                'order_number' => 'ORD-00000'.($i + 1),
            ]);
        }

        $html = $this->actingAs($admin)->get('/orders')->getContent();

        // Setiap nomor order harus muncul.
        foreach (['ORD-000001', 'ORD-000002', 'ORD-000003'] as $no) {
            $this->assertStringContainsString($no, $html);
        }

        // Hitung baris <tr> di dalam tbody. Header punya 1 tr; 3 order = 3 tr.
        $bodyStart = strpos($html, '<tbody');
        $bodyEnd = strpos($html, '</tbody>');
        $tbody = substr($html, $bodyStart, $bodyEnd - $bodyStart);
        $rowCount = substr_count($tbody, '<tr');

        $this->assertSame(3, $rowCount, 'Seharusnya ada 3 baris order terpisah.');
    }
}
