{{--
  注册成功页面

  【作用】2FA 验证通过后显示成功弹窗，包含：
    - 绿色渐变头部 + 动画勾选图标
    - 撒花（confetti）动画效果
    - 欢迎信息（显示用户名字）
    - "Go to Login" 按钮
    - 3 秒自动跳转倒计时
  【路由】route('registered') — 无需登录

  Registration success page

  [Purpose] After 2FA verification passes, display a success modal containing:
    - Green gradient header + animated checkmark icon
    - Confetti animation effect
    - Welcome message (showing user name)
    - "Go to Login" button
    - 3-second auto-redirect countdown
  [Route] route('registered') — no login required
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration Successful | FoodShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #059669;
      --primary-dark: #047857;
      --accent: #D97706;
      --text: #1C1917;
      --muted: #78716C;
      --radius: 16px;
      --radius-xl: 28px;
      --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
      --font-heading: 'Lora', ui-serif, Georgia, serif;
      --font-body: 'Raleway', ui-sans-serif, system-ui, sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100dvh;
      font-family: var(--font-body);
      background:
        radial-gradient(ellipse 80% 60% at 12% 8%, rgba(217,119,6,0.06), transparent 35%),
        radial-gradient(ellipse 70% 50% at 88% 92%, rgba(5,150,105,0.08), transparent 35%),
        linear-gradient(175deg, #FEFAF5 0%, #F8F5F0 40%, #F0F5F1 100%);
      display: grid; place-items: center;
      padding: 32px 20px;
      -webkit-font-smoothing: antialiased;
    }

    /* Backdrop overlay */
    .overlay {
      position: fixed; inset: 0;
      background: rgba(28, 25, 23, 0.35);
      backdrop-filter: blur(4px);
      z-index: 10;
      animation: fadeIn 0.3s ease;
    }

    /* Alert card */
    .alert-card {
      position: relative;
      z-index: 11;
      width: min(440px, 100%);
      background: #fff;
      border-radius: var(--radius-xl);
      box-shadow: 0 24px 64px rgba(28,25,23,0.16), 0 4px 12px rgba(28,25,23,0.06);
      padding: 0;
      overflow: hidden;
      animation: popIn 0.45s var(--ease-spring);
    }

    @keyframes popIn {
      from { opacity: 0; transform: scale(0.85) translateY(20px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    /* Green top bar */
    .alert-header {
      background: linear-gradient(135deg, var(--primary-dark), var(--primary));
      padding: 40px 32px 32px;
      text-align: center;
    }

    /* Animated checkmark */
    .check-circle {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: rgba(255,255,255,0.18);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      animation: checkPop 0.5s var(--ease-spring) 0.15s both;
    }
    @keyframes checkPop {
      from { opacity: 0; transform: scale(0); }
      to   { opacity: 1; transform: scale(1); }
    }
    .check-circle svg {
      width: 38px; height: 38px;
      stroke: #fff; stroke-width: 3;
      animation: drawCheck 0.4s ease 0.4s both;
      stroke-dasharray: 40;
      stroke-dashoffset: 40;
    }
    @keyframes drawCheck {
      to { stroke-dashoffset: 0; }
    }

    .alert-header h2 {
      font-family: var(--font-heading);
      font-size: 1.6rem;
      color: #fff;
      margin-bottom: 6px;
    }

    /* Body */
    .alert-body {
      padding: 32px;
      text-align: center;
    }
    .alert-body .welcome-msg {
      font-size: 1.05rem;
      color: var(--text);
      line-height: 1.7;
      margin-bottom: 12px;
    }
    .alert-body .welcome-msg strong {
      color: var(--primary-dark);
    }
    .alert-body .hint {
      font-size: 0.9rem;
      color: var(--muted);
      margin-bottom: 28px;
    }

    /* Button */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 15px 24px;
      border: 0;
      border-radius: 14px;
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      box-shadow: 0 12px 24px rgba(5,150,105,0.22);
    }
    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 30px rgba(5,150,105,0.30);
    }
    .btn:active { transform: translateY(0); }
    .btn:focus-visible {
      outline: none;
      box-shadow: 0 0 0 4px rgba(5,150,105,0.2), 0 12px 24px rgba(5,150,105,0.22);
    }

    /* Confetti dots */
    .confetti {
      position: fixed;
      z-index: 12;
      pointer-events: none;
    }
    .confetti span {
      position: absolute;
      width: 10px; height: 10px;
      border-radius: 3px;
      animation: confettiFall 2.5s ease-out forwards;
      opacity: 0;
    }
    @keyframes confettiFall {
      0%   { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
      100% { opacity: 0; transform: translateY(400px) rotate(720deg) scale(0); }
    }

    /* Countdown ring */
    .countdown-ring {
      display: inline-block;
      width: 24px; height: 24px;
      border: 2.5px solid rgba(255,255,255,0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-left: 6px;
      vertical-align: middle;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Auto-redirect text */
    .auto-redirect {
      margin-top: 16px;
      font-size: 0.85rem;
      color: var(--muted);
    }
    .auto-redirect span { font-weight: 600; color: var(--primary); }
  </style>
</head>
<body>

  <!-- Simple confetti -->
  <div class="confetti" aria-hidden="true">
    <span style="left:10%;top:20%;background:#059669;animation-delay:0.1s"></span>
    <span style="left:30%;top:10%;background:#D97706;animation-delay:0.3s"></span>
    <span style="left:50%;top:25%;background:#10B981;animation-delay:0.5s"></span>
    <span style="left:70%;top:15%;background:#F59E0B;animation-delay:0.2s"></span>
    <span style="left:85%;top:22%;background:#059669;animation-delay:0.4s"></span>
    <span style="left:20%;top:5%;background:#D97706;animation-delay:0.6s"></span>
    <span style="left:60%;top:8%;background:#10B981;animation-delay:0.35s"></span>
    <span style="left:90%;top:28%;background:#F59E0B;animation-delay:0.15s"></span>
  </div>

  <!-- Backdrop -->
  <div class="overlay"></div>

  <!-- Alert Card -->
  <div class="alert-card" role="dialog" aria-modal="true" aria-labelledby="alertTitle">
    <div class="alert-header">
      <div class="check-circle">
        <svg viewBox="0 0 24 24" fill="none">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h2 id="alertTitle">Registration Successful!</h2>
    </div>

    <div class="alert-body">
      <p class="welcome-msg">
        Welcome to <strong>FoodShare</strong>, {{ session('firstname', 'Friend') }}!
        <br>Your account has been created and verified.
      </p>
      <p class="hint">
        You are now part of a community dedicated to sharing food and spreading kindness.
        Sign in now to start making a difference.
      </p>

      <a href="{{ route('login') }}" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/>
          <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Go to Login
      </a>

      <p class="auto-redirect">
        Auto-redirecting in <span id="countdown">3</span>s
      </p>
    </div>
  </div>

  <script>
    (function () {
      var count = 3;
      var cd = document.getElementById('countdown');
      var timer = setInterval(function () {
        count--;
        if (count <= 0) {
          clearInterval(timer);
          window.location.href = '{{ route('login') }}';
        } else {
          cd.textContent = count;
        }
      }, 1000);
    })();
  </script>
</body>
</html>
