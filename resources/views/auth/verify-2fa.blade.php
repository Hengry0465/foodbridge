{{--
  2FA（双因素认证）验证码输入页面

  【作用】用户注册后在此页面输入邮箱收到的 6 位数字验证码。
  【提交目标】route('verify2fa.verify') → TwoFAController@verify
  【重发目标】route('verify2fa.resend') → TwoFAController@resend
  【功能】6 个独立数字输入框（自动聚焦跳转）、15 分钟倒计时、重发链接

  2FA (Two-Factor Authentication) verification code input page.

  [Purpose] After registration, users enter the 6-digit verification code
  sent to their email on this page.
  [Submit target] route('verify2fa.verify') → TwoFAController@verify
  [Resend target] route('verify2fa.resend') → TwoFAController@resend
  [Features] 6 individual digit input boxes (auto-focus jump),
  15-minute countdown timer, resend link
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Verify Email | FoodShare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #059669;
      --primary-dark: #047857;
      --primary-soft: #ECFDF5;
      --accent: #D97706;
      --text: #1C1917;
      --text-secondary: #44403C;
      --muted: #78716C;
      --line: #E7E0D8;
      --danger: #DC2626;
      --danger-soft: #FEF2F2;
      --success-soft: #ECFDF5;
      --radius-sm: 10px;
      --radius: 16px;
      --radius-xl: 28px;
      --shadow-lg: 0 20px 56px rgba(28,25,23,0.10), 0 4px 12px rgba(28,25,23,0.04);
      --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
      --font-heading: 'Lora', ui-serif, Georgia, serif;
      --font-body: 'Raleway', ui-sans-serif, system-ui, sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100dvh;
      font-family: var(--font-body);
      color: var(--text);
      background: radial-gradient(ellipse 80% 60% at 12% 8%, rgba(217,119,6,0.08), transparent 35%),
                  radial-gradient(ellipse 70% 50% at 88% 92%, rgba(5,150,105,0.10), transparent 35%),
                  linear-gradient(175deg, #FEFAF5 0%, #F8F5F0 40%, #F0F5F1 100%);
      display: grid; place-items: center;
      padding: 32px 20px;
      -webkit-font-smoothing: antialiased;
    }
    .card {
      width: min(460px, 100%);
      background: rgba(255,255,255,0.94);
      border: 1px solid rgba(5,150,105,0.08);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      padding: 44px 36px;
      text-align: center;
    }
    .icon-circle {
      width: 64px; height: 64px;
      border-radius: 50%;
      background: var(--primary-soft);
      display: inline-flex; align-items: center; justify-content: center;
      margin-bottom: 20px;
      color: var(--primary);
    }
    h1 {
      font-family: var(--font-heading);
      font-size: 1.5rem;
      margin-bottom: 8px;
    }
    .subtitle { color: var(--muted); font-size: 0.94rem; margin-bottom: 24px; line-height: 1.6; }
    .email-badge {
      display: inline-block;
      padding: 6px 16px;
      background: var(--primary-soft);
      color: var(--primary-dark);
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 28px;
    }

    .code-inputs {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-bottom: 24px;
    }
    .code-inputs input {
      width: 48px; height: 56px;
      border: 2px solid var(--line);
      border-radius: var(--radius-sm);
      text-align: center;
      font-size: 1.4rem;
      font-weight: 700;
      font-family: 'Courier New', monospace;
      color: var(--text);
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    .code-inputs input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(5,150,105,0.12);
    }

    .alert {
      padding: 12px 16px; border-radius: var(--radius-sm);
      margin-bottom: 20px; font-size: 0.87rem; font-weight: 500;
      display: flex; align-items: center; gap: 10px; text-align: left;
    }
    .alert-error { background: var(--danger-soft); color: #991B1B; border: 1px solid #FECACA; }
    .alert-success { background: var(--success-soft); color: #064E3B; border: 1px solid #A7F3D0; }
    .alert-warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }

    .btn {
      width: 100%; padding: 14px; border: 0; border-radius: 14px;
      font-family: var(--font-body); font-weight: 700; font-size: 0.98rem;
      cursor: pointer; transition: all 0.18s;
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary {
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      box-shadow: 0 12px 24px rgba(5,150,105,0.22);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(5,150,105,0.30); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .timer {
      font-family: 'Courier New', monospace;
      color: var(--accent);
      font-size: 0.95rem;
      font-weight: 600;
      margin-top: 18px;
    }
    .timer.expired { color: var(--danger); }

    .text-link {
      margin-top: 20px; font-size: 0.87rem; color: var(--muted);
    }
    .text-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .text-link a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
      .card { padding: 32px 20px; }
      .code-inputs input { width: 42px; height: 50px; font-size: 1.2rem; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-circle">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>
    <h1>Verify Your Email</h1>
    <p class="subtitle">
      Enter the 6-digit code we sent to<br>
      <span class="email-badge">{{ $email }}</span>
    </p>

    <!-- Messages -->
    @if (session('success'))
      <div class="alert alert-success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        {{ session('success') }}
      </div>
    @endif
    @if (session('warning'))
      <div class="alert alert-warning">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        {{ session('warning') }}
      </div>
    @endif
    @if ($errors->any())
      @foreach ($errors->all() as $error)
        <div class="alert alert-error">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          {{ $error }}
        </div>
      @endforeach
    @endif

    <form id="verifyForm" action="{{ route('verify2fa.verify') }}" method="POST">
      @csrf
      <input type="hidden" name="email" value="{{ $email }}" />

      <div class="code-inputs" id="codeInputs">
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
      </div>

      <button type="submit" class="btn btn-primary" id="verifyBtn">Verify Email</button>
    </form>

    <p class="timer" id="countdown">
      Code expires in <span id="timerDisplay">15:00</span>
    </p>

    <p class="text-link">
      Didn't receive the code?
      <a href="{{ route('verify2fa.resend') }}"
         onclick="event.preventDefault(); document.getElementById('resendForm').submit();">
        Resend Code
      </a>
    </p>
  </div>

  <form id="resendForm" action="{{ route('verify2fa.resend') }}" method="POST" style="display:none;">
    @csrf
  </form>

  <script>
    (function () {
      /* ---- Auto-focus & digit input ---- */
      const inputs = document.querySelectorAll('#codeInputs input');

      inputs.forEach(function (input, idx) {
        input.addEventListener('input', function () {
          const val = this.value.replace(/[^0-9]/g, '');
          this.value = val.slice(0, 1);
          if (val && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
          }
        });

        input.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace' && !this.value && idx > 0) {
            inputs[idx - 1].focus();
          }
          if (e.key === 'ArrowLeft' && idx > 0) {
            inputs[idx - 1].focus();
            inputs[idx - 1].select();
          }
          if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            inputs[idx + 1].select();
          }
        });

        input.addEventListener('paste', function (e) {
          e.preventDefault();
          const paste = (e.clipboardData || window.clipboardData).getData('text');
          const digits = paste.replace(/[^0-9]/g, '').slice(0, 6);
          digits.split('').forEach(function (d, i) {
            if (inputs[i]) inputs[i].value = d;
          });
          const focusIdx = Math.min(digits.length, inputs.length - 1);
          inputs[focusIdx].focus();
        });
      });

      /* ---- Countdown Timer (15 min) ---- */
      const timerDisplay = document.getElementById('timerDisplay');
      const countdown = document.getElementById('countdown');
      let totalSeconds = 15 * 60; // 15 minutes

      function updateTimer() {
        if (totalSeconds <= 0) {
          countdown.classList.add('expired');
          timerDisplay.textContent = 'Expired';
          return;
        }
        const mins = Math.floor(totalSeconds / 60);
        const secs = totalSeconds % 60;
        timerDisplay.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        totalSeconds--;
      }

      updateTimer();
      setInterval(updateTimer, 1000);

      /* ---- Form submit: combine 6 digit inputs into single code field ---- */
      const verifyForm = document.getElementById('verifyForm');
      verifyForm.addEventListener('submit', function () {
        // Remove the individual code[] inputs and replace with a single 'code' field
        const codeArr = [];
        inputs.forEach(function (inp) { codeArr.push(inp.value); });
        const fullCode = codeArr.join('');

        // Clear all and add single hidden
        inputs.forEach(function (inp) { inp.name = ''; });
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'code';
        hidden.value = fullCode;
        verifyForm.appendChild(hidden);

        const btn = document.getElementById('verifyBtn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';
      });
    })();
  </script>
</body>
</html>
