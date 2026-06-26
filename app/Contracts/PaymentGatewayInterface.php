<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Process a payment for the given amount.
     *
     * @param  float  $amount
     * @param  array  $context  Any gateway-specific metadata (order_id, user, etc.)
     * @return array  ['status' => 'successful|failed', 'gateway_response' => [...]]
     */
    public function process(float $amount, array $context = []): array;

    /**
     * Return the gateway identifier matching the payment_method column value.
     */
    public function getName(): string;
}
