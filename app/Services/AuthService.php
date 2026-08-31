<?php

namespace App\Services;

use App\Factories\UserFactory;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Strategies\AdminLoginStrategy;
use App\Strategies\DonorLoginStrategy;
use App\Strategies\RecipientLoginStrategy;
use App\Strategies\LoginStrategyInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * ============================================================================
 * AuthService — 用户认证服务
 * ============================================================================
 *
 * AuthService — User Authentication Service.
 *
 * 【通俗解释】
 * 这个文件是"认证中心"的大脑，负责处理用户注册、登录、退出、邮箱验证（2FA）、
 * 密码重置等所有和"身份确认"相关的业务逻辑。你可以把它想象成大楼门口的安保
 * 系统 —— 新来的要登记（注册），来过的要刷卡（登录），忘带卡的可以补办
 * （密码重置），走的时候要注销（退出）。
 *
 * [Plain-English Explanation]
 * This file is the brain of the "authentication center", responsible for handling
 * all identity-related business logic: registration, login, logout, email
 * verification (2FA), and password reset. Think of it as the building security
 * system — new arrivals must register, returning visitors must swipe their badge
 * (login), those who forget their badge can get a replacement (password reset),
 * and everyone must sign out when leaving (logout).
 *
 * 【所属模块】
 * 认证模块（Authentication / Auth）
 *
 * [Module]
 * Authentication Module (Auth).
 *
 * 【在业务流程中的位置】
 * 位于 Controller 层和 Repository 层之间，起"承上启下"的作用：
 *   前端请求 → AuthController（接收 HTTP 请求）
 *          → AuthService（本文件 — 处理业务逻辑、数据校验、安全防护）
 *          → UserRepositoryInterface（数据库操作）
 *          → User Model（数据实体）
 *
 * [Position in Business Flow]
 * Sits between the Controller and Repository layers as a bridge:
 *   Client request → AuthController (receives HTTP request)
 *         → AuthService (this file — business logic, data validation, security)
 *         → UserRepositoryInterface (database operations)
 *         → User Model (data entity).
 *
 * 【依赖的类】
 * - App\Factories\UserFactory：工厂模式，根据用户角色（捐赠者/受赠者/管理员）
 *   生成对应的用户数据
 * - App\Models\User：用户数据模型
 * - App\Repositories\UserRepositoryInterface：用户数据仓库接口，封装数据库操作
 * - App\Strategies\LoginStrategyInterface：登录策略接口，实现策略模式
 * - App\Strategies\AdminLoginStrategy：管理员登录后跳转策略
 * - App\Strategies\DonorLoginStrategy：捐赠者登录后跳转策略
 * - App\Strategies\RecipientLoginStrategy：受赠者登录后跳转策略
 * - Illuminate\Support\Facades\Auth：Laravel 认证门面（Facade）
 * - Illuminate\Support\Facades\Hash：Laravel 哈希门面，用于密码加密
 * - Carbon\Carbon：日期时间处理库
 *
 * [Dependencies]
 * - App\Factories\UserFactory: Factory pattern — generates user data by role
 *   (donor/recipient/admin).
 * - App\Models\User: User data model.
 * - App\Repositories\UserRepositoryInterface: User repository interface,
 *   encapsulates database operations.
 * - App\Strategies\LoginStrategyInterface: Login strategy interface (Strategy pattern).
 * - App\Strategies\AdminLoginStrategy: Admin post-login redirect strategy.
 * - App\Strategies\DonorLoginStrategy: Donor post-login redirect strategy.
 * - App\Strategies\RecipientLoginStrategy: Recipient post-login redirect strategy.
 * - Illuminate\Support\Facades\Auth: Laravel Auth facade.
 * - Illuminate\Support\Facades\Hash: Laravel Hash facade for password encryption.
 * - Carbon\Carbon: Date/time utility.
 *
 * 【被哪些类调用】
 * - App\Http\Controllers\Auth\AuthController::register()
 * - App\Http\Controllers\Auth\AuthController::verify2FA()
 * - App\Http\Controllers\Auth\AuthController::resend2FA()
 * - App\Http\Controllers\Auth\AuthController::login()
 * - App\Http\Controllers\Auth\AuthController::logout()
 * - App\Http\Controllers\Auth\AuthController::sendResetCode()
 * - App\Http\Controllers\Auth\AuthController::resetPassword()
 *
 * [Called By]
 * - App\Http\Controllers\Auth\AuthController::register()
 * - App\Http\Controllers\Auth\AuthController::verify2FA()
 * - App\Http\Controllers\Auth\AuthController::resend2FA()
 * - App\Http\Controllers\Auth\AuthController::login()
 * - App\Http\Controllers\Auth\AuthController::logout()
 * - App\Http\Controllers\Auth\AuthController::sendResetCode()
 * - App\Http\Controllers\Auth\AuthController::resetPassword()
 *
 * 【设计模式说明】
 * 本服务整合了三种经典设计模式：
 * 1. Factory 模式（UserFactory）：根据角色创建不同的用户数据结构
 * 2. Strategy 模式（LoginStrategy）：根据角色决定登录后跳转到哪个页面
 * 3. Repository 模式（UserRepositoryInterface）：将数据库操作与业务逻辑分离
 *
 * [Design Patterns]
 * This service integrates three classic design patterns:
 * 1. Factory pattern (UserFactory): Creates different user data structures by role.
 * 2. Strategy pattern (LoginStrategy): Determines post-login redirect by role.
 * 3. Repository pattern (UserRepositoryInterface): Separates database operations
 *    from business logic.
 *
 * 【安全措施】
 * - IDOR 防护：通过 verification_token 校验防止验证码被篡改目标用户
 * - Session 固定攻击防护：登录后重新生成 Session ID
 * - 密码哈希：使用 bcrypt 存储密码，永不明文保存
 * - 验证码时效：2FA 验证码 15 分钟过期
 * - 时序安全比较：使用 hash_equals() 防止时序攻击
 *
 * [Security Measures]
 * - IDOR protection: verification_token validation prevents code misuse against
 *   other users.
 * - Session fixation protection: Session ID is regenerated after login.
 * - Password hashing: bcrypt is used; passwords are never stored in plain text.
 * - Code expiration: 2FA codes expire after 15 minutes.
 * - Constant-time comparison: hash_equals() prevents timing attacks.
 */
