<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $context = []): array
    {
        // Simulate Stripe processing.
        // In production: \Stripe\Stripe::setApiKey(config('services.stripe.secret'))
        $success = $amount > 0;

        return [
            'status'           => $success ? 'successful' : 'failed',
            'gateway_response' => [
                'gateway'        => $this->getName(),
                'transaction_id' => 'ch_' . strtolower(uniqid()),
                'amount'         => $amount * 100, // Stripe uses cents
                'currency'       => 'usd',
                'processed_at'   => now()->toISOString(),
                'message'        => $success ? 'Payment intent succeeded' : 'Payment intent failed',
            ],
        ];
    }

    public function getName(): string
    {
        return 'stripe';
    }
}
