<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return Order::with('items')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(10);
    }

    public function create(int $userId, array $items): Order
    {
        return DB::transaction(function () use ($userId, $items) {
            $total = collect($items)->sum(fn($i) => $i['quantity'] * $i['price']);

            $order = Order::create([
                'user_id' => $userId,
                'status'  => 'pending',
                'total'   => $total,
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update(['status' => $data['status']]);

            if (isset($data['items'])) {
                $order->items()->delete();
                $order->items()->createMany($data['items']);

                $total = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['price']);
                $order->update(['total' => $total]);
            }

            return $order->fresh('items');
        });
    }

    public function delete(Order $order): void
    {
        if ($order->hasPayments()) {
            throw new \DomainException('Cannot delete an order that has associated payments.');
        }

        $order->delete();
    }
}