class AuthService
{
    /**
     * 构造函数 — 依赖注入
     *
     * Constructor — dependency injection.
     *
     * Laravel 的服务容器（Service Container）会自动解析并注入所需的依赖。
     * 所有依赖都声明为 readonly，确保在服务生命周期内不会被意外修改。
     *
     * Laravel's Service Container automatically resolves and injects the required
     * dependencies. All dependencies are declared readonly to ensure they cannot
     * be accidentally modified during the service's lifetime.
     *
     * @param UserRepositoryInterface $userRepo 用户数据仓库 — 封装所有数据库 CRUD 操作
     * @param UserFactory             $userFactory 用户工厂 — 根据角色生成标准化的用户数据
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly UserFactory $userFactory
    ) {}

    /**
     * 注册新用户
     *
     * Register a new user.
     *
     * 【通俗解释】
     * 用户填写注册表单（姓名、邮箱、密码、角色等）后，调用此方法完成注册。
     * 系统不会立即激活账户，而是生成一个 6 位数字验证码发送到用户邮箱。
     *
     * [Plain-English Explanation]
     * After a user fills in the registration form (name, email, password, role,
     * etc.), this method completes registration. The account is not activated
     * immediately — a 6-digit verification code is generated and sent to the
     * user's email instead.
     *
     * 【调用时机】
     * 由 AuthController::register() 在用户提交注册表单时调用。
     * 在 EmailService::sendVerificationCode() 之前调用（本方法生成验证码，
     * 邮件服务负责发送）。
     *
     * [When Called]
     * Called by AuthController::register() when a user submits the registration
     * form. Called before EmailService::sendVerificationCode() (this method
     * generates the code; the email service sends it).
     *
     * 【智能去重】
     * 如果同一邮箱已经注册过但未完成验证，不会创建重复记录，
     * 而是更新已有记录（相当于"重新注册"），避免数据库中产生废弃的僵尸账号。
     *
     * [Smart Deduplication]
     * If the same email is already registered but not yet verified, no duplicate
     * record is created; the existing record is updated instead (effectively a
     * "re-registration"), preventing abandoned zombie accounts in the database.
     *
     * 【关键步骤】
     * 1. 使用 UserFactory 根据角色生成标准化的用户数据
     * 2. 生成 6 位数字验证码 + SHA-256 安全令牌
     * 3. 检查是否存在同邮箱未验证用户，有则更新、无则新建
     *
     * [Key Steps]
     * 1. Use UserFactory to generate standardized user data by role.
     * 2. Generate a 6-digit verification code + SHA-256 security token.
     * 3. Check for an existing unverified user with the same email — update if
     *    found, create new otherwise.
     *
     * @param array $data 用户提交的注册数据（name, email, password, role, phone 等）
     * @return array 关联数组：
     *   - 'user'  => User 对象
     *   - 'code'  => 6 位数字验证码（需要发送到用户邮箱）
     *   - 'token' => SHA-256 安全令牌（用于前端回传验证，防止 IDOR 攻击）
     */
    public function register(array $data): array
    {
        // Factory 模式：根据角色（donor/recipient/admin）生成用户数据
        // Factory pattern: generates user data by role (donor/recipient/admin).
        $userData = $this->userFactory->make($data);

        // 生成 6 位数字验证码 — str_pad 保证不足 6 位时左侧补零（如 42 → "000042"）
        // Generate a 6-digit verification code — str_pad zero-pads on the left
        // (e.g. 42 becomes "000042").
        $userData['two_factor_code']    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // 记录验证码生成时间（Unix 时间戳），用于后续判断是否过期（15 分钟）
        // Record code generation time (Unix timestamp) to check expiry (15 min).
        $userData['2FA_start']          = now()->timestamp;
        // 生成 SHA-256 安全令牌 — 前端需回传此令牌，防止攻击者把验证码用于其他用户（IDOR 防护）
        // Generate SHA-256 security token — the frontend must echo this back,
        // preventing an attacker from using the code against a different user
        // (IDOR protection).
        $userData['verification_token'] = hash('sha256', random_bytes(32));

        // 智能去重：如果存在同邮箱但未通过验证的用户，直接更新已有记录
        // 这样避免数据库中堆积大量未验证的僵尸账号
        // Smart deduplication: if an unverified record for this email already
        // exists, update it instead of inserting a duplicate. This avoids
        // accumulating abandoned zombie accounts in the database.
        $existing = $this->userRepo->findByEmail($userData['email']);
        if ($existing && !$existing->is_verified) {
            $this->userRepo->update($existing, $userData);
            $existing->refresh(); // 刷新模型，获取数据库中最新的字段值
            // Refresh the model to get the latest field values from the database.
            return ['user' => $existing, 'code' => $userData['two_factor_code'], 'token' => $userData['verification_token']];
        }

        // Repository 模式：通过仓库层创建新用户（而非直接操作 Eloquent Model）
        // Repository pattern: create the new user through the repository layer
        // (instead of operating on the Eloquent Model directly).
        $user = $this->userRepo->create($userData);

        // 返回用户对象、验证码和安全令牌给 Controller 层
        // Controller 会调用 EmailService 将验证码发送到用户邮箱
        // Return the user object, verification code, and security token to the
        // Controller layer. The Controller will call EmailService to send the
        // verification code to the user's email address.
        return [
            'user'  => $user,
            'code'  => $userData['two_factor_code'],
            'token' => $userData['verification_token'],
        ];
    }

