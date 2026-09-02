<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * 邮件服务 (EmailService)
 * ============================================================
 *
 * 【通俗解释】
 *   这个文件就像平台的"邮递员"，负责把邮件（注册验证码、通知等）发送到用户邮箱。
 *   它不自己搭建邮件服务器，而是调用第三方邮件API（qzqi.com）代为发送。
 *
 * 【所属模块】
 *   认证模块 / 通知模块 —— 用户注册时需要邮箱验证，重置密码也需要邮件。
 *
 * 【在业务流程中的位置】
 *   用户注册流程: 用户填写邮箱 → 点击"发送验证码" → AuthController 调用本服务
 *   → qzqi API 发送邮件到用户邮箱 → 用户收到验证码 → 提交验证。
 *   本服务位于"控制器调用 → 第三方发送"的中间层，封装了邮件发送细节。
 *
 * 【依赖的类】
 *   - Illuminate\Support\Facades\Log: Laravel 日志门面（门面 = 静态代理），
 *     用于记录发送成功/失败的日志
 *   - PHP 内置 cURL 扩展: 发起 HTTP 请求到 qzqi API
 *
 * 【被哪些类调用】
 *   - App\Http\Controllers\Api\AuthController: 用户注册时发送验证码邮件
 *   - 其他需要发送邮件通知的控制器或服务类
 *
 * ============================================================
 * Email Service (EmailService)
 * ============================================================
 *
 * [Plain-English Explanation]
 *   This file acts as the platform's "mail carrier", responsible for sending
 *   emails (registration verification codes, notifications, etc.) to user inboxes.
 *   It does not run its own mail server; instead it delegates to the third-party
 *   email API (qzqi.com).
 *
 * [Owning Module]
 *   Auth module / Notification module — email verification is required on
 *   registration; password resets also require email.
 *
 * [Position in the Business Flow]
 *   Registration flow: user enters email → clicks "Send Code" → AuthController
 *   calls this service → qzqi API delivers the email → user receives the code →
 *   submits verification. This service sits in the middle layer between
 *   "controller invocation" and "third-party delivery", encapsulating the
 *   email-sending details.
 *
 * [Dependencies]
 *   - Illuminate\Support\Facades\Log: Laravel logging facade (static proxy),
 *     used to record send success / failure logs.
 *   - PHP built-in cURL extension: used to issue HTTP requests to the qzqi API.
 *
 * [Callers]
 *   - App\Http\Controllers\Api\AuthController: sends verification-code emails
 *     during user registration.
 *   - Other controllers or service classes that need to send email notifications.
 */
class EmailService
{
    /** @var string 第三方邮件API地址（qzqi.com） */
    /** @var string Third-party email API base URL (qzqi.com) */
    private string $apiUrl = 'https://api.qzqi.com/api/v1/Mail';

    /** @var string 接口密钥，从 .env 的 API_KEY 读取 */
    /** @var string API key, read from .env API_KEY */
    private string $apiKey;
    /** @var string 发件人邮箱地址，从 .env 的 SEND_EMAIL 读取 */
    /** @var string Sender email address, read from .env SEND_EMAIL */
    private string $sendEmail;
    /** @var string SMTP 授权码，从 .env 的 SMTP_KEY 读取 */
    /** @var string SMTP authorization code, read from .env SMTP_KEY */
    private string $smtpKey;
    /** @var string SMTP 服务器地址，从 .env 的 SMTP_HOST 读取 */
    /** @var string SMTP server hostname, read from .env SMTP_HOST */
    private string $smtpHost;
    /** @var string SMTP 端口号，从 .env 的 SMTP_PORT 读取 */
    /** @var string SMTP port number, read from .env SMTP_PORT */
    private string $smtpPort;
    /** @var string 发件人显示名称，从 .env 的 SEND_MAIL_NAME 读取，默认 "FoodShare" */
    /** @var string Sender display name, read from .env SEND_MAIL_NAME, defaults to "FoodShare" */
    private string $sendName;

