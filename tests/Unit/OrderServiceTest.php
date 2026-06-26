<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
    }

    public function test_create_calculates_total_correctly(): void
    {
        $user  = User::factory()->create();
        $items = [
            ['product_name' => 'Item A', 'quantity' => 2, 'price' => 15.00],
            ['product_name' => 'Item B', 'quantity' => 3, 'price' => 10.00],
        ];

        $order = $this->service->create($user->id, $items);

        $this->assertSame(60.00, $order->total); // (2*15) + (3*10)
        $this->assertCount(2, $order->items);
        $this->assertSame('pending', $order->status);
    }

    public function test_delete_throws_for_order_with_payments(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete an order that has associated payments.');

        $order = Order::factory()->confirmed()->create();
        Payment::factory()->for($order)->create();

        $this->service->delete($order);
    }

    public function test_delete_succeeds_for_order_without_payments(): void
    {
        $order = Order::factory()->create();

        $this->service->delete($order);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_update_replaces_items_and_recalculates_total(): void
    {
        $user  = User::factory()->create();
        $order = $this->service->create($user->id, [
            ['product_name' => 'Old Item', 'quantity' => 1, 'price' => 100.00],
        ]);

        $updated = $this->service->update($order, [
            'items' => [
                ['product_name' => 'New Item', 'quantity' => 2, 'price' => 20.00],
            ],
        ]);

        $this->assertSame(40.00, $updated->total);
        $this->assertCount(1, $updated->items);
        $this->assertSame('New Item', $updated->items->first()->product_name);
    }
}
