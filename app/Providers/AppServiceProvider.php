<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register payment gateway registry
        $this->app->register(PaymentGatewayServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
