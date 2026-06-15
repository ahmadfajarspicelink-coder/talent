<?php

namespace Tests\Unit;

use App\Services\MarginService;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property test untuk pembedaan margin "tidak tersedia" dari nol hasil hitung
 * (logika domain murni MarginService).
 *
 * Feature: talent-secreet-isp-broker, Property 12: Pembedaan margin "tidak tersedia" dari nol hasil hitung
 *
 * Validates: Requirements 7.5, 7.6
 */
class MarginAvailabilityPropertyTest extends TestCase
{
    use TestTrait;

    private MarginService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarginService();
    }

    /**
     * Buat objek order stub (tanpa DB) dengan empat komponen harga yang
     * masing-masing berupa integer non-negatif atau null.
     */
    private function makeOrder(?int $providerOtc, ?int $vendorOtc, ?int $providerMrc, ?int $vendorMrc): object
    {
        $order = new \stdClass();
        $order->provider_otc = $providerOtc;
        $order->vendor_otc = $vendorOtc;
        $order->provider_mrc = $providerMrc;
        $order->vendor_mrc = $vendorMrc;

        return $order;
    }

    /**
     * Property 12: Untuk setiap Order, flag "tidak tersedia" (available=false)
     * bernilai true JIKA DAN HANYA JIKA minimal satu dari empat komponen harga
     * belum terisi (null). Jika keempat harga lengkap, available=true dan margin
     * selalu berupa angka hasil hitung (termasuk nol).
     *
     * Tiap margin komponen (otc/mrc) bernilai null hanya bila pasangannya
     * sendiri tak lengkap, sehingga membedakan "tidak tersedia" dari nol hitung.
     *
     * Setiap harga dibangkitkan acak: integer non-negatif ATAU null.
     *
     * Feature: talent-secreet-isp-broker, Property 12: Pembedaan margin "tidak tersedia" dari nol hasil hitung
     * Validates: Requirements 7.5, 7.6
     */
    public function testUnavailableIffAnyPriceComponentMissing(): void
    {
        // Generator harga: integer non-negatif acak ATAU null.
        // Hindari oneOf+constant (boros memori di Eris): pakai sentinel
        // negatif yang dipetakan menjadi null sehingga tetap menghasilkan
        // campuran order lengkap & tidak lengkap.
        $price = Generators::map(
            fn (int $value): ?int => $value < 0 ? null : $value,
            Generators::choose(-20000, 100000)
        );

        $this->limitTo(100)
            ->forAll($price, $price, $price, $price)
            ->then(function (?int $providerOtc, ?int $vendorOtc, ?int $providerMrc, ?int $vendorMrc): void {
                $order = $this->makeOrder($providerOtc, $vendorOtc, $providerMrc, $vendorMrc);

                $rows = $this->service->orderMargins([$order]);
                $this->assertCount(1, $rows);
                $row = $rows->first();

                $otcMissing = $providerOtc === null || $vendorOtc === null;
                $mrcMissing = $providerMrc === null || $vendorMrc === null;
                $anyMissing = $otcMissing || $mrcMissing;

                // Flag "tidak tersedia" (available=false) bernilai true JIKA DAN
                // HANYA JIKA minimal satu dari empat komponen harga belum terisi
                // (R7.5). Sebaliknya, harga lengkap => available=true (R7.6).
                $this->assertSame(
                    ! $anyMissing,
                    $row['available'],
                    'available harus false IFF ada komponen harga yang null'
                );

                // Margin OTC null hanya jika pasangan OTC tak lengkap; selain itu
                // selalu angka hasil hitung (termasuk nol).
                if ($otcMissing) {
                    $this->assertNull($row['otc'], 'otc harus null saat pasangan OTC tak lengkap');
                } else {
                    $this->assertIsInt($row['otc'], 'otc harus berupa angka saat lengkap');
                    $this->assertSame($providerOtc - $vendorOtc, $row['otc']);
                }

                // Margin MRC null hanya jika pasangan MRC tak lengkap; selain itu
                // selalu angka hasil hitung (termasuk nol).
                if ($mrcMissing) {
                    $this->assertNull($row['mrc'], 'mrc harus null saat pasangan MRC tak lengkap');
                } else {
                    $this->assertIsInt($row['mrc'], 'mrc harus berupa angka saat lengkap');
                    $this->assertSame($providerMrc - $vendorMrc, $row['mrc']);
                }
            });
    }

    /**
     * Property 12 (kasus nol hasil hitung): ketika harga provider == vendor,
     * margin OTC dan MRC betul-betul bernilai nol hasil hitung, NAMUN tetap
     * available=true (bukan "tidak tersedia"). Membuktikan nol terhitung
     * dibedakan dari null (R7.6).
     *
     * Feature: talent-secreet-isp-broker, Property 12: Pembedaan margin "tidak tersedia" dari nol hasil hitung
     * Validates: Requirements 7.5, 7.6
     */
    public function testGenuineComputedZeroIsAvailable(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generators::choose(0, 100000),
                Generators::choose(0, 100000)
            )
            ->then(function (int $otcPrice, int $mrcPrice): void {
                // Provider == Vendor pada kedua komponen -> margin = 0.
                $order = $this->makeOrder($otcPrice, $otcPrice, $mrcPrice, $mrcPrice);

                $row = $this->service->orderMargins([$order])->first();

                $this->assertTrue($row['available'], 'Nol hasil hitung tetap tersedia');
                $this->assertSame(0, $row['otc'], 'OTC seimbang harus nol hasil hitung');
                $this->assertSame(0, $row['mrc'], 'MRC seimbang harus nol hasil hitung');
            });
    }

    /**
     * Property 12 (kasus saling meniadakan): meskipun Margin_OTC dan
     * Margin_MRC saling meniadakan sehingga TOTAL-nya nol, masing-masing
     * komponen tetap angka non-nol hasil hitung dan order tetap available.
     * Ini membuktikan bahwa "tidak tersedia" hanya soal kelengkapan harga,
     * bukan soal hasil hitung yang kebetulan nol (R7.6).
     *
     * Dibangun dengan Margin_OTC = +d dan Margin_MRC = -d (d != 0).
     *
     * Feature: talent-secreet-isp-broker, Property 12: Pembedaan margin "tidak tersedia" dari nol hasil hitung
     * Validates: Requirements 7.5, 7.6
     */
    public function testCancelingMarginsStillAvailableWithNonZeroComponents(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generators::choose(1, 100000), // selisih d > 0
                Generators::choose(0, 100000)  // basis harga vendor
            )
            ->then(function (int $delta, int $base): void {
                // Margin_OTC = (base + delta) - base = +delta
                // Margin_MRC = base - (base + delta) = -delta
                $order = $this->makeOrder($base + $delta, $base, $base, $base + $delta);

                $row = $this->service->orderMargins([$order])->first();

                // Order tetap tersedia walau OTC + MRC = 0.
                $this->assertTrue($row['available'], 'Margin saling meniadakan tetap tersedia');
                $this->assertSame($delta, $row['otc'], 'OTC harus +delta (non-nol)');
                $this->assertSame(-$delta, $row['mrc'], 'MRC harus -delta (non-nol)');
                $this->assertNotSame(0, $row['otc'], 'Komponen OTC tidak boleh nol');
                $this->assertNotSame(0, $row['mrc'], 'Komponen MRC tidak boleh nol');
                // Total saling meniadakan menjadi nol, tetapi komponen tetap ada.
                $this->assertSame(0, $row['otc'] + $row['mrc'], 'Total margin saling meniadakan = 0');
            });
    }
}
