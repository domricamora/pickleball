<?php

namespace App\Providers;

use App\Services\Payments\Gateways\Drivers\PayMongoGateway;
use App\Services\Payments\Gateways\PaymentGateway;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the payment gateway so the rest of the app depends on the abstraction,
 * not on PayMongo (plan.md §13).
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, PayMongoGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
