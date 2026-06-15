<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'provider_id' => Partner::factory()->provider(),
            'vendor_id' => Partner::factory()->vendor(),
            'status' => 'Inquiry',
            'provider_otc' => null,
            'provider_mrc' => null,
            'vendor_otc' => null,
            'vendor_mrc' => null,
        ];
    }
}
