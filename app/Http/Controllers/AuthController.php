<?php

/**
 * ============================================================================
 * 认证控制器 — AuthController
 * ============================================================================
 *
 * Authentication Controller — AuthController
 *
 * 所属模块：用户认证模块 (Authentication Module)
 * Module: User Authentication Module
 *
 * 项目名称：FoodShare — 食物捐赠平台
 * Project: FoodShare — Food Donation Platform
 *
 * 文件作用：
 *   处理所有与用户身份认证相关的 HTTP 请求，包括用户登录、用户注册、
 *   重新发送验证码、退出登录等操作。本控制器是用户进入系统前的"守门人"，
 *   确保只有通过邮箱验证的合法用户才能访问受保护的功能页面。
 *
 * Purpose:
 *   Handles all HTTP requests related to user authentication: login, registration,
 *   resending verification codes, and logout. This controller serves as the "gatekeeper"
 *   before users enter the system, ensuring only email-verified legitimate users can
 *   access protected feature pages.
 *
 * 业务流程位置：
 *   用户访问平台 → 认证页面（登录 / 注册）→ 输入凭证 → 提交表单
 *   → AuthController 验证并调用 AuthService 处理 → 发送 2FA 验证邮件
 *   → 用户输入验证码 → 验证通过 → 进入系统首页
 *
 * Business flow position:
 *   User visits platform → Auth page (Login / Register) → Enters credentials → Submits form
 *   → AuthController validates and calls AuthService → Sends 2FA verification email
 *   → User enters verification code → Code verified → Enters system homepage
 *
 * 依赖关系：
 *   - AuthService (app/Services/AuthService.php)：核心认证业务逻辑
 *     -- 用户登录验证（密码校验、状态检查）
 *     -- 用户注册（创建用户记录、生成 2FA 验证码和 token）
 *     -- 退出登录（清除 session 中的认证信息）
 *   - EmailService (app/Services/EmailService.php)：邮件发送服务
 *     -- 构建 HTML 验证邮件内容
 *     -- 通过 SMTP 发送验证码邮件
 *   - User Model (app/Models/User.php)：用户数据模型
 *     -- 用于注册时的邮箱去重检查（只检查已验证用户）
 *   - reCAPTCHA 服务（Google）：防止机器人自动提交表单
 *     -- 登录时需要校验 g-recaptcha-response token
 *   - Session：使用 Laravel session 存储认证状态和闪存消息
 *
 * Dependencies:
 *   - AuthService (app/Services/AuthService.php): Core auth business logic
 *     -- Login verification (password check, status check)
 *     -- Registration (create user record, generate 2FA code and token)
 *     -- Logout (clear auth info from session)
 *   - EmailService (app/Services/EmailService.php): Email sending service
 *     -- Build HTML verification email content
 *     -- Send verification code email via SMTP
 *   - User Model (app/Models/User.php): User data model
 *     -- Used for email dedup check during registration (checks verified users only)
 *   - reCAPTCHA (Google): Prevents automated bot form submissions
 *     -- Validates g-recaptcha-response token during login
 *   - Session: Uses Laravel session to store auth state and flash messages
 *
 * 路由映射（参考 routes/web.php）：
 *   GET  /login          → AuthController@showLogin      — 显示登录页
 *   GET  /register       → AuthController@showRegister   — 显示注册页
 *   POST /login          → AuthController@login          — 处理登录
 *   POST /register       → AuthController@register       — 处理注册
 *   POST /logout         → AuthController@logout         — 处理退出
 *   GET  /verify-2fa     → 2FaController@showVerifyForm  — 显示 2FA 验证表单
 *   POST /resend-code    → 2FaController@resendCode      — 重新发送验证码
 *   POST /verify-2fa     → 2FaController@verify          — 验证 2FA 码
 *
 * Route mapping (see routes/web.php):
 *   GET  /login          → AuthController@showLogin      — Show login page
 *   GET  /register       → AuthController@showRegister   — Show registration page
 *   POST /login          → AuthController@login          — Process login
 *   POST /register       → AuthController@register       — Process registration
 *   POST /logout         → AuthController@logout         — Process logout
 *   GET  /verify-2fa     → 2FaController@showVerifyForm  — Show 2FA verification form
 *   POST /resend-code    → 2FaController@resendCode      — Resend verification code
 *   POST /verify-2fa     → 2FaController@verify          — Verify 2FA code
 */

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 认证控制器
 *
 * Authentication Controller.
 *
 * 负责接收和处理用户登录、注册、退出等 HTTP 请求，
 * 通过构造函数注入 AuthService 和 EmailService 两个核心服务，
 * 将业务逻辑委托给服务层处理，控制器仅做请求验证和路由跳转。
 *
 * Receives and processes HTTP requests for login, registration, logout, etc.
 * Injects AuthService and EmailService via the constructor,
 * delegating business logic to the service layer while the controller only handles
 * request validation and route redirection.
 */
