<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hapus Order hanya boleh oleh Admin (Staff ditolak).
 */
class OrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        return Order::factory()->create([
            'client_id' => Client::factory(),
            'provider_id' => Partner::factory()->provider(),
            'vendor_id' => Partner::factory()->vendor(),
        ]);
    }

    public function test_admin_can_delete_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order();

        $response = $this->actingAs($admin)->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_staff_cannot_delete_order(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $order = $this->order();

        $response = $this->actingAs($staff)->delete(route('orders.destroy', $order));

        $response->assertStatus(403);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
