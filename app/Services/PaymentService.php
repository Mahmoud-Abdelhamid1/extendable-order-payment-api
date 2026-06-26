<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function process(Order $order, string $paymentMethod): Payment
    {
        if (! $order->isConfirmed()) {
            throw new \DomainException('Payments can only be processed for confirmed orders.');
        }

        $gateway = $this->resolveGateway($paymentMethod);

        return DB::transaction(function () use ($order, $paymentMethod, $gateway) {
            $result = $gateway->process($order->total, ['order_id' => $order->id]);

            return Payment::create([
                'order_id'         => $order->id,
                'payment_method'   => $paymentMethod,
                'status'           => $result['status'],
                'gateway_response' => $result['gateway_response'],
            ]);
        });
    }

    public function listForOrder(Order $order): LengthAwarePaginator
    {
        return $order->payments()->latest()->paginate(10);
    }

    public function listAll(): LengthAwarePaginator
    {
        return Payment::with('order')->latest()->paginate(10);
    }

    private function resolveGateway(string $method): PaymentGatewayInterface
    {
        $gateways = app('payment.gateways');

        if (! isset($gateways[$method])) {
            throw new \InvalidArgumentException("Payment gateway [{$method}] is not supported.");
        }

        return $gateways[$method];
    }
}
