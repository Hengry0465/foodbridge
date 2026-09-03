<?php

/**
 * ============================================================================
 * UserRepository — 用户仓储实现
 * ============================================================================
 *
 * ============================================================================
 * UserRepository — User Repository Implementation
 * ============================================================================
 *
 * ==================== 设计模式：仓储模式 (Repository Pattern) ====================
 *
 * ==================== Design Pattern: Repository Pattern ====================
 *
 * 【一句话概括】
 * 仓储模式在业务逻辑层与数据访问层（数据库）之间引入一层抽象，将数据操作细节
 * 封装在独立的"仓储"类中，让上层代码不直接依赖具体的 ORM 或数据库实现。
 *
 * 【One-sentence Summary】
 * The Repository pattern introduces an abstraction layer between the business
 * logic layer and the data access layer (database), encapsulating data operation
 * details in standalone "repository" classes so that upper-layer code does not
 * directly depend on a specific ORM or database implementation.
 *
 * ==================== 为什么需要仓储模式 ====================
 *
 * ==================== Why the Repository Pattern ====================
 *
 * 1. 解耦数据源：控制器/服务层通过接口调用数据，而非直接编写 `User::where(...)`，
 *    将来如果更换数据库（如从 MySQL 迁移到 MongoDB）或更换 ORM，只需编写新的
 *    仓储实现，上层代码无需改动。
 *
 * 1. Decouple the data source: controllers/services access data through
 *    interfaces instead of writing `User::where(...)` directly. If the database
 *    is changed (e.g., migrating from MySQL to MongoDB) or the ORM is swapped,
 *    only a new repository implementation is needed — upper-layer code remains
 *    untouched.
 *
 * 2. 可测试性：单元测试中可以轻松通过接口 mock 仓储，无需真实连接数据库，
 *    测试速度更快、更可控。
 *
 * 2. Testability: repositories can easily be mocked via the interface in unit
 *    tests without a real database connection — tests are faster and more
 *    controlled.
 *
 * 3. 单一职责：数据查询、创建、更新的具体 SQL/Eloquent 逻辑集中在仓储中，
 *    控制器只负责处理 HTTP 请求/响应，服务层只负责业务规则，各层职责清晰。
 *
 * 3. Single Responsibility: concrete SQL/Eloquent logic for querying, creating,
 *    and updating data lives in the repository; controllers handle only HTTP
 *    request/response, and services handle only business rules — each layer has
 *    a clear responsibility.
 *
 * 4. 避免代码重复：多个控制器或服务可能需要相同的查询条件（如"按邮箱查用户"），
 *    仓储统一封装这些查询，避免散落各处的重复代码。
 *
 * 4. Avoid code duplication: multiple controllers or services may need the same
 *    query criteria (e.g., "find user by email"); the repository encapsulates
 *    these queries in one place, avoiding scattered duplicate code.
 *
 * ==================== 类角色分工 ====================
 *
 * ==================== Class Role Breakdown ====================
 *
 * ┌──────────────────────────────┐
 * │  UserRepositoryInterface     │  ← 仓储接口（定义契约）
 * │  定义了一组必须实现的        │     角色：策略接口 / 抽象层
 * │  数据访问方法签名            │     作用：规定"能做什么"，不管"怎么做"
 * └──────────────▲───────────────┘
 *                │
 *                │ implements（实现）
 *                │
 * ┌──────────────┴───────────────┐
 * │  UserRepository              │  ← 具体仓储实现（本文件）
 * │  使用 Eloquent ORM 实现      │     角色：具体策略 / 实现层
 * │  每个方法的具体逻辑          │     作用：与真实数据库交互
 * └──────────────────────────────┘
 *
 * ┌──────────────────────────────┐
 * │  UserRepositoryInterface     │  ← Repository interface (defines contract)
 * │  Defines a set of required   │     Role: strategy interface / abstraction
 * │  data-access method          │     Purpose: defines "what", not "how"
 * │  signatures                  │
 * └──────────────▲───────────────┘
 *                │
 *                │ implements
 *                │
 * ┌──────────────┴───────────────┐
 * │  UserRepository              │  ← Concrete repository impl (this file)
 * │  Implements each method      │     Role: concrete strategy / implementation
 * │  using Eloquent ORM          │     Purpose: interacts with the real database
 * └──────────────────────────────┘
 *
 * ┌──────────────────────────────┐
 * │  AppServiceProvider          │  ← 服务容器绑定
 * │  $app->bind(                 │     角色：依赖注入的"连接器"
 * │    UserRepositoryInterface   │     作用：告诉 Laravel："当有人要接口时，
 * │    ::class,                  │           给出这个具体实现"
 * │    UserRepository::class     │
 * │  );                          │
 * └──────────────────────────────┘
 *
 * ┌──────────────────────────────┐
 * │  AppServiceProvider          │  ← Service container binding
 * │  $app->bind(                 │     Role: DI "connector"
 * │    UserRepositoryInterface   │     Purpose: tells Laravel: "when someone
 * │    ::class,                  │              asks for the interface,
 * │    UserRepository::class     │              give them this concrete impl"
 * │  );                          │
 * └──────────────────────────────┘
 *
 * ┌──────────────────────────────┐
 * │  UserController /            │  ← 上层调用者
 * │  UserService                 │     角色：客户端
 * │  通过构造函数注入接口，       │     作用：只依赖接口，不感知具体实现
 * │  完全不知道底层是 Eloquent   │
 * └──────────────────────────────┘
 *
 * ┌──────────────────────────────┐
 * │  UserController /            │  ← Upper-layer callers
 * │  UserService                 │     Role: client
 * │  Receive the interface via   │     Purpose: depend only on the interface,
 * │  constructor injection;      │              unaware of the concrete impl
 * │  have no idea the underlying │
 * │  layer uses Eloquent         │
 * └──────────────────────────────┘
 *
 * ==================== 协作流程 ====================
 *
 * ==================== Collaboration Flow ====================
 *
 * 1. AppServiceProvider 注册绑定：接口 → 具体实现
 * 2. 控制器构造函数声明 `UserRepositoryInterface` 类型提示
 * 3. Laravel 服务容器自动解析并注入 `UserRepository` 实例
 * 4. 控制器调用接口方法，实际执行的是本文件中的具体逻辑
 * 5. 本文件方法内部调用 Eloquent（`User::where(...)` 等），操作数据库
 *
 * 1. AppServiceProvider registers the binding: interface → concrete impl
 * 2. Controller constructor declares a `UserRepositoryInterface` type-hint
 * 3. Laravel's service container auto-resolves and injects a `UserRepository`
 *    instance
 * 4. Controller calls interface methods; the concrete logic in this file
 *    actually executes
 * 5. Methods in this file call Eloquent (`User::where(...)`, etc.) to operate
 *    on the database
 *
 * ==================== 可扩展性 ====================
 *
 * ==================== Extensibility ====================
 *
 * 如需添加缓存层，可创建 UserRepository 的装饰器（Decorator）:
 *
 * If a caching layer is needed, create a Decorator for UserRepository:
 *
 *   class CachedUserRepository implements UserRepositoryInterface
 *   {
 *       public function __construct(
 *           protected UserRepository $inner,   // 真正的数据库仓储
 *           protected Cache $cache             // 缓存驱动
 *       ) {}
 *
 *       public function __construct(
 *           protected UserRepository $inner,   // the real DB repository
 *           protected Cache $cache             // cache driver
 *       ) {}
 *
 *       public function findByEmail(string $email): ?User
 *       {
 *           // 先从缓存取，没有再查数据库并写入缓存
 *           return $this->cache->remember("user:{$email}", 3600, fn() =>
 *               $this->inner->findByEmail($email)
 *           );
 *       }
 *
 *       public function findByEmail(string $email): ?User
 *       {
 *           // Check cache first; if not found, query DB and write to cache
 *           return $this->cache->remember("user:{$email}", 3600, fn() =>
 *               $this->inner->findByEmail($email)
 *           );
 *       }
 *       // ... 其余方法
 *       // ... remaining methods
 *   }
 *
 * 然后只需在 AppServiceProvider 中更换绑定：
 *   $app->bind(UserRepositoryInterface::class, CachedUserRepository::class);
 * 所有上层调用者无需任何改动 —— 这正是接口编程的力量。
 *
 * Then simply swap the binding in AppServiceProvider:
 *   $app->bind(UserRepositoryInterface::class, CachedUserRepository::class);
 * All upper-layer callers require zero changes — this is the power of
 * programming to an interface.
 */

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 用户仓储实现
 *
 * 实现了 UserRepositoryInterface 接口中定义的所有数据访问方法。
 * 内部使用 Laravel Eloquent ORM 与 MySQL 数据库交互。
 * 不包含任何业务逻辑 —— 业务逻辑应放在 Service 层中。
 *
 * User repository implementation.
 *
 * Implements all data-access methods defined in the UserRepositoryInterface.
 * Uses Laravel Eloquent ORM internally to interact with the MySQL database.
 * Contains no business logic — business logic belongs in the Service layer.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * 根据邮箱地址查找用户
     *
     * 使用 Eloquent 查询构建器，在 users 表上按 email 字段精确匹配，
     * 返回第一条匹配记录。邮箱在系统中具有唯一索引约束，因此结果唯一。
     *
     * Find a user by email address.
     *
     * Uses the Eloquent query builder to perform an exact match on the email
     * field in the users table, returning the first matching record. Email has
     * a unique index constraint in the system, so the result is unique.
     *
     * @param string $email 用户邮箱地址（唯一标识）
     * @return User|null 找到返回 User 模型实例，未找到返回 null
     */
    public function findByEmail(string $email): ?User
    {
        // Eloquent 的 where + first 组合：生成 "SELECT * FROM users WHERE email = ? LIMIT 1"
        //
        // Eloquent where + first combo: generates "SELECT * FROM users WHERE email = ? LIMIT 1"
        return User::where('email', $email)->first();
    }

    /**
     * 根据主键 ID 查找用户
     *
     * 直接利用 Eloquent 的 find() 方法按主键查询，这是 Eloquent 的快捷方法。
     * 内部等价于 `where('id', $id)->first()`。
     *
     * Find a user by primary key ID.
     *
     * Directly uses Eloquent's find() method to query by primary key — a
     * convenience shortcut. Internally equivalent to `where('id', $id)->first()`.
     *
     * @param int $id 用户主键 ID（自增整数）
     * @return User|null 找到返回 User 模型实例，未找到返回 null
     */
    public function findById(int $id): ?User
    {
        // Eloquent find()：自动按主键查找，比手动写 where 更简洁
        //
        // Eloquent find(): auto-queries by primary key — more concise than writing where manually
        return User::find($id);
    }

    /**
     * 创建新用户记录
     *
     * 接收原始数据数组，将其映射为 User 模型属性后调用 Eloquent 的 create() 方法
     * 插入数据库。注意以下关键处理：
     *  - 密码经过 Hash::make() 哈希处理（bcrypt），绝不存储明文
     *  - `is_verified` 固定为 0，确保新用户必须通过双因素认证才能获得权限
     *  - 可选字段（phone、two_factor_code、2FA_start）不存在时赋 null
     *
     * 关于 Eloquent 的 create()：要求 User 模型的 $fillable 属性已列出这些字段，
     * 否则 create() 会忽略未声明的字段（这就是 Laravel 的批量赋值保护机制）。
     *
     * Create a new user record.
     *
     * Receives a raw data array, maps it to User model attributes, then calls
     * Eloquent's create() method to insert into the database. Key processing:
     *  - Password is hashed via Hash::make() (bcrypt) — never stored in plaintext
     *  - `is_verified` is fixed at 0, ensuring new users must complete 2FA
     *  - Optional fields (phone, two_factor_code, 2FA_start) default to null
     *
     * Regarding Eloquent's create(): the User model's $fillable property must
     * list these fields; otherwise create() ignores undeclared fields (this is
     * Laravel's mass-assignment protection mechanism).
     *
     * @param array $data 用户注册数据，键名对应数据库列名
     *                   必须包含: firstname, lastname, email, password, role
     *                   可选: phone, two_factor_code, 2FA_start
     * @return User 新创建的用户模型实例（包含自增 ID）
     */
    public function create(array $data): User
    {
        return User::create([
            'firstname'       => $data['firstname'],
            'lastname'        => $data['lastname'],
            'phone'           => $data['phone'] ?? null,
            'email'           => $data['email'],
            'password_hash'   => Hash::make($data['password']),  // 密码哈希，严防明文存储
            //                                                      // Hash password — never store plaintext
            'role'            => $data['role'],
            'two_factor_code' => $data['two_factor_code'] ?? null,
            '2FA_start'       => $data['2FA_start'] ?? null,
            'verification_token' => $data['verification_token'] ?? null,
            'is_verified'     => 0,  // 新用户默认未验证，需通过 2FA 完成身份确认
            //                          // New users default to unverified; must complete 2FA
        ]);
    }

    /**
     * 判断指定邮箱是否已被注册
     *
     * 使用 Eloquent 的 exists() 方法，生成高效的 "SELECT EXISTS(...)" 查询，
     * 而不是先取出完整记录再判断。EXISTS 查询在数据库层面优化，只返回布尔值，
     * 不产生无谓的数据传输开销。
     *
     * Check whether a given email is already registered.
     *
     * Uses Eloquent's exists() method, which generates an efficient
     * "SELECT EXISTS(...)" query instead of fetching a full record first.
     * EXISTS queries are optimized at the database level, returning only a
     * boolean without unnecessary data-transfer overhead.
     *
     * @param string $email 待检查的邮箱地址
     * @return bool 存在则返回 true，不存在则返回 false
     */
    public function emailExists(string $email): bool
    {
        // exists() 生成 "SELECT EXISTS(SELECT * FROM users WHERE email = ?)"，
        // 数据库引擎在找到第一条匹配后就停止扫描，性能优于 count() > 0
        //
        // exists() generates "SELECT EXISTS(SELECT * FROM users WHERE email = ?)";
        // the DB engine stops scanning at the first match — faster than count() > 0
        return User::where('email', $email)->exists();
    }

    /**
     * 更新用户信息
     *
     * 接收已查询出的 User 模型实例和更新数据数组，调用 Eloquent 的 update() 方法
     * 将变更写入数据库。注意是先查出模型再更新（findById → update），而非直接
     * `User::where(...)->update(...)`，后者不会触发 Eloquent 模型事件（如观察者、
     * 访问器等），且不会更新 updated_at 时间戳（除非手动指定）。
     *
     * Update a user's information.
     *
     * Receives an already-retrieved User model instance and an update data array,
     * then calls Eloquent's update() method to write changes to the database.
     * Note: the model is fetched first, then updated (findById → update), rather
     * than `User::where(...)->update(...)` directly — the latter does not fire
     * Eloquent model events (observers, accessors, etc.) and does not update the
     * updated_at timestamp (unless specified manually).
     *
     * @param User  $user 已查询出的用户模型实例（必须已持久化到数据库）
     * @param array $data 待更新的字段数组，键名对应数据库列名
     *                    若包含 'password' 键，会自动哈希处理后存入 password_hash 字段
     * @return bool 更新成功返回 true，失败返回 false
     */
    public function update(User $user, array $data): bool
    {
        // 密码字段特殊处理：明文密码需要先哈希再存入 password_hash 列
        // 注意数据库中实际存储密码哈希的列名是 password_hash，不是 password
        //
        // Special handling for password field: hash plaintext before storing
        // into the password_hash column (not "password" — that's the column name)
        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);  // 清除明文，确保不误存到数据库
            //                          // Remove plaintext so it never hits the DB
        }

        // Eloquent 的 update() 会触发模型事件（saving/updated）并自动更新 updated_at
        //
        // Eloquent update() fires model events (saving/updated) and auto-updates updated_at
        return $user->update($data);
    }
}
