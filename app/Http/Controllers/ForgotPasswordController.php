<?php

/**
 * 忘记密码控制器
 *
 * 所属模块：用户认证模块
 * 业务流程位置：用户登录/注册流程中的"忘记密码"分支
 * 业务流程概述：
 *   1. 用户访问"忘记密码"页面，输入注册邮箱
 *   2. 系统生成6位验证码及重置token，通过邮件发送给用户
 *   3. 用户填写验证码和新密码，提交重置请求
 *   4. 系统校验验证码和token，更新密码，完成重置
 *
 * 依赖关系：
 *   - AuthService：处理重置码生成、token管理、密码更新等核心业务逻辑
 *   - EmailService：构建验证码邮件HTML模板并通过SMTP发送邮件
 *   - 视图文件：auth.forgot-password（输入邮箱页面）、auth.reset-password（重置密码页面）
 *
 * 会话数据流转：
 *   - sendCode 阶段：将 email、reset_token、success消息存入闪存session
 *   - showResetForm 阶段：从session读取email和reset_token，验证有效性后保持闪存数据
 *   - reset 阶段：从session读取reset_token进行安全校验
 */

/**
 * Forgot Password Controller
 *
 * Module: User Authentication Module
 * Business Process: "Forgot Password" branch in user login/registration flow
 * Business Process Overview:
 *   1. User visits the "Forgot Password" page and enters their registered email
 *   2. System generates a 6-digit verification code and reset token, sends them via email
 *   3. User enters the verification code and new password, submits the reset request
 *   4. System validates the code and token, updates the password, completes the reset
 *
 * Dependencies:
 *   - AuthService: Handles core business logic for reset code generation, token management, password updates
 *   - EmailService: Builds verification code email HTML template and sends via SMTP
 *   - Views: auth.forgot-password (email entry page), auth.reset-password (password reset page)
 *
 * Session Data Flow:
 *   - sendCode phase: Stores email, reset_token, success message in flash session
 *   - showResetForm phase: Reads email and reset_token from session, validates validity, preserves flash data
 *   - reset phase: Reads reset_token from session for security verification
 */

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\EmailService;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /**
     * 构造函数 — 通过依赖注入获取认证服务和邮件服务
     *
     * @param AuthService  $authService  认证服务：处理重置码生成、token校验、密码更新
     * @param EmailService $emailService 邮件服务：构建邮件模板并发送验证码邮件
     */

    /**
     * Constructor — obtains authentication and email services via dependency injection.
     *
     * @param AuthService  $authService  Authentication service: handles reset code generation, token verification, password updates
     * @param EmailService $emailService  Email service: builds email template and sends verification code emails
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly EmailService $emailService
    ) {}

    /**
     * 显示"忘记密码"输入邮箱页面
     *
     * 用途：渲染用户输入注册邮箱的第一个页面
     * 返回值：auth.forgot-password 视图
     * 调用时机：用户点击登录页的"忘记密码"链接时（路由 GET /password/forgot）
     * 无需任何前置条件，直接返回静态表单页面
     */

    /**
     * Display the "Forgot Password" email entry page.
     *
     * Purpose: Renders the first page where the user enters their registered email.
     * Returns: auth.forgot-password view.
     * Trigger: When the user clicks the "Forgot Password" link on the login page (route GET /password/forgot).
     * No prerequisites required — directly returns a static form page.
     */
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * 发送密码重置验证码
     *
     * 用途：接收用户提交的邮箱地址，生成6位验证码并发送至用户邮箱
     * 调用时机：用户在"忘记密码"页面输入邮箱后提交表单（路由 POST /password/send-code）
     *
     * 关键步骤：
     *   1. 验证邮箱格式（必填、合法email格式）
     *   2. 调用 AuthService 生成重置码和token
     *   3. 如果邮箱未注册，返回错误提示
     *   4. 构建邮件HTML内容，将"完成注册"文案替换为"重置密码"
     *   5. 通过 EmailService 发送HTML邮件
     *   6. 重定向到重置密码页面，携带email、reset_token、success消息到session
     *
     * @param  Request $request 包含 email 字段的HTTP请求
     * @return \Illuminate\Http\RedirectResponse 重定向回上一页（错误时）或重定向到重置密码页面（成功时）
     */

    /**
     * Send the password reset verification code.
     *
     * Purpose: Receives the user's submitted email, generates a 6-digit verification code, and sends it to the user's email.
     * Trigger: When the user enters their email on the "Forgot Password" page and submits the form (route POST /password/send-code).
     *
     * Key Steps:
     *   1. Validate email format (required, valid email format).
     *   2. Call AuthService to generate reset code and token.
     *   3. If the email is not registered, return an error message.
     *   4. Build email HTML content, replacing "complete your registration" text with "reset your password".
     *   5. Send HTML email via EmailService.
     *   6. Redirect to the reset password page, carrying email, reset_token, and success message in session.
     *
     * @param  Request $request  HTTP request containing the email field
     * @return \Illuminate\Http\RedirectResponse  Redirect back to previous page (on error) or to the reset password page (on success)
     */
    public function sendCode(Request $request)
    {
        // --- 第一步：验证邮箱输入 ---

        // --- Step 1: Validate email input ---
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
        ]);

        // --- 第二步：调用认证服务生成重置码 ---
        // AuthService::sendResetCode 负责：
        //   1. 查询邮箱是否已注册
        //   2. 生成6位随机数字验证码（存入缓存，有效期通常10-15分钟）
        //   3. 生成重置token用于后续安全校验
        // 返回值结构：['success' => bool, 'message' => string, 'user' => User|null, 'code' => string, 'token' => string]

        // --- Step 2: Call authentication service to generate reset code ---
        // AuthService::sendResetCode is responsible for:
        //   1. Checking whether the email is registered
        //   2. Generating a 6-digit random numeric verification code (stored in cache, typically valid for 10-15 minutes)
        //   3. Generating a reset token for subsequent security verification
        // Return value structure: ['success' => bool, 'message' => string, 'user' => User|null, 'code' => string, 'token' => string]
        $result = $this->authService->sendResetCode($request->email);

        // --- 第三步：邮箱未注册时回退到上一页并显示错误 ---

        // --- Step 3: If email is not registered, fall back to previous page and show error ---
        if (!$result['success']) {
            // withErrors 将错误绑定到email字段，withInput 保留用户已输入的邮箱

            // withErrors binds the error to the email field, withInput preserves the email the user entered
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        // --- 第四步：构建并发送验证码邮件 ---

        // --- Step 4: Build and send the verification code email ---
        $user = $result['user'];
        // 复用注册验证邮件的HTML模板，将"complete your registration"替换为"reset your password"

        // Reuse the registration verification email HTML template, replacing "complete your registration" with "reset your password"
        $htmlBody = $this->emailService->buildVerificationEmail($user->firstname, $result['code']);
        $this->emailService->sendHtmlMail(
            $user->email,
            'FoodShare — Password Reset Code',
            str_replace('complete your registration', 'reset your password', $htmlBody)
        );

        // --- 第五步：重定向到重置密码页面 ---

        // --- Step 5: Redirect to the reset password page ---
        // 通过闪存session传递：用户邮箱（回填表单）、重置token（安全校验）、成功提示

        // Pass via flash session: user email (to pre-fill the form), reset token (security verification), success message
        return redirect()->route('password.reset.form')
            ->with('email', $user->email)
            ->with('reset_token', $result['token'])
            ->with('success', 'A reset code has been sent to your email.');
    }

    /**
     * 显示重置密码页面（验证码输入 + 新密码设置）
     *
     * 用途：渲染包含验证码输入框和新密码输入框的完整重置表单
     * 调用时机：
     *   1. 发送验证码成功后，重定向至此页面（路由 GET /password/reset）
     *   2. 重置成功或失败后再次查看结果弹窗
     *
     * 关键安全逻辑：
     *   - 必须同时存在 email 和 reset_token 两个session值才允许访问
     *   - 缺失任意一个则重定向回"忘记密码"首页，防止直接通过URL访问
     *   - 重置成功/失败后允许重新查看页面（结果通过弹窗展示）
     *
     * session数据流：
     *   - 读取 email、reset_token（由 sendCode 存入的闪存数据）
     *   - 读取 password_reset_done（重置成功标记）
     *   - 读取 password_reset_failed（重置失败标记）
     *   - 调用 session()->keep() 保留 email 和 reset_token，防止闪存数据在本次请求后被清除
     *     （因为 reset 方法是POST请求，需要再次读取这些值）
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */

    /**
     * Display the reset password page (verification code entry + new password setup).
     *
     * Purpose: Renders the complete reset form with verification code input and new password input.
     * Trigger:
     *   1. After the verification code is sent successfully, redirected to this page (route GET /password/reset).
     *   2. After a successful or failed reset, return to view the result popup again.
     *
     * Key Security Logic:
     *   - Both email and reset_token session values must exist to allow access.
     *   - If either is missing, redirect back to the "Forgot Password" homepage to prevent direct URL access.
     *   - After a successful/failed reset, allow revisiting the page (results are shown via popup).
     *
     * Session Data Flow:
     *   - Reads email, reset_token (flash data stored by sendCode).
     *   - Reads password_reset_done (reset success flag).
     *   - Reads password_reset_failed (reset failure flag).
     *   - Calls session()->keep() to preserve email and reset_token, preventing flash data from being cleared
     *     after this request (since the reset method is a POST request that needs to read these values again).
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetForm()
    {
        $email = session('email');
        $token = session('reset_token');

        // 允许重设成功/失败后查看弹窗 — 此时不需要token校验

        // Allow viewing the popup after a successful or failed reset — token verification is not needed here
        // 场景：用户刚完成重置（无论成败），再次访问页面可以看到结果提示

        // Scenario: The user has just completed a reset (regardless of outcome); revisiting the page shows the result message
        if (session('password_reset_done') || session('password_reset_failed')) {
            return view('auth.reset-password', ['email' => $email ?? '']);
        }

        // 安全校验：缺少email或token，说明是非法访问（直接输入URL或session已过期）

        // Security check: Missing email or token indicates unauthorized access (direct URL entry or expired session)
        // 重定向回"忘记密码"首页，强制用户重新发起流程

        // Redirect back to the "Forgot Password" homepage, forcing the user to restart the process
        if (!$email || !$token) {
            return redirect()->route('password.forgot');
        }

        // 保持闪存数据：表单 POST 提交时仍需验证 reset_token

        // Preserve flash data: the reset_token must still be verified when the form is submitted via POST
        // 默认情况下闪存数据在一次请求后即被清除，这里需要keep以延续到下次POST请求

        // By default, flash data is cleared after one request; keep() is needed here to persist it into the next POST request
        session()->keep(['reset_token', 'email']);

        return view('auth.reset-password', compact('email'));
    }

    /**
     * 处理密码重置请求
     *
     * 用途：接收验证码和新密码，校验后完成密码更新
     * 调用时机：用户在重置密码页面填写验证码和新密码后提交表单（路由 POST /password/reset）
     *
     * 关键步骤：
     *   1. 表单验证：email格式、code为6位数字串、password规则、确认密码一致性
     *   2. 从session读取reset_token，与email、code、新密码一同传给AuthService
     *   3. AuthService 校验验证码有效性（是否过期、是否匹配）
     *   4. 成功：更新密码，重定向回本页并携带password_reset_done标记触发成功弹窗
     *   5. 失败：重定向回本页并携带password_reset_failed标记和错误信息触发失败弹窗
     *
     * 密码规则：至少8位，必须包含大写字母、小写字母、数字（通过regex验证）
     * Token安全机制：reset_token由sendCode阶段生成并存在session中，
     *   即使验证码被猜到，没有匹配的token也无法完成重置
     *
     * @param  Request $request 包含 email、code、password、password_confirmation 的HTTP请求
     * @return \Illuminate\Http\RedirectResponse 始终重定向回重置密码页面（携带成功或失败标记）
     */

    /**
     * Handle the password reset request.
     *
     * Purpose: Receives the verification code and new password, validates them, and completes the password update.
     * Trigger: When the user fills in the verification code and new password on the reset password page and submits the form (route POST /password/reset).
     *
     * Key Steps:
     *   1. Form validation: email format, code as a 6-digit numeric string, password rules, password confirmation match.
     *   2. Read reset_token from session and pass it along with email, code, and new password to AuthService.
     *   3. AuthService verifies the code validity (expired or matched).
     *   4. On success: update password, redirect back to this page with password_reset_done flag to trigger success popup.
     *   5. On failure: redirect back to this page with password_reset_failed flag and error message to trigger failure popup.
     *
     * Password Rules: At least 8 characters, must contain uppercase letters, lowercase letters, and numbers (validated via regex).
     * Token Security Mechanism: The reset_token is generated during the sendCode phase and stored in session;
     *   even if the verification code is guessed, the reset cannot complete without a matching token.
     *
     * @param  Request $request  HTTP request containing email, code, password, password_confirmation
     * @return \Illuminate\Http\RedirectResponse  Always redirects back to the reset password page (carrying success or failure flag)
     */
    public function reset(Request $request)
    {
        // --- 第一步：表单字段验证 ---

        // --- Step 1: Form field validation ---
        // code.size:6 — 验证码必须是6位

        // code.size:6 — The reset code must be exactly 6 digits
        // password.regex — 至少包含一个小写字母、一个大写字母、一个数字

        // password.regex — Must contain at least one lowercase letter, one uppercase letter, and one digit
        // password_confirmation.same:password — 两次输入必须一致

        // password_confirmation.same:password — Both password entries must match
        $validated = $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string|size:6',
            'password'              => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
            'password_confirmation' => 'required|string|same:password',
        ], [
            'email.required'                 => 'Email is required.',
            'code.required'                  => 'Please enter the reset code.',
            'code.size'                      => 'Reset code must be 6 digits.',
            'password.required'              => 'Please enter a new password.',
            'password.min'                   => 'Password must be at least 8 characters.',
            'password.regex'                 => 'Password must contain uppercase, lowercase, and numbers.',
            'password_confirmation.required' => 'Please confirm your new password.',
            'password_confirmation.same'     => 'The passwords do not match.',
        ]);

        // --- 第二步：调用认证服务执行密码重置 ---

        // --- Step 2: Call authentication service to execute password reset ---
        // AuthService::resetPassword 负责：
        //   1. 从缓存中查找对应邮箱的验证码
        //   2. 比对用户输入的code与缓存的code是否一致
        //   3. 校验reset_token与发送验证码时生成的token是否匹配（防伪造）
        //   4. 更新用户密码（hash后存入数据库）
        //   5. 清除已使用的验证码缓存

        // AuthService::resetPassword is responsible for:
        //   1. Looking up the verification code for the corresponding email from cache
        //   2. Comparing the user-entered code against the cached code
        //   3. Verifying that the reset_token matches the token generated when the code was sent (anti-spoofing)
        //   4. Updating the user's password (hashed before storing in database)
        //   5. Clearing the used verification code from cache
        // session('reset_token', '') — 从session读取token，不存在则为空字符串

        // session('reset_token', '') — Reads the token from session; empty string if it does not exist
        // 返回值结构：['success' => bool, 'message' => string]

        // Return value structure: ['success' => bool, 'message' => string]
        $result = $this->authService->resetPassword(
            $validated['email'],
            $validated['code'],
            $validated['password'],
            session('reset_token', '')
        );

        // --- 第三步：重置失败 — 携带失败标记和错误信息重定向 ---

        // --- Step 3: Reset failed — redirect with failure flag and error message ---
        if (!$result['success']) {
            return redirect()->route('password.reset.form')
                ->with('email', $validated['email'])           // 保留邮箱以便回填
                                                               // Preserve email for pre-filling the form
                ->with('password_reset_failed', $result['message']); // 错误信息通过弹窗展示
                                                                     // Error message displayed via popup
        }

        // --- 第四步：重置成功 — 携带成功标记重定向 ---

        // --- Step 4: Reset successful — redirect with success flag ---
        // 返回当前页面并触发成功弹窗（由 showResetForm 检测 password_reset_done 标记）

        // Return to the current page and trigger the success popup (detected by showResetForm via password_reset_done flag)
        return redirect()->route('password.reset.form')
            ->with('email', $validated['email'])      // 保留邮箱以便回填
                                                       // Preserve email for pre-filling the form
            ->with('password_reset_done', true);      // true标记触发前端成功弹窗
                                                       // true flag triggers the front-end success popup
    }
}
