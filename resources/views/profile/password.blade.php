{{--
  ============================================================
  修改密码页 — Change Password
  ============================================================

  Change Password Page — Change Password.

  【页面作用】
  登录用户在此页面修改自己的登录密码。必须输入"当前密码"以确认本人，
  新密码须符合 8 位以上 + 包含大小写字母和数字的强度规则，且两次输入
  必须一致；新密码不能与当前密码相同（防"改了个寂寞"）。

  [Page Purpose]
  Authenticated users change their login password on this page. They must
  enter the "current password" to confirm identity. The new password must
  satisfy strength rules (8+ chars with upper/lowercase + digit), the two
  new-password inputs must match, and the new password must differ from
  the current one.

  【数据来源】仅从 session 中取 success / error 消息，无业务数据传入
  【数据提交】POST /profile/password → ProfileController@updatePassword →
              AuthService::updatePasswordForAuthenticatedUser

  [Data Source]   Only success / error flash messages from session; no
                  business data passed in.
  [Submission]    POST /profile/password → ProfileController@updatePassword
                  → AuthService::updatePasswordForAuthenticatedUser.

  【需要认证】此页面受 auth 中间件保护，未登录用户会被重定向到登录页。

  [Authentication Required] Protected by the auth middleware; unauthenticated
                            users are redirected to the login page.

  【页面结构】
    1. Navbar（sticky）：Logo | 用户名 + 角色 | 头像 | Profile | Sign Out
    2. 主卡片：标题 + 副标题 + flash 提示条
    3. 表单卡片：current password + new password + retype new password
       - 实时密码强度条件指示器（4 条规则实时变绿/红）
       - 实时两次输入是否匹配的提示
       - 密码可见性 toggle（眼睛图标）
    4. 提交区：Cancel 链接 + Update Password 按钮
    5. Footer：版权信息

  [Page Structure]
    1. Navbar (sticky): Logo | Username + Role | Avatar | Profile | Sign Out
    2. Main card: title + subtitle + flash banner
    3. Form card: current + new + retype password
       - Live strength requirements indicator (4 rules turn green/red live)
       - Live hint for new/retype match
       - Password visibility toggle (eye icon)
    4. Submit zone: Cancel link + Update Password button
    5. Footer: copyright info

  【设计系统】与 home / profile/edit 一致：Organic Biophilic、Lora+Raleway、
                #059669+#D97706；新增 .req-list 等密码强度专用样式。

  [Design System] Consistent with home / profile/edit: Organic Biophilic,
                  Lora + Raleway, #059669 + #D97706; new .req-list styles
                  added for password strength feedback.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="description" content="FoodShare — Change your password" />
  <title>FoodShare | Change Password</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <style>
    :root {
      --primary: #059669; --primary-dark: #047857; --primary-soft: #ECFDF5;
      --primary-glow: rgba(5, 150, 105, 0.18); --accent: #D97706;
      --accent-dark: #B45309; --accent-soft: #FFFBEB;
      --text: #1C1917; --text-secondary: #44403C; --muted: #78716C;
      --line: #E7E0D8; --surface: #FFFFFF;
      --surface-glass: rgba(255, 255, 255, 0.94); --bg: #FEFAF5;
      --danger: #DC2626; --danger-soft: #FEF2F2;
      --success: #059669; --success-soft: #ECFDF5;

      --radius-sm: 10px; --radius: 16px; --radius-lg: 22px; --radius-xl: 28px;
      --radius-organic: 40% 60% 60% 40% / 40% 40% 60% 60%;

      --shadow-sm: 0 1px 3px rgba(28,25,23,0.06);
      --shadow: 0 8px 32px rgba(28,25,23,0.07), 0 2px 6px rgba(28,25,23,0.04);
      --shadow-lg: 0 20px 56px rgba(28,25,23,0.10), 0 4px 12px rgba(28,25,23,0.04);

      --ease-out: cubic-bezier(0.16,1,0.3,1);
      --ease-spring: cubic-bezier(0.34,1.56,0.64,1);
      --transition-fast: 150ms var(--ease-out);
      --transition: 250ms var(--ease-out);

      --font-heading: 'Lora',ui-serif,Georgia,serif;
      --font-body: 'Raleway',ui-sans-serif,system-ui,sans-serif;
    }

    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{font-size:16px;scroll-behavior:smooth}
    body{
      min-height:100dvh;font-family:var(--font-body);color:var(--text);
      background:
        radial-gradient(ellipse 80% 60% at 12% 8%,rgba(217,119,6,0.06),transparent 35%),
        radial-gradient(ellipse 70% 50% at 88% 92%,rgba(5,150,105,0.08),transparent 35%),
        linear-gradient(175deg,#FEFAF5 0%,#F8F5F0 40%,#F0F5F1 100%);
      -webkit-font-smoothing:antialiased;
    }

    /* ===== Navbar ===== */
    .navbar{
      position:sticky;top:0;z-index:50;
      background:rgba(255,255,255,0.85);backdrop-filter:blur(16px);
      -webkit-backdrop-filter:blur(16px);
      border-bottom:1px solid rgba(5,150,105,0.08);
      padding:0 32px;
    }
    .navbar-inner{
      max-width:1200px;margin:0 auto;height:64px;
      display:flex;align-items:center;justify-content:space-between;
    }
    .nav-logo{
      display:flex;align-items:center;gap:10px;
      font-family:var(--font-heading);font-size:1.1rem;font-weight:700;
      color:var(--primary-dark);text-decoration:none;
    }
    .nav-logo-mark{
      width:38px;height:38px;display:grid;place-items:center;
      border-radius:10px;color:#fff;
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      box-shadow:0 8px 20px rgba(5,150,105,0.25);
    }
    .nav-user{display:flex;align-items:center;gap:10px}
    .nav-user-info{text-align:right}
    .nav-user-info .name{font-weight:600;font-size:0.92rem;color:var(--text)}
    .nav-user-info .role{font-size:0.75rem;color:var(--muted);text-transform:capitalize}
    .nav-avatar{
      width:42px;height:42px;border-radius:50%;
      background:linear-gradient(135deg,var(--accent),var(--primary));
      display:grid;place-items:center;
      color:#fff;font-weight:700;font-size:1.1rem;
      box-shadow:0 4px 12px rgba(5,150,105,0.20);
    }
    .nav-link{
      display:flex;align-items:center;gap:6px;
      padding:8px 14px;border:1.5px solid var(--line);border-radius:10px;
      background:#fff;color:var(--text);font-family:var(--font-body);
      font-size:0.84rem;font-weight:600;cursor:pointer;
      text-decoration:none;transition:all var(--transition-fast);
    }
    .nav-link:hover{color:var(--primary-dark);border-color:var(--primary);background:var(--primary-soft)}
    .nav-link.active{color:var(--primary-dark);border-color:var(--primary);background:var(--primary-soft)}
    .nav-logout{
      display:flex;align-items:center;gap:6px;
      padding:8px 14px;border:1.5px solid var(--line);border-radius:10px;
      background:#fff;color:var(--muted);font-family:var(--font-body);
      font-size:0.84rem;font-weight:600;cursor:pointer;
      text-decoration:none;transition:all var(--transition-fast);
    }
    .nav-logout:hover{color:var(--danger);border-color:var(--danger);background:var(--danger-soft)}

    /* ===== Main Content ===== */
    .main{max-width:760px;margin:0 auto;padding:40px 32px 60px}

    /* ===== Back Link ===== */
    .back-link{
      display:inline-flex;align-items:center;gap:6px;
      margin-bottom:18px;padding:6px 12px;border-radius:8px;
      color:var(--muted);font-size:0.84rem;font-weight:600;
      text-decoration:none;transition:all var(--transition-fast);
    }
    .back-link:hover{color:var(--primary-dark);background:var(--primary-soft)}

    /* ===== Page Header ===== */
    .page-header{margin-bottom:28px}
    .page-eyebrow{
      display:inline-flex;align-items:center;gap:8px;
      margin-bottom:14px;padding:6px 14px;
      border:1px solid var(--primary-soft);border-radius:999px;
      background:var(--primary-soft);color:var(--primary-dark);
      font-size:0.78rem;font-weight:600;letter-spacing:0.4px;
    }
    .page-eyebrow .dot{
      width:6px;height:6px;border-radius:50%;
      background:var(--primary);box-shadow:0 0 8px var(--primary-glow);
    }
    .page-header h1{
      font-family:var(--font-heading);
      font-size:clamp(1.6rem,3vw,2.1rem);line-height:1.2;
      letter-spacing:-0.6px;margin-bottom:8px;
    }
    .page-header p{color:var(--muted);font-size:0.95rem;line-height:1.6;max-width:560px}

    /* ===== Form Card ===== */
    .profile-form-card{
      background:var(--surface);border-radius:var(--radius-xl);
      padding:36px 32px;box-shadow:var(--shadow);
      border:1px solid rgba(5,150,105,0.06);
      margin-bottom:24px;
    }
    .form-section{margin-bottom:28px}
    .form-section:last-child{margin-bottom:0}
    .section-title{
      font-family:var(--font-heading);
      font-size:1.05rem;font-weight:600;
      color:var(--text);margin-bottom:4px;
    }
    .section-subtitle{font-size:0.84rem;color:var(--muted);margin-bottom:18px}

    /* ===== Password Fields ===== */
    .field{margin-bottom:18px}
    .field:last-child{margin-bottom:0}
    .field label{
      display:block;margin-bottom:7px;
      font-size:0.84rem;font-weight:600;color:var(--text-secondary);
    }
    .input-wrap{position:relative}
    .input{
      width:100%;height:48px;padding:0 48px 0 14px;
      border:1.5px solid var(--line);border-radius:12px;
      background:#fff;color:var(--text);
      font-family:var(--font-body);font-size:0.95rem;
      transition:all var(--transition-fast);
    }
    .input::placeholder{color:var(--muted)}
    .input:focus{
      outline:none;border-color:var(--primary);
      box-shadow:0 0 0 4px var(--primary-glow);
    }
    .input.is-invalid{border-color:var(--danger);background:var(--danger-soft)}
    .toggle-eye{
      position:absolute;right:8px;top:50%;transform:translateY(-50%);
      width:36px;height:36px;display:grid;place-items:center;
      border:0;background:transparent;color:var(--muted);cursor:pointer;
      border-radius:8px;transition:all var(--transition-fast);
    }
    .toggle-eye:hover{color:var(--primary);background:var(--primary-soft)}

    /* ===== Error / Hint ===== */
    .error{
      margin-top:6px;font-size:0.82rem;color:var(--danger);
      display:flex;align-items:center;gap:6px;
    }
    .error::before{
      content:"";width:14px;height:14px;flex-shrink:0;
      background:currentColor;opacity:0.9;
      mask:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg>") center/contain no-repeat;
      -webkit-mask:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg>") center/contain no-repeat;
    }
    .hint{margin-top:6px;font-size:0.8rem;color:var(--muted)}
    .hint.is-match{color:var(--success)}
    .hint.is-mismatch{color:var(--danger)}

    /* ===== Strength Requirements List ===== */
    .req-list{
      list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:6px 18px;
      margin-top:10px;padding:14px 16px;border-radius:12px;
      background:#FAFAF9;border:1px solid var(--line);
    }
    .req-list li{
      display:flex;align-items:center;gap:8px;
      font-size:0.82rem;color:var(--muted);
      transition:color var(--transition-fast);
    }
    .req-list .dot{
      width:14px;height:14px;flex-shrink:0;border-radius:50%;
      background:var(--line);transition:background var(--transition-fast);
      display:grid;place-items:center;
    }
    .req-list .dot svg{width:10px;height:10px;color:#fff;opacity:0;transition:opacity var(--transition-fast)}
    .req-list li.ok{color:var(--success)}
    .req-list li.ok .dot{background:var(--success)}
    .req-list li.ok .dot svg{opacity:1}

    /* ===== Alert ===== */
    .alert{
      padding:14px 16px;border-radius:12px;
      font-size:0.9rem;display:flex;align-items:center;gap:10px;
      margin-bottom:20px;
    }
    .alert-success{background:var(--success-soft);color:var(--success);border:1px solid rgba(5,150,105,0.18)}
    .alert-error{background:var(--danger-soft);color:var(--danger);border:1px solid rgba(220,38,38,0.18)}

    /* ===== Form Actions ===== */
    .form-actions{
      display:flex;justify-content:space-between;align-items:center;
      gap:16px;margin-top:8px;padding-top:24px;
      border-top:1px solid var(--line);
    }
    .cancel-link{
      color:var(--muted);font-weight:600;font-size:0.9rem;
      text-decoration:none;padding:10px 16px;border-radius:10px;
      transition:all var(--transition-fast);
    }
    .cancel-link:hover{color:var(--text);background:#FAFAF9}
    .submit-btn{
      display:inline-flex;align-items:center;justify-content:center;gap:8px;
      height:48px;padding:0 28px;border:0;border-radius:12px;
      color:#fff;font-family:var(--font-body);font-weight:700;font-size:0.95rem;
      cursor:pointer;
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      box-shadow:0 12px 24px rgba(5,150,105,0.22);
      transition:all var(--transition-fast);
    }
    .submit-btn:hover:not(:disabled){
      transform:translateY(-1px);
      box-shadow:0 16px 30px rgba(5,150,105,0.30);
    }
    .submit-btn:disabled{opacity:0.55;cursor:not-allowed;transform:none}

    /* ===== Footer ===== */
    .footer{
      text-align:center;padding:32px;
      color:var(--muted);font-size:0.82rem;
      border-top:1px solid var(--line);
    }
    .footer a{color:var(--primary);font-weight:600;text-decoration:none}

    @media(max-width:768px){
      .navbar{padding:0 16px}
      .main{padding:24px 16px 40px}
      .profile-form-card{padding:24px 20px}
      .req-list{grid-template-columns:1fr}
      .form-actions{flex-direction:column-reverse;align-items:stretch}
      .submit-btn{width:100%}
      .cancel-link{text-align:center}
    }
  </style>
</head>
<body>

  @php
    // 从已登录用户取首字母用于头像（如果 Controller 注入了 $user 即可直接用，
    // 这里通过 Auth 门面兜底，保持页面自包含）。
    // Grab the initial from the authenticated user for the avatar.
    $navbarUser = Auth::user();
  @endphp

  {{-- Navbar --}}
  <nav class="navbar">
    <div class="navbar-inner">
      <a href="{{ route('home') }}" class="nav-logo">
        <span class="nav-logo-mark">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
          </svg>
        </span>
        FoodShare
      </a>

      <div class="nav-user">
        <div class="nav-user-info">
          <div class="name">{{ $navbarUser->firstname }} {{ $navbarUser->lastname }}</div>
          <div class="role">{{ $navbarUser->role }}</div>
        </div>
        <div class="nav-avatar">{{ strtoupper(substr($navbarUser->firstname, 0, 1)) }}</div>
        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" aria-label="Edit profile">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          Profile
        </a>
        <a href="{{ route('profile.password.form') }}" class="nav-link {{ request()->routeIs('profile.password.*') ? 'active' : '' }}" aria-label="Change password">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Password
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display:contents">
          @csrf
          <button type="submit" class="nav-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
          </button>
        </form>
      </div>
    </div>
  </nav>

  {{-- Main Content --}}
  <main class="main">

    {{-- Flash Messages --}}
    @if (session('success'))
      <div class="alert alert-success" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        {{ session('success') }}
      </div>
    @endif
    @if ($errors->has('current_password'))
      <div class="alert alert-error" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ $errors->first('current_password') }}
      </div>
    @endif

    {{-- Page Header --}}
    <a href="{{ route('profile.edit') }}" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
      Back to profile
    </a>
    <header class="page-header">
      <span class="page-eyebrow"><span class="dot"></span> Account Settings</span>
      <h1>Change your password</h1>
      <p>For your security, please enter your current password to confirm this change. Choose a strong password you haven't used before.</p>
    </header>

    {{-- Form Card --}}
    <section class="profile-form-card">
      <form method="POST" action="{{ route('profile.password.update') }}" id="passwordForm" novalidate>
        @csrf
        @method('POST')

        {{-- Current Password --}}
        <div class="field">
          <label for="current_password">Current password</label>
          <div class="input-wrap">
            <input
              id="current_password"
              name="current_password"
              type="password"
              class="password-input input @error('current_password') is-invalid @enderror"
              autocomplete="current-password"
              placeholder="Enter your current password"
              required
            />
            <button type="button" class="toggle-eye" data-target="current_password" aria-label="Toggle password visibility">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          @error('current_password')
            <p class="error" role="alert">{{ $message }}</p>
          @enderror
        </div>

        {{-- New Password --}}
        <div class="field">
          <label for="new_password">New password</label>
          <div class="input-wrap">
            <input
              id="new_password"
              name="new_password"
              type="password"
              class="password-input input @error('new_password') is-invalid @enderror"
              autocomplete="new-password"
              placeholder="At least 8 characters with upper/lowercase + digit"
              required
            />
            <button type="button" class="toggle-eye" data-target="new_password" aria-label="Toggle password visibility">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          @error('new_password')
            <p class="error" role="alert">{{ $message }}</p>
          @enderror

          {{-- Live strength indicator --}}
          <ul class="req-list" id="pwdReqs" aria-live="polite">
            <li data-req="length"><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> At least 8 characters</li>
            <li data-req="lower"><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> One lowercase letter</li>
            <li data-req="upper"><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> One uppercase letter</li>
            <li data-req="digit"><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> One number</li>
          </ul>
        </div>

        {{-- Confirm Password --}}
        <div class="field">
          <label for="confirm_password">Retype new password</label>
          <div class="input-wrap">
            <input
              id="confirm_password"
              name="confirm_password"
              type="password"
              class="password-input input @error('confirm_password') is-invalid @enderror"
              autocomplete="new-password"
              placeholder="Enter the same password again"
              required
            />
            <button type="button" class="toggle-eye" data-target="confirm_password" aria-label="Toggle password visibility">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <p class="hint" id="matchHint" aria-live="polite"></p>
          @error('confirm_password')
            <p class="error" role="alert">{{ $message }}</p>
          @enderror
        </div>

        {{-- Form Actions --}}
        <div class="form-actions">
          <a href="{{ route('profile.edit') }}" class="cancel-link">Cancel</a>
          <button type="submit" class="submit-btn" id="submitBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <span id="submitText">Update password</span>
          </button>
        </div>
      </form>
    </section>

  </main>

  <footer class="footer">
    &copy; 2026 <a href="{{ route('home') }}">FoodShare</a> — Let Every Meal Reach Someone in Need
  </footer>

  <script>
    // ============================================================
    // 修改密码页 — 交互逻辑
    // Change-password page — interaction logic.
    //
    // 功能：
    //   1. 密码可见性 toggle（点击眼睛图标切换 type=password/text）
    //   2. 实时密码强度条件指示器（4 条规则实时变绿/红）
    //   3. 实时两次输入是否一致的提示
    //   4. 提交时按钮禁用 + 改文字"防双击"
    // ============================================================
    (function () {
      const form          = document.getElementById('passwordForm');
      const currentInput  = document.getElementById('current_password');
      const newInput      = document.getElementById('new_password');
      const confirmInput  = document.getElementById('confirm_password');
      const reqList       = document.getElementById('pwdReqs');
      const matchHint     = document.getElementById('matchHint');
      const btn           = document.getElementById('submitBtn');
      const btnText       = document.getElementById('submitText');

      // ---------- 1. 密码可见性 toggle ----------
      // Password visibility toggle.
      document.querySelectorAll('.toggle-eye').forEach(function (btnEye) {
        btnEye.addEventListener('click', function () {
          const targetId = btnEye.getAttribute('data-target');
          const input = document.getElementById(targetId);
          if (!input) return;
          const isHidden = input.type === 'password';
          input.type = isHidden ? 'text' : 'password';
          const show = btnEye.querySelector('.eye-show');
          const hide = btnEye.querySelector('.eye-hide');
          if (show) show.style.display = isHidden ? 'none' : '';
          if (hide) hide.style.display = isHidden ? '' : 'none';
        });
      });

      // ---------- 2. 实时密码强度校验 ----------
      // Live password strength checks.
      function checkRequirements(pwd) {
        return {
          length: pwd.length >= 8,
          lower:  /[a-z]/.test(pwd),
          upper:  /[A-Z]/.test(pwd),
          digit:  /[0-9]/.test(pwd),
        };
      }

      function renderRequirements(pwd) {
        if (!reqList) return true;
        const checks = checkRequirements(pwd);
        let allOk = true;
        reqList.querySelectorAll('li[data-req]').forEach(function (li) {
          const key = li.getAttribute('data-req');
          if (checks[key]) {
            li.classList.add('ok');
          } else {
            li.classList.remove('ok');
            allOk = false;
          }
        });
        return allOk;
      }

      if (newInput) {
        // 初次进入如有 old 值（验证失败回填）则立即渲染一次
        // Render once on load in case there's an old() prefill after a failed submit.
        if (newInput.value) renderRequirements(newInput.value);
        newInput.addEventListener('input', function () {
          renderRequirements(newInput.value);
          // 重新触发两次匹配的实时提示
          // Re-trigger the live match hint.
          checkMatch();
        });
      }

      // ---------- 3. 实时两次密码匹配提示 ----------
      // Live match hint for new and retype passwords.
      function checkMatch() {
        if (!matchHint) return;
        const a = newInput ? newInput.value : '';
        const b = confirmInput ? confirmInput.value : '';
        if (!a && !b) {
          matchHint.textContent = '';
          matchHint.classList.remove('is-match', 'is-mismatch');
          return;
        }
        if (a && !b) {
          matchHint.textContent = 'Please retype your new password above.';
          matchHint.classList.remove('is-match');
          matchHint.classList.add('is-mismatch');
          return;
        }
        if (a === b) {
          matchHint.textContent = '✓ Passwords match.';
          matchHint.classList.add('is-match');
          matchHint.classList.remove('is-mismatch');
        } else {
          matchHint.textContent = '✗ Passwords do not match.';
          matchHint.classList.add('is-mismatch');
          matchHint.classList.remove('is-match');
        }
      }

      if (confirmInput) {
        if (confirmInput.value) checkMatch();
        confirmInput.addEventListener('input', checkMatch);
        newInput && newInput.addEventListener('input', checkMatch);
      }

      // ---------- 4. 提交时按钮禁用 ----------
      // Disable the submit button on submit and change its label to prevent
      // accidental double-click.
      if (form && btn && btnText) {
        form.addEventListener('submit', function () {
          btn.disabled = true;
          btnText.textContent = 'Updating...';
          // 兜底：5 秒后若服务端没响应，恢复按钮
          // Fallback: re-enable after 5s in case the server does not respond.
          setTimeout(function () { btn.disabled = false; btnText.textContent = 'Update password'; }, 5000);
        });
      }
    })();
  </script>

</body>
</html>