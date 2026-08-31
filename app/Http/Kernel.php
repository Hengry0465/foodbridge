<?php

/**
 * ============================================================================
 * FoodShare HTTP 内核 (HTTP Kernel)
 * ============================================================================
 *
 * 【文件作用】
 * 本文件是 Laravel 9 风格的 HTTP 内核，负责注册全局 HTTP 中间件栈、
 * 路由中间件组（web / api）以及中间件别名。它替代了 Laravel 11+ 瘦启动
 * 架构中的 `bootstrap/app.php` 的 `withMiddleware()` 闭包。
 *
 * 【执行时机】
 * Laravel 启动时通过 `Illuminate\Foundation\Application` 创建 HTTP Kernel 实例，
 * 每个 HTTP 请求都会通过此内核的中间件管道进行预处理。
 *
 * 【调用链】
 *   public/index.php → HTTP Kernel → 全局中间件 → 路由中间件组 → 控制器
 *
 * 【中间件分类】
 *   1. $middleware           — 全局中间件，每个 HTTP 请求都会经过
 *   2. $middlewareGroups     — 中间件组，可通过 'web' / 'api' 一次性应用多个中间件
 *   3. $middlewareAliases    — 中间件别名，可在路由定义中使用简短的字符串
 *
 * 【与 Laravel 11+ 的差异】
 * Laravel 11+ 使用 `bootstrap/app.php` 的链式调用注册中间件；
 * Laravel 9 使用本文件中的三个 protected 属性集中注册。
 *
 * ============================================================================
 * FoodShare HTTP Kernel
 * ============================================================================
 *
 * [File Purpose]
 * This file is the Laravel 9-style HTTP kernel. It is responsible for
 * registering the global HTTP middleware stack, route middleware groups
 * (web / api), and middleware aliases. It replaces the `withMiddleware()`
 * closure in `bootstrap/app.php` from the Laravel 11+ slim-boot architecture.
 *
 * [Invocation Timing]
 * When Laravel boots, the `Illuminate\Foundation\Application` creates an HTTP
 * Kernel instance, and every HTTP request flows through this kernel's middleware
 * pipeline.
 *
 * [Call Chain]
 *   public/index.php → HTTP Kernel → Global Middleware → Route Middleware
 *     Groups → Controller
 *
 * [Middleware Categories]
 *   1. $middleware        — Global middleware, every HTTP request passes through
 *   2. $middlewareGroups  — Middleware groups, apply multiple middleware via 'web' or 'api'
 *   3. $middlewareAliases — Middleware aliases, use short strings in route definitions
 *
 * [Difference from Laravel 11+]
 * Laravel 11+ uses chained calls in `bootstrap/app.php` to register middleware;
 * Laravel 9 uses three protected properties in this file for centralized registration.
 */

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * HTTP 内核类
 *
 * 继承自 Laravel 框架的 Illuminate\Foundation\Http\Kernel，
 * 通过三个属性配置中间件栈。
 *
 * HTTP Kernel class.
 *
 * Extends Laravel's Illuminate\Foundation\Http\Kernel,
 * configured via three properties for the middleware stack.
 */
class Kernel extends HttpKernel
{
    /**
     * ----------------------------------------------------------
     * 全局 HTTP 中间件栈
     * ----------------------------------------------------------
     * 这些中间件在每个 HTTP 请求处理期间都会执行。
     * 执行顺序：按照数组顺序依次处理（请求方向），
     *          然后逆序返回（响应方向）。
     *
     * 注意：此处仅保留应用自定义的 SecurityHeaders 中间件。
     * Laravel 9 框架的默认全局中间件由父类 Illuminate\Foundation\Http\Kernel 提供。
     *
     * Global HTTP middleware stack.
     * ----------------------------------------------------------
     * These middleware run during every HTTP request.
     * Execution order: processed in array order (request direction),
     *                  then reversed (response direction).
     *
     * Note: Only the custom SecurityHeaders middleware is registered here.
     * Laravel 9 framework's default global middleware is provided by the parent class.
     *
     * @var array<int, class-string>
     */
    protected $middleware = [
        // 应用自定义：安全响应头中间件
        // App custom: security response headers middleware
        \App\Http\Middleware\SecurityHeaders::class,
    ];

