<?php

/**
 * ============================================================================
 * FoodShare 应用服务提供者
 * ============================================================================
 *
 * 【文件作用】
 * AppServiceProvider 是 Laravel 应用的"核心服务注册中心"。它是所有自定义
 * 服务（仓储、业务逻辑、第三方集成等）与框架 IoC 容器之间的桥梁。
 *
 * Laravel 启动时，会依次执行所有 ServiceProvider 的 register() 方法，然后
 * 再依次执行所有 boot() 方法。这保证了服务先注册、后引导的顺序。
 *
 * 【服务提供者的生命周期】
 *   1. register() — 将服务绑定到 IoC 容器（此时其他服务可能尚未就绪）
 *   2. boot()     — 在所有服务都注册完毕后执行引导逻辑（可安全依赖其他服务）
 *
 * 【IoC 容器与依赖注入】
 * Laravel 的 IoC（Inversion of Control，控制反转）容器负责管理对象的创建
 * 和依赖关系。当 Controller 构造函数要求一个接口类型参数时，容器会自动
 * 查找已绑定的实现类并注入，无需手动 new。
 *
 * 【安全考量】
 * - 通过接口（Interface）编程而非具体类（Concrete Class），实现了代码
 *   与实现的解耦，降低了模块间的耦合度。
 * - 当需要替换底层实现（例如从 MySQL 切换到 MongoDB，或替换加密算法）时，
 *   只需修改此文件的一行绑定，无需改动业务代码，避免了引入回归 Bug 的风险。
 * - 接口隔离也使得单元测试更为安全：测试时可以注入 Mock（模拟对象），
 *   避免直接操作生产数据库。
 *
 * ============================================================================
 * FoodShare Application Service Provider
 * ============================================================================
 *
 * [File Purpose]
 * AppServiceProvider is the "core service registration center" of the Laravel
 * application. It acts as the bridge between all custom services (repositories,
 * business logic, third-party integrations, etc.) and the framework's IoC
 * container.
 *
 * When Laravel boots, it executes every ServiceProvider's register() method in
 * sequence, then every boot() method in sequence. This ensures services are
 * registered first and bootstrapped second.
 *
 * [Service Provider Lifecycle]
 *   1. register() — Bind services into the IoC container (other services may
 *      not be ready yet)
 *   2. boot()     — Run bootstrap logic after all services are registered
 *      (safe to depend on other services)
 *
 * [IoC Container and Dependency Injection]
 * Laravel's IoC (Inversion of Control) container manages object creation and
 * dependency resolution. When a Controller constructor type-hints an interface,
 * the container automatically resolves and injects the bound implementation
 * without manual instantiation.
 *
 * [Security Considerations]
 * - Programming against interfaces rather than concrete classes decouples code
 *   from implementation and reduces coupling between modules.
 * - When the underlying implementation needs to be replaced (e.g. switching
 *   from MySQL to MongoDB, or replacing the encryption algorithm), only one
 *   binding in this file needs to change — no business code is touched,
 *   eliminating the risk of regression bugs.
 * - Interface isolation also makes unit testing safer: tests can inject Mocks
 *   (simulated objects) instead of operating on the production database.
 */

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * register() — 服务注册阶段
     * ============================================================================
     *
     * 【调用时机】
     * 在 Laravel 启动的早期阶段被调用，此时所有 ServiceProvider 的 register()
     * 方法会按顺序执行，但 boot() 方法尚未执行。
     *
     * 【重要规则】
     * 在 register() 方法中，只能做"绑定"操作，不能依赖任何其他服务。
     * 因为其他提供者的 register() 可能尚未运行，调用其服务会导致"服务未找到"
     * 的运行时错误。
     *
     * 【当前绑定】
     * 下面这行代码将 UserRepositoryInterface（接口/契约）绑定到
     * UserRepository（具体实现）。这意味着：
     *
     *   - 当控制器构造函数要求 UserRepositoryInterface 时，
     *     Laravel 的 IoC 容器会自动注入一个 UserRepository 实例。
     *   - 这是"仓储模式"的核心 —— 业务层只依赖抽象接口，不关心数据
     *     来自 MySQL、Redis 还是 Mock。
     *
     * 【为什么要用 bind 而非 singleton？】
     * - bind:  每次请求都创建一个新实例（适合无状态或需隔离的服务）
     * - singleton: 整个请求生命周期共享同一个实例（适合有状态或需要复用的服务）
     *
     * 此处使用 bind 意味着每次依赖解析都会获得新的 Repository 实例，
     * 避免了请求间数据残留的安全隐患。
     *
     * register() — Service Registration Phase
     * ============================================================================
     *
     * [Invocation Timing]
     * Called during the early stage of Laravel's boot process, when all
     * ServiceProviders' register() methods execute in order but boot() has not
     * yet run.
     *
     * [Important Rule]
     * Inside register(), you may ONLY perform binding operations. Do NOT depend
     * on any other service, because another provider's register() may not have
     * run yet, causing a "service not found" runtime error.
     *
     * [Current Binding]
     * The line below binds UserRepositoryInterface (the interface/contract) to
     * UserRepository (the concrete implementation). This means:
     *
     *   - When a controller constructor type-hints UserRepositoryInterface,
     *     Laravel's IoC container automatically injects a UserRepository
     *     instance.
     *   - This is the core of the "Repository Pattern" — the business layer
     *     depends only on the abstract interface and does not care whether the
     *     data comes from MySQL, Redis, or a Mock.
     *
     * [Why bind instead of singleton?]
     * - bind:     Creates a new instance on every resolution (suitable for
     *             stateless or isolation-requiring services)
     * - singleton: Shares the same instance across the entire request lifecycle
     *              (suitable for stateful or reusable services)
     *
     * Using bind here means every dependency resolution gets a fresh Repository
     * instance, avoiding the security risk of data leaking between requests.
     */
    public function register(): void
    {
        // Repository 模式：绑定接口到实现（依赖反转原则 DIP）
        // Repository pattern: bind interface to implementation (Dependency Inversion Principle)
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * boot() — 服务引导阶段
     * ============================================================================
     *
     * 【调用时机】
     * 在所有 ServiceProvider 的 register() 方法都执行完毕后，Laravel 会
     * 依次调用每个 ServiceProvider 的 boot() 方法。
     *
     * 【可安全执行的操作】
     * - 注册视图合成器（View Composer）
     * - 扩展验证规则
     * - 注册全局中间件
     * - 调用已注册的服务进行初始化配置
     * - 通过 Event::listen() 注册事件监听器
     *
     * 【当前为空的原因】
     * FoodShare 应用目前的启动引导逻辑较为简洁，尚未需要在此阶段执行
     * 额外操作。后续如需添加全局视图共享数据、自定义验证规则或事件监听，
     * 可在此方法中实现。
     *
     * boot() — Service Bootstrap Phase
     * ============================================================================
     *
     * [Invocation Timing]
     * After every ServiceProvider's register() method has finished executing,
     * Laravel calls each ServiceProvider's boot() method in sequence.
     *
     * [Safe Operations]
     * - Register view composers
     * - Extend validation rules
     * - Register global middleware
     * - Call already-registered services for initialization configuration
     * - Register event listeners via Event::listen()
     *
     * [Why This Is Currently Empty]
     * The FoodShare app's bootstrap logic is currently straightforward and does
     * not yet need to perform additional operations in this phase. Future
     * additions — such as global view shared data, custom validation rules, or
     * event listeners — can be implemented here.
     */
    public function boot(): void
    {
        // 暂无引导逻辑，预留扩展
        // No bootstrap logic yet; reserved for future extension
    }
}
