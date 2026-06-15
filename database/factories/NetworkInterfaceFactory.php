<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\NetworkInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NetworkInterface>
 */
class NetworkInterfaceFactory extends Factory
{
    protected $model = NetworkInterface::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'if_index' => $this->faker->unique()->numberBetween(1, 1000),
            'if_name' => 'eth'.$this->faker->numberBetween(1, 48),
            'if_descr' => 'Gigabit Ethernet',
            'if_alias' => $this->faker->words(2, true),
            'if_speed' => 1000000000,
            'if_type' => 'ethernetCsmacd',
            'if_oper_status' => 'up',
            'if_admin_status' => 'up',
            'if_in_octets' => 0,
            'if_out_octets' => 0,
            'if_in_errors' => 0,
            'if_out_errors' => 0,
        ];
    }

    public function up(): static
    {
        return $this->state(fn () => [
            'if_oper_status' => 'up',
            'if_in_octets' => $this->faker->numberBetween(1000000, 10000000000),
            'if_out_octets' => $this->faker->numberBetween(1000000, 10000000000),
        ]);
    }

    public function down(): static
    {
        return $this->state(fn () => [
            'if_oper_status' => 'down',
            'if_in_octets' => 0,
            'if_out_octets' => 0,
        ]);
    }
}