    /**
     * ----------------------------------------------------------
     * 路由中间件组
     * ----------------------------------------------------------
     * 通过分组的方式批量应用多个中间件。
     * 在 routes/web.php 中使用 Route::middleware('web') 即可应用 'web' 组内所有中间件。
     *
     * 路由中间件组：
     *   - web：用于浏览器访问的页面路由（包含会话、CSRF、Cookie 加解密等）
     *   - api：用于 API 接口路由（限流、路由模型绑定等，无 Session）
     *
     * Route middleware groups.
     * ----------------------------------------------------------
     * Apply multiple middleware in batches via grouping.
     * Use Route::middleware('web') in routes/web.php to apply all 'web' group middleware.
     *
     * Route middleware groups:
     *   - web: For browser-facing page routes (session, CSRF, cookie encryption, etc.)
     *   - api: For API endpoint routes (throttling, route model binding, no session)
     *
     * @var array<string, array<int, class-string>>
     */
    protected $middlewareGroups = [
        // web 中间件组：浏览器路由的标准中间件栈
        // Web middleware group: standard middleware stack for browser routes
        'web' => [
            // Cookie 加解密：保护客户端 Cookie 不被篡改
            // Cookie encryption: protect client cookies from tampering
            \Illuminate\Cookie\Middleware\EncryptCookies::class,

            // 将队列中的 Cookie 添加到响应
            // Add queued cookies to response
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,

            // 启动 Session：记录用户登录状态、闪存消息
            // Start session: track user login state, flash messages
            \Illuminate\Session\Middleware\StartSession::class,

            // 共享 Session 中的错误到视图：让 @error Blade 指令可用
            // Share session errors with views: enables @error Blade directive
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            // CSRF 令牌验证：防止跨站请求伪造攻击
            // CSRF token verification: prevent cross-site request forgery attacks
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,

            // 路由模型绑定：自动解析路由参数到 Eloquent 模型
            // Route model binding: auto-resolve route parameters to Eloquent models
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        // api 中间件组：API 路由的标准中间件栈
        // API middleware group: standard middleware stack for API routes
        'api' => [
            // API 限流：防止接口被恶意刷请求
            // API throttling: prevent API from being maliciously flooded
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',

            // 路由模型绑定
            // Route model binding
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * ----------------------------------------------------------
     * 路由中间件别名
     * ----------------------------------------------------------
     * 将中间件类绑定到简短的字符串别名，便于在路由定义中使用。
     *
     * 使用示例：
     *   Route::middleware(['auth', 'role:admin'])->group(function () {
     *       Route::get('/admin', [AdminController::class, 'index']);
     *   });
     *
     * 业务自定义别名：
     *   - 'role' → App\Http\Middleware\RoleMiddleware
     *     用于路由级角色权限校验。
     *
     * Route middleware aliases.
     * ----------------------------------------------------------
     * Bind middleware classes to short string aliases for use in route definitions.
     *
     * Example:
     *   Route::middleware(['auth', 'role:admin'])->group(function () {
     *       Route::get('/admin', [AdminController::class, 'index']);
     *   });
     *
     * Business custom aliases:
     *   - 'role' → App\Http\Middleware\RoleMiddleware
     *     For route-level role permission checks.
     *
     * @var array<string, class-string>
     */
    protected $middlewareAliases = [
        // Laravel 框架内置别名
        // Laravel framework built-in aliases
        'auth'             => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers'    => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'              => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'            => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed'           => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // 业务自定义别名：角色权限校验
        // Business custom alias: role permission check
        'role'             => \App\Http\Middleware\RoleMiddleware::class,
    ];
}