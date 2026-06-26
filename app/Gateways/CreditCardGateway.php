<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $context = []): array
    {
        // Simulate credit card processing.
        // In production: call your processor SDK (Stripe, Braintree, etc.)
        $success = $amount > 0 && $amount < 100000;

        return [
            'status'           => $success ? 'successful' : 'failed',
            'gateway_response' => [
                'gateway'        => $this->getName(),
                'transaction_id' => 'CC-' . strtoupper(uniqid()),
                'amount'         => $amount,
                'currency'       => 'USD',
                'processed_at'   => now()->toISOString(),
                'message'        => $success ? 'Charge approved' : 'Charge declined',
            ],
        ];
    }

    public function getName(): string
    {
        return 'credit_card';
    }
}
