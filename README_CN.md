# 食物捐赠平台 — Food Donation Platform

> 基于 Laravel 13 + 原生 HTML/CSS/JS 的用户登录与注册系统，面向"食物捐赠"主题。
> 项目中合理应用了 **Factory（工厂模式）**、**Strategy（策略模式）**、**Repository（仓储模式）** 三种设计模式。

---

## 一、项目概述

本系统是一个食物捐赠平台的用户认证模块，支持三种角色：

| 角色 | 说明 |
|------|------|
| **Admin（管理员）** | 管理用户、审核捐赠、查看统计 |
| **Donor（捐赠者）** | 发布食物捐赠、管理捐赠记录 |
| **Recipient（接收者）** | 浏览食物、领取捐赠 |

实现了完整的用户注册、登录、角色路由跳转和权限控制功能。

---

## 二、项目目录结构

```
food_donation/
├── app/
│   ├── Factories/
│   │   └── UserFactory.php                  # 工厂模式 — 角色用户数据工厂
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php            # 认证控制器（登录/注册/退出）
│   │   │   └── DashboardController.php       # 仪表盘控制器
│   │   └── Middleware/
│   │       └── RoleMiddleware.php            # 角色权限中间件
│   ├── Models/
│   │   └── User.php                          # 用户模型
│   ├── Providers/
│   │   └── AppServiceProvider.php            # 服务容器绑定
│   ├── Repositories/
│   │   ├── UserRepositoryInterface.php       # 仓储接口
│   │   └── UserRepository.php                # 仓储实现
│   ├── Services/
│   │   └── AuthService.php                   # 认证服务层
│   └── Strategies/
│       ├── LoginStrategyInterface.php        # 策略接口
│       ├── AdminLoginStrategy.php            # 管理员登录策略
│       ├── DonorLoginStrategy.php            # 捐赠者登录策略
│       └── RecipientLoginStrategy.php        # 接收者登录策略
├── bootstrap/
│   └── app.php                               # 中间件注册
├── database/
│   └── migrations/
│       └── 2026_07_11_020834_create_user_table.php  # users 表迁移
├── resources/views/
│   ├── auth/
│   │   ├── login.blade.php                   # 登录页面
│   │   └── register.blade.php                # 注册页面
│   ├── dashboard/
│   │   ├── admin.blade.php                   # 管理员仪表盘
│   │   ├── donor.blade.php                   # 捐赠者仪表盘
│   │   └── recipient.blade.php               # 接收者仪表盘
│   └── layouts/
│       └── app.blade.php                     # 主布局模板
├── routes/
│   └── web.php                                # 路由配置
├── .env                                       # 环境配置
└── README_CN.md                               # 本文档
```

---

## 三、数据库配置

### 3.1 连接信息

编辑项目根目录下的 `.env` 文件：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8080
DB_DATABASE=food_donation
DB_USERNAME=root
DB_PASSWORD=thresh1462
```

### 3.2 数据表结构

表名：`users`

```sql
CREATE TABLE `users` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname`        VARCHAR(100)    DEFAULT NULL,
  `lastname`         VARCHAR(100)    DEFAULT NULL,
  `phone`            VARCHAR(100)    DEFAULT NULL,
  `email`            VARCHAR(100)    NOT NULL,
  `password_hash`    VARCHAR(100)    NOT NULL,
  `role`             VARCHAR(100)    DEFAULT NULL,
  `two_factor_code`  VARCHAR(100)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 四、User Model

**文件：** `app/Models/User.php`

```php
class User extends Authenticatable
{
    protected $table = 'users';          // 指定表名
    public $timestamps = false;          // 表中没有 timestamps 字段

    protected $fillable = [
        'firstname', 'lastname', 'phone',
        'email', 'password_hash', 'role', 'two_factor_code',
    ];

    protected $hidden = ['password_hash', 'two_factor_code'];

    // 覆盖密码字段名（默认是 password，我们使用 password_hash）
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
```

---

## 五、三种设计模式说明

### 5.1 Factory（工厂模式）

**目的：** 避免在 Controller 中写大量 `if-else` 判断角色，由工厂统一管理不同角色的用户数据构建逻辑。

**文件：** `app/Factories/UserFactory.php`

```php
class UserFactory
{
    const ROLES = ['donor', 'recipient', 'admin'];

