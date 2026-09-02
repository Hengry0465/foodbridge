<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DonationReleaseGateway;
use App\Services\FakeDonationReleaseService;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * App Service Provider - register services and bindings
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind DonationReleaseGateway interface to fake implementation
        // In production, this would be replaced with the actual Module 2 integration
        $this->app->bind(DonationReleaseGateway::class, FakeDonationReleaseService::class);
    }

    public function boot(): void
    {
        //
    }
}
