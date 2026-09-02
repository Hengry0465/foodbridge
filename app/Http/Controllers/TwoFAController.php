<?php

/**
 * ============================================================
 * TwoFAController — 双因素认证（2FA）控制器
 * ============================================================
 *
 * 所属模块：用户认证模块（Auth）
 *
 * 业务流程位置：
 *   用户注册流程的第二步 —— 用户在注册页面提交邮箱等基本信息后，
 *   系统会发送一封含6位验证码的邮件，然后跳转到本控制器管理的
 *   验证码输入页面。用户输入正确验证码后，注册才算正式完成。
 *
 *   流程概览：
 *     RegisterController（提交注册信息）
 *       → AuthService::send2FA() 发送验证码邮件
 *       → 重定向到 verify2fa.form 路由
 *       → TwoFAController::showVerifyForm() 显示验证码输入页
 *       → TwoFAController::verify() 处理验证
 *       → 成功后跳转到注册成功页
 *
 * 依赖关系：
 *   - AuthService：核心认证业务逻辑（生成/校验验证码、发送2FA邮件）
 *   - EmailService：邮件构建与发送（构建HTML邮件正文、发送邮件）
 *   - Session：存储邮箱地址和验证令牌（verify_token），用于跨请求
 *     保持2FA流程状态
 *
 * 涉及路由：
 *   - GET  verify2fa.form     → showVerifyForm()
 *   - POST verify2fa.verify   → verify()
 *   - POST verify2fa.resend   → resend()
 *
 * ============================================================
 * TwoFAController — Two-Factor Authentication (2FA) Controller
 * ============================================================
 *
 * Module: User Authentication Module (Auth)
 *
 * Position in business flow:
 *   This is the second step of the user registration flow — after the user
 *   submits their email and basic info on the registration page, the system
 *   sends an email containing a 6-digit verification code, then redirects to
 *   the code entry page managed by this controller. Registration is only
 *   completed once the user enters the correct verification code.
 *
 *   Flow overview:
 *     RegisterController (submit registration info)
 *       → AuthService::send2FA() sends verification email
 *       → Redirect to verify2fa.form route
 *       → TwoFAController::showVerifyForm() displays code entry page
 *       → TwoFAController::verify() processes verification
 *       → Redirect to registration success page on success
 *
 * Dependencies:
 *   - AuthService: Core auth business logic (generate/verify code, send 2FA email)
 *   - EmailService: Email construction and delivery (build HTML email body, send)
 *   - Session: Stores email address and verification token (verify_token) to
 *     maintain 2FA flow state across requests
 *
 * Related routes:
 *   - GET  verify2fa.form     → showVerifyForm()
 *   - POST verify2fa.verify   → verify()
 *   - POST verify2fa.resend   → resend()
 */

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\EmailService;
use Illuminate\Http\Request;

class TwoFAController extends Controller
{
    /**
     * 构造函数 — 通过依赖注入引入业务服务层
     *
     * @param AuthService  $authService  认证服务（生成/校验验证码、发送2FA）
     * @param EmailService $emailService 邮件服务（构建HTML邮件内容、发送邮件）
     *
     * Constructor — injects business service layer via dependency injection.
     *
     * @param AuthService  $authService  Auth service (generate/verify code, send 2FA)
     * @param EmailService $emailService Email service (build HTML email content, send)
     */
    public function __construct(
        private readonly AuthService $authService,
        private readonly EmailService $emailService
    ) {}

