<?php

namespace Tests\Unit;

use App\Services\OrderStatusService;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property test untuk perhitungan Persentase_Progress (logika domain murni).
 *
 * Feature: talent-secreet-isp-broker, Property 8: Persentase_Progress adalah fungsi dari Status_Order
 *
 * Validates: Requirements 6.7, 6.10
 */
class OrderProgressPropertyTest extends TestCase
{
    use TestTrait;

    private OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderStatusService();
    }

    /**
     * Property 8: Untuk setiap Status_Order, Persentase_Progress sama dengan
     * (indeks status pada urutan, mulai 0) / 6 * 100. Nilainya deterministik
     * (fungsi dari status), dengan Inquiry = 0% dan Complete = 100%.
     *
     * Feature: talent-secreet-isp-broker, Property 8: Persentase_Progress adalah fungsi dari Status_Order
     * Validates: Requirements 6.7, 6.10
     */
    public function testProgressPercentIsAFunctionOfStatus(): void
    {
        $statuses = OrderStatusService::STATUSES;
        $lastIndex = count($statuses) - 1; // 6

        $this->forAll(
            Generators::elements($statuses)
        )
            ->then(function (string $status) use ($statuses, $lastIndex): void {
                $index = array_search($status, $statuses, true);
                // progressPercent dideklarasikan mengembalikan float, jadi nilai
                // harapan juga float agar perbandingan tipe (assertSame) konsisten.
                $expected = (float) ($index / $lastIndex * 100);

                $actual = $this->service->progressPercent($status);

                // Persentase_Progress = index/6*100 (R6.7)
                $this->assertSame($expected, $actual, "progressPercent($status) harus $expected%");

                // Deterministik: pemanggilan ulang menghasilkan nilai sama (R6.10)
                $this->assertSame(
                    $actual,
                    $this->service->progressPercent($status),
                    "progressPercent($status) harus deterministik"
                );

                // Batas: nilai berada dalam [0, 100]
                $this->assertGreaterThanOrEqual(0.0, $actual);
                $this->assertLessThanOrEqual(100.0, $actual);
            });
    }

    /**
     * Property 8 (lanjutan): Persentase_Progress monoton naik mengikuti urutan
     * Status_Order. Untuk pasangan acak (a, b), jika indeks a < indeks b maka
     * progress(a) < progress(b); jika indeks sama maka progress sama.
     *
     * Feature: talent-secreet-isp-broker, Property 8: Persentase_Progress adalah fungsi dari Status_Order
     * Validates: Requirements 6.7, 6.10
     */
    public function testProgressPercentIsMonotonicAlongStatusOrder(): void
    {
        $statuses = OrderStatusService::STATUSES;
        $lastIndex = count($statuses) - 1;

        $this->forAll(
            Generators::choose(0, $lastIndex),
            Generators::choose(0, $lastIndex)
        )
            ->then(function (int $i, int $j) use ($statuses): void {
                $progressI = $this->service->progressPercent($statuses[$i]);
                $progressJ = $this->service->progressPercent($statuses[$j]);

                if ($i < $j) {
                    $this->assertLessThan($progressJ, $progressI);
                } elseif ($i > $j) {
                    $this->assertGreaterThan($progressJ, $progressI);
                } else {
                    $this->assertSame($progressJ, $progressI);
                }
            });
    }

    /**
     * Property 8 (titik batas): Inquiry = 0% dan Complete = 100%.
     *
     * Feature: talent-secreet-isp-broker, Property 8: Persentase_Progress adalah fungsi dari Status_Order
     * Validates: Requirements 6.7, 6.10
     */
    public function testBoundaryStatusesMapToZeroAndHundred(): void
    {
        $this->assertSame(0.0, $this->service->progressPercent('Inquiry'));
        $this->assertSame(100.0, $this->service->progressPercent('Client_Aktif'));
    }
}
