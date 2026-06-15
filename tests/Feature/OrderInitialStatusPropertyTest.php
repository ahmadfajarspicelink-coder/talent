<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Partner;
use App\Models\User;
use App\Services\OrderStatusService;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test untuk status awal Order baru (menyentuh DB + route HTTP).
 *
 * Pembuatan Order dilakukan lewat OrderController@store yang selalu menetapkan
 * Status_Order awal ke tahap pertama OrderStatusService::STATUSES, yaitu
 * 'Inquiry', terlepas dari input harga. Test ini mengeksekusi alur nyata
 * (POST /orders sebagai pengguna ber-akses Modul_Order) sehingga memakai
 * Laravel TestCase + RefreshDatabase.
 *
 * Feature: talent-secreet-isp-broker, Property 5: Order baru selalu berstatus awal Inquiry
 *
 * Validates: Requirements 5.1
 */
class OrderInitialStatusPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 5: Untuk Order baru yang valid (memiliki Client, Provider, dan
     * Vendor) dengan kombinasi harga acak (termasuk null/0), status awal yang
     * tersimpan selalu 'Inquiry' dan Persentase_Progress awalnya 0%.
     *
     * Generator: empat komponen harga non-negatif acak (0..10_000_000) plus
     * sebuah bitmask (0..15) yang menentukan komponen harga mana yang dikirim
     * sebagai null. Setiap iterasi membuat Client/Provider/Vendor sendiri,
     * mengirim POST /orders sebagai pengguna ber-akses order, lalu memverifikasi
     * Order tersimpan berstatus 'Inquiry' dengan progres 0. Data dibersihkan
     * tiap iterasi agar tidak terakumulasi di bawah RefreshDatabase.
     *
     * Feature: talent-secreet-isp-broker, Property 5: Order baru selalu berstatus awal Inquiry
     * Validates: Requirements 5.1
     */
    public function testNewOrderAlwaysStartsWithInquiryStatus(): void
    {
        $statusService = app(OrderStatusService::class);

        $this->limitTo(100)
            ->forAll(
                Generators::choose(0, 10_000_000), // provider_otc
                Generators::choose(0, 10_000_000), // provider_mrc
                Generators::choose(0, 10_000_000), // vendor_otc
                Generators::choose(0, 10_000_000), // vendor_mrc
                Generators::choose(0, 15)          // bitmask penentu komponen null
            )
            ->then(function (
                int $providerOtc,
                int $providerMrc,
                int $vendorOtc,
                int $vendorMrc,
                int $nullMask
            ) use ($statusService): void {
                $user = User::factory()->create(['role' => 'admin']);
                $client = Client::factory()->create();
                $provider = Partner::factory()->provider()->create();
                $vendor = Partner::factory()->vendor()->create();

                // Terapkan bitmask: bit yang menyala => komponen harga null.
                $payload = [
                    'client_name' => $client->name,
                    'provider_id' => $provider->id,
                    'vendor_id' => $vendor->id,
                    'provider_otc' => ($nullMask & 1) ? null : $providerOtc,
                    'provider_mrc' => ($nullMask & 2) ? null : $providerMrc,
                    'vendor_otc' => ($nullMask & 4) ? null : $vendorOtc,
                    'vendor_mrc' => ($nullMask & 8) ? null : $vendorMrc,
                ];

                $response = $this->actingAs($user)->post(route('orders.store'), $payload);

                // Pembuatan valid: tidak ada error validasi, Order tersimpan.
                $response->assertSessionHasNoErrors();

                $order = Order::query()->latest('id')->first();
                $this->assertNotNull($order, 'Order baru seharusnya tersimpan.');

                // R5.1: status awal selalu 'Inquiry' dan progres awal 0%.
                $this->assertSame('Inquiry', $order->status);
                $this->assertSame(0.0, $statusService->progressPercent($order->status));

                // Bersihkan data iterasi agar tidak terakumulasi antar iterasi Eris.
                OrderStatusHistory::query()->delete();
                Order::query()->delete();
                Partner::query()->delete();
                Client::query()->delete();
                User::query()->delete();
            });
    }
}
