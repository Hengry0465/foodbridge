<?php

/**
 * ============================================================================
 * 个人资料控制器 — ProfileController
 * ============================================================================
 *
 * Profile Controller — ProfileController
 *
 * 所属模块：用户资料管理 (Profile Management Module)
 * Module: User Profile Management
 *
 * 项目名称：FoodShare — 食物捐赠平台
 * Project: FoodShare — Food Donation Platform
 *
 * 文件作用：
 *   处理登录用户在"个人中心"页面提交的所有 HTTP 请求，包括编辑基本资料
 *   （firstname / lastname / phone）与修改登录密码。所有写操作都要求
 *   auth 中间件保护，确保只有本人能改本人。
 *
 * Purpose:
 *   Handles all HTTP requests submitted by an authenticated user on the
 *   "Profile" section — editing basic information (firstname / lastname /
 *   phone) and changing the login password. All write operations are
 *   protected by the auth middleware to guarantee only the owner can edit
 *   their own account.
 *
 * 业务流程：
 *   用户登录 → 顶栏 Profile 链接 → /profile（编辑 firstname/lastname/phone）
 *          或 /profile/password（修改密码，需要 current password）
 *
 * Business flow:
 *   User logs in → clicks "Profile" in the navbar → /profile (edits name/phone)
 *          or /profile/password (changes password — requires current password)
 *
 * 依赖关系：
 *   - AuthService (app/Services/AuthService.php)：核心认证/资料业务逻辑
 *     -- updateProfile()：写入 firstname / lastname / phone
 *     -- updatePasswordForAuthenticatedUser()：校验当前密码 + 更新密码 + 刷新 CSRF token
 *   - Illuminate\Support\Facades\Auth：取当前登录用户（Auth::user()）
 *   - Session：用于 success / error 闪存消息
 *
 * Dependencies:
 *   - AuthService: core auth/profile business logic.
 *     -- updateProfile(): persists firstname / lastname / phone.
 *     -- updatePasswordForAuthenticatedUser(): verifies current password,
 *        updates password, refreshes the CSRF token.
 *   - Illuminate\Support\Facades\Auth: retrieves the current authenticated user.
 *   - Session: stores success / error flash messages.
 *
 * 路由映射（参考 routes/web.php）：
 *   GET  /profile            → ProfileController@edit              — 显示资料编辑页
 *   POST /profile            → ProfileController@update           — 处理资料更新
 *   GET  /profile/password   → ProfileController@showPasswordForm — 显示修改密码页
 *   POST /profile/password   → ProfileController@updatePassword   — 处理密码修改
 *
 * Route mapping (see routes/web.php):
 *   GET  /profile            → ProfileController@edit              — Show profile edit form
 *   POST /profile            → ProfileController@update           — Process profile update
 *   GET  /profile/password   → ProfileController@showPasswordForm — Show change-password form
 *   POST /profile/password   → ProfileController@updatePassword   — Process password change
 */

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 个人资料控制器
 *
 * Profile Controller.
 *
 * 负责接收和处理登录用户对个人资料的编辑与密码修改请求，
 * 通过构造函数注入 AuthService，将业务逻辑委托给服务层处理，
 * 控制器仅做请求验证、当前用户取用与路由跳转。
 *
 * Receives and processes authenticated-user requests for profile editing
 * and password changes. Injects AuthService via the constructor,
 * delegating business logic to the service layer while the controller
 * only handles request validation, fetching the current user, and
 * routing redirects.
 */
