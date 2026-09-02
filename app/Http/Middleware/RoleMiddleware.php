<?php

/**
 * ============================================================================
 * FoodShare - 角色权限中间件
 * ============================================================================
 *
 * FoodShare - Role Permission Middleware
 *
 * 【文件作用】
 * 本中间件在 HTTP 请求到达控制器之前拦截请求，根据当前登录用户的角色
 * （admin / donor / recipient）与路由所要求的角色进行比对，从而限制不同
 * 类型用户对页面的访问权限。例如：只有 admin 角色可以访问后台管理页面，
 * 只有 donor 角色可以访问捐赠发布页面。
 *
 * Purpose:
 * This middleware intercepts HTTP requests before they reach controllers,
 * comparing the currently authenticated user's role (admin / donor / recipient)
 * against the role required by the route, thereby restricting page access based
 * on user type. For example: only admin users can access the admin dashboard;
 * only donors can access the donation publishing page.
 *
 * 【工作流程】
 * 1. 检查用户是否已登录 — 未登录则重定向到登录页。
 * 2. 获取当前用户的角色字段（数据库中 role 列）。
 * 3. 将用户的角色与路由传入的 $role 参数进行不区分大小写的比对。
 * 4. 匹配成功 → 放行请求，继续执行后续中间件/控制器。
 * 5. 匹配失败 → 返回 403 Forbidden 响应。
 *
 * Workflow:
 * 1. Check if the user is authenticated — redirect to login if not.
 * 2. Retrieve the user's role field (the "role" column in the database).
 * 3. Compare the user's role against the route's $role parameter, case-insensitively.
 * 4. Match succeeds → allow the request through to subsequent middleware/controllers.
 * 5. Match fails → return a 403 Forbidden response.
 *
 * 【在路由中的使用方式】
 *   Route::middleware(['auth', 'role:admin'])->group(function () {
 *       Route::get('/admin/dashboard', [AdminController::class, 'index']);
 *   });
 *
 * Usage in routes:
 *   Route::middleware(['auth', 'role:admin'])->group(function () {
 *       Route::get('/admin/dashboard', [AdminController::class, 'index']);
 *   });
 *
 * 【安全原理】
 * - Auth::check() 确保只有已认证用户才能进入角色检查，防止匿名用户绕过。
 * - strtolower() 对角色值做大小写归一化处理，防止因数据库存储大小写差异
 *   导致的权限绕过（例如数据库中存 "Admin" 而路由要求 "admin"）。
 * - 采用"白名单"策略：只放行角色完全匹配的用户，不匹配的用户一律拒绝，
 *   这是安全设计中的最小权限原则。
 * - abort(403) 立即终止请求并返回 403 状态码，不会执行任何后续业务逻辑，
 *   这是"失败关闭"（fail-closed）的安全模式。
 *
 * Security principles:
 * - Auth::check() ensures only authenticated users reach the role check,
 *   preventing anonymous users from bypassing it.
 * - strtolower() normalizes role values for case-insensitive comparison,
 *   preventing permission bypass due to case differences in database storage
 *   (e.g. "Admin" in the database vs. "admin" required by the route).
 * - A "whitelist" strategy is used: only users with an exact role match are
 *   allowed through; all others are denied. This follows the principle of
 *   least privilege in security design.
 * - abort(403) immediately terminates the request with a 403 status code;
 *   no subsequent business logic executes. This is a "fail-closed" security
 *   pattern.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * 处理传入的 HTTP 请求，执行角色权限校验。
     *
     * Handle the incoming HTTP request and perform role permission verification.
     *
     * 【参数说明】
     * @param  Request  $request  当前 HTTP 请求实例，包含用户、路由、输入等所有请求上下文
     * @param  Closure  $next     闭包回调，用于将请求传递给下一个中间件或控制器
     * @param  string   $role     路由中声明的必需角色名称，支持的值：admin / donor / recipient
     *
     * Parameter details:
     * @param  Request  $request  The current HTTP request instance, containing all
     *                            request context (user, route, input, etc.)
     * @param  Closure  $next     A closure callback to pass the request to the next
     *                            middleware or controller
     * @param  string   $role     The required role name declared in the route.
     *                            Supported values: admin / donor / recipient
     *
     * 【返回值】
     * @return Response           角色匹配时返回 $next($request) 的正常响应；
     *                            未登录时返回重定向到登录页的响应；
     *                            角色不匹配时返回 403 Forbidden 响应。
     *
     * Return value:
     * @return Response           On role match, returns the normal response from
     *                            $next($request); when not logged in, returns a
     *                            redirect to the login page; on role mismatch,
     *                            returns a 403 Forbidden response.
     *
     * 【安全设计要点】
     * - 先验证登录状态，再验证角色权限，顺序不可颠倒。
     * - 使用严格不等于（!==）而非松散比较（!=），防止 PHP 类型转换导致逻辑漏洞。
     * - 失败时使用 abort() 辅助函数，它内部会抛出 HTTP 异常并触发 Laravel
     *   的异常处理器，保证错误响应格式统一且不泄漏敏感信息。
     *
     * Security design notes:
     * - Verify login state first, then role permission — order is critical.
     * - Use strict not-equal (!==) rather than loose comparison (!=) to prevent
     *   logic bugs from PHP type coercion.
     * - On failure, the abort() helper throws an HTTP exception and triggers
     *   Laravel's exception handler, ensuring a uniform error response format
     *   that does not leak sensitive information.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        /*
         * 【步骤一】身份认证检查
         * Auth::check() 验证当前会话是否关联了一个已登录的用户。
         * 如果用户未登录（游客或会话过期），直接重定向到登录页面，
         * 并通过闪存错误消息提示用户需要先登录。
         * 此处不会继续执行角色检查，从根本上避免了未认证访问。
         *
         * Step 1: Authentication check.
         * Auth::check() verifies that the current session is associated with a
         * logged-in user. If the user is not authenticated (guest or expired session),
         * they are redirected to the login page with a flash error message prompting
         * them to log in first. Role checking never executes in this case,
         * fundamentally preventing unauthenticated access.
         */
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors(['login' => '请先登录']);
        }

        /*
         * 【步骤二】获取当前用户模型
         * Auth::user() 返回通过认证 Guard 获取的当前用户 Eloquent 模型实例。
         * 该实例包含了数据库 users 表中的所有字段，包括 role 角色字段。
         *
         * Step 2: Retrieve the current user model.
         * Auth::user() returns the authenticated user's Eloquent model instance via
         * the authentication guard. This instance contains all fields from the users
         * table, including the role column.
         */
        $user = Auth::user();

        /*
         * 【步骤三】角色权限比对
         * 对用户的角色值和路由要求的角色值同时进行 strtolower() 大小写归一化处理。
         * 使用 !== 严格比较，防止 PHP 的松散类型转换意外产生 true 判定。
         *
         * 角色不匹配时调用 abort(403)，Laravel 底层机制：
         * 1. 抛出一个 Symfony HTTP 403 异常。
         * 2. Laravel 异常处理器（App\Exceptions\Handler）捕获该异常。
         * 3. 返回 403 状态码的 HTML/JSON 错误页面。
         * 4. 请求处理链在此处终止，后续中间件和控制器均不会执行。
         *
         * Step 3: Role permission comparison.
         * Both the user's role and the route's required role are normalized with
         * strtolower() for case-insensitive comparison. The strict !== operator
         * prevents PHP's loose type coercion from accidentally producing a true result.
         *
         * On mismatch, abort(403) triggers the following Laravel mechanism:
         * 1. A Symfony HTTP 403 exception is thrown.
         * 2. Laravel's exception handler (App\Exceptions\Handler) catches it.
         * 3. An HTML/JSON error page with a 403 status code is returned.
         * 4. The request pipeline terminates here — no further middleware or
         *    controller code executes.
         */
        if (strtolower($user->role) !== strtolower($role)) {
            abort(403, '您没有权限访问该页面');
        }

        /*
         * 【步骤四】放行请求
         * 角色校验通过后，调用 $next($request) 将请求传递给 HTTP 内核
         * 中注册的下一个中间件，如果当前是最后一个中间件，则传递给路由
         * 对应的控制器方法。此方法是 Laravel 中间件管道（Pipeline）模式
         * 的核心机制，每个中间件像一个洋葱层，请求依次穿过各层到达核心
         * 业务逻辑，响应再依次经过各层返回客户端。
         *
         * Step 4: Pass the request through.
         * Once the role check passes, $next($request) forwards the request to the
         * next middleware registered in the HTTP kernel, or to the route's controller
         * method if this is the last middleware. This is the core mechanism of
         * Laravel's middleware pipeline pattern — each middleware acts like an onion
         * layer: the request passes through each layer inward to the core business
         * logic, and the response passes back through each layer outward to the client.
         */
        return $next($request);
    }
}
