<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmsService;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService();
        });

        // Also bind with a shorter name for convenience
        $this->app->alias(SmsService::class, 'smsService');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //
    }
}
