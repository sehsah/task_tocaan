<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
            'total' => $this->faker->randomFloat(2, 10, 500),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Set the order status to pending.
     */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    /**
     * Set the order status to confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    /**
     * Set the order status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
