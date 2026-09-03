<?php

/**
 * ============================================================
 * Google reCAPTCHA v2 配置文件
 * ============================================================
 *
 * 【文件作用】
 * 本文件定义了 reCAPTCHA 人机验证服务所需的两个密钥。
 * 它是 Laravel 标准配置文件，通过 config('recaptcha.site_key') 和
 * config('recaptcha.secret_key') 供全项目引用。
 *
 * 【通俗解释 — reCAPTCHA 是什么】
 * reCAPTCHA 是 Google 提供的免费"我不是机器人"验证服务。
 * 用户在登录页面上看到的那个"我不是机器人"复选框就是 reCAPTCHA v2。
 * 它的目的是区分真实人类用户和自动化脚本/机器人，
 * 防止暴力破解登录、垃圾注册、恶意提交等攻击行为。
 *
 * 【安全防护原理 — 完整验证流程】
 * 第 1 步（前端 — 浏览器端）：
 *   加载 reCAPTCHA API 脚本后，Google 会在用户点击复选框时
 *   分析鼠标轨迹、点击行为、cookie 等信息判断是否人类。
 *   如果 Google 无法直接判断（如 VPN/可疑行为），会弹出
 *   图片选择题（"选出所有包含交通灯的图片"）做二次验证。
 *   验证通过后，Google 生成一个临时的 response token（g-recaptcha-response），
 *   存入表单的隐藏字段，随表单一同提交到后端。
 *
 * 第 2 步（后端 — 服务器端）：
 *   服务器收到登录请求后，用 secret_key（后端密钥）和用户提交的
 *   response token 向 Google 验证接口发起 POST 请求：
 *     POST https://www.google.com/recaptcha/api/siteverify
 *     参数：secret=你的后端密钥 & response=用户的token & remoteip=用户IP
 *   Google 返回 JSON，包含 success（布尔值）、score（v3）、
 *   challenge_ts（时间戳）、hostname（域名）等字段。
 *   服务器检查 success 是否为 true 以及 hostname 是否匹配。
 *   如果验证失败，直接拒绝登录请求，连用户名密码都不用查。
 *   这样就阻止了绕过前端直接向登录接口发送大量请求的自动化攻击。
 *
 * 【两个密钥的详细说明】
 * - site_key（站点密钥 / 前端密钥）：
 *   性质：公开密钥，可以安全地出现在 HTML 源代码中。
 *   用途：在网页中渲染 reCAPTCHA 复选框控件。
 *   位置：resources/views/auth/index.blade.php 中的 data-sitekey 属性。
 *   安全：被泄露不会有安全隐患，因为最终验证依赖 secret_key。
 *
 * - secret_key（私密密钥 / 后端密钥）：
 *   性质：机密密钥，必须严格保密，绝不能泄露到前端或版本库。
 *   用途：服务器端向 Google 确认 response token 的真实性。
 *   位置：AuthController::login() 方法中向 Google 发起验证请求时使用。
 *   安全：一旦泄露，攻击者可以自行构造假验证，整个防护形同虚设。
 *   存储：通过 .env 文件读取，.env 不提交到 Git（在 .gitignore 中）。
 *
 * 【配置来源】
 * 两个密钥都通过 Laravel 的 env() 辅助函数从 .env 环境文件中读取：
 *   RECAPTCHA_SITE_KEY=6Lc...（你的站点密钥，约40个字符）
 *   RECAPTCHA_SECRET_KEY=6Lc...（你的私密密钥，约40个字符）
 * 没有 .env 配置时 env() 返回 null，reCAPTCHA 功能将不可用。
 *
 * 【获取密钥的步骤】
 * 1. 访问 https://www.google.com/recaptcha/admin
 * 2. 注册你的网站域名（如 foodshare.example.com）
 * 3. 选择 reCAPTCHA v2 类型（"我不是机器人"复选框）
 * 4. 获取两个密钥，分别填入 .env 对应字段
 *
 * 【CSP 兼容性说明】
 * 为保障中国大陆用户的访问体验，reCAPTCHA 脚本从以下镜像域名加载：
 *   - www.recaptcha.net（Google 官方提供的全球 CDN 镜像，国内可访问）
 * 而非默认的 www.google.com（国内可能有连接问题）。
 * 同时，在 SecurityHeaders 中间件的 Content-Security-Policy 策略中
 * 已将 *.recaptcha.net 和 *.gstatic.com 加入 script-src 白名单，
 * 确保浏览器不会因 CSP 策略拦截 reCAPTCHA 的资源加载。
 *
 * 【注意事项】
 * - 开发/测试环境建议使用 Google 提供的测试密钥，避免干扰正常开发：
 *   site_key:   6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
 *   secret_key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
 *   使用测试密钥时，reCAPTCHA 复选框始终通过验证。
 * - 生产环境必须替换为真实域名对应的密钥。
 *
 * ============================================================
 * Google reCAPTCHA v2 Configuration
 * ============================================================
 *
 * Purpose: Defines the two keys required for the reCAPTCHA anti-bot
 * verification service. This is a standard Laravel config file,
 * referenced project-wide via config('recaptcha.site_key') and
 * config('recaptcha.secret_key').
 *
 * What is reCAPTCHA: A free Google service that displays the
 * "I'm not a robot" checkbox (reCAPTCHA v2). It distinguishes
 * real human users from automated scripts/bots, preventing
 * brute-force login attacks, spam registrations, and malicious
 * form submissions.
 *
 * Verification Flow:
 *   Step 1 (Frontend): After loading the reCAPTCHA API script,
 *     Google analyzes mouse movement, click behavior, cookies, etc.
 *     If uncertain (e.g., VPN, suspicious behavior), a picture
 *     challenge is shown for secondary verification. On success,
 *     a temporary response token is generated and submitted with
 *     the form.
 *   Step 2 (Backend): The server sends the secret_key and user's
 *     response token to Google's verification endpoint
 *     (POST https://www.google.com/recaptcha/api/siteverify)
 *     along with the user's IP. Google returns a JSON result with
 *     success status, score, timestamp, and hostname. If verification
 *     fails, the login request is rejected outright, blocking
 *     automated attacks that bypass the frontend.
 *
 * Key Types:
 *   - site_key (public): Renders the reCAPTCHA checkbox in HTML.
 *     Safe to expose in frontend source code. Used in the
 *     data-sitekey attribute in resources/views/auth/index.blade.php.
 *   - secret_key (private): Server-side credential for verifying
 *     response tokens with Google. Must NEVER be exposed to the
 *     frontend, logs, or version control. Stored in .env (not
 *     committed to Git). If leaked, the entire protection is
 *     compromised.
 *
 * Config Source: Both keys are read from .env via Laravel's env()
 * helper: RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY. Without .env
 * values, reCAPTCHA functionality is unavailable (env() returns null).
 *
 * How to Obtain Keys:
 *   1. Visit https://www.google.com/recaptcha/admin
 *   2. Register your domain (e.g., foodshare.example.com)
 *   3. Choose reCAPTCHA v2 ("I'm not a robot" checkbox)
 *   4. Copy both keys into the .env file
 *
 * CSP Compatibility: For accessibility in mainland China, the
 * reCAPTCHA script is loaded from www.recaptcha.net (Google's
 * official global CDN mirror) instead of www.google.com.
 * *.recaptcha.net and *.gstatic.com are whitelisted in the
 * Content-Security-Policy script-src directive.
 *
 * Notes:
 *   - Dev/test environments should use Google's test keys:
 *     site_key:   6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
 *     secret_key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
 *     (these keys always pass verification)
 *   - Production must use keys registered for the real domain.
 */

