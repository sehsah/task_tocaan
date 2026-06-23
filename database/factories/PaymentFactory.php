<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $method = $this->faker->randomElement(['credit_card', 'paypal', 'stripe']);
        $status = $this->faker->randomElement(['pending', 'successful', 'failed']);

        return [
            'order_id' => Order::factory()->confirmed(),
            'idempotency_key' => 'idem-' . uniqid(),
            'payment_method' => $method,
            'status' => $status,
            'gateway_response' => [
                'gateway' => $method,
                'status' => $status,
                'transaction_id' => strtoupper(uniqid()),
                'message' => 'Simulated payment response.',
                'processed_at' => now()->toIso8601String(),
            ],
            'processed_at' => now(),
        ];
    }

    /**
     * Set payment status to successful.
     */
    public function successful(): static
    {
        return $this->state(['status' => 'successful']);
    }

    /**
     * Set payment status to failed.
     */
    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