    /**
     * 验证两步验证码（2FA）
     *
     * Verify the two-factor authentication (2FA) code.
     *
     * 【通俗解释】
     * 用户注册后会收到一封包含 6 位数字验证码的邮件。用户在网页上输入这 6 位
     * 数字后，调用此方法验证是否正确、是否过期、是否是本人操作。
     *
     * [Plain-English Explanation]
     * After registration, the user receives an email containing a 6-digit
     * verification code. When the user enters the 6 digits on the website, this
     * method is called to verify that the code is correct, not expired, and
     * belongs to the same user.
     *
     * 【调用时机】
     * 由 AuthController::verify2FA() 在用户提交验证码时调用。
     *
     * [When Called]
     * Called by AuthController::verify2FA() when the user submits the
     * verification code.
     *
     * 【安全防护】
     * 1. IDOR 防护：前端必须回传 verification_token（注册时返回的安全令牌），
     *    通过 hash_equals() 做时序安全比较，防止攻击者把验证码用于其他人的账号。
     * 2. 时效控制：验证码 15 分钟后自动失效，防止验证码泄露后被恶意使用。
     * 3. 一次性使用：验证成功后立即清除验证码和令牌，防止重放攻击。
     *
     * [Security Protections]
     * 1. IDOR protection: the frontend must echo back the verification_token
     *    (security token returned during registration), compared with
     *    hash_equals() for constant-time safety — prevents an attacker from
     *    applying the code to another user's account.
     * 2. Expiry control: codes automatically expire after 15 minutes, preventing
     *    misuse of leaked codes.
     * 3. Single-use: the code and token are cleared immediately upon success,
     *    preventing replay attacks.
     *
     * 【关键步骤】
     * 1. 根据邮箱查找用户
     * 2. 校验安全令牌（防止 IDOR）
     * 3. 检查验证码是否存在
     * 4. 检查验证码是否过期（15 分钟）
     * 5. 检查验证码是否匹配
     * 6. 全部通过后清除验证码、令牌，标记用户为已验证
     *
     * [Key Steps]
     * 1. Look up the user by email.
     * 2. Validate the security token (IDOR protection).
     * 3. Check whether a verification code exists.
     * 4. Check whether the code has expired (15 min).
     * 5. Check whether the code matches.
     * 6. After all checks pass, clear the code and token, mark the user verified.
     *
     * @param string $email       用户邮箱
     * @param string $inputCode   用户在前端输入的 6 位数字验证码
     * @param string $sessionToken 前端回传的安全令牌（注册时发放）
     * @return array 关联数组：
     *   - 'success' => bool   是否验证成功
     *   - 'message' => string 提示信息（成功或失败原因）
     *   - 'user'    => ?User  验证成功时返回用户对象
     */
    public function verify2FA(string $email, string $inputCode, string $sessionToken): array
    {
        $user = $this->userRepo->findByEmail($email);

        // 第一步：用户是否存在
        // Step 1: Check whether the user exists.
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // 第二步：IDOR 防护 — 比较前端回传的安全令牌与数据库中的令牌是否一致
        // 使用 hash_equals() 而非 ===，因为 hash_equals() 是时序安全的（constant-time），
        // 可以防止攻击者通过测量响应时间来逐字节猜测正确的令牌值（时序攻击）
        // Step 2: IDOR protection — compare the frontend-returned security token
        // with the database token. hash_equals() is used instead of === because
        // it is constant-time, preventing attackers from guessing the correct
        // token byte-by-byte by measuring response time (timing attack).
        if (!$user->verification_token || !hash_equals($user->verification_token, $sessionToken)) {
            return ['success' => false, 'message' => 'Invalid verification session. Please register again.'];
        }

        // 第三步：验证码和生成时间是否都存在（防止数据库字段被意外清空）
        // Step 3: Ensure both the code and its generation time exist (guard
        // against database fields being accidentally cleared).
        if (!$user->two_factor_code || !$user->{'2FA_start'}) {
            return ['success' => false, 'message' => 'No verification code found. Please register again.'];
        }

        // 第四步：时效校验 — 验证码有效期为 15 分钟
        // 从验证码生成时的 Unix 时间戳创建 Carbon 实例，加上 15 分钟
        // Step 4: Expiry check — verification codes are valid for 15 minutes.
        // Create a Carbon instance from the code's Unix timestamp and add 15 min.
        $expiryTime = Carbon::createFromTimestamp($user->{'2FA_start'})->addMinutes(15);
        if (Carbon::now()->greaterThan($expiryTime)) {
            return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        // 第五步：验证码匹配校验
        // Step 5: Verify the code matches.
        if ($user->two_factor_code !== $inputCode) {
            return ['success' => false, 'message' => 'Invalid verification code. Please try again.'];
        }

        // 全部校验通过！清除验证码和令牌，标记用户为已验证
        // 将 two_factor_code、2FA_start、verification_token 设为 null，
        // 确保验证码只能用一次（防止重放攻击）
        // All checks passed! Clear the code and token, and mark the user as
        // verified. Set two_factor_code, 2FA_start, and verification_token to
        // null so the code can only be used once (preventing replay attacks).
        $this->userRepo->update($user, [
            'two_factor_code'    => null,
            '2FA_start'          => null,
            'verification_token' => null,
            'is_verified'        => 1,
        ]);

        return ['success' => true, 'message' => 'Verification successful.', 'user' => $user];
    }

    /**
     * 重新发送 2FA 验证码
     *
     * Resend the 2FA verification code.
     *
     * 【通俗解释】
     * 用户没收到验证邮件，或者验证码过期了，点"重新发送"按钮时调用此方法。
     * 系统生成一套全新的验证码和安全令牌，旧的立即失效。
     *
     * [Plain-English Explanation]
     * Called when the user didn't receive the verification email or the code
     * expired and they click "Resend". The system generates a brand-new code and
     * security token; the old ones become invalid immediately.
     *
     * 【调用时机】
     * 由 AuthController::resend2FA() 在用户点击"重新发送验证码"时调用。
     *
     * [When Called]
     * Called by AuthController::resend2FA() when the user clicks
     * "Resend verification code".
     *
     * 【关键步骤】
     * 1. 根据邮箱查找用户
     * 2. 生成新的 6 位验证码 + 安全令牌
     * 3. 更新数据库记录（旧验证码自动失效）
     * 4. 返回新验证码和令牌给 Controller（由 EmailService 负责发送）
     *
     * [Key Steps]
     * 1. Look up the user by email.
     * 2. Generate a new 6-digit code + security token.
     * 3. Update the database record (old code becomes invalid automatically).
     * 4. Return the new code and token to the Controller (EmailService handles
     *    sending).
     *
     * @param string $email 用户邮箱
     * @return array 关联数组：
     *   - 'success' => bool
     *   - 'user'    => User
     *   - 'code'    => 新的 6 位数字验证码
     *   - 'token'   => 新的安全令牌
     */
    public function resend2FA(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // 生成全新的 6 位验证码和安全令牌，旧的自动失效
        // Generate a brand-new 6-digit verification code and security token;
        // the old ones become invalid automatically.
        $newCode  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $newToken = hash('sha256', random_bytes(32));
        // 更新数据库：用新验证码/令牌覆盖旧值，同时重置计时器
        // Update the database: overwrite old code/token with new values, and
        // reset the timer.
        $this->userRepo->update($user, [
            'two_factor_code'    => $newCode,
            '2FA_start'          => now()->timestamp,
            'verification_token' => $newToken,
        ]);

        return ['success' => true, 'user' => $user, 'code' => $newCode, 'token' => $newToken];
    }

    /**
     * 用户登录
     *
     * User login.
     *
     * 【通俗解释】
     * 用户在登录页输入邮箱和密码，点击"登录"后调用此方法。系统依次检查：
     * 邮箱是否存在 → 密码是否正确 → 是否已完成邮箱验证。全部通过后，使用
     * Laravel 的 Auth 门面完成登录，并根据用户角色（捐赠者/受赠者/管理员）
     * 决定跳转到不同的页面。
     *
     * [Plain-English Explanation]
     * Called when a user enters their email and password on the login page and
     * clicks "Login". The system checks in sequence: does the email exist → is
     * the password correct → has email verification been completed. Once all
     * checks pass, the Laravel Auth facade completes the login and determines
     * the redirect page based on the user's role (donor/recipient/admin).
     *
     * 【调用时机】
     * 由 AuthController::login() 在用户提交登录表单时调用。
     *
     * [When Called]
     * Called by AuthController::login() when the user submits the login form.
     *
     * 【安全防护】
     * 1. 密码使用 Hash::check() 验证（bcrypt），永不明文比对
     * 2. 登录成功后调用 session()->regenerate() 重新生成 Session ID，
     *    防止 Session 固定攻击（Session Fixation）
     * 3. 未完成邮箱验证的用户无法登录
     *
     * [Security Protections]
     * 1. Password is verified with Hash::check() (bcrypt); never compared in
     *    plain text.
     * 2. session()->regenerate() is called after login to regenerate the Session
     *    ID, preventing Session Fixation attacks.
     * 3. Users who have not completed email verification cannot log in.
     *
     * 【设计模式】
     * 使用策略模式（Strategy Pattern）处理不同角色的登录后跳转：
     * - 管理员 → 管理后台
     * - 捐赠者 → 捐赠面板
     * - 受赠者 → 食品浏览页
     * 新增角色时只需添加新策略类，无需修改本方法（符合开闭原则 OCP）。
     *
     * [Design Pattern]
     * Uses the Strategy Pattern for post-login redirects by role:
     * - Admin → admin dashboard
     * - Donor → donation panel
     * - Recipient → food browsing page
     * Adding a new role only requires a new strategy class; this method does not
     * need to change (Open/Closed Principle).
     *
     * @param string $email    用户邮箱
     * @param string $password 用户输入的明文密码
     * @return array 关联数组：
     *   - 'success'  => bool   登录是否成功
     *   - 'message'  => string 提示信息
     *   - 'redirect' => ?string 登录成功后的跳转路由名称
     *   - 'user'     => ?User   登录成功时返回用户对象
     */
    public function login(string $email, string $password): array
    {
        // Repository 模式：通过仓库层查找用户
        // Repository pattern: look up the user through the repository layer.
        $user = $this->userRepo->findByEmail($email);

        // 第一步：邮箱是否已注册
        // Step 1: Check whether the email is registered.
        if (!$user) {
            return ['success' => false, 'message' => 'This email is not registered.'];
        }

        // 第二步：密码校验 — 使用 Hash::check() 将用户输入的明文密码
        // 与数据库中存储的 bcrypt 哈希值进行比较，永不明文存储或比较密码
        // Step 2: Password verification — uses Hash::check() to compare the
        // user-supplied plaintext password against the bcrypt hash stored in the
        // database. Passwords are never stored or compared in plain text.
        if (!Hash::check($password, $user->password_hash)) {
            return ['success' => false, 'message' => 'Incorrect password.'];
        }

        // 第三步：是否已完成邮箱验证（未验证用户无法登录，防止垃圾注册）
        // Step 3: Check whether email verification is complete (unverified users
        // cannot log in, preventing spam registrations).
        if (!$user->is_verified) {
            return ['success' => false, 'message' => 'Please verify your email first. Check your inbox for the verification code.'];
        }

        // 登录用户并重新生成 Session ID
        // session()->regenerate() 是重要的安全措施：
        // 防止攻击者先获取一个 Session ID，诱导用户登录后，
        // 攻击者使用同一个 Session ID 冒充已登录用户（Session 固定攻击）
        // Log the user in and regenerate the Session ID.
        // session()->regenerate() is an important security measure:
        // it prevents an attacker from obtaining a Session ID first, tricking
        // the user into logging in, and then using that same Session ID to
        // impersonate the authenticated user (Session Fixation attack).
        Auth::login($user);
        session()->regenerate();

        // Strategy 模式：根据角色获取对应的登录策略
        // 每种角色有不同的登录后跳转页面，由各自的策略类决定
        // Strategy pattern: obtain the login strategy for the user's role.
        // Each role has a different post-login redirect page, determined by its
        // respective strategy class.
        $strategy = $this->getLoginStrategy($user->role);
        $redirectRoute = $strategy->handle($user);

        return [
            'success'  => true,
            'message'  => '登录成功',
            'redirect' => $redirectRoute,
            'user'     => $user,
        ];
    }

    /**
     * 退出登录
     *
     * Logout.
     *
     * 【通俗解释】
     * 用户点击"退出登录"按钮后调用此方法。系统会清除用户的登录状态和
     * 所有 Session 数据，防止其他人通过浏览器"后退"按钮重新进入已登录页面。
     *
     * [Plain-English Explanation]
     * Called when the user clicks the "Logout" button. The system clears the
     * user's login state and all Session data, preventing someone else from
     * using the browser's "Back" button to re-enter an authenticated page.
     *
     * 【调用时机】
     * 由 AuthController::logout() 在用户点击退出时调用。
     *
     * [When Called]
     * Called by AuthController::logout() when the user clicks logout.
     *
     * 【安全防护】
     * 执行三步清理操作：
     * 1. Auth::logout() — 清除 Laravel 认证状态
     * 2. session()->invalidate() — 销毁当前 Session 所有数据
     * 3. session()->regenerateToken() — 重新生成 CSRF 令牌，防止退出后
     *    旧的 CSRF 令牌被恶意利用
     *
     * [Security Protections]
     * Performs a three-step cleanup:
     * 1. Auth::logout() — clears the Laravel authentication state.
     * 2. session()->invalidate() — destroys all data in the current Session.
     * 3. session()->regenerateToken() — regenerates the CSRF token so the old
     *    token cannot be exploited after logout.
     *
     * @return void
     */
    public function logout(): void
    {
        // 清除认证状态（移除 Auth guard 中的用户标识）
        // Clear the authentication state (removes the user identity from the
        // Auth guard).
        Auth::logout();
        // 销毁当前 Session 的所有数据（清除购物车、临时数据等）
        // Destroy all data in the current Session (clear cart, temp data, etc.).
        session()->invalidate();
        // 重新生成 CSRF 令牌（Cross-Site Request Forgery 防护令牌）
        // 防止退出登录后旧的 CSRF 令牌被用于伪造请求
        // Regenerate the CSRF token (Cross-Site Request Forgery protection).
        // Prevents the old CSRF token from being used to forge requests after
        // logout.
        session()->regenerateToken();
    }

    /**
     * 更新登录用户的基本资料（firstname / lastname / phone）
     *
     * Update the authenticated user's basic profile fields.
     *
     * 【通俗解释】
     * 用户在"个人资料"页面提交姓名/电话修改表单后调用此方法。
     * 仅更新允许修改的字段（firstname、lastname、phone），绝不触碰 email、
     * role 等身份标识字段——这些字段的变更需要更严格的二次验证流程。
     *
     * [Plain-English Explanation]
     * Called when a user submits the profile edit form (firstname, lastname,
     * phone). Only the fields allowed for self-service editing are updated;
     * identity fields such as email and role are never touched — changing them
     * requires a stricter verification flow.
     *
     * 【调用时机】
     * 由 ProfileController@update() 在用户提交资料编辑表单时调用。
     *
     * [When Called]
     * Called by ProfileController@update() when the user submits the profile
     * edit form.
     *
     * 【为什么不接收 $id 参数】
     * 直接接收当前登录的 User 模型对象作为第一个参数，
     * 避免任何可能的 IDOR 风险：调用方永远无法指定"更新别的用户"。
     *
     * [Why No $id Parameter]
     * The current User model is passed as the first argument directly,
     * eliminating any possible IDOR risk — the caller can never target
     * "another user" for update.
     *
     * 【关键步骤】
     * 1. 仅从传入的 profileData 中提取允许修改的字段（白名单过滤）
     * 2. 通过仓储层 update() 写入数据库
     *
     * [Key Steps]
     * 1. Whitelist-allow only the editable fields from profileData.
     * 2. Persist the change through the repository layer's update().
     *
     * @param User   $user        当前登录用户（由 Controller 通过 Auth::user() 提供）
     * @param array  $profileData 关联数组，包含 firstname / lastname / phone
     * @return bool  更新成功返回 true，失败返回 false
     */
    public function updateProfile(User $user, array $profileData): bool
    {
        // 显式仅写入允许修改的字段 —— 即使 profileData 含多余键也不会被滥用
        // Explicitly write only the editable fields — even if profileData
        // contains extra keys, they will be ignored.
        return $this->userRepo->update($user, [
            'firstname' => $profileData['firstname'],
            'lastname'  => $profileData['lastname'],
            'phone'     => $profileData['phone'] ?? null,
        ]);
    }

    /**
     * 修改登录用户的密码
     *
     * Update the authenticated user's password.
     *
     * 【通俗解释】
     * 用户在"修改密码"页面输入当前密码 + 新密码 + 确认新密码后调用此方法。
     * 系统先校验当前密码是否正确（防他人篡改），再用 bcrypt 加密新密码写入数据库，
     * 最后刷新 CSRF token 以防旧 token 被重放。
     *
     * [Plain-English Explanation]
     * Called when a user submits the change-password form (current password +
     * new password + retype new password). The system first verifies the
     * current password, then encrypts the new password with bcrypt and persists
     * it, and finally refreshes the CSRF token to prevent replay of the old one.
     *
     * 【调用时机】
     * 由 ProfileController@updatePassword() 在用户提交修改密码表单时调用。
     *
     * [When Called]
     * Called by ProfileController@updatePassword() when the user submits the
     * change-password form.
     *
     * 【业务流程】
     * 1. 用 Hash::check() 比对 currentPassword 与 $user->password_hash
     * 2. 失败：返回 ['success' => false, 'message' => 'Current password is incorrect.']
     * 3. 成功：通过仓储的 update() 方法把新密码写入 password_hash
     *    （仓储内部会自动 Hash::make）
     * 4. 调用 session()->regenerateToken() 刷新 CSRF token
     *
     * [Process Flow]
     * 1. Use Hash::check() to compare currentPassword against $user->password_hash.
     * 2. On failure: return ['success' => false, 'message' => 'Current password is incorrect.'].
     * 3. On success: call the repository's update() to write the new password into
     *    password_hash (the repository auto-hashes it via Hash::make()).
     * 4. Call session()->regenerateToken() to refresh the CSRF token.
     *
     * 【为什么 NOT regenerate() 只 regenerateToken()】
     * regenerate() 用于登录场景防 Session Fixation：防止攻击者预先获得
     * 一个 Session ID，待用户登录后用同一个 ID 冒充用户。
     * 在已登录场景下改密码，不存在"预先持有未认证 Session ID"的攻击面，
     * 调用 regenerate() 会无谓地丢失用户的其他会话数据（如购物车、临时表单）。
     * 但必须 regenerateToken()：CSRF token 在浏览器中可能被缓存，
     * 密码已变更的会话继续使用旧 CSRF token 存在被重放的风险。
     *
     * [Why Only regenerateToken(), Not regenerate()]
     * regenerate() defends against Session Fixation at login — preventing an
     * attacker from pre-acquiring a Session ID and impersonating the user after
     * they authenticate. Within an already-authenticated session, no such attack
     * surface exists, so calling regenerate() would unnecessarily discard other
     * session data (cart, temp forms, etc.).
     * However, regenerateToken() is required: the CSRF token may be cached in
     * the browser, and continuing to use the old token after a password change
     * risks replay.
     *
     * 【与 resetPassword() 的区别】
     * resetPassword() 走的是"忘记密码"路径，依赖 verification_token + 6 位验证码
     * + 15 分钟时效；本方法走的是"已登录用户在站内主动改密"路径，
     * 只需要当前密码即可确认本人，不需要邮件验证码。两个流程机制完全不同，
     * 因此不复用 resetPassword()。
     *
     * [Difference from resetPassword()]
     * resetPassword() is the "Forgot Password" flow — it relies on a
     * verification_token + a 6-digit code with a 15-minute expiry. The current
     * method is the "logged-in user actively changes password" flow, which
     * only needs the current password to confirm the user's identity; no email
     * code is required. The two mechanisms are fundamentally different, so this
     * method deliberately does not reuse resetPassword().
     *
     * @param User    $user            当前登录用户
     * @param string  $currentPassword 用户在表单中输入的当前密码（明文）
     * @param string  $newPassword     用户在表单中输入的新密码（明文）
     * @return array  关联数组：
     *   - 'success' => bool
     *   - 'message' => string
     */
    public function updatePasswordForAuthenticatedUser(
        User $user,
        string $currentPassword,
        string $newPassword
    ): array {
        // 第一步：校验当前密码
        // Step 1: Verify the current password.
        // Hash::check() 内部是 constant-time 比较（bcrypt 算法本身设计如此），
        // 可防止时序攻击。失败时直接返回错误消息（用户已登录操作自身账户，
        // 不存在枚举攻击威胁，提示比模糊化更友好）。
        //
        // Hash::check() is internally constant-time (bcrypt is designed this way),
        // preventing timing attacks. On failure we return a specific message —
        // since the user is already authenticated and acting on their own
        // account, there is no enumeration threat and clarity beats vagueness.
        if (!Hash::check($currentPassword, $user->password_hash)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // 第二步：写入新密码（仓储内部 Hash::make）
        // Step 2: Write the new password (the repository auto-hashes via Hash::make).
        // 直接写 password_hash 列名，避免触发 UserRepository::update() 内的
        // "password 键转哈希"分支带来的歧义；这里我们已经显式调用了 Hash::make。
        //
        // We write the password_hash column directly to avoid ambiguity with the
        // "password key → hash" branch in UserRepository::update(); we have
        // already invoked Hash::make() explicitly here.
        $updated = $this->userRepo->update($user, [
            'password_hash' => Hash::make($newPassword),
        ]);

        if (!$updated) {
            return ['success' => false, 'message' => 'Could not update password. Please try again.'];
        }

        // 第三步：刷新 CSRF token，防止旧 token 在已改密浏览器中被重放
        // Step 3: Refresh the CSRF token to prevent replay of the old token.
        session()->regenerateToken();

        return [
            'success' => true,
            'message' => 'Your password has been updated successfully.',
        ];
    }

    /**
     * 发送密码重置验证码
     *
     * Send a password reset verification code.
     *
     * 【通俗解释】
     * 用户在"忘记密码"页面输入邮箱，点击"发送验证码"后调用此方法。
     * 系统生成一个新的 6 位验证码发送到用户邮箱，用户凭此验证码才能设置新密码。
     *
     * [Plain-English Explanation]
     * Called when a user enters their email on the "Forgot Password" page and
     * clicks "Send Code". The system generates a new 6-digit verification code
     * and sends it to the user's email; the user needs this code to set a new
     * password.
     *
     * 【调用时机】
     * 由 AuthController::sendResetCode() 在用户请求密码重置时调用。
     *
     * [When Called]
     * Called by AuthController::sendResetCode() when a user requests a password
     * reset.
     *
     * 【安全防护】
     * 仅允许已验证邮箱的用户重置密码 — 未验证的用户可能是恶意注册的僵尸账号，
     * 不应允许其通过密码重置功能探测系统中是否存在某邮箱。
     *
     * [Security Protection]
     * Only verified-email users are allowed to reset their password — unverified
     * users may be maliciously-registered zombie accounts and should not be able
     * to probe whether an email exists in the system via the password reset
     * feature.
     *
     * 【关键步骤】
     * 1. 查找用户并验证其邮箱已验证
     * 2. 生成 6 位验证码 + 安全令牌
     * 3. 更新数据库（复用 2FA 字段存储重置码，两个流程互斥不会冲突）
     *
     * [Key Steps]
     * 1. Find the user and confirm their email is verified.
     * 2. Generate a 6-digit code + security token.
     * 3. Update the database (reuses 2FA fields; the two flows are mutually
     *    exclusive so no conflict occurs).
     *
     * @param string $email 用户邮箱
     * @return array 关联数组：
     *   - 'success' => bool
     *   - 'user'    => ?User
     *   - 'code'    => 6 位数字重置验证码
     *   - 'token'   => 安全令牌
     */
    public function sendResetCode(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);

        // 安全检查：只有已验证邮箱的用户才能重置密码
        // 这可以防止攻击者通过"忘记密码"功能探测系统中存在哪些邮箱
        // （如果对未验证用户也返回验证码，攻击者就能知道某邮箱已注册）
        // Security check: only users with verified emails can reset their
        // password. This prevents attackers from probing which emails exist in
        // the system via the "Forgot Password" feature (if a code were returned
        // for unverified users too, the attacker would know that email is
        // registered).
        if (!$user || !$user->is_verified) {
            return ['success' => false, 'message' => 'No verified account found with this email.'];
        }

        // 生成 6 位重置验证码 + 安全令牌
        // Generate a 6-digit reset code + security token.
        $code  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = hash('sha256', random_bytes(32));
        // 复用 two_factor_code 和 verification_token 字段存储密码重置验证码
        // 这两个场景（2FA 验证 vs 密码重置）互斥，复用字段是安全的
        // Reuse the two_factor_code and verification_token fields to store the
        // password reset code. These two scenarios (2FA verification vs. password
        // reset) are mutually exclusive, so field reuse is safe.
        $this->userRepo->update($user, [
            'two_factor_code'    => $code,
            '2FA_start'          => now()->timestamp,
            'verification_token' => $token,
        ]);

        return ['success' => true, 'user' => $user, 'code' => $code, 'token' => $token];
    }

    /**
     * 验证重置码并更新密码
     *
     * Verify the reset code and update the password.
     *
     * 【通俗解释】
     * 用户收到密码重置验证码后，输入验证码和新密码，调用此方法完成密码重置。
     * 系统会依次验证：用户存在 → 令牌有效 → 验证码存在 → 未过期 → 匹配。
     * 全部通过后用 bcrypt 加密新密码并存入数据库。
     *
     * [Plain-English Explanation]
     * After receiving the password reset verification code, the user enters the
     * code and a new password; this method completes the password reset. The
     * system validates in sequence: user exists → token valid → code exists →
     * not expired → matches. Once all checks pass, the new password is encrypted
     * with bcrypt and stored in the database.
     *
     * 【调用时机】
     * 由 AuthController::resetPassword() 在用户提交重置密码表单时调用。
     *
     * [When Called]
     * Called by AuthController::resetPassword() when the user submits the reset
     * password form.
     *
     * 【安全防护】
     * 1. IDOR 防护：必须回传发送验证码时返回的安全令牌
     * 2. 时效控制：重置码 15 分钟过期
     * 3. 一次性使用：重置成功后清除验证码和令牌
     * 4. 密码哈希：使用 Hash::make()（bcrypt）加密，永不明文存储
     *
     * [Security Protections]
     * 1. IDOR protection: the security token returned when the code was sent must
     *    be echoed back.
     * 2. Expiry control: reset codes expire after 15 minutes.
     * 3. Single-use: the code and token are cleared after a successful reset.
     * 4. Password hashing: encrypted with Hash::make() (bcrypt); never stored in
     *    plain text.
     *
     * 【关键步骤】
     * 1. 根据邮箱查找用户
     * 2. 校验安全令牌（IDOR 防护）
     * 3. 检查重置码是否存在
     * 4. 检查重置码是否过期（15 分钟）
     * 5. 检查重置码是否匹配
     * 6. 用 bcrypt 加密新密码并更新数据库
     *
     * [Key Steps]
     * 1. Look up the user by email.
     * 2. Validate the security token (IDOR protection).
     * 3. Check whether a reset code exists.
     * 4. Check whether the code has expired (15 min).
     * 5. Check whether the code matches.
     * 6. Encrypt the new password with bcrypt and update the database.
     *
     * @param string $email        用户邮箱
     * @param string $code         用户输入的 6 位重置验证码
     * @param string $newPassword  用户输入的新密码（明文，由本方法加密）
     * @param string $sessionToken 前端回传的安全令牌
     * @return array 关联数组：
     *   - 'success' => bool   密码重置是否成功
     *   - 'message' => string 提示信息
     */
    public function resetPassword(string $email, string $code, string $newPassword, string $sessionToken): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // IDOR 防护：校验前端回传的安全令牌与数据库中的令牌是否一致
        // 使用 hash_equals() 做时序安全比较，防止时序攻击
        // IDOR protection: verify the frontend-returned security token matches
        // the database token. hash_equals() provides constant-time comparison to
        // prevent timing attacks.
        if (!$user->verification_token || !hash_equals($user->verification_token, $sessionToken)) {
            return ['success' => false, 'message' => 'Invalid reset session. Please request a new code.'];
        }

        // 检查是否已发送过重置码（防止跳过"发送验证码"步骤直接调用此接口）
        // Check whether a reset code was actually sent (prevents calling this
        // endpoint directly without going through the "Send Code" step first).
        if (!$user->two_factor_code || !$user->{'2FA_start'}) {
            return ['success' => false, 'message' => 'No reset code found. Please request a new one.'];
        }

        // 时效校验：重置码有效期为 15 分钟
        // Expiry check: reset codes are valid for 15 minutes.
        if (Carbon::now()->greaterThan(Carbon::createFromTimestamp($user->{'2FA_start'})->addMinutes(15))) {
            return ['success' => false, 'message' => 'Reset code has expired. Please request a new one.'];
        }

        // 验证码匹配校验
        // Code match check.
        if ($user->two_factor_code !== $code) {
            return ['success' => false, 'message' => 'Invalid reset code.'];
        }

        // 全部校验通过！
        // Hash::make() 使用 bcrypt 算法加密新密码（自动加盐，不可逆）
        // 同时清除验证码和令牌，确保重置码只能用一次（防止重放攻击）
        // All checks passed!
        // Hash::make() encrypts the new password using bcrypt (auto-salted,
        // irreversible). The code and token are also cleared so the reset code
        // can only be used once (preventing replay attacks).
        $this->userRepo->update($user, [
            'password_hash'      => Hash::make($newPassword),
            'two_factor_code'    => null,
            '2FA_start'          => null,
            'verification_token' => null,
        ]);

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    /**
     * 根据角色获取对应的登录策略实例（策略模式）
     *
     * Get the corresponding login strategy instance by role (Strategy pattern).
     *
     * 【通俗解释】
     * 不同角色的用户登录后应该看到不同的页面：
     * - 管理员 → 管理后台仪表盘
     * - 捐赠者 → 我的捐赠面板
     * - 受赠者 → 可领取的食品列表
     *
     * 本方法使用 PHP 8 的 match 表达式，根据角色名返回对应的策略类实例。
     * 每个策略类实现 LoginStrategyInterface 接口，都有 handle() 方法，
     * 返回该角色登录后应该跳转的路由名称。
     *
     * [Plain-English Explanation]
     * Users with different roles should see different pages after login:
     * - Admin → admin dashboard
     * - Donor → my donations panel
     * - Recipient → available food listing
     *
     * This method uses PHP 8's match expression to return the appropriate
     * strategy class instance by role name. Each strategy class implements
     * LoginStrategyInterface and has a handle() method that returns the route
     * name for that role's post-login redirect.
     *
     * 【设计模式 — 策略模式】
     * 如果不使用策略模式，就需要在 login() 方法中写一长串 if/else 或
     * switch/case 来判断角色。使用策略模式后：
     * - 每种角色的跳转逻辑封装在自己的策略类中（单一职责原则 SRP）
     * - 新增角色时只需添加新的策略类，不必修改本方法（开闭原则 OCP）
     * - 可以轻松为策略类编写独立的单元测试
     *
     * [Design Pattern — Strategy Pattern]
     * Without the Strategy pattern, the login() method would need a long chain
     * of if/else or switch/case to determine the role. With the Strategy pattern:
     * - Each role's redirect logic is encapsulated in its own strategy class
     *   (Single Responsibility Principle).
     * - Adding a new role only requires a new strategy class; this method does
     *   not need to change (Open/Closed Principle).
     * - Strategy classes can easily be unit-tested independently.
     *
     * 【调用时机】
     * 仅由本服务的 login() 方法在密码验证通过后调用。
     *
     * [When Called]
     * Only called by this service's login() method after password verification
     * passes.
     *
     * @param string $role 用户角色（admin / donor / recipient）
     * @return LoginStrategyInterface 对应角色的登录策略实例
     * @throws \InvalidArgumentException 如果角色未知（数据库脏数据或前端篡改）
     */
    private function getLoginStrategy(string $role): LoginStrategyInterface
    {
        // PHP 8 match 表达式：类似 switch，但更严格（全等比较 ===）且必须穷举所有情况
        // strtolower() 统一转小写，防止 "Admin" / "ADMIN" 等大小写变体导致的匹配失败
        // PHP 8 match expression: like switch, but stricter (identity check ===)
        // and must be exhaustive. strtolower() normalizes case to prevent
        // matching failures from variants like "Admin" / "ADMIN".
        return match (strtolower($role)) {
            'admin'     => new AdminLoginStrategy(),      // 管理员 → 后台仪表盘
                                                           // Admin → admin dashboard
            'donor'     => new DonorLoginStrategy(),      // 捐赠者 → 捐赠面板
                                                           // Donor → donation panel
            'recipient' => new RecipientLoginStrategy(),  // 受赠者 → 食品浏览页
                                                           // Recipient → food browsing page
            // 如果角色不在上述三种中（如数据库被篡改、前端注入攻击），抛出异常
            // If the role is not one of the three above (e.g. database tampering
            // or front-end injection attack), throw an exception.
            default     => throw new \InvalidArgumentException("未知角色：{$role}"),
        };
    }
}
