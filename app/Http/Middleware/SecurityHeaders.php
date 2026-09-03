<?php

/**
 * =============================================================================
 * FoodShare 安全响应头中间件
 * =============================================================================
 *
 * 文件作用：
 *   本中间件在每次 HTTP 响应返回给浏览器之前，自动向响应头注入一系列安全相关的
 *   HTTP 头部。这些头部指示浏览器启用其内置的安全防护机制，从而在客户端层面抵御
 *   常见的 Web 攻击（XSS、点击劫持、内容嗅探攻击、数据泄露等）。
 *
 * FoodShare Security Response Headers Middleware
 * =============================================================================
 *
 * Purpose:
 *   This middleware automatically injects a series of security-related HTTP headers
 *   into every HTTP response before it is returned to the browser. These headers
 *   instruct the browser to enable its built-in security protection mechanisms,
 *   defending against common web attacks (XSS, clickjacking, MIME sniffing attacks,
 *   data leakage, etc.) at the client level.
 *
 * 工作原理：
 *   Laravel 的中间件采用"洋葱模型"（管道模式）。请求进入时，经过一系列中间件到达
 *   控制器；响应返回时，再逆序经过这些中间件。本中间件的 `handle()` 方法先调用
 *   `$next($request)` 让请求继续向下传递并获得响应对象，然后在此响应对象上附加
 *   安全头，最后返回修改后的响应。这属于"后置中间件"（post-middleware）模式。
 *
 * How it works:
 *   Laravel middleware uses the "onion model" (pipeline pattern). An incoming request
 *   passes through a series of middleware to reach the controller; the response then
 *   passes back through these middleware in reverse order. This middleware's `handle()`
 *   method first calls `$next($request)` to pass the request downstream and obtain a
 *   response object, then attaches security headers to that response, and finally
 *   returns the modified response. This is the "post-middleware" pattern.
 *
 * 使用场景：
 *   该中间件已在 `app/Http/Kernel.php` 中注册为全局中间件，对所有路由生效。
 *   适合一个需要处理用户数据、支持第三方登录且包含 reCAPTCHA 验证的捐赠平台。
 *
 * Use case:
 *   This middleware is registered as a global middleware in `app/Http/Kernel.php`
 *   and applies to all routes. It is suitable for a donation platform that handles
 *   user data, supports third-party login, and includes reCAPTCHA verification.
 *
 * 安全注意事项：
 *   - CSP 策略需要与前端实际加载的资源保持同步，否则会误阻断合法资源
 *   - 新增第三方 CDN/API 域名时，必须在此文件中同步更新对应的 CSP 指令
 *   - CSP 仍建议配合其他后端防护措施（输入验证、输出转义、CSRF Token 等）共同使用
 *
 * Security notes:
 *   - The CSP policy must stay in sync with the resources actually loaded by the
 *     frontend, otherwise legitimate resources may be incorrectly blocked
 *   - When adding new third-party CDN/API domains, the corresponding CSP directives
 *     must be updated in this file simultaneously
 *   - CSP should still be used together with other backend protection measures
 *     (input validation, output escaping, CSRF tokens, etc.)
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * 处理请求并附加安全响应头
     *
     * 该方法在请求通过后续中间件及控制器处理后，对响应对象进行二次加工，
     * 注入多层安全头部。所有头部设置完毕后返回修改后的 Response 对象。
     *
     * Handle the request and attach security response headers.
     *
     * This method performs secondary processing on the response object after
     * the request has passed through subsequent middleware and controllers,
     * injecting multiple layers of security headers. The modified Response
     * object is returned once all headers have been set.
     *
     * @param  Request  $request  当前 HTTP 请求实例
     * @param  Request  $request  Current HTTP request instance
     * @param  Closure  $next     下一个中间件/控制器的闭包
     * @param  Closure  $next     Closure for the next middleware/controller
     * @return Response           已附加安全头部的 HTTP 响应
     * @return Response           HTTP response with security headers attached
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 将请求传递给后续中间件链，获取原始响应对象
        // Pass the request through the subsequent middleware chain and obtain the raw response object
        $response = $next($request);

        // -------------------------------------------------------------------------
        // 1. Content-Security-Policy（内容安全策略，CSP）
        // 1. Content-Security-Policy (CSP)
        // -------------------------------------------------------------------------
        // CSP 是现代浏览器最重要的安全机制之一。它通过白名单方式限制页面可以加载
        // 和执行的资源来源，从而有效防御 XSS（跨站脚本攻击）、数据注入攻击等。
        // CSP is one of the most important security mechanisms in modern browsers. It uses
        // a whitelist approach to restrict the origins from which a page can load and
        // execute resources, effectively defending against XSS (cross-site scripting) and
        // data injection attacks.
        //
        // 原理：浏览器在解析页面时，会检查每个资源（脚本、样式、图片等）的来源
        // 是否匹配 CSP 中声明的白名单。任何不匹配的来源都会被浏览器拦截并拒绝加载。
        // 即使攻击者成功向页面注入恶意脚本标签，CSP 也可阻止其执行。
        //
        // Rationale: When parsing a page, the browser checks whether the origin of each
        // resource (scripts, styles, images, etc.) matches the whitelist declared in the
        // CSP. Any mismatched origin is blocked and refused by the browser. Even if an
        // attacker successfully injects a malicious script tag into the page, CSP can
        // prevent its execution.
        //
        // 各指令说明：
        //   default-src 'self'  — 默认策略：仅允许加载同源资源，其他指令未指定时生效
        //   script-src          — 脚本来源白名单：同源 + 内联脚本(reCAPTCHA需要) + reCAPTCHA/Gstatic CDN
        //   style-src           — 样式来源白名单：同源 + 内联样式 + Google Fonts + reCAPTCHA样式
        //   font-src            — 字体来源白名单：同源 + Google Fonts CDN
        //   img-src             — 图片来源白名单：同源 + data: URI（用于内联图片/Base64）
        //   frame-src           — iframe 来源白名单：同源 + reCAPTCHA 验证组件
        //   frame-ancestors     — 控制本页面能否被嵌入 iframe：'none' 禁止任何页面嵌入（防点击劫持）
        //   base-uri            — 限制 <base> 标签的 href 值，防止攻击者篡改相对路径
        //   form-action         — 限制表单提交的目标 URL，防止表单数据被重定向到恶意站点
        //
        // Directive explanation:
        //   default-src 'self'  — Default policy: only allow same-origin resources; applies when no other directive is specified
        //   script-src          — Script origin whitelist: same-origin + inline scripts (reCAPTCHA needs) + reCAPTCHA/Gstatic CDN
        //   style-src           — Style origin whitelist: same-origin + inline styles + Google Fonts + reCAPTCHA styles
        //   font-src            — Font origin whitelist: same-origin + Google Fonts CDN
        //   img-src             — Image origin whitelist: same-origin + data: URI (for inline images/Base64)
        //   frame-src           — iframe origin whitelist: same-origin + reCAPTCHA verification component
        //   frame-ancestors     — Control whether this page can be embedded in an iframe: 'none' denies all embedding (clickjacking protection)
        //   base-uri            — Restrict the href value of <base> tags, preventing attackers from tampering with relative paths
        //   form-action         — Restrict form submission target URLs, preventing form data from being redirected to malicious sites
        //
        // 注意：'unsafe-inline' 虽然降低了 CSP 的严格性，但为了兼容 reCAPTCHA
        // 和 Google Fonts 的内联样式/脚本，目前是必要的妥协。未来可考虑使用
        // nonce 或 hash 方式替代 'unsafe-inline' 以提升安全性。
        //
        // Note: Although 'unsafe-inline' reduces CSP strictness, it is a necessary
        // compromise for compatibility with reCAPTCHA and Google Fonts inline styles/scripts.
        // In the future, consider using nonce or hash approaches to replace 'unsafe-inline'
        // for improved security.
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://www.recaptcha.net https://www.google.com https://www.gstatic.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://www.gstatic.com https://cdn.jsdelivr.net; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: https://images.unsplash.com; "
            . "frame-src 'self' https://www.recaptcha.net https://www.google.com; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; " 
            . "form-action 'self'"
        );

        // -------------------------------------------------------------------------
        // 2. X-XSS-Protection（跨站脚本过滤器）
        // 2. X-XSS-Protection (Cross-site scripting filter)
        // -------------------------------------------------------------------------
        // 该头部启用旧版浏览器的内置 XSS 过滤器（主要用于 IE/旧版 Chrome）。
        // This header enables the built-in XSS filter in older browsers (mainly IE / older Chrome).
        //
        // 原理：当浏览器检测到反射型 XSS 攻击模式（URL 参数中的脚本出现在页面内容中）
        // 时，自动过滤或阻止页面渲染。
        //
        // Rationale: When the browser detects a reflected XSS attack pattern (script from
        // URL parameters appearing in page content), it automatically filters or blocks
        // page rendering.
        //
        // 取值说明：
        //   0          — 禁用过滤器
        //   1          — 启用过滤器，检测到攻击时会清理（sanitize）页面
        //   1; mode=block — 启用过滤器，检测到攻击时直接阻止页面加载（推荐）
        //
        // Value explanation:
        //   0          — Disable the filter
        //   1          — Enable the filter; sanitize the page when an attack is detected
        //   1; mode=block — Enable the filter; directly block page loading when an attack is detected (recommended)
        //
        // 注意：现代浏览器（Chrome/Edge）已弃用此头部，转而依赖 CSP 进行防护。
        // 但保留此配置可以兼容旧版浏览器。它与 CSP 并不冲突，而是形成纵深防御。
        //
        // Note: Modern browsers (Chrome/Edge) have deprecated this header in favor of CSP.
        // Retaining this configuration provides backward compatibility for older browsers.
        // It does not conflict with CSP but rather forms defense-in-depth.
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // -------------------------------------------------------------------------
        // 3. X-Content-Type-Options（MIME 类型嗅探防护）
        // 3. X-Content-Type-Options (MIME type sniffing protection)
        // -------------------------------------------------------------------------
        // 禁止浏览器对响应内容的 MIME 类型进行"嗅探"（MIME Sniffing）。
        // Prevent the browser from "sniffing" (MIME Sniffing) the MIME type of response content.
        //
        // 原理：浏览器默认会尝试自动检测文件的实际类型，如果 Content-Type 头声明的
        // 类型与实际内容不符，浏览器可能将其按检测到的类型处理。例如，一个被上传
        // 的文本文件如果包含 HTML 代码，浏览器可能将其当作 HTML 执行，导致 XSS 攻击。
        //
        // Rationale: Browsers by default try to auto-detect the actual file type. If the
        // Content-Type header's declared type does not match the actual content, the browser
        // may process it as the detected type. For example, an uploaded text file containing
        // HTML code could be executed as HTML by the browser, leading to an XSS attack.
        //
        // 取值：nosniff — 强制浏览器严格遵循服务器声明的 Content-Type，禁止猜测。
        // 这是唯一有效的值。
        //
        // Value: nosniff — Force the browser to strictly follow the server-declared
        // Content-Type, prohibiting guessing. This is the only valid value.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // -------------------------------------------------------------------------
        // 4. X-Frame-Options（点击劫持防护）
        // 4. X-Frame-Options (Clickjacking protection)
        // -------------------------------------------------------------------------
        // 控制页面是否允许被嵌入到 `<iframe>` 中，用于防御点击劫持（Clickjacking）攻击。
        // Control whether the page can be embedded in an `<iframe>`, to defend against clickjacking attacks.
        //
        // 原理：攻击者在其网站上用一个透明的 iframe 覆盖本网站页面，诱使用户在
        // 不知情的情况下点击本网站的按钮或链接，执行其本不想执行的操作（如转账、
        // 删除数据等）。通过禁止页面被嵌入 iframe，可以从根本上阻止此类攻击。
        //
        // Rationale: An attacker overlays a transparent iframe of this site's page on
        // their own website, tricking users into clicking buttons or links without their
        // knowledge, performing actions they did not intend (such as transferring funds,
        // deleting data, etc.). By preventing the page from being embedded in an iframe,
        // this class of attack can be stopped at the root.
        //
        // 取值说明：
        //   DENY        — 完全禁止页面被任何网站以 iframe 嵌入（最严格）
        //   SAMEORIGIN  — 仅允许同源页面嵌入（如需内部 iframe 通信可用此值）
        //   ALLOW-FROM  — 仅允许指定域名嵌入（已废弃，现代浏览器不支持）
        //
        // Value explanation:
        //   DENY        — Completely forbid the page from being embedded in any iframe (strictest)
        //   SAMEORIGIN  — Only allow same-origin pages to embed (use this if internal iframe communication is needed)
        //   ALLOW-FROM  — Only allow a specific domain to embed (deprecated, not supported by modern browsers)
        //
        // 注意：本系统使用 CSP 的 frame-ancestors 'none' 指令也能达到同样效果，
        // 且 CSP 方式优先级更高。同时设置两者可提供向后兼容保护。
        //
        // Note: This system's CSP frame-ancestors 'none' directive achieves the same
        // effect, and CSP takes higher priority. Setting both provides backward-compatible protection.
        $response->headers->set('X-Frame-Options', 'DENY');

        // -------------------------------------------------------------------------
        // 5. Referrer-Policy（引用来源策略）
        // 5. Referrer-Policy
        // -------------------------------------------------------------------------
        // 控制浏览器在跳转/请求时，是否在 Referer 请求头中携带当前页面地址。
        // Control whether the browser includes the current page URL in the Referer
        // request header during navigation/requests.
        //
        // 原理：默认情况下，浏览器会在导航跳转或资源请求时附带上一个页面的完整 URL
        // 作为 Referer 头。这可能导致敏感信息泄露——例如，URL 中可能包含会话 Token、
        // 用户 ID、搜索关键词等隐私数据被发送到第三方站点。
        //
        // Rationale: By default, browsers include the full URL of the previous page
        // as the Referer header during navigation or resource requests. This can leak
        // sensitive information — for example, URLs may contain session tokens,
        // user IDs, search keywords, and other private data sent to third-party sites.
        //
        // 取值说明：
        //   strict-origin-when-cross-origin（推荐值）：
        //     - 同源请求：发送完整 URL（路径+参数+查询字符串）
        //     - 跨域请求：仅发送源（协议+域名，不含路径和参数）
        //     - HTTPS → HTTP（降级）：不发送任何 Referer
        //
        // Value explanation:
        //   strict-origin-when-cross-origin (recommended):
        //     - Same-origin requests: send full URL (path + params + query string)
        //     - Cross-origin requests: send only the origin (protocol + domain, no path or params)
        //     - HTTPS → HTTP (downgrade): do not send any Referer
        //
        // 这个设置在安全性和分析需求之间取得了较好的平衡——不向第三方泄露路径细节，
        // 同时仍然提供域名级别的来源信息用于分析统计。
        //
        // This setting strikes a good balance between security and analytics needs —
        // it does not leak path details to third parties, while still providing
        // domain-level origin information for analytics and statistics.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // -------------------------------------------------------------------------
        // 6. Permissions-Policy（权限策略，原名 Feature-Policy）
        // 6. Permissions-Policy (formerly Feature-Policy)
        // -------------------------------------------------------------------------
        // 控制浏览器允许当前页面（及其嵌入的 iframe）使用哪些浏览器 API/功能。
        // Control which browser APIs/features the current page (and its embedded iframes) are allowed to use.
        //
        // 原理：即使攻击者通过 XSS 或其他方式获取了页面控制权，该策略仍能阻止
        // 恶意脚本调用敏感硬件（摄像头、麦克风）或获取隐私数据（地理位置）。
        //
        // Rationale: Even if an attacker gains page control through XSS or other means,
        // this policy still prevents malicious scripts from accessing sensitive hardware
        // (camera, microphone) or obtaining private data (geolocation).
        //
        // 当前配置：
        //   camera=()       — 禁止页面和所有 iframe 使用摄像头
        //   microphone=()   — 禁止页面和所有 iframe 使用麦克风
        //   geolocation=()  — 禁止页面和所有 iframe 获取地理位置
        //
        // Current configuration:
        //   camera=()       — Deny camera access for the page and all iframes
        //   microphone=()   — Deny microphone access for the page and all iframes
        //   geolocation=()  — Deny geolocation access for the page and all iframes
        //
        // 使用场景：FoodShare 是一个食物捐赠平台，不需要访问用户的摄像头、
        // 麦克风或地理位置。禁用这些功能可以缩小攻击面，防止被恶意利用。
        //
        // Use case: FoodShare is a food donation platform that does not require access
        // to the user's camera, microphone, or geolocation. Disabling these features
        // shrinks the attack surface and prevents malicious exploitation.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // -------------------------------------------------------------------------
        // 7. 移除 X-Powered-By（服务器信息隐藏）
        // 7. Remove X-Powered-By (Server information hiding)
        // -------------------------------------------------------------------------
        // 移除 PHP/Laravel 默认输出的服务器标识头。
        // Remove the server identification header output by PHP/Laravel by default.
        //
        // 原理：默认情况下，PHP 会在响应头中附加 `X-Powered-By: PHP/x.y.z`，
        // 一些框架还会追加框架名和版本号。这些信息会暴露服务端技术栈和版本，
        // 攻击者可以利用已知版本漏洞进行定向攻击。
        //
        // Rationale: By default, PHP appends `X-Powered-By: PHP/x.y.z` to response
        // headers, and some frameworks append their name and version as well. This
        // information exposes the backend technology stack and version, allowing
        // attackers to launch targeted attacks using known version vulnerabilities.
        //
        // 移除该头部后，攻击者无法通过简单手段确定后端技术栈和版本，增加了
        // 攻击的探测成本——这是纵深防御策略中的"信息隐蔽"环节。
        //
        // After removing this header, attackers cannot easily identify the backend
        // technology stack and version, increasing the cost of reconnaissance — this
        // is the "information concealment" layer of the defense-in-depth strategy.
        $response->headers->remove('X-Powered-By');

        // 返回已附加全面安全头部的响应对象
        // Return the response object with all security headers attached
        return $response;
    }
}