class AuthController extends Controller
{
    /**
     * 构造函数 — 依赖注入认证服务和邮件服务
     *
     * Constructor — dependency injection of auth service and email service.
     *
     * Laravel 的服务容器会自动解析并注入 AuthService 和 EmailService 实例。
     * Laravel's service container auto-resolves and injects AuthService and EmailService instances.
     * 使用 PHP 8.1 的 readonly 属性确保服务实例在构造后不可修改。
     * Uses PHP 8.1 readonly properties to ensure service instances are immutable after construction.
     *
     * @param AuthService $authService 认证业务逻辑服务（登录、注册、退出）
     * @param AuthService $authService Auth business logic service (login, register, logout).
     * @param EmailService $emailService 邮件构建与发送服务（2FA 验证邮件）
     * @param EmailService $emailService Email building and sending service (2FA verification emails).
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly EmailService $emailService
    ) {}

    /**
     * 显示登录页面（认证统一入口）
     *
     * Show the login page (unified auth entry point).
     *
     * 用途：
     *   渲染认证首页视图，该页面同时包含"登录"和"注册"两个 Tab 页签，
     *   默认显示登录 Tab。用户可在此页面输入邮箱、密码完成登录。
     *
     * Purpose:
     *   Render the auth index view, which contains both "Login" and "Registration" tabs,
     *   defaulting to the Login tab. Users enter email and password here to log in.
     *
     * 调用时机：
     *   - 用户访问 /login 路由（GET 请求）
     *   - 未登录用户被中间件重定向到登录页时
     *   - 退出登录后跳转回登录页
     *
     * When called:
     *   - User visits the /login route (GET request)
     *   - Unauthenticated users are redirected here by middleware
     *   - After logout, user is redirected back to the login page
     *
     * 返回值：
     *   View — auth.index 视图（对应 resources/views/auth/index.blade.php）
     *
     * Returns:
     *   View — the auth.index view (resources/views/auth/index.blade.php)
     *
     * 关键说明：
     *   - 不检查用户是否已登录，即使已登录也可直接访问（可在外层路由添加 guest 中间件限制）
     *   - 与 showRegister() 返回的是同一个视图文件，通过前端 JS 切换 Tab
     *
     * Key notes:
     *   - Does not check if user is already logged in; can be accessed even when authenticated
     *     (add guest middleware at the route level to restrict)
     *   - Returns the same view file as showRegister(); frontend JS switches between tabs
     */
    public function showLogin()
    {
        return view('auth.index');
    }

    /**
     * 显示注册页面（与登录共用同一页面）
     *
     * Show the registration page (shares the same page as login).
     *
     * 用途：
     *   渲染认证首页视图，与 showLogin() 返回相同的视图文件 (auth.index)，
     *   前端通过 URL 路径或查询参数判断应默认展示"注册"Tab。
     *
     * Purpose:
     *   Render the auth index view — same view file (auth.index) as showLogin().
     *   The frontend determines which tab (Registration) to show by default based on URL path or query params.
     *
     * 调用时机：
     *   - 用户访问 /register 路由（GET 请求）
     *   - 用户点击页面中的"创建账户"链接或注册按钮时
     *
     * When called:
     *   - User visits the /register route (GET request)
     *   - User clicks the "Create Account" link or registration button on the page
     *
     * 返回值：
     *   View — auth.index 视图
     *
     * Returns:
     *   View — the auth.index view
     *
     * 关键说明：
     *   - 与登录共用同一个 Blade 模板，前端通过 Tab 切换实现登录/注册的界面分离
     *   - 注册表单包含：名、姓、电话（可选）、邮箱、密码、确认密码、角色选择
     *
     * Key notes:
     *   - Shares the same Blade template as login; frontend Tab switching separates login/registration UI
     *   - Registration form includes: first name, last name, phone (optional), email, password, confirm password, role selection
     */
    public function showRegister()
    {
        return view('auth.index');
    }