    /**
     * 显示 2FA 验证码输入页面
     *
     * 用途：展示用户输入6位验证码的页面视图。
     *
     * 调用时机：
     *   用户提交注册表单后，RegisterController 将邮箱地址和验证令牌
     *   存入 session 并重定向到 verify2fa.form 路由，由本方法处理。
     *
     * 前置条件：
     *   - session 中必须存在 'email'（用户邮箱）
     *   - session 中必须存在 'verify_token'（验证令牌）
     *
     * 返回值：
     *   - 前置条件满足：返回 auth.verify-2fa 视图，携带用户邮箱
     *   - 前置条件不满足：重定向到登录页，附带错误提示
     *
     * 关键步骤：
     *   1. 从 session 读取 email 和 verify_token
     *   2. 若任一缺失，说明用户未走正常注册流程，拒绝访问并跳转登录页
     *   3. 使用 session()->keep() 保持闪存数据，确保后续 POST 请求
     *      仍能读取到 verify_token 用于验证
     *   4. 渲染验证码输入视图 auth.verify-2fa
     *
     * Display the 2FA verification code entry page.
     *
     * Purpose: Render the view where the user enters their 6-digit code.
     *
     * When called:
     *   After the user submits the registration form, RegisterController stores
     *   the email address and verification token in the session and redirects to
     *   the verify2fa.form route, which is handled by this method.
     *
     * Preconditions:
     *   - 'email' (user email) must exist in the session
     *   - 'verify_token' (verification token) must exist in the session
     *
     * Returns:
     *   - Preconditions met: returns the auth.verify-2fa view with the user email
     *   - Preconditions not met: redirects to login page with an error message
     *
     * Key steps:
     *   1. Read email and verify_token from session
     *   2. If either is missing, the user did not follow the normal registration
     *      flow — deny access and redirect to login
     *   3. Use session()->keep() to preserve flash data so subsequent POST
     *      requests can still read verify_token for validation
     *   4. Render the verification code entry view auth.verify-2fa
     */
    public function showVerifyForm()
    {
        // 从 session 中读取注册流程中保存的邮箱和验证令牌
        // Read the email and verification token saved during the registration flow from the session
        $email = session('email');
        $token = session('verify_token');

        // 安全检查：如果 session 中缺少必要数据，说明用户未正常进入2FA流程
        // 可能是直接访问URL或session已过期，拒绝访问并引导至登录页
        // Safety check: if required data is missing from the session, the user did not
        // enter the 2FA flow properly — possibly a direct URL visit or expired session.
        // Deny access and redirect to the login page.
        if (!$email || !$token) {
            return redirect()->route('login')
                ->withErrors(['login' => 'Please register first.']);
        }

        // 【重要】保持闪存数据不销毁
        // Laravel 默认在读取 session 闪存数据后会自动清除，
        // session()->keep() 可阻止此行为，确保后续 POST 表单提交时
        // 仍能从 session 中读取 verify_token 进行校验
        // [IMPORTANT] Keep flash data alive.
        // Laravel auto-clears session flash data after reading it by default.
        // session()->keep() prevents this so that subsequent POST form submissions
        // can still read verify_token from the session for validation.
        session()->keep(['verify_token', 'email']);

        // 渲染验证码输入页面，将邮箱传给视图（用于显示和隐藏字段提交）
        // Render the verification code entry page, passing the email to the view
        // (used for display and for hidden field submission)
        return view('auth.verify-2fa', compact('email'));
    }

