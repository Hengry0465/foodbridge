<?php

/**
 * ============================================================
 * Web 路由定义文件 — 系统请求入口的总调度中心
 * ============================================================
 *
 * 【文件在 Laravel 系统中的角色】
 * 本文件是 Laravel 应用层最核心的"请求分发器"。当用户在浏览器地址栏
 * 输入一个 URL 或点击页面中的链接/按钮时，HTTP 请求到达服务器后，
 * Laravel 内核会首先加载本文件，将请求的 URL 和 HTTP 方法（GET/POST）
 * 与本文件中定义的路由规则逐一匹配。匹配成功后，框架自动调用对应的
 * Controller 方法处理业务逻辑，最后将响应（HTML 页面、重定向等）返回
 * 给浏览器。
 *
 * 调用链：浏览器请求 → public/index.php → Kernel → routes/web.php → Controller → View
 *
 * 【路由文件的分类与加载顺序】
 * Laravel 将路由按应用场景拆分为多个文件，由 RouteServiceProvider 统一加载：
 *   - routes/web.php   → 浏览器访问的页面路由（本文件），自动包裹 web 中间件组
 *   - routes/api.php   → RESTful API 路由，自动包裹 api 中间件组（无 Session）
 *   - routes/channels.php → WebSocket 频道授权路由
 *   - routes/console.php  → Artisan 命令行路由
 *
 * 【web 中间件组自动注入的安全防护】
 * routes/web.php 中的所有路由会由框架自动应用 web 中间件组，包括：
 *   - EncryptCookies：    加解密客户端 Cookie，防止明文篡改
 *   - AddQueuedCookiesToResponse：将队列中的 Cookie 附加到响应
 *   - StartSession：      开启服务端会话（Session），记录用户登录状态
 *   - ShareErrorsFromSession：将 Session 中的验证错误共享给所有视图
 *   - VerifyCsrfToken：   防止跨站请求伪造（CSRF）攻击——每次 POST/PUT/DELETE
 *                         请求都必须携带 _token 字段，否则请求被拒绝
 *   - SubstituteBindings：路由模型绑定——自动将 {id} 参数解析为 Eloquent 模型
 *
 * 【路由命名规则 — 解耦 URL 与代码】
 * 每个路由通过 ->name('xxx') 赋予一个唯一标识名。在 Blade 模板或 Controller
 * 中调用 route('xxx') 即可生成完整 URL。当产品经理要求 URL 从 /login 改为
 * /signin 时，只需修改本文件一处，全站所有引用自动更新，无需逐个文件查找替换。
 *
 * 【路由分组与访问控制层级】
 * 本文件按"是否需登录"将路由分为两大区域：
 *   - 公开区（认证路由）：登录/注册/2FA/密码重置 — 不需要登录即可访问
 *   - 保护区（首页路由）：需要 auth 中间件校验 — 匿名用户被重定向到登录页
 *
 * ============================================================
 * Web Route Definitions — the central dispatcher for all incoming
 * system requests
 * ============================================================
 *
 * 【Role of This File in the Laravel System】
 * This file is the core "request dispatcher" of the Laravel application
 * layer. When a user types a URL into the browser address bar or clicks a
 * link/button on a page, the HTTP request arrives at the server. The
 * Laravel kernel first loads this file and matches the request URL and
 * HTTP method (GET/POST) against the route rules defined here. Upon a
 * successful match, the framework automatically calls the corresponding
 * Controller method to handle the business logic, and finally returns the
 * response (HTML page, redirect, etc.) to the browser.
 *
 * Call chain: Browser request → public/index.php → Kernel →
 *   routes/web.php → Controller → View
 *
 * 【Route File Categories and Load Order】
 * Laravel splits routes into multiple files by use case, loaded uniformly
 * by RouteServiceProvider:
 *   - routes/web.php   → browser-accessed page routes (this file),
 *                         automatically wrapped with the web middleware
 *                         group.
 *   - routes/api.php   → RESTful API routes, automatically wrapped with
 *                         the api middleware group (no Session).
 *   - routes/channels.php → WebSocket channel authorization routes.
 *   - routes/console.php  → Artisan console command routes.
 *
 * 【Security Protections Automatically Injected by the web Middleware Group】
 * All routes in routes/web.php automatically receive the web middleware
 * group from the framework, including:
 *   - EncryptCookies:          encrypt/decrypt client cookies to prevent
 *                              plaintext tampering.
 *   - AddQueuedCookiesToResponse: attach queued cookies to the response.
 *   - StartSession:            start a server-side session to track user
 *                              login state.
 *   - ShareErrorsFromSession:  share validation errors from the Session
 *                              with all views.
 *   - VerifyCsrfToken:         prevent Cross-Site Request Forgery (CSRF)
 *                              attacks — every POST/PUT/DELETE request must
 *                              carry a _token field or the request is
 *                              rejected.
 *   - SubstituteBindings:      route-model binding — automatically resolve
 *                              {id} parameters to Eloquent models.
 *
 * 【Route Naming Convention — Decoupling URLs from Code】
 * Each route is given a unique identifier via ->name('xxx'). Calling
 * route('xxx') in a Blade template or Controller generates the full URL.
 * When the product manager asks to change the URL from /login to /signin,
 * only one line in this file needs to be changed — all references across
 * the entire site update automatically, no file-by-file search-and-replace
 * needed.
 *
 * 【Route Groups and Access Control Layers】
 * This file divides routes into two major zones based on whether login is
 * required:
 *   - Public zone (auth routes): login / register / 2FA / password reset —
 *     accessible without login.
 *   - Protected zone (home route): requires auth middleware verification —
 *     anonymous users are redirected to the login page.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFAController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;

// =============================================================
// 一、认证路由 — 所有用户（含未登录访客）均可访问
// =============================================================
//
// 【安全设计原则】
// 以下"修改状态"的操作（登录、注册、退出）全部使用 POST 方法，而非 GET。
// 原因有三：
//   1. 防止浏览器预加载/爬虫意外触发写操作（浏览器和搜索引擎只会 GET 链接）
//   2. CSRF 保护仅对 POST/PUT/DELETE 生效，GET 请求不受 CSRF 令牌保护
//   3. 符合 HTTP 语义规范 — GET 是"读取"（幂等），POST 是"提交/修改"
//      若退出登录用 GET，攻击者只需在恶意页面上放 <img src="/logout">
//      即可强制已登录用户退出，属于一种"强制退出攻击"（Forced Logout）
//
// =============================================================
// 1. Auth Routes — accessible to all users (including unauthenticated
//    guests)
// =============================================================
//
// 【Security Design Principles】
// All state-changing operations below (login, register, logout) use the
// POST method, not GET. Three reasons:
//   1. Prevent browser preloading / crawlers from accidentally triggering
//      write operations (browsers and search engines only GET links).
//   2. CSRF protection only applies to POST/PUT/DELETE; GET requests are
//      not protected by CSRF tokens.
//   3. Complies with HTTP semantics — GET is "read" (idempotent), POST is
//      "submit/modify". If logout used GET, an attacker could simply place
//      <img src="/logout"> on a malicious page to force-logout a logged-in
//      user — a "Forced Logout" attack.

// --- 登录流程（两步） ---
//
// --- Login Flow (two steps) ---

// GET /login — 显示登录表单页面
// 用户输入邮箱和密码的 HTML 页面，表单的 action 提交到 POST /login
//
// GET /login — display the login form page.
// An HTML page where the user enters their email and password; the form's
// action submits to POST /login.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// POST /login — 处理登录表单提交
// 1. 验证邮箱格式 + 密码长度
// 2. 调用 Auth::attempt() 尝试验证凭据（密码通过 Hash::check 比对 bcrypt 哈希）
// 3. 成功：Session 中写入登录标识 → 跳转到 2FA 验证页
// 4. 失败：重定向回登录页，并在 Session 中闪存错误消息
// 安全特性：Laravel 内置"登录节流"（throttle），同一 IP+邮箱组合频繁失败
//           会被临时锁定，防止暴力破解
//
// POST /login — process the login form submission.
// 1. Validate email format + password length.
// 2. Call Auth::attempt() to verify credentials (password is compared
//    against the bcrypt hash via Hash::check).
// 3. Success: write the login identifier into the Session → redirect to
//    the 2FA verification page.
// 4. Failure: redirect back to the login page with an error message flashed
//    into the Session.
// Security feature: Laravel's built-in login throttling — repeated failures
//   from the same IP + email combination will trigger a temporary lockout
//   to prevent brute-force attacks.
Route::post('/login', [AuthController::class, 'login']);

// --- 注册流程（两步） ---
//
// --- Registration Flow (two steps) ---

// GET /register — 显示注册表单页面
// 包含字段：昵称、邮箱、密码（含强度指示器的最低 8 位要求）、确认密码、角色选择
//
// GET /register — display the registration form page.
// Fields: nickname, email, password (with strength indicator, minimum 8
// characters), confirm password, role selection.
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// POST /register — 处理注册表单提交
// 安全流程：
//   1. 输入验证：邮箱唯一性、密码最小长度（8位）、密码与确认密码一致
//   2. 密码加密：通过 bcrypt（Blowfish 哈希）算法单向加密后存入数据库
//      bcrypt 的特点：自带随机盐值（salt）、计算成本可调（cost factor），
//      即使两个用户密码相同，存储的哈希值也完全不同，有效防御彩虹表攻击
//   3. 邮箱过滤：所有邮箱统一转为小写，避免 "User@Example.com" 和
//      "user@example.com" 被识别为两个不同账户
//   4. 角色默认值：注册时角色默认为 recipient（受助者），后续无法自行更改
//   5. 创建用户记录后，自动跳转到 2FA 邮箱验证环节
//
// POST /register — process the registration form submission.
// Security flow:
//   1. Input validation: email uniqueness, minimum password length (8
//      chars), password and confirmation must match.
//   2. Password hashing: one-way encrypt via bcrypt (Blowfish hash) before
//      storing in the database. bcrypt features: built-in random salt,
//      adjustable cost factor — even if two users have the same password,
//      the stored hashes are completely different, effectively defending
//      against rainbow-table attacks.
//   3. Email normalization: all emails are lowercased to prevent
//      "User@Example.com" and "user@example.com" from being treated as
//      two different accounts.
//   4. Default role: the role defaults to "recipient" at registration and
//      cannot be changed by the user afterwards.
//   5. After creating the user record, automatically redirect to the 2FA
//      email verification step.
Route::post('/register', [AuthController::class, 'register']);

// --- 退出登录 ---
//
// --- Logout ---

// POST /logout — 退出登录
// 为什么必须是 POST 而不能是 GET？
//   假设 /logout 支持 GET：攻击者可以在任意论坛/评论区发布
//   <img src="https://foodshare.test/logout" width="1" height="1">
//   已登录用户浏览该页面时，浏览器会自动加载这张"图片"，实际上是发送了
//   GET /logout 请求，用户瞬间被强制退出——这就是 CSRF 辅助的"强制退出攻击"。
//   使用 POST + CSRF 令牌，攻击者的跨站请求无法携带有效令牌，退出操作被拦截。
// 执行逻辑：Auth::logout() → Session 销毁 → Session ID 重新生成 → 跳转到登录页
// Session ID 重新生成（session()->regenerate()）是为了防御"会话固定攻击"
// （Session Fixation）：防止攻击者预先获得一个 Session ID，等用户登录后
// 该 ID 变为已认证状态，攻击者即可冒充用户。
//
// POST /logout — log out.
// Why must this be POST and not GET?
//   If /logout supported GET: an attacker could post the following on any
//   forum or comment section:
//   <img src="https://foodshare.test/logout" width="1" height="1">
//   When a logged-in user views that page, the browser automatically loads
//   this "image", which is actually a GET /logout request — the user is
//   instantly force-logged-out. This is a CSRF-assisted "Forced Logout"
//   attack.
//   Using POST + CSRF token, the attacker's cross-site request cannot carry
//   a valid token, so the logout is blocked.
// Execution flow: Auth::logout() → destroy Session → regenerate Session ID
//   → redirect to login page.
// Session ID regeneration (session()->regenerate()) defends against Session
// Fixation: prevents an attacker from obtaining a Session ID in advance,
// then impersonating the user once that ID becomes authenticated after
// login.
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =============================================================
// 二、2FA（双因素认证）验证路由
// =============================================================
//
// 【2FA 在注册流程中的定位】
// 用户在 POST /register 提交注册表单后，账号已创建但状态为"未验证"。
// Laravel 自动发送一封邮件到注册邮箱，内含 6 位随机数字验证码。
// 用户需要在验证码过期前（通常 10-15 分钟）在下方页面输入该验证码，
// 完成身份验证后账号才变为"已验证"状态，此时用户才能进入首页。
//
// 这是"带外认证"（Out-of-Band）：通过另一独立通道（邮箱）验证用户身份，
// 即使攻击者获取了用户密码，若没有邮箱访问权限，也无法完成注册/登录。
//
// =============================================================
// 2. 2FA (Two-Factor Authentication) Verification Routes
// =============================================================
//
// 【2FA's Role in the Registration Flow】
// After the user submits the registration form via POST /register, the
// account has been created but is in an "unverified" state. Laravel
// automatically sends an email to the registered address containing a
// 6-digit random verification code. The user must enter this code on the
// page below before it expires (typically 10-15 minutes). Once identity
// verification is complete, the account becomes "verified" and the user
// can enter the home page.
//
// This is "Out-of-Band" authentication: verifying the user's identity
// through a separate, independent channel (email). Even if an attacker
// obtains the user's password, they cannot complete registration/login
// without access to the email account.

// GET /verify-2fa — 显示 6 位验证码输入页面
// 页面上展示一个 6 格数字输入框和倒计时器
//
// GET /verify-2fa — display the 6-digit verification code entry page.
// The page shows a 6-slot digit input and a countdown timer.
Route::get('/verify-2fa', [TwoFAController::class, 'showVerifyForm'])->name('verify2fa.form');

// POST /verify-2fa — 验证提交的 6 位验证码
// 后端比对逻辑：
//   1. 从 Session/缓存 中取出该用户当时生成的验证码
//   2. 检查是否过期（用户侧倒计时仅辅助，后端才是最终裁判）
//   3. 比对用户提交的 6 位数字与存储值是否一致
//   4. 一致 → 标记用户为"已验证" → 登录进入首页
//   5. 不一致/过期 → 返回错误提示，允许重新输入或重新发送
// 安全防护：验证码一次性使用，验证成功或重新发送后旧码立即失效
//
// POST /verify-2fa — validate the submitted 6-digit verification code.
// Backend comparison logic:
//   1. Retrieve the code generated for this user from Session/cache.
//   2. Check whether it has expired (the client-side countdown is only a
//      helper; the backend is the final arbiter).
//   3. Compare the user-submitted 6 digits against the stored value.
//   4. Match → mark user as "verified" → log in and enter the home page.
//   5. Mismatch / expired → return an error message; allow re-entry or
//      resend.
// Security: the code is single-use — after successful verification or
// resend, the old code is immediately invalidated.
Route::post('/verify-2fa', [TwoFAController::class, 'verify'])->name('verify2fa.verify');

// POST /verify-2fa/resend — 重新发送验证码
// 适用场景：验证码过期、邮件进入垃圾箱、网络延迟等原因导致用户未收到
// 安全限制（防滥用）：
//   - 频率限制（throttle）：每个用户每分钟最多请求 1 次重发
//   - 旧验证码作废：每次重发生成新验证码，旧码立即失效
//   - 累计次数限制：连续重发超过 5 次后触发冷却期
//
// POST /verify-2fa/resend — resend the verification code.
// Use cases: code expired, email landed in spam, network delay, etc.
// Security limits (anti-abuse):
//   - Rate limiting (throttle): each user may request at most 1 resend per
//     minute.
//   - Old code invalidation: each resend generates a new code; the old code
//     is immediately invalidated.
//   - Cumulative limit: after 5 consecutive resends a cooldown period is
//     triggered.
Route::post('/verify-2fa/resend', [TwoFAController::class, 'resend'])->name('verify2fa.resend');

// GET /registered — 注册成功庆祝页
// 这是一个仅用于展示的纯视图路由，无需任何业务逻辑，因此直接使用
// 闭包（Closure/匿名函数）返回视图，无需创建 Controller 方法。
// 页面上显示成功提示弹窗 + 撒花动画，2-3 秒后自动跳转到首页。
//
// GET /registered — registration success celebration page.
// This is a presentation-only view route with no business logic, so a
// closure (anonymous function) is used directly to return the view instead
// of creating a dedicated Controller method.
// The page displays a success toast/popup + confetti animation and
// auto-redirects to the home page after 2-3 seconds.
Route::get('/registered', function () {
    return view('auth.registered');
})->name('registered');

// =============================================================
// 三、密码重置路由（"忘记密码"流程）
// =============================================================
//
// 【密码重置的两阶段设计】
// 第一阶段（邮箱验证）：用户输入注册邮箱，系统发送验证码
// 第二阶段（重置密码）：用户输入验证码 + 新密码，系统更新密码
//
// 这种"邮箱验证码模式"相比"邮件链接模式"的优缺点：
//   优点：不依赖超链接，用户手动输入验证码，避免邮件客户端预加载链接
//        导致密码被意外重置（部分邮件安全扫描器会主动点击邮件中的链接）
//   缺点：用户体验稍差，需要手动输入一串数字
//
// =============================================================
// 3. Password Reset Routes ("Forgot Password" flow)
// =============================================================
//
// 【Two-Stage Password Reset Design】
// Stage 1 (email verification): user enters registered email, system sends
//   a verification code.
// Stage 2 (reset password): user enters verification code + new password,
//   system updates the password.
//
// Pros and cons of this "email verification code" approach vs. "magic link":
//   Pros: does not rely on hyperlinks — the user manually enters the code,
//         which avoids email clients preloading the link and accidentally
//         resetting the password (some email security scanners actively click
//         links in emails).
//   Cons: slightly worse user experience — the user has to manually type a
//         string of digits.

// GET /forgot-password — 显示"忘记密码"第一步页面（输入邮箱）
//
// GET /forgot-password — display step 1 of the "Forgot Password" flow
// (enter email address).
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.forgot');

// POST /forgot-password — 发送验证码到指定邮箱
// 安全考虑：
//   - 无论邮箱是否存在于数据库，都显示"验证码已发送"（防止用户枚举攻击）
//   - 若邮箱存在：生成验证码 → 存入缓存 → 发送邮件 → 返回成功页面
//   - 若邮箱不存在：不发送邮件，但仍返回相同的成功提示
//   - 这样做是为了防止攻击者通过"用户不存在"的错误提示来探测哪些邮箱
//     已在平台注册（User Enumeration）
//
// POST /forgot-password — send a verification code to the specified email
// Security considerations:
//   - Whether or not the email exists in the database, always show
//     "Verification code sent" (prevents user enumeration attacks).
//   - If the email exists: generate code → store in cache → send email →
//     return success page.
//   - If the email does not exist: do not send email, but still return the
//     same success message.
//   - This prevents attackers from using "user not found" error messages to
//     discover which emails are registered on the platform (User Enumeration).
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode'])->name('password.send-code');

// GET /reset-password — 显示"重置密码"第二步页面（输入验证码+新密码）
// URL 中不带 token 参数（验证码由用户手动输入，而非通过 URL 携带）
//
// GET /reset-password — display step 2 of password reset (enter
// verification code + new password).
// The URL does not carry a token parameter (the verification code is
// entered manually by the user rather than passed via URL).
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');

// POST /reset-password — 执行密码重置
// 处理逻辑：
//   1. 验证验证码是否匹配且未过期
//   2. 验证新密码强度（最低 8 位）
//   3. 通过 bcrypt 哈希算法加密新密码
//   4. 将该用户的 password 字段更新为新哈希值
//   5. 强制该账户所有活跃 Session 失效（session()->invalidate()）
//   6. 清除该用户所有"记住我"Token（防御：盗用旧密码+记住我Token维持登录）
// 这个设计确保：即使账户被盗，只要原主人重置了密码，攻击者也会被立即踢出
//
// POST /reset-password — execute the password reset
// Processing logic:
//   1. Verify the verification code matches and has not expired.
//   2. Validate new password strength (minimum 8 characters).
//   3. Encrypt the new password using the bcrypt hash algorithm.
//   4. Update the user's password field with the new hash value.
//   5. Invalidate all active sessions for this account
//      (session()->invalidate()).
//   6. Clear all "remember me" tokens for this user (defense: prevents
//      an attacker from using an old password + remember-me token to
//      stay logged in).
// This design ensures: even if the account is compromised, as soon as
// the original owner resets the password, the attacker is immediately
// kicked out.
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');

// =============================================================
// 四、Landing Page（落地页）— 无需登录
// =============================================================
//
// 【落地页的作用】
// 这是用户访问网站根路径时看到的第一个页面，相当于"门面"。
// 展示平台的品牌介绍、运作方式、角色说明和影响力数据，
// 引导用户点击"Join FoodBridge"或"Get Started"跳转到登录页。
//
// =============================================================
// 4. Landing Page — no authentication required
// =============================================================
//
// 【Purpose of the Landing Page】
// This is the first page users see when visiting the site root URL —
// essentially the "storefront". It showcases the platform's brand,
// how it works, role descriptions, and impact stats, guiding users
// to click "Join FoodBridge" or "Get Started" to go to the login page.

Route::get('/', function () {
    return view('welcome');
});

// =============================================================
// 五、首页路由 — 登录后才能访问
// =============================================================
//
// 【auth 中间件的工作原理】
// Route::middleware('auth') 告诉 Laravel：在执行 HomeController@index
// 之前，先运行 auth 中间件检查用户是否已登录。
//
// 检查逻辑（Authenticate 中间件）：
//   1. 读取当前请求的 Session，查找用户登录标识（user_id）
//   2. 若 user_id 存在 → 从数据库加载用户对象 → 附加到请求 → 放行
//   3. 若 user_id 不存在 → 拦截请求，返回 HTTP 302 重定向到登录页
//      同时将当前请求的完整 URL 存入 Session 的 'url.intended' 键中
//      用户登录成功后，系统会自动跳转回原来的目标页面（智能重定向）
//
// =============================================================
// 5. Home Route — accessible only after login
// =============================================================
//
// 【How the auth Middleware Works】
// Route::middleware('auth') tells Laravel: before executing
// HomeController@index, run the auth middleware to check whether the user
// is logged in.
//
// Check logic (Authenticate middleware):
//   1. Read the current request's Session and look for the login identifier
//      (user_id).
//   2. If user_id exists → load the user object from the database → attach it
//      to the request → allow through.
//   3. If user_id does not exist → intercept the request, return an HTTP 302
//      redirect to the login page, while storing the full original URL in the
//      Session's 'url.intended' key. After successful login the system
//      automatically redirects back to the original target page (smart
//      redirect).

Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// =============================================================
// 六、个人资料管理路由 — 登录后才能访问
// =============================================================
//
// 【为什么需要独立的两个页面（/profile 与 /profile/password）】
// 编辑基本信息和修改密码虽然都属于"资料管理"，但涉及完全不同的安全考量：
//   - 基本信息（firstname/lastname/phone）：社交工程风险低，无须二次验证
//   - 修改密码：高度敏感操作，必须输入当前密码确认本人
// 拆分为两个独立页面可避免把改密逻辑藏在长表单里（用户容易漏掉改密意图），
// 也方便后续单独对改密路径加 reCAPTCHA / 邮件通知 / 二次验证等增强。
//
// 【为什么必须挂 auth 中间件】
// 这两个路由都只服务已登录用户本人。auth 中间件会拦截未登录请求，
// 将其 302 到 /login 并保存 url.intended，登录后自动回到原页面。
//
// 【为什么写操作必须用 POST】
// 与本文件已有的 /logout、/register、/verify-2fa 等"修改状态"路由保持一致，
// 防止 CSRF 辅助的"强制退出攻击"模式蔓延到资料/密码场景。
//
// =============================================================
// 6. Profile Management Routes — accessible only after login
// =============================================================
//
// 【Why Two Separate Pages (/profile and /profile/password)】
// Although editing basic info and changing password both fall under
// "profile management", they involve very different security considerations:
//   - Basic info (firstname/lastname/phone): low social-engineering risk —
//     no second-factor needed.
//   - Changing password: highly sensitive — requires the current password
//     to confirm the user's identity.
// Splitting into two pages prevents the password-change flow from being
// hidden inside a long form (users might miss the intent to change their
// password), and lets us later add reCAPTCHA / email notifications / 2FA
// only to the password-change path if needed.
//
// 【Why the auth Middleware Is Required】
// Both routes serve only the currently logged-in user. The auth middleware
// intercepts unauthenticated requests, 302-redirects them to /login, and
// preserves url.intended so the user returns to the original page after
// logging in.
//
// 【Why Writes Must Use POST】
// Consistent with the other state-changing routes in this file (/logout,
// /register, /verify-2fa), preventing CSRF-assisted "forced action"
// patterns from spreading to the profile / password flows.

// GET /profile — 显示个人资料编辑页面
// 渲染 profile/edit.blade.php，注入 $user（当前登录用户）。
// 预填 firstname、lastname、phone；email 与 role 以只读形式展示。
//
// GET /profile — display the profile edit page.
// Renders profile/edit.blade.php with $user (current authenticated user).
// Pre-fills firstname, lastname, phone; email and role are shown read-only.
Route::get('/profile', [ProfileController::class, 'edit'])
    ->middleware('auth')
    ->name('profile.edit');

// POST /profile — 处理资料更新
// 仅修改 firstname / lastname / phone，email 与 role 不可改（安全考虑）。
// 验证规则与 ProfileController@update 内的内联 validate 保持一致。
//
// POST /profile — process the profile update.
// Only firstname / lastname / phone are modified; email and role cannot be
// changed (security consideration). Validation rules stay in sync with the
// inline validate inside ProfileController@update.
Route::post('/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');

// GET /profile/password — 显示修改密码页面
// 渲染 profile/password.blade.php，包含 current / new / retype 三个字段。
//
// GET /profile/password — display the change-password page.
// Renders profile/password.blade.php with three fields: current, new, retype.
Route::get('/profile/password', [ProfileController::class, 'showPasswordForm'])
    ->middleware('auth')
    ->name('profile.password.form');

// POST /profile/password — 处理密码修改
// 安全设计：
//   1. 服务端验证三字段（强度、两次一致、新密码 ≠ 当前密码）
//   2. AuthService::updatePasswordForAuthenticatedUser() 校验当前密码
//      （Hash::check 内部 constant-time 防时序攻击）
//   3. 新密码通过 UserRepository::update() 自动 bcrypt 后写入 password_hash
//   4. 成功后 session()->regenerateToken() 刷新 CSRF token，防旧 token 重放
//
// POST /profile/password — process the password change.
// Security design:
//   1. Server-side validates all three fields (strength, consistency,
//      new ≠ current).
//   2. AuthService::updatePasswordForAuthenticatedUser() verifies the current
//      password (Hash::check is internally constant-time, preventing timing
//      attacks).
//   3. The new password is bcrypt-hashed automatically by
//      UserRepository::update() before being written to password_hash.
//   4. On success, session()->regenerateToken() refreshes the CSRF token to
//      prevent replay of the old token.
Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('profile.password.update');

/**
 * ============================================================
 * 附录：本文件涉及的安全术语速查表
 * ============================================================
 *
 * 【CSRF（跨站请求伪造）】
 *   攻击者诱导用户点击一个恶意页面，该页面暗中向目标网站发起请求。
 *   因为浏览器会自动携带目标网站的 Cookie，服务器认为这是用户本人的操作。
 *   预防：所有写操作必须附带 CSRF Token，该 Token 由服务端生成，嵌入页面表单，
 *         攻击者的跨站页面无法获取这个值。
 *
 * 【Session Fixation（会话固定）】
 *   攻击者先获取一个合法的 Session ID，然后诱骗用户使用这个 Session ID 登录。
 *   用户登录后，攻击者用同一个 Session ID 即可冒充用户。
 *   预防：登录成功后调用 session()->regenerate() 生成全新的 Session ID。
 *
 * 【User Enumeration（用户枚举）】
 *   攻击者通过系统不同的错误提示（"用户不存在" vs "密码错误"）来判断哪些
 *   邮箱/用户名已注册，从而收集有效的攻击目标列表。
 *   预防：统一错误提示——"邮箱或密码不正确"（不论实际是哪个错了）。
 *
 * 【bcrypt 哈希算法】
 *   bcrypt 基于 Blowfish 分组密码，专为密码存储设计：
 *     - 自带盐值（salt）：每次加密自动生成随机盐值，两个相同密码的哈希也不同
 *     - 可调成本因子（cost factor）：值越大加密越慢，有效对抗暴力破解
 *     - 单向不可逆：无法从哈希值反推出原始密码
 *   Laravel 默认 cost = 12，即 2^12 = 4096 轮迭代，每次登录验证耗时约 0.2 秒。
 *   对正常用户影响微乎其微，但攻击者尝试大量密码组合时成本急剧增加。
 *
 * 【bcrypt Hash Algorithm】
 *   bcrypt is built on the Blowfish block cipher, designed specifically for
 *   password storage:
 *     - Built-in salt: a random salt is generated for every encryption, so
 *       identical passwords produce different hashes.
 *     - Adjustable cost factor: higher values slow down encryption, effectively
 *       defending against brute-force attacks.
 *     - One-way and irreversible: the original password cannot be recovered
 *       from the hash.
 *   Laravel defaults to cost = 12 (2^12 = 4,096 iterations); each login
 *   verification takes roughly 0.2 seconds — negligible for legitimate users
 *   but prohibitively expensive for attackers trying large password lists.
 */
