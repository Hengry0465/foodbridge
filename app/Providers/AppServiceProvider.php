<?php

namespace App\Providers;

use App\Observers\DonorNotifier;
use App\Observers\RecipientNotifier;
use App\Services\MatchPublisher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MatchPublisher::class, function ($app): MatchPublisher {
            $publisher = new MatchPublisher;
            $publisher->attach($app->make(DonorNotifier::class));
            $publisher->attach($app->make(RecipientNotifier::class));

            return $publisher;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('module-api', fn (Request $request) => Limit::perMinute(10)
            ->by(($request->header('X-Module-ID') ?: 'unknown').'|'.$request->ip()));

        RateLimiter::for('recipient-actions', fn (Request $request) => Limit::perMinute(10)
            ->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip())));
    }
}
