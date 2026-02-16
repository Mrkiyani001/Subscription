<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Passport token expiration
        \Laravel\Passport\Passport::tokensExpireIn(now()->addMinutes(60));
        \Laravel\Passport\Passport::refreshTokensExpireIn(now()->addDays(7));

        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            [\App\Listeners\StripeEventListener::class, 'handle']
        );
    }
}
