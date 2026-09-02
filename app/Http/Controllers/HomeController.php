<?php

/**
 * ============================================================
 * 首页控制器 — HomeController
 * ============================================================
 *
 * 【文件作用】
 * 负责处理用户登录后的首页展示逻辑，包括：
 *   - 获取当前登录用户信息
 *   - 根据当前时段生成问候语（早安/午安/晚安）
 *   - 根据用户角色生成个性化的欢迎副标题
 *   - 将角色英文标识转换为可读的中文标签
 *   - 渲染并返回首页视图
 *
 * 【所属模块】
 * 前端展示模块 — 首页与用户欢迎页
 *
 * 【业务流程位置】
 * 用户认证 → HomeController@index → home.blade.php（首页视图）
 * 仅允许已认证用户访问，通常由认证中间件 `auth` 保护路由。
 *
 * 【依赖关系】
 *   - Illuminate\Support\Facades\Auth：获取当前已认证用户实例
 *   - App\Http\Controllers\Controller：继承基础控制器（提供中间件、验证等通用能力）
 *   - 视图资源：resources/views/home.blade.php
 *   - 用户模型：App\Models\User（通过 Auth::user() 返回）
 *
 * ============================================================
 * Home Controller — HomeController
 * ============================================================
 *
 * [File Purpose]
 * Handles the homepage display logic after user login, including:
 *   - Retrieving the currently logged-in user's information
 *   - Generating a greeting based on the time of day (Good Morning/Afternoon/Evening)
 *   - Generating a personalized welcome subtitle based on user role
 *   - Converting role identifiers to human-readable Chinese labels
 *   - Rendering and returning the homepage view
 *
 * [Module]
 * Frontend display module — Homepage & user welcome page
 *
 * [Business Flow Position]
 * User authentication → HomeController@index → home.blade.php (homepage view)
 * Only authenticated users are allowed access; routes are typically protected
 * by the `auth` middleware.
 *
 * [Dependencies]
 *   - Illuminate\Support\Facades\Auth: Retrieves the currently authenticated user instance
 *   - App\Http\Controllers\Controller: Base controller (provides middleware, validation, etc.)
 *   - View resource: resources/views/home.blade.php
 *   - User model: App\Models\User (returned via Auth::user())
 */
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * 显示首页（需要用户已登录认证）
     *
     * 【用途】
     * 这是用户登录成功后的入口方法，负责组装首页所需的所有展示数据，
     * 包括问候语、角色欢迎词和角色标签，然后渲染首页视图。
     *
     * 【参数】
     * 无方法参数。通过 Laravel 路由直接调用，不接收 URL 参数或请求体。
     *
     * 【返回值】
     * Illuminate\View\View — Laravel 视图实例，渲染 `home.blade.php`，
     * 并向视图模板注入以下变量：
     *   - $user         (App\Models\User) 当前登录用户实例
     *   - $greeting     (string)         基于时段的英文问候语
     *   - $greetingSub  (string)         基于角色的欢迎副标题
     *   - $roleLabel    (string)         角色的可读显示标签
     *
     * 【调用时机】
     *   - 用户登录成功后由 Laravel 认证流程重定向至此
     *   - 路由定义中需挂载 `auth` 中间件，确保未登录用户无法直接访问
     *
     * 【关键步骤】
     *   1. 通过 Auth::user() 获取当前认证用户
     *   2. 根据当前小时数判断时段，生成对应问候语
     *   3. 根据用户角色（donor / recipient / admin）匹配欢迎词
     *   4. 将角色枚举值映射为可读标签
     *   5. 将所有变量打包传入 home 视图并返回渲染结果
     *
     * Display the homepage (requires authenticated user).
     *
     * [Purpose]
     * This is the entry point after successful login. It assembles all display data
     * needed for the homepage — greeting, role-based welcome message, and role label —
     * then renders the homepage view.
     *
     * [Parameters]
     * None. Called directly by Laravel routing; receives no URL parameters or request body.
     *
     * [Returns]
     * Illuminate\View\View — a Laravel view instance rendering `home.blade.php`,
     * with the following variables injected into the view template:
     *   - $user         (App\Models\User) The currently logged-in user instance
     *   - $greeting     (string)          Time-of-day based greeting in English
     *   - $greetingSub  (string)          Role-based welcome subtitle
     *   - $roleLabel    (string)          Human-readable display label for the role
     *
     * [When Called]
     *   - Redirected here by Laravel's authentication flow after successful login
     *   - The route definition must use the `auth` middleware to prevent unauthenticated access
     *
     * [Key Steps]
     *   1. Retrieve the currently authenticated user via Auth::user()
     *   2. Determine the time of day from the current hour and generate a greeting
     *   3. Match the user's role (donor / recipient / admin) to a welcome message
     *   4. Map the role enum value to a readable label
     *   5. Package all variables and pass them to the home view for rendering
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // --- 步骤1：获取当前登录用户 ---
        //
        // --- Step 1: Get the currently logged-in user ---
        //
        // Auth::user() 返回当前 session 关联的 User 模型实例
        //
        // Auth::user() returns the User model instance associated with the current session.
        //
        // 若用户未登录则返回 null（由 auth 中间件保证不会出现此情况）
        //
        // Returns null if the user is not logged in (guaranteed not to happen due to the auth middleware).
        $user = Auth::user();

        // --- 步骤2：根据当前时段生成问候语 ---
        //
        // --- Step 2: Generate a greeting based on the time of day ---
        //
        // 获取当前服务器时间的小时数（24小时制，'H' 格式）
        //
        // Get the current server time's hour (24-hour format, 'H' format).
        $hour = (int) now()->format('H');
        // match 表达式按时间段匹配问候语：
        //   - 0:00 ~ 11:59  → Good Morning（早安）
        //   - 12:00 ~ 17:59 → Good Afternoon（午安）
        //   - 18:00 ~ 23:59 → Good Evening（晚安）
        //
        // The match expression maps time ranges to greetings:
        //   - 0:00 ~ 11:59  → Good Morning
        //   - 12:00 ~ 17:59 → Good Afternoon
        //   - 18:00 ~ 23:59 → Good Evening
        $greeting = match (true) {
            $hour < 12  => 'Good Morning',
            $hour < 18  => 'Good Afternoon',
            default     => 'Good Evening',
        };

        // --- 步骤3：根据用户角色生成欢迎副标题 ---
        //
        // --- Step 3: Generate a welcome subtitle based on user role ---
        //
        // 针对不同角色展示不同的个性化欢迎信息：
        //   - donor（捐赠者）：感谢慷慨捐赠，传递温暖与希望
        //   - recipient（受助者）：介绍平台使命，连接需求与资源
        //   - admin（管理员）：欢迎进入后台，感谢维护平台运行
        //   - 其他角色：显示默认欢迎语
        //
        // Display personalized welcome info for each role:
        //   - donor: Thanks the donor for their generosity and spreading warmth & hope
        //   - recipient: Introduces the platform's mission to connect needs with resources
        //   - admin: Welcomes to the admin panel and thanks for keeping the platform running
        //   - Other roles: Shows a default welcome message
        $greetingSub = match ($user->role) {
            'donor'     => 'Thank you for your generosity. Every donation brings warmth and hope to those in need.',
            'recipient' => 'FoodShare is dedicated to connecting people in need with available resources.',
            'admin'     => 'Welcome to the admin panel. Thank you for keeping the platform running smoothly.',
            default     => 'Welcome back to FoodShare — the food donation platform.',
        };

        // --- 步骤4：将角色枚举值映射为可读标签 ---
        //
        // --- Step 4: Map role enum values to readable labels ---
        //
        // 用于在视图模板中显示用户身份标识（如页面标题、用户卡片等）：
        //   - donor     → Donor（捐赠者）
        //   - recipient → Recipient（受助者）
        //   - admin     → Administrator（管理员）
        //   - 未知角色   → 直接使用原始角色值作为兜底
        //
        // Used to display user identity labels in view templates (page titles, user cards, etc.):
        //   - donor     → Donor
        //   - recipient → Recipient
        //   - admin     → Administrator
        //   - Unknown role → Falls back to the raw role value
        $roleLabel = match ($user->role) {
            'donor'     => 'Donor',
            'recipient' => 'Recipient',
            'admin'     => 'Administrator',
            default     => $user->role,
        };

        // --- 步骤5：渲染首页视图 ---
        //
        // --- Step 5: Render the homepage view ---
        //
        // compact() 将以上变量打包为关联数组注入 home.blade.php 视图
        //
        // compact() packages the above variables as an associative array and injects them into home.blade.php.
        //
        // 路由示例：Route::get('/home', [HomeController::class, 'index'])->middleware('auth');
        //
        // Example route: Route::get('/home', [HomeController::class, 'index'])->middleware('auth');
        return view('home', compact('user', 'greeting', 'greetingSub', 'roleLabel'));
    }
}
