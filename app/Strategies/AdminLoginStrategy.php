<?php

namespace App\Strategies;

use App\Models\User;

/**
 * ============================================================================
 * AdminLoginStrategy — 管理员登录策略（具体策略类 / Concrete Strategy）
 * ============================================================================
 *
 * 【设计模式】
 * 本类是"策略模式（Strategy Pattern）"中的**具体策略（Concrete Strategy）**角色。
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │                     策略模式 — 类角色关系图                       │
 * ├──────────────────────────────────────────────────────────────────┤
 * │                                                                  │
 * │  LoginStrategyInterface（策略接口 / Strategy）                   │
 * │  ├─ 声明了 handle(User): string 方法                            │
 * │  ├─ AdminLoginStrategy（本类）—— 管理员登录策略                 │
 * │  ├─ DonorLoginStrategy        —— 捐赠者登录策略                 │
 * │  └─ RecipientLoginStrategy    —— 受赠者登录策略                 │
 * │                                                                  │
 * │  AuthService::getLoginStrategy()（上下文 / Context）             │
 * │  └─ 通过 match 表达式选择具体策略，并调用 handle() 方法         │
 * │                                                                  │
 * └──────────────────────────────────────────────────────────────────┘
 *
 * 【为什么需要策略模式 — 解决的问题】
 * 在 FoodShare 平台中，存在三种用户角色：管理员（admin）、捐赠者（donor）、
 * 受赠者（recipient）。每种角色登录成功后需要跳转到不同的页面：
 *   - 管理员 → 后台管理仪表盘（home）
 *   - 捐赠者 → 捐赠管理面板（home）
 *   - 受赠者 → 可领取食品列表（home）
 *
 * 如果不使用策略模式，在 AuthService::login() 方法中就需要写一长串
 * if/elseif/else 或 switch/case 来判断角色并决定跳转路由。这会带来三个问题：
 *
 *   1. 违反"开闭原则（Open-Closed Principle, OCP）"——
 *      新增角色时必须修改 AuthService 的 login() 方法，增加了引入 bug 的风险。
 *
 *   2. 违反"单一职责原则（Single Responsibility Principle, SRP）"——
 *      AuthService 既要处理认证逻辑，又要负责不同角色的跳转路由决策，
 *      导致类越来越臃肿。
 *
 *   3. 难以进行单元测试——
 *      跳转逻辑与认证逻辑耦合在一起，无法对跳转路由逻辑进行独立测试。
 *
 * 使用策略模式后，**每种角色的跳转逻辑被封装在各自的策略类中**，
 * 彼此独立、互不干扰。新增角色时，只需：
 *   1. 新建一个实现了 LoginStrategyInterface 的策略类
 *   2. 在 AuthService::getLoginStrategy() 的 match 中增加一行
 *   3. 无需修改 AuthService::login() 主流程
 *
 * 【类之间如何协作】
 * 1. AuthService::login() 在密码验证通过后，调用 getLoginStrategy($role)
 *    根据用户角色获取对应的策略实例。
 * 2. getLoginStrategy() 返回 LoginStrategyInterface 类型的对象（多态）。
 * 3. AuthService 调用策略实例的 handle($user) 方法，该方法返回跳转路由名称。
 * 4. 调用方（AuthService）不需要知道具体是哪个策略类在工作，
 *    只需面向接口编程（Program to Interface, not Implementation）。
 *
 * 【本类职责】
 * 处理 admin 角色用户的登录后逻辑：
 *   - 可以初始化管理员权限（如加载角色权限列表到 Session）
 *   - 可以记录管理员登录日志（如登录时间、IP 地址等审计信息）
 *   - 决定跳转到管理后台首页
 *
 * 【扩展场景示例】
 * 如果未来需要新增一个"超级管理员（super_admin）"角色，只需：
 *   1. 新建 SuperAdminLoginStrategy 类实现 LoginStrategyInterface
 *   2. 在 handle() 中返回 'super_admin.dashboard' 路由
 *   3. 在 AuthService::getLoginStrategy() 的 match 中添加一行：
 *      'super_admin' => new SuperAdminLoginStrategy()
 *
 *   无需修改 AdminLoginStrategy（本文件），也无需修改 DonorLoginStrategy、
 *   RecipientLoginStrategy 或 AuthService::login() 方法。
 *
 * 【被哪些类调用】
 * - App\Services\AuthService::getLoginStrategy()
 *   （由 AuthService::login() 间接触发，仅在用户角色 == 'admin' 时被实例化）
 *
 * 【依赖的类】
 * - App\Models\User（作为 handle() 方法的参数传入，用于获取管理员信息）
 * - App\Strategies\LoginStrategyInterface（本类实现的接口，定义策略契约）
 *
 * ============================================================================
 * AdminLoginStrategy — Admin Login Strategy (Concrete Strategy)
 * ============================================================================
 *
 * [Design Pattern]
 * This class acts as the **Concrete Strategy** role in the Strategy Pattern.
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │          Strategy Pattern — Class Role Relationship Diagram      │
 * ├──────────────────────────────────────────────────────────────────┤
 * │                                                                  │
 * │  LoginStrategyInterface (Strategy Interface)                     │
 * │  ├─ Declares the handle(User): string method                    │
 * │  ├─ AdminLoginStrategy (this class) — Admin login strategy     │
 * │  ├─ DonorLoginStrategy          — Donor login strategy         │
 * │  └─ RecipientLoginStrategy      — Recipient login strategy     │
 * │                                                                  │
 * │  AuthService::getLoginStrategy() (Context)                      │
 * │  └─ Selects concrete strategy via match, then calls handle()   │
 * │                                                                  │
 * └──────────────────────────────────────────────────────────────────┘
 *
 * [Why the Strategy Pattern — Problems It Solves]
 * The FoodShare platform has three user roles: admin, donor, and recipient.
 * Each role needs a different post-login redirect:
 *   - Admin     → Admin dashboard (home)
 *   - Donor     → Donation management panel (home)
 *   - Recipient → Available food list (home)
 *
 * Without the Strategy Pattern, AuthService::login() would need a long
 * if/elseif/else or switch/case chain to determine the role and redirect
 * route. This causes three problems:
 *
 *   1. Violates the Open-Closed Principle (OCP) —
 *      Adding a new role requires modifying AuthService::login(), increasing
 *      the risk of introducing bugs.
 *
 *   2. Violates the Single Responsibility Principle (SRP) —
 *      AuthService handles both authentication logic and role-based redirect
 *      routing, making the class increasingly bloated.
 *
 *   3. Difficult to unit-test —
 *      Redirect logic is coupled with authentication logic, preventing
 *      isolated testing of the redirect routing.
 *
 * With the Strategy Pattern, **each role's redirect logic is encapsulated
 * in its own strategy class**, independent and isolated. Adding a new role
 * only requires:
 *   1. Creating a new strategy class implementing LoginStrategyInterface
 *   2. Adding one line in AuthService::getLoginStrategy()'s match expression
 *   3. No changes to AuthService::login()'s main flow
 *
 * [How Classes Collaborate]
 * 1. AuthService::login() calls getLoginStrategy($role) after password
 *    verification to obtain the appropriate strategy instance.
 * 2. getLoginStrategy() returns a LoginStrategyInterface object (polymorphism).
 * 3. AuthService calls the strategy's handle($user) method, which returns
 *    the redirect route name.
 * 4. The caller (AuthService) does not need to know which concrete strategy
 *    is at work — just program to the interface, not the implementation.
 *
 * [This Class's Responsibility]
 * Handles post-login logic for admin users:
 *   - May initialize admin permissions (load role permissions into Session)
 *   - May record admin login audit logs (login time, IP address, etc.)
 *   - Determines the redirect to the admin dashboard home page
 *
 * [Extension Scenario Example]
 * If a "super_admin" role is needed in the future, simply:
 *   1. Create a SuperAdminLoginStrategy class implementing
 *      LoginStrategyInterface
 *   2. Return 'super_admin.dashboard' in its handle() method
 *   3. Add one line in AuthService::getLoginStrategy()'s match:
 *      'super_admin' => new SuperAdminLoginStrategy()
 *
 *   No changes to AdminLoginStrategy (this file), DonorLoginStrategy,
 *   RecipientLoginStrategy, or AuthService::login() are needed.
 *
 * [Called By]
 * - App\Services\AuthService::getLoginStrategy()
 *   (Triggered indirectly by AuthService::login(); instantiated only when
 *   the user role == 'admin')
 *
 * [Dependencies]
 * - App\Models\User (passed as parameter to handle() for admin info)
 * - App\Strategies\LoginStrategyInterface (the interface this class implements)
 */
