<?php

namespace Tests\Unit;

use App\Services\MarginService;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property test untuk perhitungan margin per Order (logika domain murni).
 *
 * Feature: talent-secreet-isp-broker, Property 10: Perhitungan margin per Order
 *
 * Validates: Requirements 7.1, 7.2
 */
class MarginPerOrderPropertyTest extends TestCase
{
    use TestTrait;

    private MarginService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarginService();
    }

    /**
     * Property 10: Untuk setiap Order dengan Harga_Provider & Harga_Vendor
     * lengkap (bilangan bulat non-negatif), Margin_OTC sama dengan
     * provider_otc - vendor_otc dan Margin_MRC sama dengan
     * provider_mrc - vendor_mrc.
     *
     * Feature: talent-secreet-isp-broker, Property 10: Perhitungan margin per Order
     * Validates: Requirements 7.1, 7.2
     */
    public function testMarginEqualsProviderMinusVendor(): void
    {
        $nonNegativeInt = Generators::choose(0, 1_000_000);

        $this->limitTo(100)
            ->forAll(
                $nonNegativeInt,
                $nonNegativeInt,
                $nonNegativeInt,
                $nonNegativeInt
            )
            ->then(function (int $providerOtc, int $vendorOtc, int $providerMrc, int $vendorMrc): void {
                $order = new \stdClass();
                $order->provider_otc = $providerOtc;
                $order->vendor_otc = $vendorOtc;
                $order->provider_mrc = $providerMrc;
                $order->vendor_mrc = $vendorMrc;

                // Margin_OTC = Harga_Provider OTC - Harga_Vendor OTC (R7.1)
                $this->assertSame(
                    $providerOtc - $vendorOtc,
                    $this->service->marginOtc($order),
                    'marginOtc harus sama dengan provider_otc - vendor_otc'
                );

                // Margin_MRC = Harga_Provider MRC - Harga_Vendor MRC (R7.2)
                $this->assertSame(
                    $providerMrc - $vendorMrc,
                    $this->service->marginMrc($order),
                    'marginMrc harus sama dengan provider_mrc - vendor_mrc'
                );
            });
    }
}
