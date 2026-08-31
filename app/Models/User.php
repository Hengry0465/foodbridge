<?php

/**
 * ============================================================
 * FoodShare 用户模型 (User Model)
 * ============================================================
 *
 * 本文件是 FoodShare 食物捐赠平台的核心用户模型，代表 `users` 表中
 * 的一行数据。它继承自 Laravel 的 Authenticatable 类，从而具备
 * 完整的身份认证能力（登录、登出、会话管理等）。
 *
 * 在 Laravel 的 MVC 架构中，Model（模型）负责：
 *   1. 与数据库表交互（通过 Eloquent ORM）
 *   2. 定义数据字段的访问规则（fillable / hidden）
 *   3. 提供身份认证所需的方法（密码字段映射、令牌管理等）
 *   4. 数据类型转换（casts）
 *
 * Authenticatable 继承链：
 *   User → Authenticatable → Model (Eloquent)
 *   这使得 User 同时拥有 "数据库行" 和 "认证用户" 的身份。
 *
 * ============================================================
 * FoodShare User Model
 * ============================================================
 *
 * This file is the core user model of the FoodShare food donation
 * platform, representing a single row in the `users` table. It
 * extends Laravel's Authenticatable class, giving it full
 * authentication capabilities (login, logout, session management).
 *
 * In Laravel's MVC architecture, the Model is responsible for:
 *   1. Interacting with the database table (via Eloquent ORM)
 *   2. Defining field access rules (fillable / hidden)
 *   3. Providing authentication methods (password field mapping,
 *      token management, etc.)
 *   4. Data type conversion (casts)
 *
 * Authenticatable inheritance chain:
 *   User → Authenticatable → Model (Eloquent)
 *   This gives User both a "database row" and an "authenticated
 *   user" identity simultaneously.
 */

namespace App\Models;

