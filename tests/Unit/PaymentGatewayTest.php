<?php

namespace Tests\Unit;

use App\Gateways\CreditCardGateway;
use App\Gateways\PaypalGateway;
use App\Gateways\StripeGateway;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_credit_card_gateway_returns_successful_result(): void
    {
        $gateway = new CreditCardGateway();
        $result  = $gateway->process(100.00, ['order_id' => 1]);

        $this->assertSame('successful', $result['status']);
        $this->assertArrayHasKey('transaction_id', $result['gateway_response']);
        $this->assertStringStartsWith('CC-', $result['gateway_response']['transaction_id']);
    }

    public function test_paypal_gateway_returns_successful_result(): void
    {
        $gateway = new PaypalGateway();
        $result  = $gateway->process(50.00);

        $this->assertSame('successful', $result['status']);
        $this->assertStringStartsWith('PP-', $result['gateway_response']['transaction_id']);
    }

    public function test_stripe_gateway_returns_successful_result(): void
    {
        $gateway = new StripeGateway();
        $result  = $gateway->process(200.00);

        $this->assertSame('successful', $result['status']);
        $this->assertStringStartsWith('ch_', $result['gateway_response']['transaction_id']);
        $this->assertSame(20000.0, $result['gateway_response']['amount']); // cents
    }

    public function test_gateway_names_match_expected_identifiers(): void
    {
        $this->assertSame('credit_card', (new CreditCardGateway())->getName());
        $this->assertSame('paypal',      (new PaypalGateway())->getName());
        $this->assertSame('stripe',      (new StripeGateway())->getName());
    }

    public function test_credit_card_fails_for_zero_amount(): void
    {
        $gateway = new CreditCardGateway();
        $result  = $gateway->process(0.00);

        $this->assertSame('failed', $result['status']);
    }
}
