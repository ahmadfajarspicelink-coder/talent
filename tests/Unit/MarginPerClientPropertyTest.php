<?php

namespace Tests\Unit;

use App\Services\MarginService;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property test untuk agregasi Total_Margin_Per_Client (logika domain murni).
 *
 * Feature: talent-secreet-isp-broker, Property 11: Total margin per Client adalah agregasi margin Order-nya
 *
 * Validates: Requirements 7.4
 */
class MarginPerClientPropertyTest extends TestCase
{
    use TestTrait;

    private MarginService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarginService();
    }

    /**
     * Property 11: Untuk Client dengan sekumpulan Order berharga lengkap,
     * Total_Margin_Per_Client OTC sama dengan jumlah seluruh (provider_otc -
     * vendor_otc) dan MRC sama dengan jumlah seluruh (provider_mrc -
     * vendor_mrc) dari Order milik Client tersebut.
     *
     * Feature: talent-secreet-isp-broker, Property 11: Total margin per Client adalah agregasi margin Order-nya
     * Validates: Requirements 7.4
     */
    public function testTotalMarginPerClientIsAggregationOfOrderMargins(): void
    {
        // Generator harga: integer non-negatif. Satu tuple per order berisi
        // keempat komponen harga (provider/vendor x OTC/MRC).
        $priceTuple = Generators::tuple(
            Generators::choose(0, 1_000_000), // provider_otc
            Generators::choose(0, 1_000_000), // provider_mrc
            Generators::choose(0, 1_000_000), // vendor_otc
            Generators::choose(0, 1_000_000)  // vendor_mrc
        );

        $this->limitTo(100)
            ->forAll(
                // Daftar order dengan panjang acak (termasuk kosong).
                Generators::seq($priceTuple)
            )
            ->then(function (array $orderPrices): void {
                // Bangun client stub dengan koleksi order stub.
                $orders = [];
                $expectedOtc = 0;
                $expectedMrc = 0;

                foreach ($orderPrices as [$providerOtc, $providerMrc, $vendorOtc, $vendorMrc]) {
                    $order = new \stdClass();
                    $order->provider_otc = $providerOtc;
                    $order->provider_mrc = $providerMrc;
                    $order->vendor_otc = $vendorOtc;
                    $order->vendor_mrc = $vendorMrc;
                    $orders[] = $order;

                    $expectedOtc += $providerOtc - $vendorOtc;
                    $expectedMrc += $providerMrc - $vendorMrc;
                }

                $client = new \stdClass();
                $client->orders = $orders;

                $result = $this->service->totalMarginPerClient($client);

                if ($orders === []) {
                    // Tanpa order berharga lengkap, total bertanda "tidak tersedia".
                    $this->assertNull($result['otc']);
                    $this->assertNull($result['mrc']);

                    return;
                }

                // Total OTC == sum(provider_otc - vendor_otc) (R7.4)
                $this->assertSame($expectedOtc, $result['otc']);
                // Total MRC == sum(provider_mrc - vendor_mrc) (R7.4)
                $this->assertSame($expectedMrc, $result['mrc']);
            });
    }
}
