<?php

/**
 * ============================================================================
 * FoodShare 路由服务提供者 (Route Service Provider)
 * ============================================================================
 *
 * 【文件作用】
 * 本文件是 Laravel 9 标准的路由服务提供者，负责：
 *   1. 定义应用中所有路由的"主命名空间"（用于控制器解析）
 *   2. 注册 routes/web.php 和 routes/api.php 路由文件
 *   3. 定义路由模型绑定、速率限制等路由相关行为
 *
 * 【与 Laravel 11+ 的差异】
 * Laravel 11+ 使用 `bootstrap/app.php` 的 `withRouting()` 方法注册路由；
 * Laravel 9 通过本文件中的 `boot()` 方法注册。
 *
 * 【执行时机】
 * 本 Provider 的 boot() 方法会在所有服务提供者注册后被调用，
 * 此时路由表已准备好接收定义。
 *
 * ============================================================================
 * FoodShare Route Service Provider
 * ============================================================================
 *
 * [File Purpose]
 * This file is the Laravel 9 standard route service provider, responsible for:
 *   1. Defining the "main namespace" for all application routes (for controller resolution)
 *   2. Registering routes/web.php and routes/api.php route files
 *   3. Defining route model bindings, rate limiting, and other route-related behaviors
 *
 * [Difference from Laravel 11+]
 * Laravel 11+ uses the `withRouting()` method in `bootstrap/app.php` to register routes;
 * Laravel 9 registers routes through the `boot()` method in this file.
 *
 * [Invocation Timing]
 * This provider's boot() method is called after all service providers register,
 * when the route table is ready to receive definitions.
 */

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * 路由服务提供者类
 *
 * 继承自 Laravel 框架的 Illuminate\Foundation\Support\Providers\RouteServiceProvider，
 * 通过 boot() 方法注册路由文件并定义路由相关行为。
 *
 * Route Service Provider class.
 *
 * Extends Laravel's Illuminate\Foundation\Support\Providers\RouteServiceProvider,
 * registers route files and defines route-related behaviors through boot() method.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * ----------------------------------------------------------
     * 控制器方法路径常量
     * ----------------------------------------------------------
     * 当路由定义中只写方法名（如 'HomeController@index'）时，
     * Laravel 会自动在此命名空间下查找控制器类。
     *
     * 例如：'HomeController@index' → App\Http\Controllers\HomeController@index
     *
     * Controller method path constant.
     * ----------------------------------------------------------
     * When route definitions only specify a method name (e.g., 'HomeController@index'),
     * Laravel will automatically look up the controller class under this namespace.
     *
     * Example: 'HomeController@index' → App\Http\Controllers\HomeController@index
     *
     * @var string|null
     */
    public const HOME = '/home';

    /**
     * ----------------------------------------------------------
     * 注册路由前的全局配置
     * ----------------------------------------------------------
     * 此方法在每个请求开始时被调用，用于执行路由相关的全局配置。
     * 当前实现：定义 API 路由的速率限制策略。
     *
     * Configure the rate limiters for the application.
     * ----------------------------------------------------------
     * This method is called at the start of each request to perform global
     * route-related configuration. Current implementation: defines rate
     * limiting policy for API routes.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        // API 速率限制：每个 IP 每分钟最多 60 次请求
        // API rate limit: each IP up to 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }

    /**
     * ----------------------------------------------------------
     * 注册应用的所有路由
     * ----------------------------------------------------------
     * 此方法由父类的 register() 阶段调用，在所有服务注册完毕后执行。
     *
     * 步骤：
     *   1. 调用 configureRateLimiting() 配置速率限制
     *   2. 注册 routes/web.php（浏览器路由）
     *   3. 注册 routes/api.php（API 路由，如果存在）
     *
     * Define your route model bindings, pattern filters, etc.
     * ----------------------------------------------------------
     * This method is called by the parent class's register() phase, after
     * all services are registered.
     *
     * Steps:
     *   1. Call configureRateLimiting() to configure rate limiting
     *   2. Register routes/web.php (browser routes)
     *   3. Register routes/api.php (API routes, if exists)
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // 注册 routes/web.php，包裹 'web' 中间件组
        // Register routes/web.php, wrapped in 'web' middleware group
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // 注册 routes/api.php，包裹 'api' 中间件组
            // Register routes/api.php, wrapped in 'api' middleware group
            // 仅当 api.php 文件存在时加载 / Only loaded if api.php file exists
            if (file_exists(base_path('routes/api.php'))) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));
            }
        });
    }
}