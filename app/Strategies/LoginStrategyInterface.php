<?php

namespace App\Strategies;

use App\Models\User;

/**
 * ============================================================================
 * 策略模式（Strategy Pattern）— 登录后跳转策略接口
 * Strategy Pattern — Post-Login Redirect Strategy Interface
 * ============================================================================
 *
 * 【设计模式说明】
 * 本接口是 **策略模式（Strategy Pattern）** 的核心——策略接口（Strategy Interface）。
 * 策略模式属于行为型设计模式，它定义了一组算法（策略），将每个算法封装到独立的类中，
 * 并使它们可以互相替换，从而让算法的变化独立于使用它的客户端。
 *
 * [Design Pattern Description]
 * This interface is the core of the Strategy Pattern — the Strategy Interface.
 * The Strategy Pattern is a behavioral design pattern that defines a family of
 * algorithms (strategies), encapsulates each one in a separate class, and makes
 * them interchangeable, allowing the algorithm to vary independently of the
 * client that uses it.
 *
 * 【为什么需要这个模式？（解决的问题）】
 * 在 FoodShare 平台中，存在多种用户角色：普通用户（捐赠者）、管理员、食堂运营者等。
 * 不同角色登录成功后的目标页面截然不同：
 *   - 普通用户 → 跳转到食物浏览页（food.index）
 *   - 食堂管理员 → 跳转到捐赠管理后台（admin.donations.index）
 *   - 系统管理员  → 跳转到系统总控面板（admin.dashboard）
 *
 * 如果不用策略模式，我们可能写出类似这样的代码：
 *
 *   if ($user->isAdmin()) {
 *       return redirect()->route('admin.dashboard');
 *   } elseif ($user->isCanteenStaff()) {
 *       return redirect()->route('canteen.dashboard');
 *   } else {
 *       return redirect()->route('food.index');
 *   }
 *
 * 这种写法的问题在于：
 *   1. 违反 **开闭原则（Open/Closed Principle）**：每新增一种角色，都要修改这段 if-else。
 *   2. 违反 **单一职责原则（Single Responsibility）**：登录控制器同时承担了"认证"和"路由决策"两个职责。
 *   3. 代码耦合度高，难以测试和维护。
 *
 * 策略模式解决了上述问题：将"登录后跳转到哪里"这个**可变行为**抽象为策略，新增角色
 * 只需新增一个具体策略类，无需修改现有代码。
 *
 * [Why This Pattern? (Problem It Solves)]
 * The FoodShare platform has multiple user roles: regular users (donors),
 * administrators, canteen operators, etc. Each role has a distinct destination
 * after login:
 *   - Regular user    → food browsing page (food.index)
 *   - Canteen manager → donation admin panel (admin.donations.index)
 *   - System admin    → system dashboard (admin.dashboard)
 *
 * Without the Strategy Pattern, we might write code like this:
 *
 *   if ($user->isAdmin()) {
 *       return redirect()->route('admin.dashboard');
 *   } elseif ($user->isCanteenStaff()) {
 *       return redirect()->route('canteen.dashboard');
 *   } else {
 *       return redirect()->route('food.index');
 *   }
 *
 * Problems with this approach:
 *   1. Violates the Open/Closed Principle: every new role requires modifying
 *      this if-else chain.
 *   2. Violates the Single Responsibility Principle: the login controller
 *      handles both authentication and routing decisions.
 *   3. High coupling, difficult to test and maintain.
 *
 * The Strategy Pattern solves this by abstracting "where to redirect after
 * login" — this variable behavior — into a strategy. Adding a new role only
 * requires a new concrete strategy class, with no changes to existing code.
 *
 * 【参与角色说明】
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  策略接口（Strategy Interface）— 本文件                           │
 * │  LoginStrategyInterface                                         │
 * │  职责：定义所有具体策略必须遵守的契约——即 handle(User): string   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  具体策略（Concrete Strategy）                                   │
 * │  例如：AdminLoginStrategy, CanteenLoginStrategy,                 │
 * │        DonorLoginStrategy                                       │
 * │  职责：实现 handle() 方法，返回该角色对应的目标路由名称           │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  上下文（Context）/ 策略工厂（Strategy Factory）                  │
 * │  例如：LoginStrategyFactory                                      │
 * │  职责：根据用户角色选择合适的策略对象，并调用其 handle() 方法    │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * [Participating Roles]
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  Strategy Interface — this file                                 │
 * │  LoginStrategyInterface                                         │
 * │  Role: defines the contract all concrete strategies must honor  │
 * │        — i.e., handle(User): string                             │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Concrete Strategy                                              │
 * │  e.g.: AdminLoginStrategy, CanteenLoginStrategy,                │
 * │        DonorLoginStrategy                                       │
 * │  Role: implement handle() to return the target route name       │
 * │        for that role                                            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Context / Strategy Factory                                     │
 * │  e.g.: LoginStrategyFactory                                     │
 * │  Role: select the right strategy object based on user role,     │
 * │        and invoke its handle() method                           │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * 【协作流程】
 *   1. 用户登录成功后，控制器将 User 对象传给 LoginStrategyFactory。
 *   2. 工厂根据 $user->role 匹配对应的具体策略对象（如 DonorLoginStrategy）。
 *   3. 工厂调用该策略的 handle(User $user) 方法，获取目标路由名称。
 *   4. 控制器执行重定向，将用户带到对应页面。
 *
 * [Collaboration Flow]
 *   1. After successful login, the controller passes the User object to
 *      LoginStrategyFactory.
 *   2. The factory matches $user->role to the corresponding concrete strategy
 *      (e.g., DonorLoginStrategy).
 *   3. The factory calls the strategy's handle(User $user) method to get the
 *      target route name.
 *   4. The controller performs the redirect, taking the user to the appropriate
 *      page.
 *
 * 【扩展方式】
 * 当需要新增角色时，只需：
 *   1. 新建一个实现 LoginStrategyInterface 的具体策略类。
 *   2. 在 LoginStrategyFactory 中注册新角色与策略的映射关系。
 * 现有的策略类、控制器代码均无需改动——完全符合开闭原则。
 *
 * [How to Extend]
 * To add a new role, simply:
 *   1. Create a new concrete strategy class implementing
 *      LoginStrategyInterface.
 *   2. Register the new role-to-strategy mapping in LoginStrategyFactory.
 * Existing strategy classes and controller code remain untouched — fully
 * compliant with the Open/Closed Principle.
 *
 * @see LoginStrategyFactory        策略工厂，负责根据角色选择策略
 *                                   Strategy factory, selects strategy by role.
 * @see AdminLoginStrategy          管理员登录策略
 *                                   Admin login strategy.
 * @see DonorLoginStrategy          捐赠者登录策略
 *                                   Donor login strategy.
 */
interface LoginStrategyInterface
{
    /**
     * 处理用户登录成功后的重定向目标路由。
     *
     * Handle the redirect target route after successful user login.
     *
     * 每个具体策略实现此方法来定义"当前角色登录后应该跳转到哪个页面"。
     *
     * Each concrete strategy implements this method to define "which page
     * the current role should land on after login."
     *
     * @param User $user 已认证的用户实例，策略可根据用户属性（角色、偏好等）做进一步判断
     *                   The authenticated user instance; strategies may inspect
     *                   user attributes (role, preferences, etc.) for further
     *                   decisions.
     * @return string    目标路由名称（如 'admin.dashboard'、'food.index'），
     *                   供控制器中的 redirect()->route() 使用
     *                   Target route name (e.g., 'admin.dashboard',
     *                   'food.index'), used by the controller's
     *                   redirect()->route().
     */
    public function handle(User $user): string;
}