    /**
     * 处理用户登录请求
     *
     * Handle user login request.
     *
     * 用途：
     *   接收用户提交的登录表单（邮箱、密码、reCAPTCHA token），
     *   依次进行表单验证、reCAPTCHA 人机验证、业务层登录验证，
     *   全部通过后将用户信息写入 session 并跳转到对应页面。
     *
     * Purpose:
     *   Receive the login form (email, password, reCAPTCHA token),
     *   perform form validation, reCAPTCHA bot verification, and business-layer login checks,
     *   then write user info to session and redirect to the appropriate page on success.
     *
     * 调用时机：
     *   - 用户在登录 Tab 中提交登录表单时（POST /login）
     *
     * When called:
     *   - User submits the login form in the Login tab (POST /login)
     *
     * 业务流程：
     *   1. 表单字段验证（邮箱格式、密码非空、reCAPTCHA token 非空）
     *   2. 向 Google reCAPTCHA 服务发送验证请求，校验 token 有效性
     *   3. 调用 AuthService::login() 进行业务层验证（用户存在性、密码正确性、账户状态）
     *   4. 成功 → 写入 session → 根据用户角色跳转到对应首页
     *   5. 失败 → 携带错误消息返回上一页（保留已输入的表单数据）
     *
     * Business flow:
     *   1. Validate form fields (email format, password not empty, reCAPTCHA token not empty)
     *   2. Send verification request to Google reCAPTCHA service to validate the token
     *   3. Call AuthService::login() for business-layer checks (user existence, password correctness, account status)
     *   4. Success → write to session → redirect to home page based on user role
     *   5. Failure → go back with error message (preserving previously entered form data)
     *
     * @param Request $request HTTP 请求实例，包含 email、password、g-recaptcha-response
     * @param Request $request HTTP request instance containing email, password, and g-recaptcha-response.
     * @return \Illuminate\Http\RedirectResponse 成功跳转到首页，失败返回上一页
     * @return \Illuminate\Http\RedirectResponse Redirect to home page on success, back to previous page on failure.
     */
    public function login(Request $request)
    {
        // ──────────────────────────────────────────────
        // 第一步：表单字段验证
        // Step 1: Validate form fields.
        // 验证邮箱格式、密码非空、reCAPTCHA 响应非空
        // Validate email format, password not empty, reCAPTCHA response not empty.
        // ──────────────────────────────────────────────
        $validated = $request->validate([
            'email'              => 'required|email',
            'password'           => 'required|string',
            'g-recaptcha-response' => 'required|string',
        ], [
            'email.required'              => 'Please enter your email address.',
            'email.email'                 => 'Please enter a valid email address.',
            'password.required'           => 'Please enter your password.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
        ]);

        // ──────────────────────────────────────────────
        // 第二步：reCAPTCHA 人机验证
        // Step 2: reCAPTCHA bot verification.
        // 向 Google reCAPTCHA API 发送 token 验证请求，
        // Send token verification request to Google reCAPTCHA API,
        // 使用 asForm() 以 application/x-www-form-urlencoded 格式提交，
        // using asForm() for application/x-www-form-urlencoded format,
        // withoutVerifying() 跳过 SSL 证书验证（开发/内网环境需要）
        // withoutVerifying() to skip SSL cert verification (needed for dev/intranet environments).
        // ──────────────────────────────────────────────
        $verify = \Illuminate\Support\Facades\Http::asForm()
            ->withoutVerifying()
            ->post('https://www.recaptcha.net/recaptcha/api/siteverify', [
                'secret'   => config('recaptcha.secret_key'),   // 服务端密钥，从配置文件读取
                // Server-side secret key, read from config file.
                'response' => $validated['g-recaptcha-response'], // 前端 generate 的 token
                // Token generated by the frontend.
                'remoteip' => $request->ip(),                     // 用户 IP（可选，用于统计）
                // User IP (optional, for statistics).
            ]);
        $verifyBody = $verify->json();

        // 检查 reCAPTCHA 验证是否通过（success 为 true 表示是人类）
        // Check whether reCAPTCHA verification passed (success=true means human).
        if (!($verifyBody['success'] ?? false)) {
            // 验证失败 — 返回到上一页，携带错误消息和已输入数据
            // Verification failed — go back with error message and previously entered data.
            return back()->withErrors(['login' => 'reCAPTCHA verification failed. Please try again.'])->withInput();
        }

        // ──────────────────────────────────────────────
        // 第三步：调用认证服务进行业务层登录验证
        // Step 3: Call auth service for business-layer login validation.
        // AuthService::login() 负责：
        // AuthService::login() handles:
        //   - 查询用户是否存在（根据 email）
        //   - Check if user exists (by email).
        //   - 校验密码是否正确（Hash::check）
        //   - Verify password correctness (Hash::check).
        //   - 检查账户状态（是否已验证邮箱、是否被禁用等）
        //   - Check account status (email verified, not banned, etc.).
        //   - 将认证信息写入 session
        //   - Write auth info to session.
        // ──────────────────────────────────────────────
        $result = $this->authService->login(
            $validated['email'],
            $validated['password']
        );

        // 登录失败 — 返回到上一页，携带服务层返回的错误消息
        // Login failure — go back with the error message returned by the service layer.
        if (!$result['success']) {
            return back()->withErrors(['login' => $result['message']])->withInput();
        }

        // 登录成功 — 根据服务层返回的 redirect 路由名称跳转
        // Login success — redirect using the route name returned by the service layer.
        // （不同角色可能跳转到不同页面，如 donor 跳转到捐赠者首页，recipient 跳转到受赠者首页）
        // (Different roles may redirect to different pages, e.g. donor → donor home, recipient → recipient home.)
        return redirect()->route($result['redirect']);
    }

