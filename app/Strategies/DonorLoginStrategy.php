<?php

namespace App\Strategies;

use App\Models\User;

/**
 * ============================================================================
 * DonorLoginStrategy — 捐赠者登录策略（策略模式 — 具体策略）
 * ============================================================================
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ 设计模式：策略模式（Strategy Pattern）                                │
 * │ 模式角色：具体策略（Concrete Strategy）                              │
 * │ 所属模块：认证模块（Authentication / Auth）                          │
 * │ 模式目标：将登录后的跳转行为从 AuthService 中解耦出来独立封装        │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * 【设计模式概述 — 策略模式】
 *
 * 策略模式是一种行为型设计模式，它将一组可互换的算法（或行为）各自封装成
 * 独立的类，使得这些算法可以在运行时被动态替换。在本项目中，"算法"就是
 * "不同角色用户登录成功后应该跳转到哪个页面"。
 *
 * 本文件在策略模式中扮演"具体策略"角色：
 *
 *   LoginStrategyInterface（策略接口）
 *       ↑ 声明 handle(User) 方法契约
 *       │
 *       ├── AdminLoginStrategy    （具体策略 — 管理员登录后跳转）
 *       ├── DonorLoginStrategy    （具体策略 — 捐赠者登录后跳转）← 本文件
 *       └── RecipientLoginStrategy（具体策略 — 受赠者登录后跳转）
 *
 * 【上下文（Context）角色】
 *
 * AuthService 是策略模式的上下文。它不关心每种角色具体跳转到哪里，
 * 只通过 getLoginStrategy() 拿到对应的策略对象，然后调用 handle() 方法。
 * 这样 AuthService 和具体的跳转逻辑完全解耦。
 *
 * 【为什么需要策略模式 — 解决的问题】
 *
 * 问题场景：
 *   不同角色用户登录成功后应该看到不同的页面：
 *     - 管理员   → 后台仪表盘
 *     - 捐赠者   → 首页（捐赠面板）
 *     - 受赠者   → 食品浏览页
 *
 * 不使用策略模式的糟糕做法（反例）：
 *   if ($user->role === 'admin') {
 *       $redirect = 'admin.dashboard';
 *   } elseif ($user->role === 'donor') {
 *       $redirect = 'home';
 *   } elseif ($user->role === 'recipient') {
 *       $redirect = 'food.browse';
 *   }
 *   // 每当新增角色时，都要修改 AuthService::login() 方法
 *   // 这违反了开闭原则（对修改关闭，对扩展开放）
 *
 * 使用策略模式后：
 *   1. 新增角色时只需新建一个策略类（如 PartnerLoginStrategy），
 *      然后在 getLoginStrategy() 的 match 表达式中加一行，
 *      无需修改 login() 方法本身的业务逻辑。
 *   2. 每种策略类职责单一（SRP），可以独立进行单元测试。
 *   3. 如果某个角色的跳转逻辑变复杂（如需要检查额外条件），
 *      只需修改对应的策略类，不会影响其他角色。
 *
 * 【类之间如何协作 — 完整调用链】
 *
 *   用户提交登录表单
 *     │
 *     ▼
 *   AuthController::login()
 *     │
 *     ▼
 *   AuthService::login()
 *     ├── 1. 查找用户（Repository 模式）
 *     ├── 2. 校验密码（Hash::check）
 *     ├── 3. 检查邮箱验证状态
 *     ├── 4. Auth::login() + session()->regenerate()
 *     │
 *     ├── 5. $this->getLoginStrategy($user->role)  ← 上下文选择策略
 *     │      │
 *     │      └── match (strtolower($role)) {
 *     │            'donor' => new DonorLoginStrategy(),  ← 返回本类的实例
 *     │            ...
 *     │          }
 *     │
 *     └── 6. $strategy->handle($user)  ← 调用本文件的 handle() 方法
 *            │
 *            └── 返回路由名称 'home' → Controller 据此执行重定向
 *
 * 【本文件的具体职责】
 *
 * 作为捐赠者角色的具体策略，本文件负责：
 * 1. 实现 LoginStrategyInterface 接口的 handle() 方法
 * 2. 返回捐赠者登录成功后应该跳转的路由名称（当前为 'home'，首页）
 * 3. 可在 handle() 方法中执行捐赠者特有的初始化操作（如加载捐赠记录、
 *    更新最后登录时间、初始化购物车等），这些逻辑全部封装在本类内部，
 *    不会污染 AuthService 或其他策略类
 *
 * 【依赖关系】
 *
 * 本文件依赖：
 * - App\Strategies\LoginStrategyInterface（策略接口 — 定义方法契约）
 * - App\Models\User（用户模型 — 传入登录用户的数据）
 *
 * 本文件被以下类调用：
 * - App\Services\AuthService::getLoginStrategy()（策略上下文 — 选择并调用策略）
 *
 * 【扩展指南】
 *
 * 如果需要为捐赠者添加更复杂的登录后逻辑，可以在 handle() 方法中：
 * - 通过 User 模型查询捐赠记录统计数据
 * - 触发预加载（缓存捐赠列表、更新通知计数等）
 * - 记录审计日志
 * - 检查账户状态（如是否被冻结）
 *
 * 所有这些逻辑都在本策略类内部完成，AuthService 只需拿到返回的路由名称即可。
 *
 * @see LoginStrategyInterface  策略接口
 * @see AuthService             策略上下文（调用者）
 * @see AdminLoginStrategy      管理员策略（兄弟策略）
 * @see RecipientLoginStrategy  受赠者策略（兄弟策略）
 */

