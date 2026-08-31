<?php

/**
 * ============================================================
 * ★★★ 应用入口文件 — 所有 HTTP 请求的起点 ★★★
 * ============================================================
 *
 * 【通俗解释】
 * 这是整个 FoodShare 平台的大门。无论用户访问哪个页面
 * （登录页、注册页、首页、重置密码页...），浏览器发送的请求
 * 都会先到达这个文件，然后由它转交给 Laravel 框架处理。
 *
 * 你可以把它想象成写字楼的一楼大堂前台：
 * - 所有访客（HTTP 请求）都从这里进入
 * - 前台（这个文件）检查大楼是否在维护中
 * - 然后引导访客到正确的楼层（Controller）
 *
 * 【执行流程】
 * 1. 记录请求开始时间（用于性能分析）
 * 2. 检查应用是否处于维护模式（如 php artisan down）
 * 3. 加载 Composer 自动加载器（让 PHP 能找到所有类文件）
 * 4. 启动 Laravel 应用实例（bootstrap/app.php）
 * 5. 捕获 HTTP 请求并交给应用处理
 * 6. 应用通过路由匹配 → 中间件过滤 → 控制器处理 → 返回响应
 *
 * 【重要说明 — 安全架构】
 * - public/ 是唯一对外公开的目录（又称"文档根目录"或 Document Root）
 * - Web 服务器（Nginx/Apache）配置将域名根路径映射到此目录
 * - 其他所有 PHP 文件（app/、config/、routes/、.env 等）存放在 public/ 的
 *   上级目录中，浏览器无法通过 URL 直接访问它们
 * - 这是出于安全考虑：
 *   (a) 防止源代码被下载 → 攻击者访问 example.com/../app/Models/User.php 会失败
 *   (b) 防止配置文件泄露 → .env 包含数据库密码、API密钥等敏感信息，绝不能公开
 *   (c) 防止日志文件泄露 → storage/logs/ 中的日志可能含用户隐私数据
 * - Apache/Nginx 通过 URL 重写将所有非文件请求指向此 index.php
 *   例如：用户访问 /foods/123 → Nginx 内部转发给 index.php → Laravel 路由解析
 *   过程对用户透明，用户永远不知道自己被路由到了这个入口文件
 *
 * ============================================================
 * ★★★ Application Entry Point — the starting point for all HTTP requests ★★★
 * ============================================================
 *
 * [Plain-language explanation]
 * This is the front door of the entire FoodShare platform. No matter which page a user visits
 * (login, registration, home, password reset...), the browser's request always
 * reaches this file first, which then hands it off to the Laravel framework.
 *
 * Think of it as the front desk in an office building lobby:
 * - All visitors (HTTP requests) enter here
 * - The front desk (this file) checks whether the building is under maintenance
 * - Then directs visitors to the correct floor (Controller)
 *
 * [Execution flow]
 * 1. Record the request start time (for performance profiling)
 * 2. Check whether the application is in maintenance mode (e.g. php artisan down)
 * 3. Load the Composer autoloader (so PHP can find all class files)
 * 4. Bootstrap the Laravel application instance (bootstrap/app.php)
 * 5. Capture the HTTP request and hand it to the application for processing
 * 6. The application: route matching → middleware filtering → controller processing → return response
 *
 * [Important note — Security architecture]
 * - public/ is the only publicly exposed directory (aka "Document Root").
 * - The web server (Nginx/Apache) maps the domain root to this directory.
 * - All other PHP files (app/, config/, routes/, .env, etc.) live in the parent
 *   directory of public/ and are NOT directly accessible by URL from a browser.
 * - This is for security reasons:
 *   (a) Prevents source code from being downloaded → an attacker visiting
 *       example.com/../app/Models/User.php will fail.
 *   (b) Prevents configuration file leaks → .env contains database passwords,
 *       API keys, and other sensitive information — it must never be public.
 *   (c) Prevents log file leaks → logs in storage/logs/ may contain user privacy data.
 * - Apache/Nginx rewrites all non-file requests to this index.php via URL rewriting.
 *   Example: user visits /foods/123 → Nginx internally forwards to index.php → Laravel route resolution.
 *   This process is transparent to the user, who never knows they were routed to this entry point.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// ================================================================
// 第 1 步：定义启动时间常量
// ================================================================
// LARAVEL_START 记录应用开始处理请求的精确时刻（微秒精度）
// 用途：框架内部（如 Laravel Debugbar、Telescope）用此值计算总响应时间
// 计算公式：总耗时 = 当前 microtime(true) - LARAVEL_START
// 注意：该常量必须在此文件最顶部定义，才能准确反映"从入口到响应"的完整耗时
//
// ================================================================
// Step 1: Define the start-time constant
// ================================================================
// LARAVEL_START records the exact moment (microsecond precision) when the app begins processing.
// Purpose: internal framework tooling (Laravel Debugbar, Telescope) uses it to measure total response time.
// Formula: total elapsed = current microtime(true) - LARAVEL_START
// Note: this constant must be defined at the very top of this file to accurately capture
//   the complete "entry → response" duration.
define('LARAVEL_START', microtime(true));

// ================================================================
// 第 2 步：维护模式检查
// ================================================================
// 触发方式：运维人员执行 `php artisan down` 命令
// 原理：artisan down 会在 storage/framework/ 下生成 maintenance.php 文件
//       该文件存在即代表"网站暂停服务"
// 安全价值：在数据库迁移、安全漏洞修补等维护窗口期间，
//         阻止外部用户看到不完整的页面或错误信息
// 恢复方式：执行 `php artisan up` 命令，删除 maintenance.php 文件
// 补充说明：php artisan down --secret=xxx 可生成一个带密钥的维护模式，
//         允许运维人员通过 ?secret=xxx 参数绕过维护页面预览网站
//
// ================================================================
// Step 2: Check for maintenance mode
// ================================================================
// Trigger: an operator runs `php artisan down`.
// How it works: artisan down generates a maintenance.php file in storage/framework/;
//   the presence of that file means "site is temporarily out of service."
// Security value: during maintenance windows (database migrations, security patches, etc.),
//   prevents external users from seeing incomplete pages or error messages.
// Recovery: run `php artisan up` to delete the maintenance.php file.
// Additional note: `php artisan down --secret=xxx` creates a secret-keyed maintenance mode
//   so operators can bypass the maintenance page and preview the site via ?secret=xxx.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// ================================================================
// 第 3 步：加载 Composer 自动加载器
// ================================================================
// Composer 是 PHP 生态的依赖管理工具（类似 npm、pip）
// vendor/autoload.php 按 PSR-4 规范将命名空间映射到文件路径
// 例如：use App\Models\User → 自动查找 app/Models/User.php
// 例如：use Illuminate\Support\Str → 自动查找 vendor/laravel/framework/...
// 原理：Composer 在 install/update 时生成了一套类名→文件路径的映射表
//       运行时通过 spl_autoload_register() 注册自动加载函数
//       当 PHP 遇到未定义的类时触发该函数，按映射表找到对应文件并 require
// 如果没有这一步，你需要手动写几百行 require 来引入各种类文件
//
// ================================================================
// Step 3: Load the Composer autoloader
// ================================================================
// Composer is PHP's dependency manager (similar to npm or pip).
// vendor/autoload.php maps namespaces to file paths according to PSR-4.
// Example: use App\Models\User → auto-discovers app/Models/User.php
// Example: use Illuminate\Support\Str → auto-discovers vendor/laravel/framework/...
// How it works: Composer generates a class-name → file-path map during install/update;
//   at runtime, spl_autoload_register() registers an autoload function;
//   when PHP encounters an undefined class, the function fires, finds the file, and requires it.
// Without this step, you would need hundreds of manual require statements for every class.
require __DIR__.'/../vendor/autoload.php';

// ================================================================
// 第 4 步：启动 Laravel 应用实例（单例模式）
// ================================================================
// bootstrap/app.php 负责：
//   - 创建 Application 容器（Laravel 的核心依赖注入容器）
//   - 绑定核心接口到具体实现（如 Illuminate\Contracts\Http\Kernel）
//   - 注册基础服务提供者（EventServiceProvider、RouteServiceProvider 等）
//   - 配置异常处理机制
// require_once 确保整个请求生命周期中只有一个 Application 实例
// （单例模式，避免重复初始化造成的性能浪费和状态不一致）
//
// ================================================================
// Step 4: Bootstrap the Laravel application instance (singleton pattern)
// ================================================================
// bootstrap/app.php is responsible for:
//   - Creating the Application container (Laravel's core DI container)
//   - Binding core interfaces to concrete implementations (e.g. Illuminate\Contracts\Http\Kernel)
//   - Registering base service providers (EventServiceProvider, RouteServiceProvider, etc.)
//   - Configuring the exception handling mechanism
// require_once ensures only one Application instance exists per request lifecycle
// (singleton pattern — avoids wasted performance and state inconsistency from re-initialization)
/** @var Application $app — 类型提示，让 IDE 能提供代码补全和类型检查 */
/** @var Application $app — Type hint so the IDE can provide code completion and type checking */
$app = require_once __DIR__.'/../bootstrap/app.php';

