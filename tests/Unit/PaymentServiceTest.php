<?php

namespace Tests\Unit;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_process_throws_for_non_confirmed_order(): void
    {
        $this->expectException(\DomainException::class);

        $order = Order::factory()->pending()->create();

        $this->service->process($order, 'credit_card');
    }

    public function test_process_throws_for_unsupported_gateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = Order::factory()->confirmed()->create();

        $this->service->process($order, 'bitcoin');
    }

    public function test_process_persists_payment_record(): void
    {
        $order = Order::factory()->confirmed()->create(['total' => 75.00]);

        $payment = $this->service->process($order, 'stripe');

        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('stripe', $payment->payment_method);
        $this->assertContains($payment->status, ['successful', 'failed']);
        $this->assertNotEmpty($payment->gateway_response);
    }

    public function test_gateway_registry_contains_all_defaults(): void
    {
        $gateways = app('payment.gateways');

        $this->assertArrayHasKey('credit_card', $gateways);
        $this->assertArrayHasKey('paypal', $gateways);
        $this->assertArrayHasKey('stripe', $gateways);

        foreach ($gateways as $gateway) {
            $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        }
    }

    public function test_mock_gateway_can_be_swapped_in(): void
    {
        // Demonstrates how easy it is to swap gateway logic in tests.
        // getName() must return a value accepted by the payments.payment_method enum.
        $mockGateway = new class implements PaymentGatewayInterface {
            public function process(float $amount, array $context = []): array
            {
                return [
                    'status'           => 'successful',
                    'gateway_response' => ['transaction_id' => 'MOCK-001'],
                ];
            }

            public function getName(): string
            {
                // Use a real enum value so the DB constraint is satisfied;
                // the actual processing logic is fully replaced by this mock.
                return 'credit_card';
            }
        };

        $gateways                = app('payment.gateways');
        $gateways['credit_card'] = $mockGateway;
        app()->instance('payment.gateways', $gateways);

        $order   = Order::factory()->confirmed()->create(['total' => 10.00]);
        $payment = $this->service->process($order, 'credit_card');

        $this->assertSame('successful', $payment->status);
        $this->assertSame('MOCK-001', $payment->gateway_response['transaction_id']);
    }
}
