<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test untuk penolakan harga negatif (R5.4).
 *
 * Pada desain alur baru, harga provider diisi saat memajukan order ke tahap
 * PO_Provider (bukan saat pembuatan). Test ini membuat order, memajukannya
 * hingga tahap Penawaran, lalu mencoba memajukan ke PO_Provider dengan minimal
 * satu komponen harga provider bernilai negatif. Validasi `min:0` harus menolak
 * transisi sepenuhnya: status order tidak berubah dan harga tidak tersimpan.
 *
 * Feature: talent-secreet-isp-broker, Property 6: Harga negatif menolak penyimpanan Order sepenuhnya
 *
 * Validates: Requirements 5.4
 */
class OrderNegativePricePropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Komponen harga provider yang diisi pada tahap PO_Provider.
     *
     * @var list<string>
     */
    private const PRICE_FIELDS = [
        'provider_otc',
        'provider_mrc',
    ];

    /**
     * Property 6: Untuk kombinasi harga provider (OTC & MRC) yang mengandung
     * minimal satu nilai negatif, transisi ke PO_Provider ditolak seluruhnya:
     * status order tetap di Penawaran dan harga tidak tersimpan.
     *
     * Feature: talent-secreet-isp-broker, Property 6: Harga negatif menolak penyimpanan Order sepenuhnya
     * Validates: Requirements 5.4
     */
    public function testNegativePriceRejectsEntireOrderPersistence(): void
    {
        $this->limitTo(60)
            ->forAll(
                Generators::choose(-1000, 1000),
                Generators::choose(-1000, 1000),
                Generators::choose(0, 1) // indeks yang dipaksa negatif
            )
            ->then(function (int $otc, int $mrc, int $forcedIndex): void {
                $prices = [$otc, $mrc];
                $prices[$forcedIndex] = -abs($prices[$forcedIndex]) - 1;

                $negativeFields = [];
                foreach (self::PRICE_FIELDS as $i => $field) {
                    if ($prices[$i] < 0) {
                        $negativeFields[] = $field;
                    }
                }

                $user = User::factory()->create(['role' => 'staff']);
                $client = Client::factory()->create();
                $provider = Partner::factory()->provider()->create();
                $vendor = Partner::factory()->vendor()->create();

                // Buat order (Inquiry) lalu majukan hingga Penawaran.
                $this->actingAs($user)->post(route('orders.store'), [
                    'client_name' => $client->name,
                    'provider_id' => $provider->id,
                    'vendor_id' => $vendor->id,
                ])->assertSessionHasNoErrors();

                $order = Order::where('client_id', $client->id)->latest('id')->firstOrFail();

                $this->actingAs($user)->post(route('orders.advanceStatus', $order), [
                    'status' => 'Cek_Ketersediaan',
                ])->assertSessionHasNoErrors();

                $this->actingAs($user)->post(route('orders.advanceStatus', $order), [
                    'status' => 'Penawaran',
                    'offer_number' => 'PEN-001',
                ])->assertSessionHasNoErrors();

                // Coba majukan ke PO_Provider dengan harga negatif.
                $response = $this->actingAs($user)
                    ->from(route('orders.show', $order))
                    ->post(route('orders.advanceStatus', $order), [
                        'status' => 'PO_Provider',
                        'po_provider_number' => 'PO-P-001',
                        'provider_otc' => $prices[0],
                        'provider_mrc' => $prices[1],
                        'bandwidth' => 50,
                    ]);

                // R5.4: ditolak via validasi -> redirect back dengan error.
                $response->assertRedirect(route('orders.show', $order));
                $response->assertSessionHasErrors($negativeFields);

                // Status tidak berubah & harga tidak tersimpan.
                $order->refresh();
                $this->assertSame('Penawaran', $order->status);
                $this->assertNull($order->provider_otc);
                $this->assertNull($order->provider_mrc);

                // Bersihkan data iterasi.
                \App\Models\OrderStatusHistory::query()->delete();
                Order::query()->delete();
                Client::query()->delete();
                Partner::query()->delete();
                User::query()->delete();
            });
    }
}
