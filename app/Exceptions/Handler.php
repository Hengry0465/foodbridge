<?php

/**
 * ============================================================================
 * FoodShare 异常处理器 (Exception Handler)
 * ============================================================================
 *
 * 【文件作用】
 * 本文件是 Laravel 9 风格的全局异常处理器，负责：
 *   1. 报告异常（写入日志、推送通知等）
 *   2. 渲染异常（决定返回 HTML 错误页还是 JSON 响应）
 *
 * 它替代了 Laravel 11+ 瘦启动架构中 `bootstrap/app.php` 的
 * `withExceptions()` 闭包配置。
 *
 * 【API 异常 JSON 渲染】
 * 原 Laravel 11+ 配置：
 *   $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
 *
 * 本文件通过 register() 方法中的 renderable() 回调实现相同效果：
 *   当请求路径以 'api/' 开头时，所有异常以 JSON 格式返回。
 *
 * 【与 Laravel 11+ 的差异】
 *   Laravel 11+ 使用链式调用（shouldRenderJsonWhen、dontReport 等）；
 *   Laravel 9 通过继承 ExceptionHandler 类并配置受保护属性 + register() 方法实现。
 *
 * ============================================================================
 * FoodShare Exception Handler
 * ============================================================================
 *
 * [File Purpose]
 * This file is the Laravel 9-style global exception handler, responsible for:
 *   1. Reporting exceptions (writing logs, pushing notifications, etc.)
 *   2. Rendering exceptions (deciding whether to return HTML error pages or JSON responses)
 *
 * It replaces the `withExceptions()` closure configuration in `bootstrap/app.php`
 * from the Laravel 11+ slim-boot architecture.
 *
 * [API Exception JSON Rendering]
 * Original Laravel 11+ configuration:
 *   $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
 *
 * This file achieves the same effect via the renderable() callback in register():
 *   When the request path starts with 'api/', all exceptions are returned as JSON.
 *
 * [Difference from Laravel 11+]
 *   Laravel 11+ uses chained calls (shouldRenderJsonWhen, dontReport, etc.);
 *   Laravel 9 inherits ExceptionHandler class and configures protected properties + register() method.
 */

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * 异常处理器类
 *
 * 继承自 Laravel 框架的 Illuminate\Foundation\Exceptions\Handler。
 * 通过配置受保护属性和 register() 方法来自定义异常处理逻辑。
 *
 * Exception Handler class.
 *
 * Extends Laravel's Illuminate\Foundation\Exceptions\Handler.
 * Customizes exception handling by configuring protected properties and register() method.
 */
class Handler extends ExceptionHandler
{
    /**
     * ----------------------------------------------------------
     * 不需要报告（记录到日志）的异常类型列表
     * ----------------------------------------------------------
     * 列出不需要写入日志的异常类型，通常用于：
     *   - 404 Not Found（用户输入错误的 URL）
     *   - CSRF Token 过期（用户长时间停留后提交表单）
     *   - 验证失败（用户的正常操作错误）
     *
     * 这些异常属于"预期内"的业务异常，不需要记录到日志中。
     *
     * A list of the exception types that are not reported.
     * ----------------------------------------------------------
     * Lists exception types that should not be written to logs, typically for:
     *   - 404 Not Found (user entered wrong URL)
     *   - CSRF token expired (user stayed too long before submitting form)
     *   - Validation failure (normal user operation errors)
     *
     * These are "expected" business exceptions that don't need log recording.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * ----------------------------------------------------------
     * 不需要闪存到 session 的输入字段
     * ----------------------------------------------------------
     * 当验证失败时，Laravel 默认会将所有输入数据闪存到 session，
     * 以便在表单重新显示时填充用户已填写的内容。
     *
     * 但敏感字段（密码、确认密码等）不应回填到表单，防止泄露。
     *
     * A list of the inputs that are never flashed for validation exceptions.
     * ----------------------------------------------------------
     * When validation fails, Laravel by default flashes all input data to session,
     * so the form can be re-displayed with user-filled content.
     *
     * But sensitive fields (password, password confirmation, etc.) should NOT be
     * refilled into the form to prevent leakage.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * ----------------------------------------------------------
     * 注册异常处理回调
     * ----------------------------------------------------------
     * 此方法在父类构造函数中被调用，用于注册：
     *   1. 自定义报告回调
     *   2. 自定义渲染回调（renderable）
     *
     * 本项目重点实现 API JSON 渲染：
     *   当请求路径以 'api/' 开头时，所有异常以 JSON 格式返回，
     *   而不是默认的 HTML 错误页面。
     *
     * Register the exception handling callbacks for the application.
     * ----------------------------------------------------------
     * This method is called in the parent class's constructor to register:
     *   1. Custom reporting callbacks
     *   2. Custom rendering callbacks (renderable)
     *
     * This project focuses on implementing API JSON rendering:
     *   When the request path starts with 'api/', all exceptions are returned
     *   as JSON, instead of the default HTML error page.
     *
     * @return void
     */
    public function register(): void
    {
        // 注册 API JSON 渲染回调
        // Register API JSON rendering callback
        $this->renderable(function (Throwable $e, Request $request) {
            // 仅处理以 'api/' 开头的请求路径
            // Only handle request paths starting with 'api/'
            if ($request->is('api/*')) {
                return $this->renderJsonException($e);
            }

            // 其他路径的异常使用 Laravel 默认渲染逻辑
            // For other paths, use Laravel's default rendering logic
            return null;
        });
    }

