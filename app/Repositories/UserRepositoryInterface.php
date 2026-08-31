<?php

/**
 * ============================================================================
 * 设计模式：仓储模式（Repository Pattern）
 * ============================================================================
 *
 * 【模式简介】
 * 仓储模式是一种结构型设计模式，它在业务逻辑层与数据访问层之间引入一个
 * 中间抽象层。仓储（Repository）封装了数据持久化的具体细节（如 SQL 查询、
 * ORM 操作），对外只暴露具有业务含义的方法接口。
 *
 * 【为什么需要仓储模式？】
 * 1. 解耦业务逻辑与数据访问：
 *    Service 层不需要知道数据是来自 Eloquent ORM、Raw SQL、还是外部 API，
 *    从而保护业务逻辑不受底层数据源变更的影响。
 * 2. 可测试性提升：
 *     接口可以被 Mock（模拟），使得 Service 层的单元测试无需连接真实数据库。
 *    例如，在测试中只需 `$this->mock(UserRepositoryInterface::class)` 即可。
 * 3. 单一职责原则：
 *     数据访问逻辑集中在一个地方，业务逻辑不再散落着查询构造器代码。
 * 4. 代码复用与统一规范：
 *     所有对 User 表的操作都必须通过这个接口定义的方法，避免了各处随意
 *     写查询，保证了数据访问行为的一致性。
 *
 * 【本文件中各角色的对应关系】
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  角色              │  在本项目中的体现                   │
 * ├─────────────────────────────────────────────────────────┤
 * │  仓储接口（契约）   │  UserRepositoryInterface（本文件）  │
 * │  具体仓储（实现）   │  UserRepository（实现本接口的类）   │
 * │  模型（数据实体）   │  App\Models\User （Eloquent 模型）  │
 * │  客户端（消费者）   │  Service 层 / Controller 层        │
 * │  服务容器（工厂）   │  Laravel IoC Container             │
 * └─────────────────────────────────────────────────────────┘
 *
 * 【类之间的协作流程】
 *
 *   Controller / Service（依赖接口，而非具体实现）
 *        │
 *        │  构造函数注入: __construct(UserRepositoryInterface $repo)
 *        ▼
 *   UserRepositoryInterface  ◄── 类型提示，定义契约
 *        ▲
 *        │  实现（implements）
 *        │
 *   UserRepository  ◄── 真正的数据库操作在这里
 *        │
 *        │  使用
 *        ▼
 *   App\Models\User  ◄── Eloquent 模型（对接 MySQL 等数据库）
 *
 * 【依赖绑定（Laravel 服务提供者中）】
 *
 *   通常在 App\Providers\AppServiceProvider 或
 *   App\Providers\RepositoryServiceProvider 中完成绑定：
 *
 *   $this->app->bind(
 *       \App\Repositories\UserRepositoryInterface::class,
 *       \App\Repositories\UserRepository::class
 *   );
 *
 *   这样，当 Controller 或 Service 的构造函数类型提示为
 *   UserRepositoryInterface 时，Laravel IoC 容器会自动注入
 *   UserRepository 的实力。
 *
 * 【扩展示例：切换数据源】
 *
 *   如果将来需要将用户数据切换为 Redis 缓存或第三方 API，
 *   只需新建一个类（如 RedisUserRepository）实现本接口，
 *   然后在服务提供者中修改绑定关系即可，业务代码零改动。
 *
 * ============================================================================
 *
 * ============================================================================
 * Design Pattern: Repository Pattern
 * ============================================================================
 *
 * [Pattern Overview]
 * The Repository Pattern is a structural design pattern that introduces an
 * intermediate abstraction layer between the business logic layer and the data
 * access layer. The Repository encapsulates the concrete details of data
 * persistence (such as SQL queries, ORM operations) and exposes only
 * business-meaningful method interfaces to the outside world.
 *
 * [Why Use the Repository Pattern?]
 * 1. Decouple business logic from data access:
 *    The Service layer does not need to know whether data comes from
 *    Eloquent ORM, raw SQL, or an external API, thus protecting business
 *    logic from changes in the underlying data source.
 * 2. Improved testability:
 *    The interface can be mocked, allowing unit tests for the Service layer
 *    without connecting to a real database. For example, in tests you simply
 *    use `$this->mock(UserRepositoryInterface::class)`.
 * 3. Single Responsibility Principle:
 *    Data access logic is centralized in one place; business logic no longer
 *    has query builder code scattered throughout.
 * 4. Code reuse and unified conventions:
 *    All operations on the User table must go through methods defined by this
 *    interface, preventing ad-hoc queries everywhere and ensuring consistent
 *    data access behavior.
 *
 * [Role Mapping in This File]
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  Role                      │  Implementation            │
 * ├─────────────────────────────────────────────────────────┤
 * │  Repository Interface      │  UserRepositoryInterface   │
 * │  Concrete Repository       │  UserRepository            │
 * │  Model (Data Entity)       │  App\Models\User           │
 * │  Client (Consumer)         │  Service / Controller layer│
 * │  Service Container         │  Laravel IoC Container     │
 * └─────────────────────────────────────────────────────────┘
 *
 * [Collaboration Flow Between Classes]
 *
 *   Controller / Service (depends on interface, not concrete implementation)
 *        │
 *        │  Constructor injection: __construct(UserRepositoryInterface $repo)
 *        ▼
 *   UserRepositoryInterface  ◄── Type-hint, defines the contract
 *        ▲
 *        │  implements
 *        │
 *   UserRepository  ◄── Actual database operations live here
 *        │
 *        │  uses
 *        ▼
 *   App\Models\User  ◄── Eloquent Model (connects to MySQL, etc.)
 *
 * [Dependency Binding (in Laravel Service Provider)]
 *
 *   Typically bound in App\Providers\AppServiceProvider or
 *   App\Providers\RepositoryServiceProvider:
 *
 *   $this->app->bind(
 *       \App\Repositories\UserRepositoryInterface::class,
 *       \App\Repositories\UserRepository::class
 *   );
 *
 *   This way, when a Controller or Service constructor type-hints
 *   UserRepositoryInterface, the Laravel IoC container automatically
 *   injects an instance of UserRepository.
 *
 * [Extension Example: Switching Data Sources]
 *
 *   If in the future user data needs to switch to Redis cache or a
 *   third-party API, simply create a new class (e.g. RedisUserRepository)
 *   that implements this interface, then change the binding in the service
 *   provider — zero changes to business code.
 *
 * ============================================================================
 */

