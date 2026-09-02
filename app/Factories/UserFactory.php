<?php

namespace App\Factories;

use App\Models\User;

/**
 * 用户工厂类（UserFactory）
 *
 * ============================================
 * 设计模式：工厂方法模式（Factory Method Pattern）
 * ============================================
 *
 * 【模式定义】
 * 工厂方法模式属于"创建型设计模式"（Creational Pattern），其核心思想是：
 * 定义一个用于创建对象的接口/方法，但让子类（或内部方法）决定实例化哪一个具体类。
 * 工厂方法将对象的"创建"与"使用"解耦，使得系统在不修改客户端代码的前提下扩展新的产品类型。
 *
 * 【为什么需要这个模式——它解决了什么问题】
 * 1. 消除条件判断的蔓延：
 *    在没有工厂模式的情况下，调用方（如 Controller、Service）需要在每次创建用户时
 *    编写 if-else 或 switch-case 来判断角色，并根据角色填充不同的默认数据。
 *    当角色种类增多时，这些条件分支会散落在项目各处，形成"坏味道"（Code Smell）。
 *
 * 2. 单一职责原则（SRP）：
 *    将"如何根据角色构建用户数据"这一职责从业务逻辑层中抽离出来，
 *    让 Controller 专注于处理 HTTP 请求，让 Service 专注于业务编排，
 *    让 Factory 专注于对象创建。
 *
 * 3. 开闭原则（OCP）：
 *    当未来需要新增角色（如 'volunteer'、'partner'）时，只需在工厂内部
 *    添加一个新的私有方法（如 makeVolunteer），并在 match 分支中注册即可，
 *    无需修改 Controller、Service 等调用方代码。
 *
 * 4. 易于测试：
 *    工厂方法可以被单独进行单元测试，确保每种角色的数据构建逻辑正确，
 *    而不需要依赖 Controller 或数据库。
 *
 * 【模式中各角色的对应关系】
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  模式角色            │  本系统中的具体实现                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Product（产品）     │  array —— 构建完成的用户数据数组          │
 * │                     │  （最终会传递给 User Model 或数据库）      │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ConcreteProduct    │  针对 admin / donor / recipient            │
 * │  （具体产品）        │  三种角色分别产出的不同数据数组            │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Creator（创建者）   │  UserFactory 类本身                       │
 * │                     │  对外暴露统一的 make() 入口方法             │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  FactoryMethod      │  makeAdmin() / makeDonor() /               │
 * │  （工厂方法）        │  makeRecipient() —— 每个角色对应的         │
 * │                     │  私有构建方法                               │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client（客户端）    │  Controller / Service / Seeder 等          │
 * │                     │  调用 UserFactory::make($data) 的代码      │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * 【类之间的协作流程】
 *
 *    Client (Controller/Service)
 *       │
 *       │  1. 调用 make(['role' => 'donor', 'name' => '张三', ...])
 *       ▼
 *   UserFactory::make()
 *       │
 *       │  2. 提取 'role' 字段，通过 match 表达式进行路由分发
 *       │     相当于一个"调度中心"，根据角色选择对应的工厂方法
 *       │
 *       ├── role === 'admin'    ──▶  makeAdmin($data)
 *       ├── role === 'donor'    ──▶  makeDonor($data)
 *       └── role === 'recipient'──▶  makeRecipient($data)
 *                 │
 *                 │  3. 各具体工厂方法通过 array_merge()
 *                 │     在原始 $data 基础上合并角色默认值
 *                 │     （当前各角色逻辑相同，但未来可独立扩展）
 *                 ▼
 *              return array  ──▶  返回给 Client 使用
 *
 * 【使用示例】
 *
 *   // 在 Controller 中：
 *   $factory = new UserFactory();
 *   $userData = $factory->make([
 *       'role' => 'donor',
 *       'name' => '李四',
 *       'email' => 'lisi@example.com',
 *   ]);
 *   // $userData = ['role' => 'donor', 'name' => '李四', 'email' => 'lisi@example.com']
 *   User::create($userData);
 *
 * 【扩展方式】
 * 假设未来需要新增 'volunteer'（志愿者）角色：
 *   1. 在 ROLES 常量中添加 'volunteer'
 *   2. 添加私有方法 makeVolunteer(array $data): array
 *   3. 在 match 表达式中添加 'volunteer' => $this->makeVolunteer($data)
 * 调用方代码无需任何改动。
 *
 * --- English Translation ---
 *
 * User Factory (UserFactory)
 *
 * ============================================
 * Design Pattern: Factory Method Pattern
 * ============================================
 *
 * 【Pattern definition】
 * The Factory Method pattern is a "creational design pattern." Its core idea is:
 * define an interface/method for creating objects, but let subclasses (or internal
 * methods) decide which concrete class to instantiate. The factory method decouples
 * object "creation" from "usage," allowing the system to extend new product types
 * without modifying client code.
 *
 * 【What problem does this pattern solve】
 * 1. Eliminates proliferation of conditionals:
 *    Without a factory pattern, callers (Controller, Service) would need to write
 *    if-else or switch-case to determine the role and populate different default
 *    data for each user creation. As roles grow, these conditionals scatter across
 *    the project, creating a "code smell."
 *
 * 2. Single Responsibility Principle (SRP):
 *    Extracts the "how to build user data per role" responsibility out of the
 *    business logic layer — Controller focuses on HTTP requests, Service on
 *    business orchestration, and Factory on object creation.
 *
 * 3. Open/Closed Principle (OCP):
 *    When new roles (e.g., 'volunteer', 'partner') need to be added, only a new
 *    private method (e.g., makeVolunteer) and a match branch need to be added
 *    in the factory — no changes to Controller, Service, or other callers.
 *
 * 4. Testability:
 *    Factory methods can be unit-tested independently to verify correct data
 *    construction for each role, without depending on Controllers or the database.
 *
 * 【Role mapping in the pattern】
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  Pattern Role        │  Concrete Implementation in This System  │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Product             │  array — the constructed user data array │
 * │                      │  (ultimately passed to User Model or DB) │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  ConcreteProduct     │  Different data arrays produced for the  │
 * │                      │  admin / donor / recipient roles         │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Creator             │  The UserFactory class itself, exposing  │
 * │                      │  the unified make() entry method         │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  FactoryMethod       │  makeAdmin() / makeDonor() /             │
 * │                      │  makeRecipient() — private build methods │
 * │                      │  corresponding to each role              │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Client              │  Controller / Service / Seeder, etc. —   │
 * │                      │  code that calls UserFactory::make()     │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * 【Class collaboration flow】
 *
 *    Client (Controller/Service)
 *       │
 *       │  1. Calls make(['role' => 'donor', 'name' => 'Zhang San', ...])
 *       ▼
 *   UserFactory::make()
 *       │
 *       │  2. Extracts the 'role' field, routes via match expression
 *       │     Acts as a "dispatch center," selecting the appropriate
 *       │     factory method based on the role
 *       │
 *       ├── role === 'admin'    ──▶  makeAdmin($data)
 *       ├── role === 'donor'    ──▶  makeDonor($data)
 *       └── role === 'recipient'──▶  makeRecipient($data)
 *                 │
 *                 │  3. Each concrete factory method merges role-specific
 *                 │     defaults onto the original $data via array_merge()
 *                 │     (currently identical across roles, but independently
 *                 │     extensible in the future)
 *                 ▼
 *              return array  ──▶  returned to Client
 *
 * 【Usage example】
 *
 *   // In a Controller:
 *   $factory = new UserFactory();
 *   $userData = $factory->make([
 *       'role' => 'donor',
 *       'name' => 'Li Si',
 *       'email' => 'lisi@example.com',
 *   ]);
 *   // $userData = ['role' => 'donor', 'name' => 'Li Si', 'email' => 'lisi@example.com']
 *   User::create($userData);
 *
 * 【Extension guide】
 * To add a new 'volunteer' role in the future:
 *   1. Add 'volunteer' to the ROLES constant
 *   2. Add a private method makeVolunteer(array $data): array
 *   3. Add 'volunteer' => $this->makeVolunteer($data) to the match expression
 * No changes to caller code are needed.
 */