/**
 * ============================================================================
 * DonorLoginStrategy — Donor Login Strategy (Strategy Pattern — Concrete Strategy)
 * ============================================================================
 *
 * ┌──────────────────────────────────────────────────────────────────────┐
 * │ Design Pattern: Strategy Pattern                                     │
 * │ Pattern Role: Concrete Strategy                                      │
 * │ Module: Authentication / Auth                                        │
 * │ Goal: Decouple post-login redirect behavior from AuthService         │
 * └──────────────────────────────────────────────────────────────────────┘
 *
 * [Strategy Pattern Overview]
 *
 * The Strategy pattern is a behavioral design pattern that encapsulates a set
 * of interchangeable algorithms (or behaviors) into independent classes, so
 * they can be swapped at runtime. In this project, the "algorithm" is
 * "which page should a user of a given role be redirected to after login."
 *
 * This file plays the "Concrete Strategy" role:
 *
 *   LoginStrategyInterface (Strategy Interface)
 *       ↑ declares the handle(User) method contract
 *       │
 *       ├── AdminLoginStrategy    (Concrete Strategy — admin login redirect)
 *       ├── DonorLoginStrategy    (Concrete Strategy — donor login redirect) ← this file
 *       └── RecipientLoginStrategy(Concrete Strategy — recipient login redirect)
 *
 * [Context Role]
 *
 * AuthService is the context. It does not know the redirect target for each
 * role — it only obtains the matching strategy via getLoginStrategy() and
 * calls handle(). This keeps AuthService fully decoupled from redirect logic.
 *
 * [Why Strategy Pattern — Problem & Solution]
 *
 * Problem:
 *   Different roles should see different pages after login:
 *     - Admin     → admin dashboard
 *     - Donor     → home page (donation panel)
 *     - Recipient → food browse page
 *
 * Anti-pattern (without strategy):
 *   if ($user->role === 'admin') {
 *       $redirect = 'admin.dashboard';
 *   } elseif ($user->role === 'donor') {
 *       $redirect = 'home';
 *   } elseif ($user->role === 'recipient') {
 *       $redirect = 'food.browse';
 *   }
 *   // Every new role forces a change to AuthService::login()
 *   // This violates the Open/Closed Principle.
 *
 * With strategy pattern:
 *   1. To add a new role, create a new strategy class (e.g. PartnerLoginStrategy)
 *      and add one line in getLoginStrategy()'s match expression —
 *      no change to login() business logic.
 *   2. Each strategy has a single responsibility (SRP) and can be unit-tested independently.
 *   3. If a role's redirect logic grows complex, only its strategy class is affected.
 *
 * [Full Call Chain]
 *
 *   User submits login form
 *     │
 *     ▼
 *   AuthController::login()
 *     │
 *     ▼
 *   AuthService::login()
 *     ├── 1. Find user (Repository pattern)
 *     ├── 2. Verify password (Hash::check)
 *     ├── 3. Check email verification status
 *     ├── 4. Auth::login() + session()->regenerate()
 *     │
 *     ├── 5. $this->getLoginStrategy($user->role)  ← context selects strategy
 *     │      │
 *     │      └── match (strtolower($role)) {
 *     │            'donor' => new DonorLoginStrategy(),  ← returns instance of this class
 *     │            ...
 *     │          }
 *     │
 *     └── 6. $strategy->handle($user)  ← calls handle() on this file
 *            │
 *            └── returns route name 'home' → Controller redirects
 *
 * [Specific Responsibilities]
 *
 * As the concrete strategy for the donor role:
 * 1. Implements LoginStrategyInterface::handle()
 * 2. Returns the route name donors should land on after login (currently 'home')
 * 3. May perform donor-specific initialization (load donation records,
 *    update last login time, initialize cart, etc.) — all encapsulated here
 *
 * [Dependencies]
 *
 * Depends on:
 * - App\Strategies\LoginStrategyInterface (strategy interface — method contract)
 * - App\Models\User (user model — logged-in user data)
 *
 * Called by:
 * - App\Services\AuthService::getLoginStrategy() (strategy context)
 *
 * [Extension Guide]
 *
 * For richer post-login logic in handle():
 * - Query donation statistics via the User model
 * - Trigger preloading (cache donation lists, update notification counts)
 * - Record audit logs
 * - Check account status (e.g. frozen account)
 *
 * All of this stays inside the strategy class; AuthService only needs the route name.
 *
 * @see LoginStrategyInterface  Strategy interface
 * @see AuthService             Strategy context (caller)
 * @see AdminLoginStrategy      Admin strategy (sibling)
 * @see RecipientLoginStrategy  Recipient strategy (sibling)
 */