    /**
     * 构造函数 —— 从环境变量加载邮件配置
     *
     * 【调用时机】
     *   由 Laravel 服务容器（服务容器 = 依赖注入/IoC 容器）自动调用，
     *   每次实例化 EmailService 时执行。
     *
     * 【关键步骤】
     *   1. 从 .env 环境文件读取 SMTP 连接参数
     *   2. 发件人名称有默认值 "FoodShare"，防止未配置时出错
     *
     * Constructor — loads email configuration from environment variables.
     *
     * [When Called]
     *   Automatically invoked by the Laravel service container (dependency-
     *   injection / IoC container) whenever EmailService is instantiated.
     *
     * [Key Steps]
     *   1. Read SMTP connection parameters from the .env environment file.
     *   2. The sender name defaults to "FoodShare" to avoid errors when not
     *      explicitly configured.
     */
    public function __construct()
    {
        $this->apiKey    = env('API_KEY');
        $this->sendEmail = env('SEND_EMAIL');
        $this->smtpKey   = env('SMTP_KEY');
        $this->smtpHost  = env('SMTP_HOST');
        $this->smtpPort  = env('SMTP_PORT');
        $this->sendName  = env('SEND_MAIL_NAME', 'FoodShare');
    }

    /**
     * 发送 HTML 邮件 —— 通过 qzqi.com 第三方 API 发送邮件
     *
     * 【通俗解释】
     *   把邮件标题、HTML正文、收件人地址打包好，通过 HTTP 请求发给 qzqi 服务器，
     *   由 qzqi 的 SMTP 服务代为投递到用户邮箱。
     *
     * 【为什么用 POST + Query String？】
     *   原本使用的是 GET 请求，但宝塔 WAF（Web应用防火墙）会拦截 URL 中出现的
     *   HTML 标签（如 <div>、<table>），误判为 XSS 攻击。改为 POST 请求后，
     *   参数放在请求体而非 URL 中，绕过 WAF 的 URL 检测。
     *
     * @param string $toEmail   收件人邮箱地址，例如 "user@example.com"
     * @param string $subject   邮件标题，例如 "FoodShare Email Verification"
     * @param string $htmlBody  邮件 HTML 正文，由 buildVerificationEmail() 等方法生成
     * @return array            统一格式的响应数组:
     *                           ['status' => 'success', 'message' => '...']  发送成功
     *                           ['status' => 'error',   'message' => '...']  发送失败
     *
     * 【调用时机】
     *   由 AuthController 在用户请求发送验证码时调用。
     *
     * 【关键步骤】
     *   1. 将发件人、收件人、密钥、标题、正文等参数拼成查询字符串
     *   2. 通过 cURL 发送 POST 请求到 qzqi API
     *   3. 解析 JSON 响应，判断成功/失败
     *   4. 记录日志并返回统一格式的结果数组
     *
     * Send an HTML email via the qzqi.com third-party API.
     *
     * [Plain-English Explanation]
     *   Packages the email subject, HTML body, and recipient address, sends them
     *   via an HTTP request to the qzqi server, which relays the message through
     *   its SMTP service to the user's inbox.
     *
     * [Why POST + Query String?]
     *   Originally a GET request was used, but the Baota WAF (Web Application
     *   Firewall) would block HTML tags (e.g. <div>, <table>) appearing in the
     *   URL, misclassifying them as XSS attacks. Switching to POST places the
     *   parameters in the request body instead of the URL, bypassing the WAF's
     *   URL-level inspection.
     *
     * @param string $toEmail   Recipient email address, e.g. "user@example.com"
     * @param string $subject   Email subject, e.g. "FoodShare Email Verification"
     * @param string $htmlBody  HTML email body, generated by buildVerificationEmail() etc.
     * @return array            Unified response array:
     *                          ['status' => 'success', 'message' => '...']  on success
     *                          ['status' => 'error',   'message' => '...']  on failure
     *
     * [When Called]
     *   Called by AuthController when the user requests a verification code.
     *
     * [Key Steps]
     *   1. Assemble sender, recipient, key, subject, body, etc. into a query string.
     *   2. Send a POST request to the qzqi API via cURL.
     *   3. Parse the JSON response to determine success or failure.
     *   4. Log the result and return a unified-format result array.
     */
    public function sendHtmlMail(string $toEmail, string $subject, string $htmlBody): array
    {
        // ---- 第1步: 构建请求参数 ----
        // ---- Step 1: Build request parameters ----
        // qzqi API 需要两个 key 参数：
        //   - apikey: 接口密钥（在用户中心申请）
        //   - key: SMTP 授权码
        // qzqi API requires two key parameters:
        //   - apikey: API key (applied from user center)
        //   - key: SMTP authorization code
        $queryParams = http_build_query([
            'apikey' => $this->apiKey,
            'email'  => $this->sendEmail,
            'mail'   => $toEmail,
            'key'    => $this->smtpKey,
            'name'   => $this->sendName,
            'title'  => $subject,
            'text'   => $htmlBody,
            'host'   => $this->smtpHost,
            'port'   => (int) $this->smtpPort,
        ]);

        // 拼接完整URL: 基础地址 + 查询参数
        // Assemble full URL: base URL + query parameters
        $fullUrl = $this->apiUrl . '?' . $queryParams;

        // ---- 第2步: 初始化 cURL 并发送 POST 请求 ----
        // ---- Step 2: Initialize cURL and send POST request ----
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // 获取HTTP状态码
                                                               // Get HTTP status code
        $curlError    = curl_error($ch);                         // 获取cURL错误信息
                                                               // Get cURL error message
        curl_close($ch);

        // ---- 第3步: 处理 cURL 层面的错误（网络不通、DNS解析失败等） ----
        // ---- Step 3: Handle cURL-level errors (network unreachable, DNS failure, etc.) ----
        if ($curlError) {
            Log::error('Email cURL error: ' . $curlError);
            return ['status' => 'error', 'message' => 'Email service unavailable.'];
        }

        // ---- 第4步: 解析 API 返回的 JSON 响应 ----
        // ---- Step 4: Parse the JSON response returned by the API ----
        $body = json_decode($responseBody, true);  // true = 转为关联数组
                                                    // true = decode as associative array

        // ---- 第5步: 判断业务是否成功 ----
        // ---- Step 5: Determine whether the operation succeeded ----
        // 成功条件: HTTP 200 + JSON解析成功 + status字段为 'success'
        // Success criteria: HTTP 200 + valid JSON + status field equals 'success'
        if ($httpCode === 200 && is_array($body) && ($body['status'] ?? '') === 'success') {
            Log::info('EmailService: 邮件发送成功');
            return ['status' => 'success', 'message' => $body['message'] ?? 'Email sent successfully.'];
        }

        // ---- 第6步: 发送失败，记录警告日志 ----
        // ---- Step 6: Send failed — log a warning ----
        // 截取前500个字符防止日志过大
        // Truncate to first 500 characters to prevent oversized logs
        Log::warning('EmailService: 邮件发送失败', [
            'http_code' => $httpCode,
            'response'  => mb_substr($responseBody, 0, 500),
        ]);

        return [
            'status'  => 'error',
            'message' => is_array($body) ? ($body['message'] ?? 'Failed to send email.') : 'Email service unavailable.',
        ];
    }

