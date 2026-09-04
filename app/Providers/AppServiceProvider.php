<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Observers\DonorNotifier;
use App\Observers\RecipientNotifier;
use App\Services\MatchPublisher;
use App\Services\DonationReleaseService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        $this->app->singleton(MatchPublisher::class, function ($app) {
            $publisher = new MatchPublisher;
            $publisher->attach($app->make(DonorNotifier::class));
            $publisher->attach($app->make(RecipientNotifier::class));

            return $publisher;
        });

        $this->app->bind(\App\Services\DonationReleaseGateway::class, DonationReleaseService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('module-api', function (Request $request) {
            return Limit::perMinute(10)->by(($request->header('X-Module-ID') ?: 'unknown') . '|' . $request->ip());
        });

        RateLimiter::for('recipient-actions', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