class UserFactory
{
    /**
     * 支持的合法角色常量
     *
     * 该常量作为"白名单"，集中定义系统中所有合法的用户角色。
     * 其作用有两个：
     *   1. 为 match 分支提供编译期可验证的角色集合
     *   2. 为 isValidRole() 静态方法提供校验依据
     *
     * 当需要新增角色时，必须同步更新此常量、match 分支以及对应的私有工厂方法。
     *
     * Supported valid role constants.
     *
     * This constant serves as a "whitelist," centrally defining all legal user roles
     * in the system. It has two purposes:
     *   1. Provide a compile-time verifiable set of roles for match branches
     *   2. Serve as the validation basis for the isValidRole() static method
     *
     * When adding a new role, this constant, the match branches, and the corresponding
     * private factory methods must all be updated in sync.
     */
    public const ROLES = ['donor', 'recipient', 'admin'];

    /**
     * 工厂模式的"入口方法"——根据角色创建用户数据数组
     *
     * 这是工厂对外暴露的唯一公共创建接口，遵循"面向接口编程"原则。
     * 调用方只需传入包含 'role' 字段的数据数组，工厂内部会自动路由到
     * 对应的具体工厂方法，调用方无需关心内部的构建细节。
     *
     * 【设计要点】
     * - 使用 PHP 8 的 match 表达式替代传统的 if-elseif-else，
     *   确保分支穷举（exhaustive），遗漏角色会在编译期被检测到
     * - 先将角色转为小写，保证角色名称的大小写不敏感
     * - 非法角色会抛出 InvalidArgumentException，由全局异常处理器统一处理
     *
     * Factory method entry point — creates a user data array based on role.
     *
     * This is the only public creation interface exposed by the factory, following
     * the "program to an interface" principle. Callers only need to pass in a data
     * array containing the 'role' key; the factory internally routes to the
     * corresponding concrete factory method. Callers need not know the internal
     * construction details.
     *
     * 【Design highlights】
     * - Uses PHP 8's match expression instead of traditional if-elseif-else,
     *   ensuring exhaustive branching — missing roles are detected at compile time
     * - Converts the role to lowercase first, ensuring case-insensitive role names
     * - Invalid roles throw InvalidArgumentException, handled uniformly by the
     *   global exception handler
     *
     * @param array $data 原始用户数据，必须包含 'role' 键。
     *                    其他字段（如 name、email、password）由调用方传入。
     * @return array 经过角色特定处理后的完整用户数据数组
     * @throws \InvalidArgumentException 当传入的角色不在 ROLES 白名单中时抛出
     */
    public function make(array $data): array
    {
        // 统一转为小写，保证 'Admin'、'ADMIN'、'admin' 均被识别为同一角色
        // Normalize to lowercase so that 'Admin', 'ADMIN', and 'admin' are all recognized as the same role
        $role = strtolower($data['role']);

        // match 表达式作为"调度中心"：根据角色将创建任务分发给对应的具体工厂方法
        // The match expression acts as a "dispatch center": routes the creation task to the
        // corresponding concrete factory method based on the role
        // PHP 8 的 match 是穷举的，所有可能值必须有对应分支，保证不漏处理任何角色
        // PHP 8's match is exhaustive — every possible value must have a corresponding branch,
        // ensuring no role is ever missed
        return match ($role) {
            'admin' => $this->makeAdmin($data),
            'donor' => $this->makeDonor($data),
            'recipient' => $this->makeRecipient($data),
            default => throw new \InvalidArgumentException("无效的角色：{$role}"),
        };
    }

