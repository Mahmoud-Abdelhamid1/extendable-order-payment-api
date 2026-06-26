<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function validItems(): array
    {
        return [
            ['product_name' => 'Widget A', 'quantity' => 2, 'price' => 10.00],
            ['product_name' => 'Widget B', 'quantity' => 1, 'price' => 25.00],
        ];
    }

    public function test_authenticated_user_can_create_order(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => $this->validItems(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', 45.00);

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_create_order_requires_items(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $this->postJson('/api/orders', ['items' => $this->validItems()])
            ->assertStatus(401);
    }

    public function test_user_can_list_orders_with_status_filter(): void
    {
        Order::factory()->for($this->user)->create(['status' => 'pending']);
        Order::factory()->for($this->user)->create(['status' => 'confirmed']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders?status=pending');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_update_order_status(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => 'pending']);

        $this->actingAs($this->user)
            ->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/orders/{$order->id}")
            ->assertOk();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $order   = Order::factory()->for($this->user)->create(['status' => 'confirmed']);
        Payment::factory()->for($order)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete an order that has associated payments.');
    }

    public function test_total_is_calculated_server_side(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'items' => [
                ['product_name' => 'Item', 'quantity' => 3, 'price' => 7.50],
            ],
        ]);

        $response->assertJsonPath('data.total', 22.50);
    }
}