class ProfileController extends Controller
{
    /**
     * 构造函数 — 依赖注入认证服务
     *
     * Constructor — dependency injection of the auth service.
     *
     * Laravel 的服务容器会自动解析并注入 AuthService 实例。
     * 使用 PHP 8.1 的 readonly 属性确保服务实例在构造后不可修改。
     *
     * Laravel's service container auto-resolves and injects the AuthService
     * instance. Uses PHP 8.1 readonly properties to ensure the service
     * instance is immutable after construction.
     *
     * @param AuthService $authService 认证/资料业务逻辑服务（编辑资料、改密）
     * @param AuthService $authService Auth/profile business logic service.
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * 显示个人资料编辑页面
     *
     * Show the profile edit page.
     *
     * 用途：
     *   渲染个人资料编辑视图，预填当前登录用户的 firstname、lastname、phone。
     *   email 与 role 以"只读"形式展示，明确告知用户这两个字段不可自助修改。
     *
     * Purpose:
     *   Renders the profile edit view, pre-filling the current authenticated
     *   user's firstname, lastname, and phone. email and role are shown as
     *   read-only so the user knows these fields cannot be self-edited.
     *
     * 调用时机：
     *   - 用户访问 /profile 路由（GET 请求）
     *   - 修改资料成功后的重定向目标
     *
     * When called:
     *   - User visits the /profile route (GET request).
     *   - Redirect target after a successful profile update.
     *
     * 返回值：
     *   View — profile.edit 视图（对应 resources/views/profile/edit.blade.php）
     *
     * Returns:
     *   View — the profile.edit view (resources/views/profile/edit.blade.php).
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function edit()
    {
        // 直接通过 Auth 门面取当前登录用户；避免任何 ID 形式的传参以杜绝 IDOR
        // Retrieve the current user directly via the Auth facade — never accept
        // an ID parameter to eliminate any IDOR attack surface.
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    /**
     * 处理个人资料更新请求
     *
     * Handle the profile update request.
     *
     * 用途：
     *   接收用户提交的资料编辑表单（firstname、lastname、phone），
     *   验证后委托给 AuthService 写入数据库，成功后重定向回 /profile
     *   并 flash 成功消息。
     *
     * Purpose:
     *   Receives the profile edit form submission (firstname, lastname, phone),
     *   validates it, delegates persistence to AuthService, and on success
     *   redirects back to /profile with a success flash message.
     *
     * 调用时机：
     *   - 用户在 /profile 页面提交资料编辑表单时（POST /profile）
     *
     * When called:
     *   - User submits the profile edit form on /profile (POST /profile).
     *
     * 业务流程：
     *   1. 内联 validate（firstname/lastname 必填且 ≤100，phone 可空且 ≤100）
     *   2. 取当前登录用户（永远是本人操作，杜绝 IDOR）
     *   3. 调用 AuthService::updateProfile() 写入数据库
     *   4. 成功 → redirect 回 /profile，flash success
     *      失败 → 回上一页，flash 通用错误消息并保留输入
     *
     * Business flow:
     *   1. Inline validate (firstname/lastname required & ≤100, phone nullable & ≤100).
     *   2. Retrieve the current authenticated user (always self-action; no IDOR).
     *   3. Call AuthService::updateProfile() to persist.
     *   4. Success → redirect back to /profile with a success flash.
     *      Failure → back with a generic error and keep user input.
     *
     * @param Request $request HTTP 请求实例，包含 firstname / lastname / phone
     * @return \Illuminate\Http\RedirectResponse 重定向到 /profile（成功或失败）
     */
    public function update(Request $request)
    {
        // ──────────────────────────────────────────────
        // 第一步：表单字段验证
        // Step 1: Validate form fields.
        // ──────────────────────────────────────────────
        $validated = $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname'  => 'required|string|max:100',
            'phone'     => 'nullable|string|max:100',
        ], [
            'firstname.required' => 'Please enter your first name.',
            'firstname.max'      => 'First name is too long (max 100 characters).',
            'lastname.required'  => 'Please enter your last name.',
            'lastname.max'       => 'Last name is too long (max 100 characters).',
            'phone.max'          => 'Phone number is too long (max 100 characters).',
        ]);

        // ──────────────────────────────────────────────
        // 第二步：取当前登录用户（永远只操作本人）
        // Step 2: Get the current authenticated user (self-action only).
        // ──────────────────────────────────────────────
        $user = Auth::user();

        // ──────────────────────────────────────────────
        // 第三步：委托 AuthService 写入数据库
        // Step 3: Delegate to AuthService to persist.
        // ──────────────────────────────────────────────
        $updated = $this->authService->updateProfile($user, $validated);

        if (!$updated) {
            // 仓储层 update() 返回 false —— 数据库写入失败
            // Repository update() returned false — DB write failed.
            return back()
                ->withErrors(['profile' => 'Could not update profile. Please try again.'])
                ->withInput();
        }

        // 成功：重定向回 /profile 并 flash 成功消息
        // Success: redirect back to /profile and flash a success message.
        return redirect()->route('profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }

    /**
     * 显示修改密码页面
     *
     * Show the change-password page.
     *
     * 用途：
     *   渲染修改密码视图。该视图要求用户输入"当前密码"、"新密码"、"确认新密码"
     *   三个字段；服务端将依次校验当前密码是否正确、新密码是否符合强度规则、
     *   两次输入是否一致。
     *
     * Purpose:
     *   Renders the change-password view. The view requires the user to enter
     *   three fields — current password, new password, retype new password —
     *   which the server validates in order: current correctness, strength,
     *   and consistency between the two new-password inputs.
     *
     * 调用时机：
     *   - 用户访问 /profile/password 路由（GET 请求）
     *
     * When called:
     *   - User visits the /profile/password route (GET request).
     *
     * 返回值：
     *   View — profile.password 视图（对应 resources/views/profile/password.blade.php）
     *
     * Returns:
     *   View — the profile.password view.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function showPasswordForm()
    {
        return view('profile.password');
    }

    /**
     * 处理密码修改请求
     *
     * Handle the password-change request.
     *
     * 用途：
     *   接收用户在修改密码页提交的 current password / new password / retype
     *   new password，先做服务端校验（强度、两次一致、新密码不能等于当前密码），
     *   再委托给 AuthService 校验当前密码并写入新密码。
     *
     * Purpose:
     *   Receives the change-password form (current password, new password,
     *   retype new password), validates server-side (strength, consistency,
     *   new ≠ current), then delegates to AuthService to verify the current
     *   password and persist the new one.
     *
     * 调用时机：
     *   - 用户在 /profile/password 页面提交修改密码表单时（POST /profile/password）
     *
     * When called:
     *   - User submits the change-password form on /profile/password
     *     (POST /profile/password).
     *
     * 业务流程：
     *   1. 内联 validate（current_password 必填；new_password 必填、min:8、
     *      必须包含大小写字母和数字、不能等于 current_password；
     *      confirm_password 必填且必须等于 new_password）
     *   2. 取当前登录用户（永远只操作本人）
     *   3. 调用 AuthService::updatePasswordForAuthenticatedUser()
     *   4. 成功 → redirect 回 /profile/password，flash success
     *      失败 → 回上一页，flash 当前密码错误消息并保留输入
     *
     * Business flow:
     *   1. Inline validate (current_password required; new_password required,
     *      min:8, regex uppercase+lowercase+digit, different:current_password;
     *      confirm_password required & same:new_password).
     *   2. Retrieve the current authenticated user (self-action only).
     *   3. Call AuthService::updatePasswordForAuthenticatedUser().
     *   4. Success → redirect back to /profile/password with a success flash.
     *      Failure → back with the current-password error and keep user input.
     *
     * @param Request $request HTTP 请求实例，包含 current_password / new_password / confirm_password
     * @return \Illuminate\Http\RedirectResponse 重定向到 /profile/password（成功或失败）
     */
    public function updatePassword(Request $request)
    {
        // ──────────────────────────────────────────────
        // 第一步：表单字段验证
        // Step 1: Validate form fields.
        // 密码强度规则与注册保持一致（min:8 + 必须含大小写字母和数字）。
        // Password strength rules match the registration flow (min:8 + must
        // include uppercase, lowercase, and digits).
        // ──────────────────────────────────────────────
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => [
                'required', 'string', 'min:8',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/',
                // 新密码不能等于当前密码 —— 防止"改了个寂寞"
                // New password must differ from current — prevents a no-op change.
                'different:current_password',
            ],
            'confirm_password' => 'required|string|same:new_password',
        ], [
            'current_password.required'      => 'Please enter your current password.',
            'new_password.required'          => 'Please enter a new password.',
            'new_password.min'               => 'Password must be at least 8 characters.',
            'new_password.regex'             => 'Password must contain uppercase, lowercase, and numbers.',
            'new_password.different'         => 'New password must be different from your current password.',
            'confirm_password.required'      => 'Please confirm your new password.',
            'confirm_password.same'          => 'The passwords do not match.',
        ]);

        // ──────────────────────────────────────────────
        // 第二步：取当前登录用户（永远只操作本人）
        // Step 2: Get the current authenticated user (self-action only).
        // ──────────────────────────────────────────────
        $user = Auth::user();

        // ──────────────────────────────────────────────
        // 第三步：委托 AuthService 校验当前密码 + 更新密码 + 刷新 CSRF token
        // Step 3: Delegate to AuthService to verify current password, update
        //         the password, and refresh the CSRF token.
        // ──────────────────────────────────────────────
        $result = $this->authService->updatePasswordForAuthenticatedUser(
            $user,
            $validated['current_password'],
            $validated['new_password']
        );

        if (!$result['success']) {
            // 失败（通常原因：当前密码错误）—— 把错误挂在 current_password 字段下
            // Failure (typically wrong current password) — attach the error to
            // the current_password field for clear inline display.
            return back()
                ->withErrors(['current_password' => $result['message']])
                ->withInput();
        }

        // 成功：重定向回 /profile/password 并 flash 成功消息
        // Success: redirect back to /profile/password and flash a success message.
        return redirect()->route('profile.password.form')
            ->with('success', $result['message']);
    }
}