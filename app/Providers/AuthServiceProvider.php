<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Policy registration relies on Laravel's default policy discovery convention
 * (App\Models\{Model} -> App\Policies\{Model}Policy), so App\Models\Pickup is
 * automatically mapped to App\Policies\PickupPolicy without an explicit entry.
 * The $policies array below is left available for any future explicit mappings.
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Pickup::class => \App\Policies\PickupPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