namespace App\Repositories;

use App\Models\User;

/**
 * 用户仓储接口
 *
 * 定义用户数据访问的标准契约。
 * 所有用户数据的增删改查操作都必须通过此接口定义的方法进行。
 * 具体的持久化实现（数据库、缓存、API 等）由实现类负责。
 *
 * User Repository Interface
 *
 * Defines the standard contract for user data access.
 * All CRUD operations on user data must go through methods defined by this interface.
 * Concrete persistence implementations (database, cache, API, etc.) are the
 * responsibility of the implementing class.
 */
interface UserRepositoryInterface
{
    /**
     * 根据邮箱地址查找用户
     *
     * @param string $email 用户邮箱
     * @return User|null 找到则返回 User 模型实例，否则返回 null
     *
     * Find a user by email address.
     *
     * @param string $email User email
     * @return User|null Returns the User model instance if found, null otherwise
     */
    public function findByEmail(string $email): ?User;

    /**
     * 根据用户 ID 查找用户
     *
     * @param int $id 用户主键 ID
     * @return User|null 找到则返回 User 模型实例，否则返回 null
     *
     * Find a user by ID.
     *
     * @param int $id User primary key ID
     * @return User|null Returns the User model instance if found, null otherwise
     */
    public function findById(int $id): ?User;

    /**
     * 创建新用户
     *
     * @param array $data 用户数据（如 name, email, password 等字段）
     * @return User 返回新创建的用户模型实例
     *
     * Create a new user.
     *
     * @param array $data User data (fields such as name, email, password, etc.)
     * @return User Returns the newly created User model instance
     */
    public function create(array $data): User;

    /**
     * 判断指定邮箱是否已被注册
     *
     * 常用于注册流程中的唯一性校验，避免重复注册。
     *
     * @param string $email 待检查的邮箱地址
     * @return bool 已存在返回 true，否则返回 false
     *
     * Check whether the given email address is already registered.
     *
     * Commonly used for uniqueness validation during the registration
     * process to prevent duplicate registrations.
     *
     * @param string $email The email address to check
     * @return bool Returns true if it already exists, false otherwise
     */
    public function emailExists(string $email): bool;

    /**
     * 更新指定用户的信息
     *
     * @param User  $user 待更新的用户模型实例（必须是已持久化的记录）
     * @param array $data 要更新的字段和值
     * @return bool 更新成功返回 true，失败返回 false
     *
     * Update the specified user's information.
     *
     * @param User  $user The User model instance to update (must be a persisted record)
     * @param array $data The fields and values to update
     * @return bool Returns true on success, false on failure
     */
    public function update(User $user, array $data): bool;
}
