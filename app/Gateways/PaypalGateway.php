<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;

class PaypalGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $context = []): array
    {
        // Simulate PayPal processing.
        // In production: use PayPal SDK with config('services.paypal.client_id') etc.
        $success = $amount > 0;

        return [
            'status'           => $success ? 'successful' : 'failed',
            'gateway_response' => [
                'gateway'        => $this->getName(),
                'transaction_id' => 'PP-' . strtoupper(uniqid()),
                'amount'         => $amount,
                'currency'       => 'USD',
                'processed_at'   => now()->toISOString(),
                'message'        => $success ? 'Payment completed' : 'Payment failed',
            ],
        ];
    }

    public function getName(): string
    {
        return 'paypal';
    }
}
