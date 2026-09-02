<?php

/**
 * ============================================================================
 * FoodShare 应用引导文件 (Bootstrap) — Laravel 9 风格
 * ============================================================================
 *
 * 【文件作用】
 * 本文件是 Laravel 9 标准的应用引导文件，负责创建 Application 容器实例，
 * 并将三个核心接口绑定到具体实现类：
 *   - Illuminate\Contracts\Http\Kernel         → App\Http\Kernel
 *   - Illuminate\Contracts\Console\Kernel      → App\Console\Kernel
 *   - Illuminate\Contracts\Debug\ExceptionHandler → App\Exceptions\Handler
 *
 * 【与 Laravel 11+ 的差异】
 * Laravel 11+ 使用 `Application::configure()->...->create()` 链式调用；
 * Laravel 9 通过直接 new Application 实例并手动绑定接口。
 *
 * 【调用时机】
 * public/index.php 通过 `require_once` 引入本文件，返回 Application 实例，
 * 后续通过 `$app->make(Kernel::class)` 获取 HTTP/Console 内核。
 *
 * ============================================================================
 * FoodShare Application Bootstrap File — Laravel 9 style
 * ============================================================================
 *
 * [File Purpose]
 * This is the standard Laravel 9 application bootstrap file, responsible for
 * creating the Application container instance and binding three core
 * interfaces to their concrete implementations:
 *   - Illuminate\Contracts\Http\Kernel         → App\Http\Kernel
 *   - Illuminate\Contracts\Console\Kernel      → App\Console\Kernel
 *   - Illuminate\Contracts\Debug\ExceptionHandler → App\Exceptions\Handler
 *
 * [Difference from Laravel 11+]
 * Laravel 11+ uses `Application::configure()->...->create()` chained calls;
 * Laravel 9 instantiates Application directly and binds interfaces manually.
 *
 * [Invocation Timing]
 * public/index.php includes this file via `require_once`, returns the Application
 * instance, and subsequently obtains the HTTP/Console kernel via
 * `$app->make(Kernel::class)`.
 */

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;