    /**
     * 具体工厂方法：构建管理员（admin）用户数据
     *
     * 这是工厂方法模式中的"具体工厂方法"（Concrete Factory Method）之一。
     * 负责处理管理员角色特有的数据构建逻辑。
     *
     * 【当前逻辑】
     * 目前管理员角色没有额外的默认字段，仅确保 'role' 被正确设置为 'admin'。
     * 未来可以在此处扩展管理员专属逻辑，例如：
     *   - 设置默认权限级别（如 'is_super_admin' => false）
     *   - 添加管理员专属的审计日志字段
     *   - 触发管理员创建后的通知事件
     *
     * Concrete factory method: builds admin user data.
     *
     * This is one of the concrete factory methods in the Factory Method pattern.
     * It handles data construction logic specific to the admin role.
     *
     * 【Current logic】
     * Currently the admin role has no additional default fields; it only ensures
     * 'role' is correctly set to 'admin'. Future admin-specific extensions may include:
     *   - Setting a default permission level (e.g., 'is_super_admin' => false)
     *   - Adding admin-specific audit log fields
     *   - Triggering notification events after admin creation
     *
     * @param array $data 原始用户数据
     * @return array 合并了管理员默认值后的用户数据
     */
    private function makeAdmin(array $data): array
    {
        return array_merge($data, [
            'role' => 'admin',
        ]);
    }

