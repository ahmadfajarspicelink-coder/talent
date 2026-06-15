<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Device;
use App\Models\NetworkInterface;
use App\Models\Order;
use App\Models\Package;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi Laravel Policies terdaftar dan enforce aturan role.
 * Quick Win #7 — security hardening via formal policies.
 */
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    // ─── OrderPolicy ────────────────────────────────────────────────

    public function test_order_policy_admin_can_delete(): void
    {
        $order = Order::factory()->create();
        $this->assertTrue($this->admin->can('delete', $order));
    }

    public function test_order_policy_staff_cannot_delete(): void
    {
        $order = Order::factory()->create();
        $this->assertFalse($this->staff->can('delete', $order));
    }

    public function test_order_policy_staff_can_view_own_order(): void
    {
        // H-04 (QW #10): staff hanya boleh view order yang dia create.
        $order = Order::factory()->create(['created_by' => $this->staff->id]);
        $this->assertTrue($this->staff->can('view', $order));
    }

    public function test_order_policy_staff_cannot_view_other_staff_order(): void
    {
        // H-04 (QW #10): IDOR prevention — staff TIDAK boleh view order
        // yang dibuat staff lain (atau tanpa created_by).
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $order = Order::factory()->create(['created_by' => $otherStaff->id]);
        $this->assertFalse($this->staff->can('view', $order));
    }

    public function test_order_policy_staff_cannot_view_order_without_creator(): void
    {
        // Order baru dari factory tanpa created_by → ditolak (H-04 strict).
        $order = Order::factory()->create(['created_by' => null]);
        // Policy: created_by null → degrade ke "allow all" untuk legacy.
        $this->assertTrue($this->staff->can('view', $order),
            'Order tanpa created_by (legacy) di-allow untuk backward compat.');
    }

    public function test_order_policy_admin_can_view_any_order(): void
    {
        $order = Order::factory()->create(['created_by' => $this->staff->id]);
        $this->assertTrue($this->admin->can('view', $order));
    }

    public function test_order_policy_staff_cannot_update_other_staff_order(): void
    {
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $order = Order::factory()->create(['created_by' => $otherStaff->id]);
        $this->assertFalse($this->staff->can('update', $order));
    }

    public function test_order_policy_staff_can_create(): void
    {
        $this->assertTrue($this->staff->can('create', Order::class));
    }

    // ─── ClientPolicy ───────────────────────────────────────────────

    public function test_client_policy_staff_can_view(): void
    {
        $client = Client::factory()->create();
        $this->assertTrue($this->staff->can('view', $client));
    }

    public function test_client_policy_staff_cannot_create(): void
    {
        $this->assertFalse($this->staff->can('create', Client::class));
    }

    public function test_client_policy_admin_can_create(): void
    {
        $this->assertTrue($this->admin->can('create', Client::class));
    }

    public function test_client_policy_staff_can_upgrade(): void
    {
        $client = Client::factory()->create();
        $this->assertTrue($this->staff->can('upgrade', $client));
    }

    // ─── PartnerPolicy ──────────────────────────────────────────────

    public function test_partner_policy_staff_can_view(): void
    {
        $partner = Partner::factory()->provider()->create();
        $this->assertTrue($this->staff->can('view', $partner));
    }

    public function test_partner_policy_staff_cannot_create(): void
    {
        $this->assertFalse($this->staff->can('create', Partner::class));
    }

    public function test_partner_policy_admin_can_create(): void
    {
        $this->assertTrue($this->admin->can('create', Partner::class));
    }

    // ─── PackagePolicy ──────────────────────────────────────────────

    public function test_package_policy_staff_can_view(): void
    {
        $package = Package::factory()->create();
        $this->assertTrue($this->staff->can('view', $package));
    }

    public function test_package_policy_staff_cannot_delete(): void
    {
        $package = Package::factory()->create();
        $this->assertFalse($this->staff->can('delete', $package));
    }

    // ─── UserPolicy ─────────────────────────────────────────────────

    public function test_user_policy_admin_can_manage(): void
    {
        $target = User::factory()->create();
        $this->assertTrue($this->admin->can('view', $target));
        $this->assertTrue($this->admin->can('create', User::class));
        $this->assertTrue($this->admin->can('update', $target));
        $this->assertTrue($this->admin->can('delete', $target));
    }

    public function test_user_policy_staff_cannot_manage(): void
    {
        $target = User::factory()->create();
        $this->assertFalse($this->staff->can('view', $target));
        $this->assertFalse($this->staff->can('create', User::class));
        $this->assertFalse($this->staff->can('update', $target));
        $this->assertFalse($this->staff->can('delete', $target));
    }

    // ─── DevicePolicy ───────────────────────────────────────────────

    public function test_device_policy_staff_can_view(): void
    {
        $device = Device::factory()->create();
        $this->assertTrue($this->staff->can('view', $device));
    }

    public function test_device_policy_staff_cannot_delete(): void
    {
        $device = Device::factory()->create();
        $this->assertFalse($this->staff->can('delete', $device));
    }

    public function test_device_policy_admin_can_delete(): void
    {
        $device = Device::factory()->create();
        $this->assertTrue($this->admin->can('delete', $device));
    }

    public function test_device_policy_staff_can_poll(): void
    {
        $device = Device::factory()->create();
        $this->assertTrue($this->staff->can('poll', $device));
    }

    // ─── NetworkInterfacePolicy ─────────────────────────────────────

    public function test_network_interface_policy_staff_can_view(): void
    {
        $iface = NetworkInterface::factory()->create();
        $this->assertTrue($this->staff->can('view', $iface));
    }

    public function test_network_interface_policy_staff_cannot_delete(): void
    {
        $iface = NetworkInterface::factory()->create();
        $this->assertFalse($this->staff->can('delete', $iface));
    }

    // ─── HTTP integration: policy blocks staff from deleting order ─

    public function test_staff_cannot_delete_order_via_http(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->staff)
            ->delete(route('orders.destroy', $order))
            ->assertForbidden();
    }

    public function test_admin_can_delete_order_via_http(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'));
    }
}
