<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $method = $this->faker->randomElement(['credit_card', 'paypal', 'stripe']);

        return [
            'order_id'         => Order::factory()->confirmed(),
            'payment_method'   => $method,
            'status'           => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'gateway_response' => [
                'gateway'        => $method,
                'transaction_id' => strtoupper($this->faker->bothify('??-########')),
                'amount'         => $this->faker->randomFloat(2, 10, 500),
                'currency'       => 'USD',
                'processed_at'   => now()->toISOString(),
                'message'        => 'Simulated',
            ],
        ];
    }

    public function successful(): static
    {
        return $this->state(['status' => 'successful']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
