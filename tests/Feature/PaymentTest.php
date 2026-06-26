<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_process_payment_for_confirmed_order(): void
    {
        $order = Order::factory()->for($this->user)->create([
            'status' => 'confirmed',
            'total'  => 99.99,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/orders/{$order->id}/payments", [
                'payment_method' => 'credit_card',
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'order_id', 'payment_method', 'status', 'gateway_response'],
            ])
            ->assertJsonPath('data.payment_method', 'credit_card');

        $this->assertDatabaseHas('payments', ['order_id' => $order->id]);
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'pending']);

        $this->actingAs($this->user)
            ->postJson("/api/orders/{$order->id}/payments", [
                'payment_method' => 'paypal',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Payments can only be processed for confirmed orders.');
    }

    public function test_cannot_process_payment_for_cancelled_order(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'cancelled']);

        $this->actingAs($this->user)
            ->postJson("/api/orders/{$order->id}/payments", [
                'payment_method' => 'stripe',
            ])
            ->assertStatus(422);
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'confirmed']);

        $this->actingAs($this->user)
            ->postJson("/api/orders/{$order->id}/payments", [
                'payment_method' => 'bitcoin',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_can_list_payments_for_order(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'confirmed']);
        Payment::factory()->for($order)->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/orders/{$order->id}/payments");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_list_all_payments(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'confirmed']);
        Payment::factory()->for($order)->count(2)->create();

        $this->actingAs($this->user)
            ->getJson('/api/payments')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_all_three_gateways_are_supported(): void
    {
        $methods = ['credit_card', 'paypal', 'stripe'];

        foreach ($methods as $method) {
            $order = Order::factory()->for($this->user)->create([
                'status' => 'confirmed',
                'total'  => 50.00,
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/orders/{$order->id}/payments", [
                    'payment_method' => $method,
                ])
                ->assertStatus(201)
                ->assertJsonPath('data.payment_method', $method);
        }
    }
}
