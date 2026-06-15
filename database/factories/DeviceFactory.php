<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        $vendors = ['mikrotik', 'cisco', 'huawei', 'juniper'];
        $locations = ['Lt.1 Rack A', 'Lt.2 Rack B', 'POP Sudirman', 'POP Kemang', 'Data Center'];

        return [
            'name' => $this->faker->randomElement(['Switch', 'Router', 'Gateway']).' '.$this->faker->word(),
            'ip_address' => $this->faker->unique()->ipv4(),
            'snmp_community' => 'public',
            'snmp_version' => '2c',
            'vendor' => $this->faker->randomElement($vendors),
            'model' => strtoupper($this->faker->bothify('??-####')),
            'location' => $this->faker->randomElement($locations),
            'status' => 'unknown',
            'last_polled_at' => null,
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'status' => 'online',
            'last_polled_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => 'offline',
        ]);
    }
}
