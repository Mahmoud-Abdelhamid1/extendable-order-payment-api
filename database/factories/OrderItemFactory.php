<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id'     => Order::factory(),
            'product_name' => $this->faker->words(3, true),
            'quantity'     => $this->faker->numberBetween(1, 10),
            'price'        => $this->faker->randomFloat(2, 1, 200),
        ];
    }
}