    /**
     * 处理验证码提交 — 校验用户输入的6位验证码
     *
     * 用途：接收用户提交的邮箱和验证码，调用 AuthService 进行校验，
     *       验证通过则完成注册，失败则返回错误提示。
     *
     * 调用时机：
     *   用户在 auth.verify-2fa 页面填写验证码后点击提交按钮，
     *   表单通过 POST 方式提交到 verify2fa.verify 路由。
     *
     * @param Request $request 包含以下表单字段：
     *   - email (string, required|email)：用户邮箱
     *   - code  (string, required|size:6)：用户输入的6位验证码
     *
     * 返回值：
     *   - 验证成功：重定向到注册成功页（registered 路由），
     *     携带用户 firstname 用于个性化欢迎信息
     *   - 验证失败：重定向回验证码输入页，保留邮箱预填，
     *     并附带具体错误提示
     *
     * 关键步骤：
     *   1. 表单验证：校验邮箱格式和验证码长度（6位）
     *   2. 调用 AuthService::verify2FA() 执行核心验证逻辑
     *      （比对验证码、校验令牌、检查有效期等）
     *   3. 根据返回结果决定跳转到成功页或返回错误
     *
     * Handle verification code submission — validate the user's 6-digit code.
     *
     * Purpose: Receive the email and code submitted by the user, call AuthService
     *   to validate, complete registration on success, or return an error message.
     *
     * When called:
     *   After the user fills in the code on the auth.verify-2fa page and clicks
     *   submit, the form POSTs to the verify2fa.verify route.
     *
     * @param Request $request Contains the following form fields:
     *   - email (string, required|email): User email
     *   - code  (string, required|size:6): 6-digit verification code
     *
     * Returns:
     *   - Success: redirects to registration success page (registered route)
     *     with the user's firstname for a personalized welcome message
     *   - Failure: redirects back to code entry page, preserving the pre-filled
     *     email with a specific error message
     *
     * Key steps:
     *   1. Form validation: validate email format and code length (6 digits)
     *   2. Call AuthService::verify2FA() for core verification logic
     *      (compare code, validate token, check expiration, etc.)
     *   3. Redirect to success page or return error based on the result
     */
    public function verify(Request $request)
    {
        // 第一步：表单字段验证
        // - email：必须为有效邮箱格式
        // - code：必须为恰好6位字符串（数字验证码）
        // Step 1: Form field validation
        // - email: must be a valid email format
        // - code: must be exactly 6 characters (numeric verification code)
        $validated = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ], [
            'email.required' => 'Email is required.',
            'code.required'  => 'Please enter the verification code.',
            'code.size'      => 'Verification code must be 6 digits.',
        ]);

        // 第二步：调用认证服务进行验证码校验
        // 参数：邮箱、用户输入的验证码、session中的验证令牌
        // session('verify_token', '') 第二个参数为空字符串默认值，
        // 防止 session 中不存在该键时传入 null 导致服务层类型错误
        // Step 2: Call the auth service to validate the verification code.
        // Parameters: email, user-entered code, verification token from session.
        // session('verify_token', '') — the second argument defaults to empty string,
        // preventing null from being passed if the key is missing from session,
        // which would cause a type error in the service layer.
        //
        // 【重要】先捕获 verify_token，因为 session()->keep() 只能保持一次
        // 如果验证失败重定向回去时没有重新传递 token，showVerifyForm() 会
        // 因缺少 token 而重定向到登录页显示 "Please register first"
        // [IMPORTANT] Capture verify_token first because session()->keep() only
        // preserves flash data through one extra request. If we don't re-flash the
        // token on error redirect, showVerifyForm() will redirect to login with
        // "Please register first" due to the missing token.
        $verifyToken = session('verify_token', '');

        $result = $this->authService->verify2FA(
            $validated['email'],
            $validated['code'],
            $verifyToken
        );

        // 第三步：验证失败处理 — 返回验证码输入页并提示错误
        // 注意：必须同时重新传递 verify_token，否则闪存数据已消耗，
        // showVerifyForm() 会因缺少 token 而重定向回登录页
        // Step 3: Handle verification failure — return to code entry page with error.
        // NOTE: Must also re-pass verify_token, otherwise the flash data has been
        // consumed and showVerifyForm() will redirect to login due to missing token.
        if (!$result['success']) {
            return redirect()->route('verify2fa.form')
                ->with('email', $validated['email'])
                ->with('verify_token', $verifyToken)
                ->withErrors(['2fa' => $result['message']]);
        }

        // 第四步：验证成功 — 注册完成，跳转到成功页面
        // 携带用户名字（firstname）用于页面展示个性化问候语
        // Step 4: Verification success — registration complete, redirect to success page.
        // Carry the user's firstname for displaying a personalized greeting.
        return redirect()->route('registered')
            ->with('firstname', $result['user']->firstname);
    }

    /**
     * 重新发送验证码
     *
     * 用途：当用户未收到验证码邮件或验证码已过期时，允许用户请求
     *       重新发送一封包含新验证码的邮件。
     *
     * 调用时机：
     *   用户在 auth.verify-2fa 页面点击"重新发送"按钮，
     *   通过 POST 方式提交到 verify2fa.resend 路由。
     *
     * @param Request $request 请求对象（本方法主要依赖 session 数据）
     *
     * 返回值：
     *   - 发送成功：重定向回验证码输入页，附带新 verify_token、
     *     成功提示消息，以及保持邮箱预填
     *   - session 中无邮箱：重定向到登录页（用户可能已离开注册流程）
     *   - 服务层返回失败：重定向回验证码输入页，附带错误提示
     *
     * 关键步骤：
     *   1. 从 session 获取用户邮箱，若不存在则拒绝请求
     *   2. 调用 AuthService::resend2FA() 生成新验证码和令牌
     *   3. 通过 EmailService 构建HTML邮件内容并发送
     *   4. 将新的 verify_token 写入 session 闪存数据
     *   5. 重定向回验证码输入页
     *
     * Resend the verification code.
     *
     * Purpose: When the user did not receive the code email or the code has
     *   expired, allow the user to request a new code email be sent.
     *
     * When called:
     *   The user clicks the "Resend" button on the auth.verify-2fa page,
     *   which POSTs to the verify2fa.resend route.
     *
     * @param Request $request Request object (this method primarily relies on session data)
     *
     * Returns:
     *   - Send success: redirects back to code entry page with new verify_token,
     *     success message, and pre-filled email
     *   - No email in session: redirects to login (user may have left the
     *     registration flow)
     *   - Service layer failure: redirects back to code entry page with error
     *
     * Key steps:
     *   1. Retrieve user email from session; reject if missing
     *   2. Call AuthService::resend2FA() to generate a new code and token
     *   3. Build HTML email content via EmailService and send it
     *   4. Write the new verify_token to session flash data
     *   5. Redirect back to the code entry page
     */
    public function resend(Request $request)
    {
        // 从 session 中获取当前2FA流程绑定的邮箱
        // Get the email associated with the current 2FA flow from the session
        $email = session('email');

        // 安全检查：如果 session 中没有邮箱，说明用户未正常进入注册流程，
        // 可能是直接访问该路由或 session 已过期，直接跳转到登录页
        // Safety check: if no email in session, the user did not properly enter the
        // registration flow — possibly a direct route access or expired session.
        // Redirect to login.
        if (!$email) {
            return redirect()->route('login');
        }

        // 调用认证服务的重发逻辑：
        // - 生成新的6位随机验证码
        // - 更新数据库中的 verify_token 和验证码过期时间
        // - 返回包含新验证码、新令牌和用户信息的数组
        // Call the auth service resend logic:
        // - Generate a new random 6-digit verification code
        // - Update verify_token and code expiration time in the database
        // - Return an array containing the new code, new token, and user info
        $result = $this->authService->resend2FA($email);

        // 重发失败处理（如超出频率限制、用户状态异常等）
        // Handle resend failure (e.g. rate limit exceeded, abnormal user status, etc.)
        if (!$result['success']) {
            return redirect()->route('verify2fa.form')
                ->with('email', $email)
                ->withErrors(['2fa' => $result['message']]);
        }

        // 构建带用户名字的HTML邮件内容（个性化问候）
        // Build HTML email content with the user's name (personalized greeting)
        $user = $result['user'];
        $htmlBody = $this->emailService->buildVerificationEmail($user->firstname, $result['code']);

        // 发送HTML格式的验证码邮件
        // Send the HTML-formatted verification code email
        $this->emailService->sendHtmlMail(
            $user->email,
            'FoodShare — Your New Verification Code',
            $htmlBody
        );

        // 重定向回验证码输入页，携带：
        // - email：保持邮箱预填，方便用户体验
        // - verify_token：新的验证令牌，后续提交验证时需要校验
        // - success：页面顶部展示的成功提示消息
        // Redirect back to the verification code entry page, carrying:
        // - email: keep the email pre-filled for a smooth user experience
        // - verify_token: the new verification token for subsequent validation
        // - success: a success message displayed at the top of the page
        return redirect()->route('verify2fa.form')
            ->with('email', $email)
            ->with('verify_token', $result['token'])
            ->with('success', 'A new verification code has been sent.');
    }
}
