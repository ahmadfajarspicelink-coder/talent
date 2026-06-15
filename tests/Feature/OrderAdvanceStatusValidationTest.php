<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi validasi input 'status'/'target' di OrderController::advanceStatus.
 *
 * QW #2 — security hardening: tambah Rule::in validation agar input
 * invalid (string yang bukan status valid) di-reject sebelum masuk
 * canTransition() check. Cegah bypass melalui empty string atau
 * value aneh yang lolos null check.
 */
class OrderAdvanceStatusValidationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithOrderAccess(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    private function orderAt(string $status): Order
    {
        $provider = Partner::factory()->provider()->create();
        $vendor = Partner::factory()->vendor()->create();
        return Order::factory()->create([
            'status' => $status,
            'provider_id' => $provider->id,
            'vendor_id' => $vendor->id,
        ]);
    }

    /** Invalid status string harus di-reject dengan validation error, bukan di-silent pass. */
    public function test_advance_with_invalid_status_value_is_rejected(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->orderAt('Inquiry');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'HackedStatus',  // bukan status valid
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('status');
        $this->assertSame('Inquiry', $order->fresh()->status, 'Status tidak boleh berubah jika input invalid.');
    }

    /** Invalid target value harus di-reject. */
    public function test_advance_with_invalid_target_value_is_rejected(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->orderAt('Penawaran');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'target' => 'HackedTarget',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('target');
        $this->assertSame('Penawaran', $order->fresh()->status);
    }

    /** Empty/null 'status' dan 'target' tetap harus bisa fallback ke nextStatus (auto-advance). */
    public function test_advance_with_no_status_or_target_uses_next_status(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->orderAt('Inquiry');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), []);

        // Inquiry → Cek_Ketersediaan (next status per OrderStatusService::STATUSES).
        // Lolos validasi (status nullable) + canTransition().
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertSame('Cek_Ketersediaan', $order->fresh()->status);
    }

    /** Status yang valid tapi bukan successor langsung harus di-reject (canTransition returns redirect with flash). */
    public function test_advance_skipping_stages_is_rejected(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->orderAt('Inquiry');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'PO_Provider',  // valid stage tapi skip Cek_Ketersediaan & Penawaran
            ]);

        // canTransition() reject → redirect back with error flash, bukan validation error.
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertSame('Inquiry', $order->fresh()->status);
    }
}