    public function make(array $data): array
    {
        return match (strtolower($data['role'])) {
            'admin'     => $this->makeAdmin($data),
            'donor'     => $this->makeDonor($data),
            'recipient' => $this->makeRecipient($data),
            default     => throw new \InvalidArgumentException("无效的角色"),
        };
    }
    // 不同角色可设置不同的默认值或处理逻辑
}
```

**作用：**
- 将角色相关的创建逻辑集中管理
- 新增角色时只需添加新的 `make*()` 方法
- Controller 代码简洁，不需要关心角色内部差异

### 5.2 Strategy（策略模式）

**目的：** 不同角色登录成功后执行不同的跳转/响应行为。

**接口：** `app/Strategies/LoginStrategyInterface.php`

```php
interface LoginStrategyInterface
{
    public function handle(User $user): string;
}
```

**实现：**
- `AdminLoginStrategy` → 返回 `admin.dashboard`
- `DonorLoginStrategy` → 返回 `donor.dashboard`
- `RecipientLoginStrategy` → 返回 `recipient.dashboard`

**作用：**
- 每种角色有独立的策略类，职责单一
- 新增角色时只需添加新策略，无需改动现有代码（开闭原则）
- 策略可注入到 Service 层中动态选择

### 5.3 Repository（仓储模式）

**目的：** 封装所有数据库操作，Controller 不直接写 SQL 或 Eloquent 查询。

**接口：** `app/Repositories/UserRepositoryInterface.php`

```php
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function create(array $data): User;
    public function emailExists(string $email): bool;
    public function update(User $user, array $data): bool;
}
```

**实现：** `app/Repositories/UserRepository.php`

**绑定：** 在 `AppServiceProvider.php` 中将接口绑定到实现：

```php
$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
```

**作用：**
- Controller 通过接口依赖注入，不依赖具体实现
- 将来更换数据库（如改用 Redis 缓存层）只需修改绑定
- 方便单元测试时 Mock 接口

---

## 六、Controller 层

### 6.1 AuthController

**文件：** `app/Http/Controllers/AuthController.php`

| 方法 | 路由 | 说明 |
|------|------|------|
| `showLogin()` | `GET /login` | 显示登录页面 |
| `login()` | `POST /login` | 处理登录请求 |
| `showRegister()` | `GET /register` | 显示注册页面 |
| `register()` | `POST /register` | 处理注册请求 |
| `logout()` | `POST /logout` | 退出登录 |

**注册验证规则：**
- 必填字段：firstname, lastname, email, password, role
- 密码最少 8 位，必须包含大小写字母和数字
- 邮箱唯一性验证
- 两次密码一致性验证（confirm_password same:password）

### 6.2 DashboardController

**文件：** `app/Http/Controllers/DashboardController.php`

| 方法 | 路由 | 说明 |
|------|------|------|
| `admin()` | `GET /admin/dashboard` | 管理员仪表盘 |
| `donor()` | `GET /donor/dashboard` | 捐赠者仪表盘 |
| `recipient()` | `GET /recipient/dashboard` | 接收者仪表盘 |

---

## 七、Service 服务层

**文件：** `app/Services/AuthService.php`

整合了三种设计模式：

```
AuthService
├── 接收 UserRepositoryInterface（Repository 模式）
├── 接收 UserFactory（Factory 模式）
├── register() → Factory 构建数据 → Repository 写入数据库
├── login() → Repository 查询 → Hash 验证 → Strategy 确定跳转路由
└── logout() → 清除 Session
```

**关键代码片段：**

```php
// 注册时使用 Factory + Repository
$userData = $this->userFactory->make($data);
return $this->userRepo->create($userData);

// 登录时使用 Strategy 模式确定跳转
$strategy = $this->getLoginStrategy($user->role);
$redirectRoute = $strategy->handle($user);
```

---

## 八、路由配置

**文件：** `routes/web.php`

```php
// === 公开路由 ===
GET   /login              → AuthController@showLogin      (name: login)
POST  /login              → AuthController@login
GET   /register           → AuthController@showRegister   (name: register)
POST  /register           → AuthController@register
POST  /logout             → AuthController@logout         (name: logout)

// === 受保护路由（需要登录 + 角色匹配）===
GET   /admin/dashboard    → DashboardController@admin     (name: admin.dashboard)
       中间件: auth + role:admin

GET   /donor/dashboard    → DashboardController@donor     (name: donor.dashboard)
       中间件: auth + role:donor

GET   /recipient/dashboard → DashboardController@recipient (name: recipient.dashboard)
       中间件: auth + role:recipient
