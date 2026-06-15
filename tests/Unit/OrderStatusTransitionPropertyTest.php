<?php

namespace Tests\Unit;

use App\Services\OrderStatusService;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based test untuk transisi Status_Order.
 *
 * Feature: talent-secreet-isp-broker, Property 7: Transisi Status_Order hanya sah ke penerus langsung
 *
 * Validates: Requirements 6.2, 6.3, 6.6
 */
class OrderStatusTransitionPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Penerus langsung yang diharapkan untuk setiap status pada urutan tetap.
     * Complete tidak punya penerus (null) sehingga transisi apa pun darinya ditolak.
     *
     * @return array<string, string|null>
     */
    private function expectedSuccessors(): array
    {
        $statuses = OrderStatusService::STATUSES;
        $successors = [];

        foreach ($statuses as $index => $status) {
            $successors[$status] = $statuses[$index + 1] ?? null;
        }

        return $successors;
    }

    /**
     * Property 7: untuk setiap pasangan (current, target) yang dipilih acak dari
     * STATUSES, canTransition(current, target) bernilai true jika dan hanya jika
     * target adalah penerus langsung dari current pada urutan tetap. Melompat,
     * mundur, atau transisi apa pun dari Complete selalu false.
     *
     * Feature: talent-secreet-isp-broker, Property 7: Transisi Status_Order hanya sah ke penerus langsung
     *
     * Validates: Requirements 6.2, 6.3, 6.6
     */
    public function test_transition_valid_iff_target_is_immediate_successor(): void
    {
        $service = new OrderStatusService();
        $successors = $this->expectedSuccessors();

        $this->forAll(
            Generator\elements(OrderStatusService::STATUSES),
            Generator\elements(OrderStatusService::STATUSES)
        )->then(function (string $current, string $target) use ($service, $successors): void {
            $expected = ($successors[$current] === $target);

            $this->assertSame(
                $expected,
                $service->canTransition($current, $target),
                "canTransition('{$current}', '{$target}') seharusnya " . ($expected ? 'true' : 'false')
            );

            // Transisi dari status final (Client_Aktif) selalu ditolak.
            if ($current === 'Client_Aktif') {
                $this->assertFalse(
                    $service->canTransition($current, $target),
                    "Transisi dari Client_Aktif ke '{$target}' harus ditolak"
                );
            }
        });
    }
}