class AdminLoginStrategy implements LoginStrategyInterface
{
    /**
     * 处理管理员登录成功后的操作
     *
     * 【通俗解释】
     * 当管理员用户成功通过密码验证后，AuthService 会调用此方法。
     * 此方法可以执行管理员专属的初始化逻辑（如加载权限菜单、
     * 记录登录审计日志等），然后返回要跳转的目标路由名称。
     *
     * 【调用时机】
     * 由 AuthService::login() 在以下步骤全部通过后调用：
     *   1. 邮箱存在性检查 ✓
     *   2. 密码正确性验证 ✓
     *   3. 邮箱验证状态确认 ✓
     *   4. Laravel Auth 登录 + Session 重新生成 ✓
     *   → 然后调用本方法，决定跳转到哪个页面
     *
     * 【未来可扩展的操作】
     * - 加载管理员权限列表到 Session，用于前端菜单渲染
     * - 写入管理员登录日志（audit log），记录登录时间和 IP
     * - 检查是否是该管理员当天的首次登录，发送欢迎通知
     * - 更新 last_login_at 时间戳字段
     * - 触发管理员上线 WebSocket 事件（用于实时监控面板）
     *
     * @param User $user 已认证的管理员用户对象
     *                   包含 id, name, email, role, is_verified 等字段
     * @return string 跳转路由名称（如 'home'），由 Laravel 的路由系统解析
     *                返回 'home' 表示跳转到管理后台首页（首页路由）
     *
     * Handle post-login operations for the admin user.
     *
     * [Plain-English Explanation]
     * After the admin user passes password verification, AuthService calls this
     * method. It can perform admin-specific initialization logic (such as loading
     * permission menus and recording login audit logs), then returns the target
     * route name for the redirect.
     *
     * [When It Is Called]
     * Invoked by AuthService::login() after ALL of the following steps pass:
     *   1. Email existence check ✓
     *   2. Password correctness verification ✓
     *   3. Email verification status confirmed ✓
     *   4. Laravel Auth login + Session regeneration ✓
     *   → Then this method is called to decide which page to redirect to
     *
     * [Future Extensible Operations]
     * - Load admin permission list into Session for frontend menu rendering
     * - Write admin login audit log, recording login time and IP address
     * - Check if this is the admin's first login of the day and send a welcome
     *   notification
     * - Update the last_login_at timestamp field
     * - Trigger an admin-online WebSocket event (for real-time monitoring panels)
     *
     * @param User $user The authenticated admin user object,
     *                   containing id, name, email, role, is_verified, etc.
     * @return string Redirect route name (e.g. 'home'), resolved by Laravel's
     *                routing system. Returns 'home' to redirect to the admin
     *                dashboard home page.
     */
    public function handle(User $user): string
    {
        // ===================================================================
        // 管理员登录后的专属初始化逻辑
        // ===================================================================
        //
        // 以下操作可根据业务需求逐步添加（当前阶段为占位，返回首页路由）：
        //
        // 1.【权限初始化】
        //    将管理员的权限菜单列表加载到 Session 中，供前端 sidebar 渲染：
        //    session(['admin_permissions' => $user->getAllPermissions()]);
        //
        // 2.【审计日志】
        //    记录管理员登录事件（可用于安全审计和异常登录检测）：
        //    AuditLog::create([
        //        'user_id'    => $user->id,
        //        'action'     => 'admin_login',
        //        'ip_address' => request()->ip(),
        //        'user_agent' => request()->userAgent(),
        //    ]);
        //
        // 3.【登录时间更新】
        //    更新管理员的最后登录时间戳：
        //    $user->update(['last_login_at' => now()]);
        //
        // 4.【欢迎通知】
        //    检查当日是否为首次登录，发送系统通知：
        //    if ($user->last_login_at?->isYesterday() ?? true) {
        //        Notification::send($user, new WelcomeBackNotification());
        //    }
        //
        // ===================================================================
        // Admin-specific initialization logic after login
        // ===================================================================
        //
        // The following operations can be added incrementally based on business
        // needs (currently a placeholder, returning the home route):
        //
        // 1. [Permission Initialization]
        //    Load the admin's permission menu list into Session for frontend
        //    sidebar rendering:
        //    session(['admin_permissions' => $user->getAllPermissions()]);
        //
        // 2. [Audit Log]
        //    Record admin login event (for security auditing and anomaly
        //    detection):
        //    AuditLog::create([
        //        'user_id'    => $user->id,
        //        'action'     => 'admin_login',
        //        'ip_address' => request()->ip(),
        //        'user_agent' => request()->userAgent(),
        //    ]);
        //
        // 3. [Login Timestamp Update]
        //    Update the admin's last login timestamp:
        //    $user->update(['last_login_at' => now()]);
        //
        // 4. [Welcome Notification]
        //    Check if this is the first login of the day and send a system
        //    notification:
        //    if ($user->last_login_at?->isYesterday() ?? true) {
        //        Notification::send($user, new WelcomeBackNotification());
        //    }

        return 'home';
    }
}