```

---

## 九、中间件配置

### 9.1 RoleMiddleware

**文件：** `app/Http/Middleware/RoleMiddleware.php`

- 检查用户是否已登录
- 检查用户角色是否与路由要求角色匹配
- 不匹配则返回 403 禁止访问

**注册别名：** `bootstrap/app.php`

```php
$middleware->alias([
    'role' => \App\Http\Middleware\RoleMiddleware::class,
]);
```

---

## 十、前端页面

所有页面使用原生 HTML、CSS、JavaScript，不使用 Vue/React 框架。

### 10.1 主布局（`resources/views/layouts/app.blade.php`）

- 暖色系 + 绿色主题（橙色主色调 #e87d22、绿色辅助色 #2ecc71）
- 响应式卡片布局
- 统一的页头和页脚
- 错误/成功提示自动显示
- CSRF Token 嵌入 meta 标签

### 10.2 登录页面（`auth/login.blade.php`）

- 邮箱 + 密码表单
- 前端 JS 验证（非空、邮箱格式检查）
- 后端 Laravel 表单验证（CSRF 保护）
- 注册链接跳转

### 10.3 注册页面（`auth/register.blade.php`）

- First Name / Last Name / Phone / Email / Password / Confirm Password / Role
- 前端 JS 验证（密码复杂度、一致性检查）
- 后端 Laravel 验证（唯一性、密码规则、角色枚举）
- 登录链接跳转

### 10.4 Dashboard 页面

- **Admin：** 系统概览、用户管理、捐赠管理、审核管理（功能占位，用 `alert` 提示）
- **Donor：** 我的捐赠、已完成、待领取、帮助人数统计卡片
- **Recipient：** 浏览食物、我的领取、收藏、消息卡片

---

## 十一、安全措施

| 措施 | 实现方式 |
|------|----------|
| CSRF 防护 | `@csrf` 指令 + `meta[name="csrf-token"]` |
| 密码加密 | `Hash::make()` 存储，`Hash::check()` 验证 |
| SQL 注入防护 | 使用 Eloquent ORM 参数绑定 |
| 表单验证 | Laravel Validation（必填、邮箱格式、密码规则） |
| 邮箱唯一性 | `unique:users,email` 验证规则 |
| Session 固定攻击 | 登录后 `session()->regenerate()` |
| 退出清除 Session | `session()->invalidate()` + `regenerateToken()` |
| 角色权限控制 | RoleMiddleware 中间件 |
| 未登录保护 | Laravel `auth` 中间件 |

---

## 十二、项目运行步骤

### 前提条件
- PHP 8.3+（本项目使用 8.3.32）
- Composer 2.x
- MySQL 8.x（本项目使用 8.1.0，端口 8080）

### 步骤

```bash
# 1. 进入项目目录
cd Z:/food_donation

# 2. 安装 PHP 依赖
composer install

# 3. 配置 .env 文件，设置数据库连接
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=8080
# DB_DATABASE=food_donation
# DB_USERNAME=root
# DB_PASSWORD=thresh1462

# 4. 生成应用密钥
php artisan key:generate

# 5. 执行数据库迁移
php artisan migrate:fresh

# 6. 启动开发服务器
php artisan serve --port=8000

# 7. 浏览器访问
# http://localhost:8000
```

---

## 十三、三种设计模式总结

```
请求流程：

┌─────────────┐     ┌──────────────────┐     ┌──────────────────┐
│ AuthController │ →  │    AuthService     │ →  │  UserRepository   │
│ (接收请求)     │     │ (业务逻辑编排)     │     │ (数据库操作封装)   │
└─────────────┘     └──────────────────┘     └──────────────────┘
                            │                          ↑
                    ┌───────┼───────┐          Repository 模式
                    ↓       ↓       ↓           接口 → 实现分离
              UserFactory  LoginStrategy
              ───────────  ────────────
              Factory 模式  Strategy 模式
              角色数据构建   角色登录行为
```

| 设计模式 | 解决的问题 | 项目中的位置 |
|----------|-----------|-------------|
| **Factory** | 避免 Controller 中大量 `if-else` 判断角色 | `app/Factories/UserFactory.php` |
| **Strategy** | 不同角色登录后执行不同跳转/行为 | `app/Strategies/` |
| **Repository** | 数据库操作与业务逻辑分离 | `app/Repositories/` |

---

## 十四、关键代码注释

所有核心代码均包含中文注释，标注了模式用途和关键逻辑。

- `AuthService::register()` — "使用 Factory 模式生成角色对应的用户数据"
- `AuthService::login()` — "使用 Strategy 模式根据角色获取跳转路由"
- `UserRepository::create()` — "使用 Hash::make() 加密密码"
- `RoleMiddleware::handle()` — "根据用户角色限制页面访问权限"
