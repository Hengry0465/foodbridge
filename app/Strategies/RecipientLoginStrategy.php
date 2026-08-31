<?php

namespace App\Strategies;

use App\Models\User;

/**
 * ============================================================================
 * 设计模式：策略模式（Strategy Pattern）
 * ============================================================================
 *
 * 【模式概述】
 * 策略模式属于行为型设计模式，定义了一组算法/行为（即"策略"），将每个算法封装到独立的类中，
 * 使它们可以互相替换。策略模式让算法的变化独立于使用算法的客户端。
 *
 * 【为什么需要这个模式 — 解决的问题】
 * 在 FoodShare 平台中，存在多种用户角色：管理员(admin)、捐赠者(donor)、受助者(recipient)。
 * 不同角色登录成功后需要执行不同的业务逻辑和跳转到不同的页面：
 *   - 管理员 → 可能需要跳转到管理后台仪表盘
 *   - 捐赠者 → 可能需要跳转到捐赠记录页
 *   - 受助者 → 可能需要跳转到首页查看可领取的食物
 *
 * 如果没有策略模式，这些逻辑会被塞进一个巨大的 if-else / switch 分支中：
 *
 *   if ($user->role === 'admin') {
 *       // 管理员逻辑...
 *   } elseif ($user->role === 'donor') {
 *       // 捐赠者逻辑...
 *   } elseif ($user->role === 'recipient') {
 *       // 受助者逻辑...
 *   }
 *
 * 这样的代码存在严重问题：
 *   1. 违反"开闭原则"（OCP）—— 每新增一种角色，都要修改这段核心登录逻辑。
 *   2. 违反"单一职责原则"（SRP）—— 一个类承载了所有角色的登录后处理。
 *   3. 代码耦合度高，难以单独测试某一角色的登录行为。
 *
 * 策略模式优雅地解决了这些问题：将每种角色的登录逻辑封装为独立的"策略"类，
 * 通过统一的接口调用，做到了"对扩展开放，对修改关闭"。
 *
 * 【类的角色分工】
 *
 * ┌──────────────────────────┐
 * │ LoginStrategyInterface   │  ← 策略接口（Strategy Interface）
 * │ (app/Strategies/)        │     定义统一的方法签名 handle(User): string
 * └───────────┬──────────────┘
 *             │ 实现（implements）
 *     ┌───────┼───────┬──────────────┐
 *     │       │       │              │
 *     ▼       ▼       ▼              ▼
 * ┌──────┐ ┌──────┐ ┌──────────┐  其他未来角色...
 * │Admin │ │Donor │ │Recipient │  ← 具体策略（Concrete Strategy）
 * │Login │ │Login │ │Login     │     各自封装本角色的登录逻辑
 * │Strat.│ │Strat.│ │Strategy  │
 * └──────┘ └──────┘ └──────────┘
 *
 * ┌──────────────────────┐
 * │ LoginController 或   │  ← 上下文（Context）
 * │ AuthService          │     持有策略接口引用，通过接口调用具体策略
 * └──────────────────────┘
 *
 * 本文件（RecipientLoginStrategy）担任的角色：**具体策略（Concrete Strategy）**
 *
 * 【类之间如何协作】
 * 1. 上下文（如登录控制器）根据当前登录用户的角色，从策略容器/工厂中取出对应的策略实例。
 * 2. 上下文通过 LoginStrategyInterface 接口调用 handle() 方法，不关心内部实现细节。
 * 3. 本策略类执行受助者角色特有的登录后处理逻辑（如初始化待领取食物列表等），
 *    返回一个路由名称（route name），上下文据此进行页面跳转。
 * 4. 未来如需新增角色（如"机构管理员"），只需新增一个实现了接口的类即可，
 *    无需修改任何现有代码。
 *
 * ============================================================================
 *
 * 受助者（Recipient）角色登录策略
 *
 * 职责：处理受助者角色登录成功后的业务逻辑，并返回跳转目标路由。
 */

