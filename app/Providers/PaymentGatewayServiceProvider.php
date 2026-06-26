<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\CreditCardGateway;
use App\Gateways\PaypalGateway;
use App\Gateways\StripeGateway;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register all payment gateways.
     *
     * To add a new gateway:
     *   1. Create App\Gateways\YourGateway implements PaymentGatewayInterface
     *   2. Add it to the $gateways array below
     *   3. Add the method name to the payments.payment_method enum migration
     *   That's it — no other files need touching.
     */
    public function register(): void
    {
        $gateways = [
            CreditCardGateway::class,
            PaypalGateway::class,
            StripeGateway::class,
        ];

        $map = [];

        foreach ($gateways as $gatewayClass) {
            /** @var PaymentGatewayInterface $gateway */
            $gateway = new $gatewayClass();
            $map[$gateway->getName()] = $gateway;
        }

        $this->app->instance('payment.gateways', $map);
    }
}