    /**
     * ----------------------------------------------------------
     * 将异常渲染为 JSON 响应（API 用）
     * ----------------------------------------------------------
     * 根据异常类型返回合适的 HTTP 状态码和 JSON 内容。
     *
     * 安全考量：
     *   - 生产环境（APP_DEBUG=false）不应暴露详细错误信息
     *   - 仅返回 message 字段，避免泄露堆栈跟踪、文件路径、SQL 语句等
     *
     * Render an exception as a JSON response (for API).
     * ----------------------------------------------------------
     * Returns appropriate HTTP status code and JSON content based on exception type.
     *
     * Security considerations:
     *   - Production environments (APP_DEBUG=false) should not expose detailed error info
     *   - Only the message field is returned, avoiding stack traces, file paths, SQL statements, etc.
     *
     * @param  \Throwable  $e  要渲染的异常 / Exception to render
     * @return \Illuminate\Http\JsonResponse  JSON 响应 / JSON response
     */
    protected function renderJsonException(Throwable $e): \Illuminate\Http\JsonResponse
    {
        // 根据异常类型确定 HTTP 状态码
        // Determine HTTP status code based on exception type
        $statusCode = 500;

        if ($e instanceof HttpExceptionInterface) {
            // HTTP 异常（404、403、405 等）
            // HTTP exceptions (404, 403, 405, etc.)
            $statusCode = $e->getStatusCode();
        } elseif ($e instanceof AuthenticationException) {
            // 未认证异常
            // Authentication exception
            $statusCode = 401;
        } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
            // 验证异常
            // Validation exception
            $statusCode = 422;
        }

        // 返回标准化 JSON 错误响应
        // Return standardized JSON error response
        return response()->json([
            'message' => $e->getMessage() ?: 'Server Error',
            'errors'  => $e instanceof \Illuminate\Validation\ValidationException
                ? $e->errors()
                : [],
        ], $statusCode);
    }

    /**
     * ----------------------------------------------------------
     * 将未认证用户重定向到登录页（非 API 请求）
     * ----------------------------------------------------------
     * 当用户访问需要认证的页面但未登录时，
     * Laravel 默认会调用此方法决定重定向行为。
     *
     * Convert an authentication exception into a redirect response (for non-API requests).
     * ----------------------------------------------------------
     * When a user accesses a page that requires authentication but isn't logged in,
     * Laravel by default calls this to determine the redirect behavior.
     *
     * @param  \Illuminate\Http\Request  $request  当前请求 / Current request
     * @param  \Illuminate\Auth\AuthenticationException  $exception  认证异常 / Authentication exception
     * @return \Symfony\Component\HttpFoundation\Response  重定向响应或 null / Redirect response or null
     */
    protected function unauthenticated($request, AuthenticationException $exception): ?Response
    {
        // API 请求返回 401 JSON 响应
        // API request returns 401 JSON response
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 浏览器请求重定向到登录页（路由名 'login'）
        // Browser request redirects to login page (route name 'login')
        return redirect()->guest(route('login'));
    }
}