class DonorLoginStrategy implements LoginStrategyInterface
{
    /**
     * 处理捐赠者登录成功后的操作
     *
     * 【通俗解释】
     * 当捐赠者角色用户登录成功后，AuthService 会调用本方法来完成该角色
     * 特有的初始化操作，并返回该角色应该跳转到的页面路由名称。
     *
     * 【调用时机】
     * 由 AuthService::login() 在用户通过密码校验和邮箱验证后调用。
     * 调用链：AuthService::login() → getLoginStrategy('donor') → handle($user)
     *
     * 【执行流程】
     * 1. 接收已认证的 User 模型（包含用户所有字段数据）
     * 2. （可选）执行捐赠者特有的初始化操作：
     *    - 加载用户的捐赠历史记录到缓存
     *    - 统计本月/本年捐赠次数
     *    - 更新最后登录时间
     *    - 初始化通知/消息计数
     * 3. 返回目标路由名称，由 Controller 执行 HTTP 重定向
     *
     * 【返回值说明】
     * 返回字符串 'home'，对应 Laravel 路由中名称为 'home' 的路由。
     * 该路由通常指向网站的首页（捐赠者的主操作面板）。
     *
     * 【与兄弟策略的对比】
     * - AdminLoginStrategy::handle() 返回 'home'（管理员后台首页）
     * - RecipientLoginStrategy::handle() 返回 'home'（受赠者食品浏览页）
     * 虽然当前三者都返回 'home'，但在未来扩展中各策略可以返回不同路由。
     * 策略模式的价值在于：修改其中一个策略的跳转目标时，完全不需要改动
     * 其他策略类和 AuthService。
     *
     * @param User $user 已通过认证的捐赠者用户对象（包含 id/name/email/role 等字段）
     * @return string 登录后跳转的目标路由名称（当前固定为 'home'，即首页）
     */

    /**
     * Handle post-login operations for donors.
     *
     * [Plain-language explanation]
     * When a donor-role user logs in successfully, AuthService calls this method
     * to perform role-specific initialization and return the route name for the
     * donor's landing page.
     *
     * [When called]
     * Invoked by AuthService::login() after password verification and email
     * verification pass. Call chain: AuthService::login() → getLoginStrategy('donor') → handle($user)
     *
     * [Execution flow]
     * 1. Receive the authenticated User model (with all user fields)
     * 2. (Optional) Perform donor-specific initialization:
     *    - Load user's donation history into cache
     *    - Count this month's / this year's donations
     *    - Update last login timestamp
     *    - Initialize notification / message counts
     * 3. Return the target route name; the Controller performs the HTTP redirect
     *
     * [Return value]
     * Returns the string 'home', matching the Laravel route named 'home'.
     * This route typically points to the site's home page (donor's main panel).
     *
     * [Comparison with sibling strategies]
     * - AdminLoginStrategy::handle() returns 'home' (admin dashboard)
     * - RecipientLoginStrategy::handle() returns 'home' (food browse page)
     * Currently all three return 'home', but each can return a different route
     * in future. The strategy pattern's value: changing one strategy's redirect
     * target requires zero changes to the other strategies or AuthService.
     *
     * @param User $user Authenticated donor user object (fields: id, name, email, role, etc.)
     * @return string Target route name after login (currently fixed as 'home')
     */
    public function handle(User $user): string
    {
        /*
         * 捐赠者登录后可执行以下初始化操作（目前为占位注释，可按需启用）：
         *
         * 示例：
         * // 1. 加载用户捐赠记录到缓存
         * // Cache::put("donations_{$user->id}", $user->donations()->recent()->get());
         *
         * // 2. 统计本月捐赠次数
         * // $monthlyCount = $user->donations()->thisMonth()->count();
         * // session(['donor_monthly_count' => $monthlyCount]);
         *
         * // 3. 更新最后登录时间
         * // $user->update(['last_login_at' => now()]);
         *
         * // 4. 推送登录通知（如"欢迎回来！您本月已捐赠 X 次"）
         * // event(new UserLoggedIn($user));
         */

        /*
         * The following initialization operations can be executed after donor
         * login (placeholder comments; enable as needed):
         *
         * Examples:
         * // 1. Load user donation records into cache
         * // Cache::put("donations_{$user->id}", $user->donations()->recent()->get());
         *
         * // 2. Count this month's donations
         * // $monthlyCount = $user->donations()->thisMonth()->count();
         * // session(['donor_monthly_count' => $monthlyCount]);
         *
         * // 3. Update last login time
         * // $user->update(['last_login_at' => now()]);
         *
         * // 4. Push a login notification (e.g. "Welcome back! You've donated X times this month")
         * // event(new UserLoggedIn($user));
         */

        return 'donor.dashboard'; // 捐赠者登录后跳转到捐赠者仪表盘
    }
}