/*
| 通过 IoC 容器解析 HTTP 内核实例。
| 这是 Laravel 9 标准的应用启动流程：获取 HTTP 内核后处理请求，
| 然后调用 terminate() 完成终止逻辑（中间件收尾、资源清理等）。

| Resolve the HTTP kernel instance via the IoC container.
| This is the Laravel 9 standard application boot flow: obtain the HTTP
| kernel, handle the request, then call terminate() to complete cleanup
| logic (middleware finalization, resource cleanup, etc.).
*/

/** @var Kernel $kernel — HTTP 内核实例 / HTTP Kernel instance */
$kernel = $app->make(Kernel::class);

// ================================================================
// 第 5 步：捕获请求并处理（核心调度）
// ================================================================
// Request::capture() 做了什么：
//   从 PHP 超全局变量创建 Request 对象：
//     $_GET     → query string 参数（?page=1&search=食物）
//     $_POST    → 表单提交的数据
//     $_COOKIE  → 客户端 Cookie
//     $_FILES   → 上传的文件
//     $_SERVER  → 请求方法(GET/POST)、URL路径、请求头等
//   Symfony HttpFoundation 库负责创建，Laravel 在此基础上扩展
//
// $app->handleRequest() 的处理流程：
//   ① 将 Request 送入 HTTP Kernel（app/Http/Kernel.php）
//   ② Kernel 将 Request 依次穿过全局中间件栈：
//      - TrustProxies        → 信任反向代理（Nginx/Cloudflare）转发的真实IP
//      - PreventRequestsDuringMaintenance → 维护模式下拒绝非白名单请求
//      - ValidatePostSize    → 拒绝过大的 POST 请求体（防止内存耗尽攻击）
//      - TrimStrings         → 自动去除输入字符串两端的空格
//      - ConvertEmptyStringsToNull → 将空字符串转为 null（数据库友好）
//   ③ 路由匹配：在 routes/web.php 或 routes/api.php 中找匹配的路由
//   ④ 执行路由级别的中间件（如 auth、verified、admin 等）
//   ⑤ 调用对应的 Controller 方法处理业务逻辑
//   ⑥ Controller 返回的响应（视图、JSON、重定向等）经中间件反向穿回
//   ⑦ 最终 Response 对象的 send() 方法输出 HTTP 状态码、头和内容体
//
// ================================================================
// Step 5: Capture the request and handle it (core dispatch)
// ================================================================
// What Request::capture() does:
//   Creates a Request object from PHP superglobals:
//     $_GET     → Query string parameters (e.g. ?page=1&search=food)
//     $_POST    → Form-submitted data
//     $_COOKIE  → Client-side cookies
//     $_FILES   → Uploaded files
//     $_SERVER  → Request method (GET/POST), URL path, request headers, etc.
//   Built on Symfony's HttpFoundation library, extended by Laravel.
//
// $app->handleRequest() processing pipeline:
//   ① Feeds the Request into the HTTP Kernel (app/Http/Kernel.php)
//   ② Kernel passes the Request through the global middleware stack:
//      - TrustProxies        → Trust real IPs forwarded by reverse proxies (Nginx/Cloudflare)
//      - PreventRequestsDuringMaintenance → Block non-allowlisted requests in maintenance mode
//      - ValidatePostSize    → Reject oversized POST bodies (prevent memory-exhaustion attacks)
//      - TrimStrings         → Auto-trim whitespace from input strings
//      - ConvertEmptyStringsToNull → Convert empty strings to null (database-friendly)
//   ③ Route matching: find a matching route in routes/web.php or routes/api.php
//   ④ Execute route-level middleware (e.g. auth, verified, admin)
//   ⑤ Invoke the matched Controller method to process business logic
//   ⑥ The Controller's response (view, JSON, redirect, etc.) passes back through middleware
//   ⑦ Finally, the Response object's send() method outputs the HTTP status, headers, and body
$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
