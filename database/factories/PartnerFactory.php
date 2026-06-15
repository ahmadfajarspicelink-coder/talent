<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['provider', 'vendor']),
            'address' => fake()->address(),
            'pic' => fake()->name(),
            'status' => 'active',
        ];
    }

    /**
     * Partner bertipe provider.
     */
    public function provider(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'provider']);
    }

    /**
     * Partner bertipe vendor.
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'vendor']);
    }
}