/**
 * ============================================================================
 * Design Pattern: Strategy Pattern
 * ============================================================================
 *
 * [Pattern Overview]
 * The Strategy pattern is a behavioral design pattern that defines a family of
 * algorithms/behaviors (i.e., "strategies"), encapsulates each one in a separate
 * class, and makes them interchangeable. The Strategy pattern lets the algorithm
 * vary independently from the clients that use it.
 *
 * [Why This Pattern Is Needed — Problems It Solves]
 * In the FoodShare platform, there are multiple user roles: admin, donor, recipient.
 * Different roles need different post-login business logic and page redirects:
 *   - Admin → redirect to admin dashboard
 *   - Donor → redirect to donation history page
 *   - Recipient → redirect to home page to browse available food
 *
 * Without the Strategy pattern, this logic would be stuffed into a massive
 * if-else / switch block:
 *
 *   if ($user->role === 'admin') {
 *       // admin logic...
 *   } elseif ($user->role === 'donor') {
 *       // donor logic...
 *   } elseif ($user->role === 'recipient') {
 *       // recipient logic...
 *   }
 *
 * This code has serious problems:
 *   1. Violates the Open/Closed Principle (OCP) — adding a new role requires
 *      modifying this core login logic.
 *   2. Violates the Single Responsibility Principle (SRP) — one class handles
 *      post-login processing for all roles.
 *   3. High coupling — difficult to test a single role's login behavior in isolation.
 *
 * The Strategy pattern elegantly solves these problems: each role's login logic
 * is encapsulated in a separate "strategy" class, invoked through a unified
 * interface — achieving "open for extension, closed for modification."
 *
 * [Class Role Assignments]
 *
 * ┌──────────────────────────┐
 * │ LoginStrategyInterface   │  ← Strategy Interface
 * │ (app/Strategies/)        │     Defines the unified method signature handle(User): string
 * └───────────┬──────────────┘
 *             │ implements
 *     ┌───────┼───────┬──────────────┐
 *     │       │       │              │
 *     ▼       ▼       ▼              ▼
 * ┌──────┐ ┌──────┐ ┌──────────┐  Other future roles...
 * │Admin │ │Donor │ │Recipient │  ← Concrete Strategy
 * │Login │ │Login │ │Login     │     Each encapsulates its own role's login logic
 * │Strat.│ │Strat.│ │Strategy  │
 * └──────┘ └──────┘ └──────────┘
 *
 * ┌──────────────────────┐
 * │ LoginController or   │  ← Context
 * │ AuthService          │     Holds a reference to the strategy interface,
 * └──────────────────────┘     invokes concrete strategies through it
 *
 * This file (RecipientLoginStrategy) plays the role: **Concrete Strategy**
 *
 * [How Classes Collaborate]
 * 1. The context (e.g., login controller) fetches the corresponding strategy
 *    instance from a strategy container/factory based on the logged-in user's role.
 * 2. The context calls handle() through the LoginStrategyInterface, without
 *    caring about internal implementation details.
 * 3. This strategy class executes recipient-specific post-login processing
 *    (such as initializing the available food list, etc.), and returns a route
 *    name that the context uses for page redirection.
 * 4. In the future, adding a new role (e.g., "organization admin") only requires
 *    adding a new class implementing the interface — no existing code needs to change.
 *
 * ============================================================================
 *
 * Recipient role login strategy.
 *
 * Responsibility: Handle post-login business logic for the recipient role and
 * return the redirect target route.
 */
class RecipientLoginStrategy implements LoginStrategyInterface
{
    /**
     * 处理受助者登录成功后的操作
     *
     * 当前流程：
     *   1. 可在此加载受助者相关的初始化数据（如可领取的食物列表、附近的捐赠点等）
     *   2. 返回目标路由名称，由上下文负责执行实际跳转
     *
     * @param User $user 当前登录的受助者用户实例
     * @return string 跳转目标的 route name（当前固定为 'home'）
     */

    /**
     * Handle post-login operations for the recipient.
     *
     * Current flow:
     *   1. Initialize recipient-related data here (e.g., available food list,
     *      nearby donation points, etc.)
     *   2. Return the target route name; the context performs the actual redirect.
     *
     * @param User $user The currently logged-in recipient user instance
     * @return string Redirect target route name (currently fixed to 'home')
     */
    public function handle(User $user): string
    {
        // 接收者登录后可执行相关初始化

        // Perform initialization after recipient login.
        // 例如：加载可领取的食物列表等

        // For example: load the available food list, etc.

        return 'home';
    }
}