    /**
     * 具体工厂方法：构建捐赠者（donor）用户数据
     *
     * 这是工厂方法模式中的"具体工厂方法"（Concrete Factory Method）之一。
     * 负责处理捐赠者角色特有的数据构建逻辑。
     *
     * 【当前逻辑】
     * 目前捐赠者角色没有额外的默认字段，仅确保 'role' 被正确设置为 'donor'。
     * 未来可以在此处扩展捐赠者专属逻辑，例如：
     *   - 设置默认捐赠偏好（如 'preferred_category' => 'general'）
     *   - 初始化捐赠统计字段（如 'total_donations' => 0）
     *   - 关联默认的通知设置
     *
     * Concrete factory method: builds donor user data.
     *
     * This is one of the concrete factory methods in the Factory Method pattern.
     * It handles data construction logic specific to the donor role.
     *
     * 【Current logic】
     * Currently the donor role has no additional default fields; it only ensures
     * 'role' is correctly set to 'donor'. Future donor-specific extensions may include:
     *   - Setting default donation preferences (e.g., 'preferred_category' => 'general')
     *   - Initializing donation statistics fields (e.g., 'total_donations' => 0)
     *   - Associating default notification settings
     *
     * @param array $data 原始用户数据
     * @return array 合并了捐赠者默认值后的用户数据
     */
    private function makeDonor(array $data): array
    {
        return array_merge($data, [
            'role' => 'donor',
        ]);
    }

    /**
     * 具体工厂方法：构建接收者（recipient）用户数据
     *
     * 这是工厂方法模式中的"具体工厂方法"（Concrete Factory Method）之一。
     * 负责处理食物接收者角色特有的数据构建逻辑。
     *
     * 【当前逻辑】
     * 目前接收者角色没有额外的默认字段，仅确保 'role' 被正确设置为 'recipient'。
     * 未来可以在此处扩展接收者专属逻辑，例如：
     *   - 设置默认饮食限制（如 'dietary_restrictions' => []）
     *   - 记录接收资格验证状态（如 'verified' => false）
     *   - 关联默认的取餐地点偏好
     *
     * Concrete factory method: builds recipient user data.
     *
     * This is one of the concrete factory methods in the Factory Method pattern.
     * It handles data construction logic specific to the food recipient role.
     *
     * 【Current logic】
     * Currently the recipient role has no additional default fields; it only ensures
     * 'role' is correctly set to 'recipient'. Future recipient-specific extensions may include:
     *   - Setting default dietary restrictions (e.g., 'dietary_restrictions' => [])
     *   - Recording eligibility verification status (e.g., 'verified' => false)
     *   - Associating default pickup location preferences
     *
     * @param array $data 原始用户数据
     * @return array 合并了接收者默认值后的用户数据
     */
    private function makeRecipient(array $data): array
    {
        return array_merge($data, [
            'role' => 'recipient',
        ]);
    }

    /**
     * 静态辅助方法：验证角色是否合法
     *
     * 该方法独立于工厂创建流程，供外部在使用工厂之前进行"预校验"。
     * 例如在表单验证（Form Request）或中间件中预先检查角色是否合法，
     * 避免将无效角色传入 make() 方法导致异常。
     *
     * 【与 make() 方法的协作关系】
     * - make() 内部也会校验角色（通过 match 的 default 分支），
     *   但抛出的是异常，适用于"运行时发现非法数据"的场景
     * - isValidRole() 返回布尔值，适用于"条件判断"的场景，
     *   如：if (UserFactory::isValidRole($role)) { ... }
     *
     * Static helper: validates whether a role is legal.
     *
     * This method is independent of the factory creation flow and is provided for
     * external "pre-validation" before using the factory. For example, it can be used
     * in form validation (Form Request) or middleware to check role validity upfront,
     * avoiding exceptions from passing an invalid role into make().
     *
     * 【Collaboration with make()】
     * - make() also validates the role internally (via match's default branch),
     *   but throws an exception, suitable for the "invalid data discovered at runtime" scenario
     * - isValidRole() returns a boolean, suitable for "conditional check" scenarios,
     *   e.g., if (UserFactory::isValidRole($role)) { ... }
     *
     * @param string $role 待校验的角色名称，大小写不敏感
     * @return bool 角色是否在 ROLES 白名单中
     */
    public static function isValidRole(string $role): bool
    {
        return in_array(strtolower($role), self::ROLES);
    }
}