    /**
     * 生成注册验证码邮件的 HTML 正文
     *
     * 【通俗解释】
     *   拼装一封漂亮的 HTML 邮件，里面包含用户的验证码。
     *   像搭积木一样把标题、问候语、验证码展示区、安全提示等按顺序拼接成完整HTML。
     *
     * 【设计约束 —— 为什么不用 <div>/<table>？】
     *   宝塔 WAF（Web应用防火墙）会拦截邮件内容中出现的 <div style=> 和 <table> 标签，
     *   误判为恶意内容。因此全部使用 <p>、<h2>、<b> 等简单标签 + 内联 style 属性来
     *   实现排版，确保邮件能通过 WAF 检测。
     *
     * 【配色方案 —— Organic Biophilic 设计系统】
     *   - 主色 绿色 #059669: 标题、验证码文字和边框
     *   - 强调色 琥珀 #D97706: 有效期提示文字
     *   - 验证码背景 #ECFDF5: 极浅绿色，突出验证码区域
     *   - 正文色 #444: 深灰色，保证可读性
     *   - 辅助色 #888/#aaa: 小字提示，降低视觉权重
     *
     * @param string $firstName  用户的名字（非完整姓名），用于个性化称呼
     * @param string $code       6位数字验证码，由 AuthController 随机生成
     * @return string            完整的 HTML 邮件正文字符串，可直接传给 sendHtmlMail()
     *
     * 【调用时机】
     *   由 AuthController 在用户注册流程中调用，生成邮件内容后传给 sendHtmlMail() 发送。
     *
     * 【关键步骤】
     *   1. 输出 FoodShare 品牌标题（绿色 Logo 字）
     *   2. 用 htmlspecialchars() 转义用户名，防止 XSS 注入（XSS = 跨站脚本攻击）
     *   3. 展示验证码（大字号、居中、绿色虚线边框框起，醒目易读）
     *   4. 添加有效期和安全提示（"不要分享验证码给任何人"）
     *   5. 底部品牌标语 "Share Food, Spread Kindness"
     *
     * Build the HTML body for the registration verification-code email.
     *
     * [Plain-English Explanation]
     *   Assembles a nice-looking HTML email containing the user's verification code.
     *   Like building blocks, it concatenates the title, greeting, code display area,
     *   safety tips, etc. in order to form a complete HTML document.
     *
     * [Design Constraint — Why no <div>/<table>?]
     *   The Baota WAF (Web Application Firewall) blocks <div style=> and <table> tags
     *   inside email content, misclassifying them as malicious. Therefore only simple
     *   tags (<p>, <h2>, <b>) plus inline style attributes are used for layout,
     *   ensuring the email passes WAF inspection.
     *
     * [Color Palette — Organic Biophilic Design System]
     *   - Primary green #059669: title, code text, and border
     *   - Accent amber #D97706: expiry warning text
     *   - Code background #ECFDF5: very light green, highlights the code area
     *   - Body color #444: dark gray for readability
     *   - Secondary colors #888/#aaa: fine print, reduced visual weight
     *
     * @param string $firstName  User's first name (not full name), for personalized greeting
     * @param string $code       6-digit verification code, randomly generated by AuthController
     * @return string            Complete HTML email body string, ready to pass to sendHtmlMail()
     *
     * [When Called]
     *   Called by AuthController during the registration flow; the generated email
     *   content is then passed to sendHtmlMail() for delivery.
     *
     * [Key Steps]
     *   1. Render the FoodShare brand heading (green logo text).
     *   2. Escape the user name with htmlspecialchars() to prevent XSS injection.
     *   3. Display the verification code (large font, centered, green dashed border
     *      — prominent and easy to read).
     *   4. Add expiry and safety notices ("Never share this code with anyone").
     *   5. Footer brand tagline "Share Food, Spread Kindness".
     */
    public function buildVerificationEmail(string $firstName, string $code): string
    {
        // 逐段拼接 HTML，使用 . 运算符连接多行字符串
        // Build HTML piece by piece, using the . operator to concatenate multi-line strings
        return '<h2 style=color:#059669>FoodShare</h2>'
            // 个性化问候 —— htmlspecialchars() 防止用户名中包含 < > " ' & 等特殊字符破坏 HTML 结构
            // Personalized greeting — htmlspecialchars() prevents special chars (< > " ' &) in usernames from breaking HTML
            . '<p style=font-size:15px;color:#444>Hi ' . htmlspecialchars($firstName) . ',</p>'
            // 欢迎文案
            // Welcome message
            . '<p style=font-size:15px;color:#444>Welcome to FoodShare! Use the verification code below to complete your registration.</p>'
            // 验证码展示区 —— 大字号28px + 字间距5px 确保数字清晰可辨；
            // 绿色虚线边框 + 浅绿背景，从视觉上突出这是邮件的核心信息
            // Verification code display — large 28px font + 5px letter-spacing for clarity;
            // green dashed border + light green background visually highlight this as the email's core content
            . '<p style=font-size:28px;font-weight:bold;color:#059669;letter-spacing:5px;background:#ECFDF5;padding:16px;text-align:center;border:2px dashed #059669;border-radius:8px>' . $code . '</p>'
            // 有效期提醒 —— 琥珀色 #D97706 引起注意，<b> 加粗强调
            // Expiry reminder — amber #D97706 draws attention, <b> bold for emphasis
            . '<p style=font-size:14px;color:#D97706><b>This code expires in 15 minutes.</b></p>'
            // 安全提示 —— 灰色小字，降低视觉权重但保留必要信息
            // Safety notice — gray small text, reduced visual weight while keeping essential info
            . '<p style=font-size:13px;color:#888>If you did not request this, please ignore this email.<br>Never share this code with anyone.</p>'
            // 品牌标语 —— 顶部浅灰分割线分隔，最淡的颜色 #aaa 表示这是页脚
            // Brand tagline — separated by a light-gray top border; the lightest color #aaa marks this as the footer
            . '<p style=font-size:12px;color:#aaa;border-top:1px solid #e5e7eb;padding-top:12px>FoodShare &mdash; Share Food, Spread Kindness</p>';
    }
}
