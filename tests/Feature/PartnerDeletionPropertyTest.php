<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property test untuk guard penghapusan Partner (menyentuh DB).
 *
 * Feature: talent-secreet-isp-broker, Property 2: Partner hanya bisa dihapus jika tidak terhubung Order
 *
 * Validates: Requirements 3.6, 3.7
 *
 * Pendekatan: properti diuji pada logika guard yang dipakai
 * PartnerController@destroy, yaitu Partner::hasLinkedOrders() yang memeriksa
 * keterkaitan Order baik lewat provider_id maupun vendor_id. Setiap iterasi
 * membangun datanya sendiri (Partner + sejumlah Order acak sebagai
 * provider/vendor), menjalankan guard yang sama persis dengan controller
 * (hapus hanya jika tidak ada Order terkait), lalu membersihkan seluruh data
 * agar iterasi tidak saling terakumulasi di bawah RefreshDatabase.
 */
class PartnerDeletionPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 2: Untuk Partner manapun, penghapusan berhasil jika dan hanya
     * jika jumlah Order yang terhubung sama dengan nol. Jika ada minimal satu
     * Order terkait (sebagai provider maupun vendor), penghapusan ditolak dan
     * data Partner tetap ada.
     *
     * Feature: talent-secreet-isp-broker, Property 2: Partner hanya bisa dihapus jika tidak terhubung Order
     * Validates: Requirements 3.6, 3.7
     */
    public function testPartnerDeletableIffNoLinkedOrders(): void
    {
        $this->limitTo(100)
            ->forAll(
                // Jumlah Order terkait: 0..4 (termasuk 0 = tidak terhubung).
                Generators::choose(0, 4),
                // Peran Partner di Order: 0 = provider, 1 = vendor.
                Generators::choose(0, 1)
            )
            ->then(function (int $linkedCount, int $roleFlag): void {
                $asProvider = $roleFlag === 0;

                $partner = Partner::factory()->create([
                    'type' => $asProvider ? 'provider' : 'vendor',
                ]);

                $client = Client::factory()->create();

                // Bangun sejumlah Order yang menghubungkan Partner ini pada
                // peran yang dipilih. Peran lawan diisi Partner pelengkap agar
                // FK terpenuhi.
                for ($i = 0; $i < $linkedCount; $i++) {
                    if ($asProvider) {
                        $vendor = Partner::factory()->vendor()->create();
                        Order::factory()->create([
                            'client_id' => $client->id,
                            'provider_id' => $partner->id,
                            'vendor_id' => $vendor->id,
                        ]);
                    } else {
                        $provider = Partner::factory()->provider()->create();
                        Order::factory()->create([
                            'client_id' => $client->id,
                            'provider_id' => $provider->id,
                            'vendor_id' => $partner->id,
                        ]);
                    }
                }

                // Guard penghapusan yang identik dengan PartnerController@destroy.
                $partnerId = $partner->id;
                $isLinked = $partner->hasLinkedOrders();
                if (! $isLinked) {
                    $partner->delete();
                }

                $stillExists = Partner::whereKey($partnerId)->exists();

                if ($linkedCount === 0) {
                    // R3.6: tidak terhubung Order => boleh dihapus, data hilang.
                    $this->assertFalse(
                        $isLinked,
                        'Partner tanpa Order terkait seharusnya tidak terdeteksi terhubung.'
                    );
                    $this->assertFalse(
                        $stillExists,
                        'Partner tanpa Order terkait seharusnya terhapus.'
                    );
                } else {
                    // R3.7: terhubung minimal satu Order => penghapusan ditolak,
                    // data Partner tetap ada.
                    $this->assertTrue(
                        $isLinked,
                        'Partner dengan Order terkait seharusnya terdeteksi terhubung.'
                    );
                    $this->assertTrue(
                        $stillExists,
                        'Partner dengan Order terkait seharusnya tetap ada (penghapusan ditolak).'
                    );
                }

                // Bersihkan data iterasi (orders dulu, lalu partners & clients)
                // agar tidak terakumulasi antar iterasi Eris.
                Order::query()->delete();
                Partner::query()->delete();
                Client::query()->delete();
            });
    }
}
