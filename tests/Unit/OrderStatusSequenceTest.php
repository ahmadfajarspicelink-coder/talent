<?php

namespace Tests\Unit;

use App\Services\OrderStatusService;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test untuk definisi urutan Status_Order (R6.1).
 *
 * Memastikan konstanta OrderStatusService::STATUSES mendefinisikan 9 tahap
 * berurutan dengan nilai dan urutan yang persis. Test contoh murni (bukan
 * property test), tanpa DB.
 */
class OrderStatusSequenceTest extends TestCase
{
    public function test_statuses_constant_matches_nine_stage_order(): void
    {
        $expected = [
            'Inquiry',
            'Cek_Ketersediaan',
            'Penawaran',
            'PO_Provider',
            'PO_Vendor',
            'Instalasi',
            'BAA_BAST',
            'BAST_Vendor',
            'Client_Aktif',
        ];

        $this->assertSame($expected, OrderStatusService::STATUSES);
        $this->assertCount(9, OrderStatusService::STATUSES);
    }
}