    /**
     * 处理用户注册请求（含 2FA 验证码发送）
     *
     * Handle user registration request (with 2FA verification code sending).
     *
     * 用途：
     *   接收用户提交的注册表单数据（姓名、邮箱、密码、角色等），
     *   完成表单验证、用户创建、2FA 验证码生成和邮件发送，
     *   最后跳转到 2FA 验证页面等待用户输入验证码。
     *
     * Purpose:
     *   Receive registration form data (name, email, password, role, etc.),
     *   complete form validation, user creation, 2FA code generation and email sending,
     *   then redirect to the 2FA verification page for the user to enter the code.
     *
     * 调用时机：
     *   - 用户在注册 Tab 中提交注册表单时（POST /register）
     *
     * When called:
     *   - User submits the registration form in the Registration tab (POST /register)
     *
     * 业务流程：
     *   1. 表单字段验证（姓名、邮箱去重、密码强度、密码确认、角色合法性）
     *   2. 调用 AuthService::register() 创建用户并生成 6 位数字验证码 + 验证 token
     *   3. 调用 EmailService 构建 HTML 验证邮件
     *   4. 通过 EmailService 发送验证码邮件
     *   5. 成功/失败均跳转到 2FA 验证页面，通过 session flash 传递必要参数
     *
     * Business flow:
     *   1. Validate form fields (name, email dedup, password strength, confirmation, role validity)
     *   2. Call AuthService::register() to create user and generate 6-digit code + verification token
     *   3. Call EmailService to build HTML verification email
     *   4. Send verification code email via EmailService
     *   5. Redirect to 2FA verification page (success or failure), passing necessary data via session flash
     *
     * 邮箱去重策略：
     *   只检查已验证（is_verified=1）的用户 —— 允许未验证的"僵尸"账户被覆盖。
     *   这意味着同一邮箱可以被重复注册，直到有人真正验证了该邮箱。
     *
     * Email dedup strategy:
     *   Only checks verified (is_verified=1) users — unverified "zombie" accounts can be overwritten.
     *   This means the same email can be re-registered until someone actually verifies it.
     *
     * 密码强度要求（通过正则实现）：
     *   - 至少 8 个字符
     *   - 至少包含一个小写字母 [a-z]
     *   - 至少包含一个大写字母 [A-Z]
     *   - 至少包含一个数字 [0-9]
     *
     * Password strength requirements (enforced via regex):
     *   - At least 8 characters
     *   - At least one lowercase letter [a-z]
     *   - At least one uppercase letter [A-Z]
     *   - At least one digit [0-9]
     *
     * 邮件发送失败的处理：
     *   - 用户已创建成功，不会因为邮件失败而回滚
     *   - 跳转到 2FA 验证页面时附带 warning 消息
     *   - 用户可在 2FA 页面点击"重新发送"按钮再次请求验证码
     *
     * Handling email send failure:
     *   - User is already created successfully; no rollback on email failure
     *   - Redirect to 2FA verification page with a warning message
     *   - User can click "Resend" button on the 2FA page to request the code again
     *
     * @param Request $request HTTP 请求实例
     * @param Request $request HTTP request instance.
     * @return \Illuminate\Http\RedirectResponse 重定向到 2FA 验证页面（verify2fa.form 路由）
     * @return \Illuminate\Http\RedirectResponse Redirect to the 2FA verification page (verify2fa.form route).
     */
    public function register(Request $request)
    {
        // ──────────────────────────────────────────────
        // 第一步：注册表单字段验证
        // Step 1: Validate registration form fields.
        // ──────────────────────────────────────────────
        $validated = $request->validate([
            'firstname'        => 'required|string|max:100',
            'lastname'         => 'required|string|max:100',
            'phone'            => 'nullable|string|max:100',
            // 邮箱验证规则
            // Email validation rules.
            'email'            => [
                'required',
                'email',
                'max:100',
                // 自定义闭包验证器：只检查已验证用户，允许覆盖未验证的注册
                // Custom closure validator: only checks verified users, allowing overwrite of unverified registrations.
                function (string $attribute, mixed $value, \Closure $fail) {
                    $verified = User::where('email', $value)->where('is_verified', 1)->exists();
                    if ($verified) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            // 密码验证规则：至少8位，必须包含大小写字母和数字
            // Password validation: min 8 chars, must include uppercase, lowercase, and digits.
            'password'         => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
            // 确认密码必须与密码一致
            // Confirm password must match the password field.
            'confirm_password' => 'required|string|same:password',
            // 角色验证规则：仅允许 donor（捐赠者）和 recipient（受赠者）
            // Role validation: only "donor" and "recipient" are allowed.
            'role'             => ['required', 'string', Rule::in(['donor', 'recipient'])],
        ], [
            'firstname.required'         => 'Please enter your first name.',
            'lastname.required'          => 'Please enter your last name.',
            'email.required'             => 'Please enter your email address.',
            'email.email'                => 'Please enter a valid email address.',
            'password.required'          => 'Please enter a password.',
            'password.min'               => 'Password must be at least 8 characters.',
            'password.regex'             => 'Password must contain uppercase, lowercase, and numbers.',
            'confirm_password.required'  => 'Please confirm your password.',
            'confirm_password.same'      => 'The passwords do not match.',
            'role.required'              => 'Please select a role.',
            'role.in'                    => 'The selected role is invalid.',
        ]);

        // ──────────────────────────────────────────────
        // 第二步：调用认证服务创建用户并生成 2FA 验证码
        // Step 2: Call auth service to create user and generate 2FA verification code.
        // AuthService::register() 返回包含三个关键数据的数组：
        // AuthService::register() returns an array with three key pieces of data:
        //   - 'user'  : 新创建的 User Eloquent 模型实例
        //   - 'user'  : Newly created User Eloquent model instance.
        //   - 'code'  : 6 位数字验证码（明文，仅用于本次请求发送邮件）
        //   - 'code'  : 6-digit verification code (plaintext; only used to send email in this request).
        //   - 'token' : 验证 token（哈希存储，用于后续 2FA 页面校验验证码）
        //   - 'token' : Verification token (hashed storage; used later on the 2FA page to validate the code).
        // ──────────────────────────────────────────────
        $result = $this->authService->register($validated);
        $user = $result['user'];
        $code = $result['code'];

        // 记录日志：用户注册完成，准备发送验证邮件（便于排查邮件发送问题）
        // Log: user registration complete; about to send verification email (for debugging email issues).
        \Illuminate\Support\Facades\Log::info('AuthController: 用户注册完成，准备发送邮件', [
            'email' => $user->email,
            'code'  => $code,
        ]);

        // ──────────────────────────────────────────────
        // 第三步：构建 HTML 验证邮件内容
        // Step 3: Build the HTML verification email content.
        // EmailService::buildVerificationEmail() 将用户名和验证码
        // EmailService::buildVerificationEmail() renders the username and code
        // 渲染到邮件 Blade 模板中，返回完整的 HTML 字符串
        // into the email Blade template, returning a complete HTML string.
        // ──────────────────────────────────────────────
        $htmlBody = $this->emailService->buildVerificationEmail($user->firstname, $code);
        \Illuminate\Support\Facades\Log::info('AuthController: HTML 构建完成', ['len' => strlen($htmlBody)]);

        // ──────────────────────────────────────────────
        // 第四步：发送验证邮件
        // Step 4: Send the verification email.
        // EmailService::sendHtmlMail() 通过 SMTP 发送 HTML 邮件
        // EmailService::sendHtmlMail() sends HTML email via SMTP.
        // 返回值包含 'status' 字段：'success' 或 'error'
        // Return value includes a 'status' field: 'success' or 'error'.
        // ──────────────────────────────────────────────
        $mailResult = $this->emailService->sendHtmlMail(
            $user->email,
            'FoodShare — Your Verification Code',
            $htmlBody
        );

        \Illuminate\Support\Facades\Log::info('AuthController: 邮件发送结果', ['result' => $mailResult]);

        // ──────────────────────────────────────────────
        // 第五步：处理邮件发送结果，跳转到 2FA 验证页面
        // Step 5: Handle email sending result and redirect to 2FA verification page.
        //
        // 两个跳转路径（都指向同一个页面）：
        // Two redirect paths (both lead to the same page):
        //   - 邮件成功 → 携带 success 消息，提示用户查看邮箱
        //   - Email success → flash a success message prompting user to check inbox.
        //   - 邮件失败 → 携带 warning 消息，提示用户后续可重新请求
        //   - Email failure → flash a warning message telling user they can re-request later.
        //
        // 通过 session flash 传递以下数据：
        // Data passed via session flash:
        //   - 'email'        : 用户邮箱（用于 2FA 页面显示或自动填充）
        //   - 'email'        : User email (for display or auto-fill on 2FA page).
        //   - 'verify_token' : 验证 token（2FA 页面需携带此 token 提交验证码）
        //   - 'verify_token' : Verification token (2FA page must include this token when submitting code).
        //   - 'success' / 'warning' : 闪存消息，在页面上方显示
        //   - 'success' / 'warning' : Flash messages displayed at the top of the page.
        // ──────────────────────────────────────────────
        if ($mailResult['status'] === 'error') {
            // 邮件发送失败 — 用户已创建，允许后续重新发送验证码
            // Email sending failed — user already created; can re-request code later.
            return redirect()->route('verify2fa.form')
                ->with('email', $user->email)
                ->with('verify_token', $result['token'])
                ->with('warning', 'Email could not be sent. You can request a new code on the next page.');
        }

        // 邮件发送成功 — 正常跳转到 2FA 验证页面
        // Email sent successfully — redirect normally to 2FA verification page.
        return redirect()->route('verify2fa.form')
            ->with('email', $user->email)
            ->with('verify_token', $result['token'])
            ->with('success', 'A verification code has been sent to your email.');
    }

    /**
     * 处理用户退出登录请求
     *
     * 用途：
     *   清除当前用户的认证会话状态，将用户安全退出系统。
     *
     * 调用时机：
     *   - 用户点击"退出登录"按钮时（POST /logout）
     *
     * 业务流程：
     *   1. 调用 AuthService::logout() 清除 session 中的用户认证数据
     *   2. 重定向到登录页面
     *   3. 携带成功消息（通过 session flash）
     *
     * 关键说明：
     *   - AuthService::logout() 内部调用 Laravel 的 Session::forget() 清除认证数据
     *   - 不使用 Laravel 内置的 Auth::logout()，因为本项目使用自定义 session 认证
     *   - 退出后用户被重定向到登录页，auth 中间件不会阻止未登录用户访问该页面
     *
     * Handle user logout request.
     *
     * Purpose:
     *   Clear the current user's authentication session state and safely log them out.
     *
     * When called:
     *   - User clicks the "Logout" button (POST /logout)
     *
     * Business flow:
     *   1. Call AuthService::logout() to clear user auth data from session
     *   2. Redirect to the login page
     *   3. Flash a success message via session
     *
     * Key notes:
     *   - AuthService::logout() internally calls Laravel's Session::forget() to clear auth data
     *   - Does not use Laravel's built-in Auth::logout() because this project uses custom session auth
     *   - After logout, user is redirected to login page; auth middleware won't block unauthenticated access
     *
     * @return \Illuminate\Http\RedirectResponse 重定向到登录页面
     * @return \Illuminate\Http\RedirectResponse Redirect to the login page.
     */
    public function logout()
    {
        // 委托 AuthService 清除 session 中的用户认证信息
        // Delegate to AuthService to clear user authentication info from session.
        $this->authService->logout();
        // 跳转到登录页面，并显示退出成功提示
        // Redirect to login page with a logout success message.
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    public function apiLogin(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Please verify your email first.'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'name' => $user->firstname . ' ' . $user->lastname,
                'role' => $user->role,
            ],
        ]);
    }
}