// Authenticatable 实现了 Illuminate\Contracts\Auth\Authenticatable 接口，
// 提供了 Laravel 认证系统所需的所有方法：getAuthIdentifier、getAuthPassword、
// getRememberToken、setRememberToken、getRememberTokenName 等。
//
// Authenticatable implements the Illuminate\Contracts\Auth\Authenticatable
// interface, providing all methods required by Laravel's auth system:
// getAuthIdentifier, getAuthPassword, getRememberToken,
// setRememberToken, getRememberTokenName, etc.
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * ----------------------------------------------------------
     * 关联数据库表名
     * ----------------------------------------------------------
     * 显式指定本模型对应的数据库表。Laravel 默认以模型名的蛇形复数
     * 作为表名（即 User → users），此处显式声明以保证与其一致。
     *
     * ----------------------------------------------------------
     * Associated database table name.
     * ----------------------------------------------------------
     * Explicitly specifies the database table for this model. Laravel
     * defaults to the snake_case plural of the model name (i.e.
     * User → users); declared explicitly here to guarantee alignment.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * ----------------------------------------------------------
     * 关闭 Eloquent 自动时间戳管理
     * ----------------------------------------------------------
     * 设为 true（默认）时，Eloquent 会自动维护 created_at 和
     * updated_at 两个时间戳字段。由于本项目 users 表中不包含
     * 这两个字段（或由应用层自行管理），故关闭此特性。
     *
     * 安全考虑：关闭自动时间戳意味着无法通过数据库记录追溯
     * 用户的创建和最后更新时间，适用于该系统可能有独立的
     * 审计日志机制的场合。
     *
     * ----------------------------------------------------------
     * Disable Eloquent's automatic timestamp management.
     * ----------------------------------------------------------
     * When true (the default), Eloquent automatically maintains the
     * created_at and updated_at timestamp columns. This feature is
     * disabled because the users table does not contain these
     * columns (or they are managed by the application layer).
     *
     * Security note: disabling automatic timestamps means database
     * records alone cannot trace user creation and last-updated times.
     * This is appropriate when the system has an independent audit-log
     * mechanism.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * ----------------------------------------------------------
     * 可批量赋值字段（白名单）
     * ----------------------------------------------------------
     * 这是 Eloquent 的"批量赋值保护"机制的核心配置。只有列在
     * $fillable 数组中的字段才允许通过 create() 或 fill() 方法
     * 进行批量赋值。不在白名单内的字段（如 id、自定义权限标记等）
     * 必须单独赋值，从而防止"批量赋值漏洞"（Mass Assignment）。
     *
     * 安全原理：
     *   攻击者可能通过修改 HTTP 请求中的表单字段来篡改敏感数据。
     *   例如，若没有白名单保护，攻击者可在注册请求中附加
     *   "role=admin"，从而使自己成为管理员。
     *   $fillable 白名单 + $hidden 黑名单（见下方）共同构成
     *   Laravel 的纵深防御体系。
     *
     * 各字段含义：
     *   - firstname           : 用户的名
     *   - lastname            : 用户的姓
     *   - phone               : 手机号码
     *   - email               : 电子邮箱地址（也是登录凭证）
     *   - password_hash       : bcrypt 哈希后的密码（非明文）
     *   - role                : 角色标识，用于权限控制
     *                           （如 'user' / 'admin' / 'donor'）
     *   - two_factor_code     : 双因素认证（2FA）的一次性验证码
     *   - 2FA_start           : 2FA 验证码生成时间戳
     *                           （用于判断验证码是否过期）
     *   - is_verified         : 邮箱是否已验证（布尔值）
     *   - verification_token  : 邮箱验证令牌
     *                           （用户点击邮件中的链接时比对用）
     *
     * ----------------------------------------------------------
     * Mass-assignable fields (allow-list).
     * ----------------------------------------------------------
     * This is the core of Eloquent's mass-assignment protection.
     * Only fields listed in $fillable may be set via create() or
     * fill(). Fields not on the list (e.g. id, custom permission
     * flags) must be assigned individually, preventing mass-assignment
     * vulnerabilities.
     *
     * Security rationale:
     *   An attacker may tamper with sensitive data by injecting extra
     *   form fields in the HTTP request. Without the allow-list, they
     *   could append "role=admin" to a registration request and grant
     *   themselves administrator privileges.
     *   $fillable (allow-list) + $hidden (block-list, see below)
     *   together form Laravel's defense-in-depth strategy.
     *
     * Field descriptions:
     *   - firstname           : User's given name
     *   - lastname            : User's surname
     *   - phone               : Phone number
     *   - email               : Email address (also the login credential)
     *   - password_hash       : bcrypt-hashed password (never plaintext)
     *   - role                : Role identifier for authorization
     *                           (e.g. 'user' / 'admin' / 'donor')
     *   - two_factor_code     : One-time 2FA verification code
     *   - 2FA_start           : Timestamp when 2FA code was generated
     *                           (used to check expiry)
     *   - is_verified         : Whether the email has been verified (bool)
     *   - verification_token  : Email verification token
     *                           (compared against the link in the email)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'email',
        'password_hash',
        'role',
        'two_factor_code',
        '2FA_start',
        'is_verified',
        'verification_token',
    ];

    /**
     * ----------------------------------------------------------
     * 字段类型转换（Attribute Casting）
     * ----------------------------------------------------------
     * Eloquent 的 casts 功能在读取/写入数据库时自动转换数据类型。
     * 这保证了 PHP 层面的类型安全，避免 "0" == false 等弱类型问题。
     *
     * 当前转换规则：
     *   - 2FA_start   → integer  : 双因素认证开始时间，从数据库的
     *                              字符串/数字统一转为 PHP int，
     *                              便于进行时间比较（如判断是否
     *                              超过 5 分钟有效期）。
     *   - is_verified → boolean  : 邮箱验证状态，从数据库的 0/1
     *                              （或 true/false 字符串）转为
     *                              PHP 原生 bool 类型，使条件判
     *                              断更加可靠和可读。
     *
     * 注：Laravel 10+ 推荐使用此方法而非传统的 $casts 属性。
     *
     * ----------------------------------------------------------
     * Attribute type casting.
     * ----------------------------------------------------------
     * Eloquent's casts feature automatically converts data types
     * when reading from / writing to the database. This ensures
     * type safety in PHP and avoids weak-type issues like "0" == false.
     *
     * Current casting rules:
     *   - 2FA_start   → integer  : 2FA start timestamp, unified to
     *                              PHP int for easy time comparisons
     *                              (e.g. checking the 5-minute expiry).
     *   - is_verified → boolean  : Email verification status, from
     *                              database 0/1 (or "true"/"false"
     *                              strings) to native PHP bool for
     *                              more reliable, readable conditions.
     *
     * Note: Laravel 10+ recommends using this method over the
     * traditional $casts property.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            '2FA_start'   => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * ----------------------------------------------------------
     * 序列化时隐藏的字段（敏感信息保护）
     * ----------------------------------------------------------
     * 当模型被转换为 JSON 或数组时（例如 API 响应、日志输出、
     * 调试输出等），$hidden 数组中的字段将自动从输出中移除。
     *
     * 当前隐藏字段：
     *   - password_hash    : bcrypt 密码哈希。即使经过哈希处理，
     *                        也不应暴露给客户端，防止离线暴力
     *                        破解风险。
     *   - two_factor_code  : 2FA 一次性验证码。仅在验证时使用，
     *                        绝不能出现在任何 API 响应或 JSON
     *                        序列化结果中。泄露将导致 2FA 机制
     *                        完全失效。
     *
     * 安全原理（纵深防御）：
     *   1. 前端组件在序列化 User 对象时可能意外暴露所有属性
     *   2. 使用 $hidden 确保即使开发者疏忽，敏感字段也不会外泄
     *   3. 配合 $fillable（控制写入）形成"写入保护 + 读取保护"的闭环
     *
     * ----------------------------------------------------------
     * Fields hidden during serialization (sensitive data protection).
     * ----------------------------------------------------------
     * When the model is converted to JSON or an array (e.g. API
     * responses, logs, debug output), fields in $hidden are
     * automatically stripped from the output.
     *
     * Currently hidden fields:
     *   - password_hash    : bcrypt password hash. Even hashed,
     *                        it must never be exposed to the client
     *                        to prevent offline brute-force attacks.
     *   - two_factor_code  : 2FA one-time code. Used only during
     *                        verification; must never appear in any
     *                        API response or JSON serialization.
     *                        Leakage renders the 2FA mechanism useless.
     *
     * Security rationale (defense in depth):
     *   1. Frontend serialization of a User object may accidentally
     *      expose all attributes
     *   2. $hidden guarantees sensitive fields are never leaked,
     *      even if a developer forgets to filter them
     *   3. Together with $fillable (write guard), this forms a
     *      "write protection + read protection" closed loop
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'two_factor_code',
    ];

    /**
     * ----------------------------------------------------------
     * 覆盖认证密码字段名
     * ----------------------------------------------------------
     * Laravel 的 Authenticatable trait 默认使用 `password` 字段
     * 存储密码哈希。本项目将密码字段命名为 `password_hash`，
     * 以更清晰地表达"存储的是哈希值而非明文"这一安全语义。
     *
     * 因此需要覆盖 getAuthPassword() 方法，告知 Laravel 认证系统
     * 从 password_hash 字段而非默认的 password 字段读取密码哈希。
     *
     * 该方法在以下场景被调用：
     *   - 用户登录时的密码验证（Auth::attempt()）
     *   - 密码哈希比对（Hash::check() 内部使用）
     *   - 任何需要获取当前用户密码哈希的内部流程
     *
     * ----------------------------------------------------------
     * Override the authentication password field name.
     * ----------------------------------------------------------
     * Laravel's Authenticatable trait defaults to `password` for the
     * hashed password field. This project names it `password_hash`
     * to clearly convey "stored value is a hash, not plaintext."
     *
     * This method tells Laravel's auth system to read the password
     * hash from `password_hash` instead of the default `password`.
     *
     * Called in the following scenarios:
     *   - Password verification during login (Auth::attempt())
     *   - Password hash comparison (used internally by Hash::check())
     *   - Any internal flow that needs the current user's password hash
     *
     * @return string  The bcrypt password hash stored in the database
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
