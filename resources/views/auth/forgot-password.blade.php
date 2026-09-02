{{--
  ============================================================
  忘记密码页面 — 第一步：输入邮箱
  ============================================================

  【页面作用】
  用户在登录页点击"Forgot password?"后跳转到此页面。
  输入注册邮箱后，系统会发送一封包含 6 位验证码的邮件。

  【所属模块】认证系统 / 密码重置流程第一步

  【业务流程】
  本页 → 输入邮箱 → POST /forgot-password → 发送验证码邮件
       → 重定向到 /reset-password（验证码输入 + 新密码设置页面）

  【表单提交目标】route('password.send-code') → ForgotPasswordController@sendCode

  【依赖】使用 Lora + Raleway 字体，Organic Biophilic 设计系统
--}}

{{--
  ============================================================
  Forgot Password Page — Step 1: Enter Email
  ============================================================

  [Purpose]
  The user lands here after clicking "Forgot password?" on the login page.
  After entering their registered email, the system sends a 6-digit verification code.

  [Module] Authentication System / Password Reset Flow Step 1

  [Business Flow]
  This page → Enter email → POST /forgot-password → Send verification code email
           → Redirect to /reset-password (code entry + new password page)

  [Form Action] route('password.send-code') → ForgotPasswordController@sendCode

  [Dependencies] Uses Lora + Raleway fonts, Organic Biophilic design system
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Forgot Password | FoodShare</title>
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #059669; --primary-dark: #047857; --accent: #D97706;
      --text: #1C1917; --muted: #78716C; --danger: #DC2626;
      --radius: 16px; --radius-xl: 28px; --radius-sm: 10px;
      --shadow-lg: 0 20px 56px rgba(28,25,23,0.10);
      --ease-spring: cubic-bezier(0.34,1.56,0.64,1);
      --font-heading: 'Lora',ui-serif,Georgia,serif;
      --font-body: 'Raleway',ui-sans-serif,system-ui,sans-serif;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{
      min-height:100dvh;font-family:var(--font-body);
      background:radial-gradient(ellipse 80% 60% at 12% 8%,rgba(217,119,6,0.06),transparent 35%),radial-gradient(ellipse 70% 50% at 88% 92%,rgba(5,150,105,0.08),transparent 35%),linear-gradient(175deg,#FEFAF5 0%,#F8F5F0 40%,#F0F5F1 100%);
      display:grid;place-items:center;padding:32px 20px;-webkit-font-smoothing:antialiased;
    }
    .card{
      width:min(440px,100%);background:#fff;border-radius:var(--radius-xl);
      box-shadow:var(--shadow-lg);overflow:hidden;
    }
    .card-header{
      background:linear-gradient(135deg,var(--primary-dark),var(--primary));
      padding:36px 32px 28px;text-align:center;color:#fff;
    }
    .card-header h1{font-family:var(--font-heading);font-size:1.5rem;margin-bottom:6px}
    .card-header p{font-size:0.92rem;opacity:0.82}

    .card-body{padding:32px}

    .field{margin-bottom:20px}
    .field label{display:block;font-weight:600;font-size:0.87rem;color:var(--text);margin-bottom:6px}
    .field input{
      width:100%;height:48px;border:1.5px solid #E7E0D8;border-radius:var(--radius-sm);
      padding:0 16px;font-family:var(--font-body);font-size:0.95rem;outline:none;
      transition:border-color .15s,box-shadow .15s;
    }
    .field input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(5,150,105,0.12)}

    .alert{padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:20px;font-size:0.87rem;display:flex;align-items:center;gap:10px}
    .alert-error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
    .alert-success{background:#ECFDF5;color:#064E3B;border:1px solid #A7F3D0}

    .btn{
      width:100%;padding:14px;border:0;border-radius:14px;font-family:var(--font-body);
      font-weight:700;font-size:1rem;cursor:pointer;color:#fff;
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      box-shadow:0 12px 24px rgba(5,150,105,0.22);transition:all .2s;
      display:flex;align-items:center;justify-content:center;gap:8px;
    }
    .btn:hover{transform:translateY(-1px);box-shadow:0 16px 30px rgba(5,150,105,0.30)}
    .btn:disabled{opacity:0.6;cursor:not-allowed;transform:none}

    .text-link{text-align:center;margin-top:20px;font-size:0.88rem;color:var(--muted)}
    .text-link a{color:var(--primary);font-weight:600;text-decoration:none}
    .text-link a:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <h1>Forgot Password</h1>
      <p>Enter your email to receive a reset code</p>
    </div>
    <div class="card-body">
      @if (session('success'))
        <div class="alert alert-success">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          {{ session('success') }}
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

      <form method="POST" action="{{ route('password.send-code') }}" id="forgotForm">
        @csrf
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}"
                 placeholder="name@example.com" autocomplete="email" required autofocus />
        </div>
        <button type="submit" class="btn" id="submitBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          Send Reset Code
        </button>
      </form>

      <p class="text-link"><a href="{{ route('login') }}">&larr; Back to Login</a></p>
    </div>
  </div>

  <script>
    document.getElementById('forgotForm').addEventListener('submit', function(){
      var btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.textContent = 'Sending...';
    });
  </script>
</body>
</html>