return [
    /*
     * 站点密钥（Site Key）— 公开的前端密钥
     *
     * 作用：嵌入 HTML 页面的 data-sitekey 属性中，用于渲染 reCAPTCHA 复选框。
     * 安全级别：公开（可出现在前端源代码中，泄露无安全风险）。
     * 环境变量：RECAPTCHA_SITE_KEY，在 .env 文件中配置。
     * 使用示例：<div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
     *
     * Site Key — public frontend key.
     *
     * Purpose: Embedded in the data-sitekey HTML attribute to render
     * the reCAPTCHA checkbox.
     * Security: Public (safe to appear in frontend source code).
     * Env variable: RECAPTCHA_SITE_KEY, configured in .env.
     * Example: <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
     */
    'site_key'   => env('RECAPTCHA_SITE_KEY'),

    /*
     * 私密密钥（Secret Key）— 机密的后端密钥
     *
     * 作用：服务器端调用 Google 验证 API 时的认证凭据。
     *       向 https://www.google.com/recaptcha/api/siteverify 发送 POST 请求，
     *       携带此密钥和用户提交的 response token，Google 返回验证结果。
     * 安全级别：机密（绝不能暴露给前端、日志、版本控制或错误消息中）。
     * 环境变量：RECAPTCHA_SECRET_KEY，在 .env 文件中配置。
     * 注意：生产与开发环境应使用不同密钥，开发环境推荐 Google 官方测试密钥。
     *
     * Secret Key — confidential backend key.
     *
     * Purpose: Server-side credential when calling Google's verification
     * API (POST https://www.google.com/recaptcha/api/siteverify).
     * Security: Confidential (must never be exposed to frontend, logs,
     * version control, or error messages).
     * Env variable: RECAPTCHA_SECRET_KEY, configured in .env.
     * Note: Use different keys for production and development.
     * Use Google's official test keys in development.
     */
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
];
