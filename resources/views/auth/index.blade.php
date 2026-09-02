{{--
  ============================================================
  认证页面（统一登录 + 注册）— FoodShare 的主要入口页面
  ============================================================

  【页面作用】
  这是用户看到的第一页。通过 Tab 切换在同一页面中显示登录表单和注册表单。
  左侧是品牌面板（绿色渐变背景 + 平台介绍），右侧是表单面板。

  【设计系统】Organic Biophilic（有机自然风格）
    - 主色：绿色 #059669 + 琥珀 #D97706
    - 字体：Lora（标题） + Raleway（正文）
    - 背景：暖米色渐变 + 呼吸动画 blob 装饰

  【表单提交目标】
    - 登录：route('login') → AuthController@login
    - 注册：route('register') → AuthController@register

  【关键功能】
    - 登录表单：邮箱 + 密码 + Google reCAPTCHA v2 + "记住我" + "忘记密码"链接
    - 注册表单：姓名 + 电话 + 邮箱 + 角色选择 + 密码（强度指示器） + 确认密码 + 同意条款
    - 实时验证：所有字段输入时即时反馈（边框变色 + 条件列表更新）
    - 按钮禁用：条件不满足时提交按钮不可用
    - Toast 通知：服务器返回的消息以右下角弹出提示显示
--}}

{{--
  ============================================================
  Auth Page (Unified Login + Registration) — FoodShare Main Entry
  ============================================================

  [Purpose]
  This is the first page users see. Tabs switch between the login form
  and the registration form on the same page. The left side contains the
  brand panel (green gradient background + platform intro); the right side
  contains the form panel.

  [Design System] Organic Biophilic
    - Colors: Green #059669 + Amber #D97706
    - Typography: Lora (headings) + Raleway (body)
    - Background: Warm beige gradient + breathing blob decorations

  [Form Submission Targets]
    - Login: route('login') → AuthController@login
    - Register: route('register') → AuthController@register

  [Key Features]
    - Login form: email + password + Google reCAPTCHA v2 + "Remember me" + "Forgot password" link
    - Registration form: name + phone + email + role + password (strength indicator) + confirm password + agree to terms
    - Real-time validation: instant feedback on all fields (border color changes + condition list updates)
    - Button disable: submit button disabled when conditions are not met
    - Toast notifications: server messages shown as bottom-right popups
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="description" content="FoodShare — A food donation platform connecting donors, recipients, and communities" />
  <title>FoodShare | Let Every Meal Reach Someone in Need</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <style>
    /* ===================================================================
       FoodShare — Authentication Page Design System
       Style:   Organic Biophilic + Nature Distilled
       Colors:  Fresh Green #059669 + Warm Amber #D97706
       Typography: Lora (headings) + Raleway (body)
       =================================================================== */

    :root {
      --primary: #059669;
      --primary-dark: #047857;
      --primary-soft: #ECFDF5;
      --primary-glow: rgba(5, 150, 105, 0.18);
      --accent: #D97706;
      --accent-dark: #B45309;
      --accent-soft: #FFFBEB;
      --text: #1C1917;
      --text-secondary: #44403C;
      --muted: #78716C;
      --line: #E7E0D8;
      --surface: #FFFFFF;
      --surface-glass: rgba(255, 255, 255, 0.94);
      --bg: #FEFAF5;
      --danger: #DC2626;
      --danger-soft: #FEF2F2;
      --success: #059669;
      --success-soft: #ECFDF5;

      --radius-sm: 10px;
      --radius: 16px;
      --radius-lg: 22px;
      --radius-xl: 28px;
      --radius-organic: 40% 60% 60% 40% / 40% 40% 60% 60%;

      --shadow-sm: 0 1px 3px rgba(28, 25, 23, 0.06);
      --shadow: 0 8px 32px rgba(28, 25, 23, 0.07), 0 2px 6px rgba(28, 25, 23, 0.04);
      --shadow-lg: 0 20px 56px rgba(28, 25, 23, 0.10), 0 4px 12px rgba(28, 25, 23, 0.04);

      --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
      --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
      --transition-fast: 150ms var(--ease-out);
      --transition: 250ms var(--ease-out);

      --font-heading: 'Lora', ui-serif, Georgia, serif;
      --font-body: 'Raleway', ui-sans-serif, system-ui, sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { font-size: 16px; }

    body {
      min-height: 100vh;
      min-height: 100dvh;
      font-family: var(--font-body);
      color: var(--text);
      background:
        radial-gradient(ellipse 80% 60% at 12% 8%, rgba(217, 119, 6, 0.08), transparent 35%),
        radial-gradient(ellipse 70% 50% at 88% 92%, rgba(5, 150, 105, 0.10), transparent 35%),
        linear-gradient(175deg, #FEFAF5 0%, #F8F5F0 40%, #F0F5F1 100%);
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .bg-blob {
      position: fixed;
      z-index: 0;
      pointer-events: none;
      border-radius: var(--radius-organic);
      opacity: 0.55;
      filter: blur(60px);
    }
    .bg-blob--top {
      width: 340px; height: 340px;
      top: -100px; right: -60px;
      background: linear-gradient(145deg, rgba(217, 119, 6, 0.22), rgba(5, 150, 105, 0.15));
      animation: blobBreathe 10s ease-in-out infinite;
    }
    .bg-blob--bottom {
      width: 280px; height: 280px;
      bottom: -90px; left: -70px;
      background: linear-gradient(145deg, rgba(5, 150, 105, 0.20), rgba(217, 119, 6, 0.12));
      animation: blobBreathe 8s ease-in-out 2s infinite;
    }
    @keyframes blobBreathe {
      0%, 100% { border-radius: 40% 60% 60% 40% / 40% 40% 60% 60%; }
      33%  { border-radius: 55% 45% 40% 60% / 50% 55% 45% 50%; }
      66%  { border-radius: 45% 55% 55% 45% / 55% 45% 55% 45%; }
    }

    .page {
      position: relative; z-index: 1;
      min-height: 100vh; min-height: 100dvh;
      display: grid; place-items: center;
      padding: 32px 20px;
    }
    .auth-shell {
      width: min(1120px, 100%);
      height: 680px;
      max-height: 92dvh;
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      grid-template-rows: 1fr;
      background: var(--surface-glass);
      border: 1px solid rgba(5, 150, 105, 0.08);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      overflow: hidden;
    }

    .brand-panel {
      display: flex; flex-direction: column; justify-content: center;
      gap: 20px;
      height: 100%;
      padding: 40px; color: #fff;
      background: linear-gradient(155deg, rgba(4, 120, 87, 0.98), rgba(5, 150, 105, 0.90)), #047857;
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,0.25) transparent;
    }
    .brand-panel::-webkit-scrollbar { width: 5px; }
    .brand-panel::-webkit-scrollbar-track { background: transparent; }
    .brand-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 3px; }
    .brand-panel::before, .brand-panel::after {
      content: ""; position: absolute;
      border-radius: var(--radius-organic);
      background: rgba(255, 255, 255, 0.07);
      pointer-events: none;
    }
    .brand-panel::before {
      width: 280px; height: 280px;
      right: -100px; top: -100px;
      animation: blobBreathe 12s ease-in-out infinite;
    }
    .brand-panel::after {
      width: 220px; height: 220px;
      left: -90px; bottom: -80px;
      background: rgba(217, 119, 6, 0.10);
      animation: blobBreathe 9s ease-in-out 1s infinite;
    }
    .brand-top, .brand-content, .impact-list, .brand-bottom { position: relative; z-index: 1; flex-shrink: 0; }

    .logo {
      display: inline-flex; align-items: center; gap: 10px;
      font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700;
    }
    .logo-mark {
      width: 40px; height: 40px;
      display: grid; place-items: center;
      border-radius: 12px;
      color: var(--primary-dark);
      background: linear-gradient(145deg, #fff, #ECFDF5);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      margin-bottom: 14px; padding: 7px 14px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.10);
      font-size: 0.78rem; font-weight: 600; letter-spacing: 0.4px;
      backdrop-filter: blur(6px);
    }
    .eyebrow .dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #A7F3D0;
      box-shadow: 0 0 8px rgba(167, 243, 208, 0.6);
    }

    .brand-content h1 {
      max-width: 480px;
      font-family: var(--font-heading);
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      line-height: 1.15; letter-spacing: -0.8px;
      margin-bottom: 12px;
    }
    .brand-content p {
      max-width: 480px;
      color: rgba(255, 255, 255, 0.78);
      font-size: 0.92rem; line-height: 1.6;
    }

    .impact-list {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px; margin-top: 0;
    }
    .impact-item {
      padding: 14px 12px;
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: var(--radius);
      background: rgba(255, 255, 255, 0.07);
      backdrop-filter: blur(8px);
      transition: background var(--transition);
    }
    .impact-item:hover { background: rgba(255, 255, 255, 0.12); }
    .impact-item strong {
      display: block; margin-bottom: 3px;
      font-family: var(--font-heading);
      font-size: 1rem; font-weight: 700;
    }
    .impact-item span { color: rgba(255, 255, 255, 0.68); font-size: 0.72rem; line-height: 1.35; }
    .brand-bottom { position: relative; z-index: 1; color: rgba(255, 255, 255, 0.55); font-size: 0.75rem; font-style: italic; flex-shrink: 0; }

    /* Form Panel */
    .form-panel {
      display: flex; align-items: flex-start;
      overflow-y: auto;
      padding: 44px;
      background: rgba(255, 255, 255, 0.90);
      scrollbar-width: thin;
      scrollbar-color: var(--primary) transparent;
    }
    .form-panel::-webkit-scrollbar { width: 6px; }
    .form-panel::-webkit-scrollbar-track { background: transparent; margin: 12px 0; }
    .form-panel::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, var(--primary), #10B981);
      border-radius: 3px;
    }
    .form-panel::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, var(--primary-dark), var(--primary));
    }
    .form-wrap { width: min(430px, 100%); margin: 0 auto; }
    .form-heading { margin-bottom: 26px; }
    .form-heading h2 {
      font-family: var(--font-heading);
      font-size: 1.9rem; letter-spacing: -0.4px;
      margin-bottom: 8px; color: var(--text);
    }
    .form-heading p { color: var(--muted); line-height: 1.6; font-size: 0.94rem; }

    /* Tabs */
    .tabs {
      display: grid; grid-template-columns: 1fr 1fr;
      padding: 5px; margin-bottom: 28px;
      border-radius: 14px; background: #F3F4F6;
    }
    .tab-btn {
      border: 0; padding: 12px 16px; border-radius: 10px;
      color: var(--muted); background: transparent;
      font-family: var(--font-body); font-weight: 600; font-size: 0.9rem;
      cursor: pointer; transition: all var(--transition-fast); outline: none;
    }
    .tab-btn:focus-visible { box-shadow: 0 0 0 3px var(--primary-glow); }
    .tab-btn.active {
      color: var(--primary-dark); background: #fff;
      box-shadow: 0 4px 14px rgba(31, 41, 55, 0.08);
    }

    /* Forms */
    .form { display: none; animation: fadeUp 0.30s var(--ease-spring); }
    .form.active { display: block; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .form-grid { display: grid; gap: 16px; }
    .form-grid.two-col { grid-template-columns: 1fr 1fr; }

    .field { display: flex; flex-direction: column; gap: 7px; }
    .field label { font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); }
    .field label .required { color: var(--danger); margin-left: 2px; }

    .input-wrap { position: relative; display: flex; align-items: center; }
    .input-wrap .input-icon {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      color: #A8A29E; pointer-events: none; z-index: 1;
      display: flex; align-items: center;
    }
    .field input, .field select {
      width: 100%; height: 48px;
      border: 1.5px solid var(--line);
      border-radius: var(--radius-sm);
      padding: 0 14px 0 44px;
      font-family: var(--font-body); font-size: 0.93rem;
      color: var(--text); background: #fff; outline: none;
      transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .field select {
      appearance: none; cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2378716C' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 12px center;
      padding-right: 36px;
    }
    .field input:focus, .field select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px var(--primary-glow);
    }

    .password-toggle {
      position: absolute; right: 10px; top: 50%;
      transform: translateY(-50%);
      border: 0; background: transparent; color: var(--muted);
      cursor: pointer; padding: 6px; border-radius: 6px;
      display: flex; align-items: center;
      transition: color var(--transition-fast), background var(--transition-fast);
      min-width: 36px; min-height: 36px;
    }
    .password-toggle:hover { color: var(--text); background: #F5F5F4; }
    .password-toggle:focus-visible { box-shadow: 0 0 0 3px var(--primary-glow); outline: none; }

    .form-row {
      display: flex; align-items: center; justify-content: space-between;
      gap: 14px; margin: 18px 0 22px; font-size: 0.87rem;
    }
    .check { display: inline-flex; align-items: center; gap: 9px; color: var(--muted); cursor: pointer; font-size: 0.86rem; user-select: none; }
    .check input { accent-color: var(--primary); width: 17px; height: 17px; }
    .link { color: var(--primary); font-weight: 600; text-decoration: none; transition: color var(--transition-fast); font-size: 0.86rem; }
    .link:hover { color: var(--primary-dark); text-decoration: underline; }

    .submit-btn {
      width: 100%; height: 50px; border: 0; border-radius: 14px;
      color: #fff;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      box-shadow: 0 12px 24px rgba(5, 150, 105, 0.22);
      font-family: var(--font-body); font-weight: 700; font-size: 0.98rem;
      letter-spacing: 0.2px; cursor: pointer;
      transition: transform var(--transition-fast), box-shadow var(--transition-fast), opacity var(--transition-fast);
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(5, 150, 105, 0.30); }
    .submit-btn:active { transform: translateY(0); }
    .submit-btn:focus-visible { box-shadow: 0 0 0 4px var(--primary-glow), 0 12px 24px rgba(5, 150, 105, 0.22); outline: none; }
    .submit-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; box-shadow: none; }

    /* ---- 实时验证指示器 ---- */
    /* ---- Real-time Validation Indicators ---- */
    .req-list { list-style: none; padding: 0; margin-top: 8px; font-size: 0.78rem; }
    .req-list li { display: flex; align-items: center; gap: 6px; padding: 2px 0; color: var(--muted); transition: color .2s; }
    .req-list li .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; background: #D1D5DB; transition: background .2s; }
    .req-list li.met { color: var(--primary); }
    .req-list li.met .dot { background: var(--primary); }
    .req-list li.unmet { color: var(--danger); }
    .req-list li.unmet .dot { background: var(--danger); }
    .field input.input-valid, .field select.input-valid { border-color: var(--primary); }
    .field input.input-invalid { border-color: var(--danger); }

    .role-help { margin-top: 8px; color: var(--muted); font-size: 0.76rem; line-height: 1.55; }
    .form-footer { margin-top: 22px; text-align: center; color: var(--muted); font-size: 0.85rem; line-height: 1.6; }

    /* Error display */
    .error {
      min-height: 18px; margin-top: 2px;
      color: var(--danger); font-size: 0.77rem; font-weight: 500;
      transition: all var(--transition-fast);
    }

    /* Server-side error alert */
    .alert {
      padding: 14px 18px; border-radius: var(--radius-sm);
      margin-bottom: 20px; font-size: 0.88rem; font-weight: 500;
      display: flex; align-items: flex-start; gap: 10px;
    }
    .alert-error { background: var(--danger-soft); color: #991B1B; border: 1px solid #FECACA; }
    .alert-success { background: var(--success-soft); color: #064E3B; border: 1px solid #A7F3D0; }

    .toast {
      position: fixed; right: 24px; bottom: 24px; z-index: 100;
      max-width: min(380px, calc(100% - 48px));
      padding: 16px 20px; border-radius: var(--radius);
      color: #fff; background: #292524;
      box-shadow: var(--shadow-lg);
      font-size: 0.9rem; font-weight: 500;
      transform: translateY(30px); opacity: 0;
      pointer-events: none;
      transition: all 0.28s var(--ease-spring);
    }
    .toast.toast-success { background: var(--primary-dark); }
    .toast.toast-error { background: var(--danger); }
    .toast.show { transform: translateY(0); opacity: 1; }

    /* Responsive */
    @media (max-width: 900px) {
      .auth-shell { grid-template-columns: 1fr; height: auto; max-height: none; }
      .brand-panel { height: auto; min-height: 340px; padding: 32px; }
      .impact-list { max-width: 540px; }
      .form-panel { overflow-y: visible; padding: 40px 28px 44px; }
    }
    @media (max-width: 620px) {
      .page { padding: 12px; }
      .auth-shell { border-radius: var(--radius-lg); height: auto; max-height: none; }
      .brand-panel { height: auto; min-height: 280px; padding: 28px 22px; }
      .brand-content h1 { font-size: 2rem; }
      .impact-list { grid-template-columns: 1fr; }
      .impact-item:nth-child(2), .impact-item:nth-child(3) { display: none; }
      .form-panel { overflow-y: visible; padding: 32px 20px 36px; }
      .form-grid.two-col { grid-template-columns: 1fr; }
      .form-row { flex-direction: column; align-items: flex-start; gap: 12px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
  </style>
</head>

<body>
  <div class="bg-blob bg-blob--top"></div>
  <div class="bg-blob bg-blob--bottom"></div>

  <main class="page">
    <section class="auth-shell" aria-label="FoodShare user authentication">

      <!-- ============ Brand Panel (Left) ============ -->
      <aside class="brand-panel">
        <div class="brand-top">
          <div class="logo">
            <div class="logo-mark" aria-hidden="true">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 8.7 21 1.5c0 0-9.3 2.5-10 9.5C9.8 14.1 7.4 14.8 5 13.5"/>
                <path d="M2 21c1.5-1.5 4-2 6.5-1.3"/>
              </svg>
            </div>
            <span>FoodShare</span>
          </div>
        </div>

        <div class="brand-content">
          <div class="eyebrow">
            <span class="dot" aria-hidden="true"></span>
            Share Food · Spread Kindness
          </div>
          <h1>Let every meal reach someone who truly needs it.</h1>
          <p>
            Connecting donors, charities, and families in need so that surplus food is not wasted
            and every act of sharing creates real value in our communities.
          </p>
          <div class="impact-list">
            <div class="impact-item">
              <strong>12,860+</strong>
              <span>Meals successfully donated</span>
            </div>
            <div class="impact-item">
              <strong>3,420+</strong>
              <span>Families supported</span>
            </div>
            <div class="impact-item">
              <strong>98%</strong>
              <span>Donor satisfaction rate</span>
            </div>
          </div>
        </div>

        <p class="brand-bottom">One act of kindness can change a meal. &mdash; Join us today.</p>
      </aside>

      <!-- ============ Form Panel (Right) ============ -->
      <section class="form-panel">
        <div class="form-wrap">
          <div class="form-heading">
            <h2 id="formTitle">Welcome back</h2>
            <p id="formSubtitle">Sign in to continue making a difference through food donation.</p>
          </div>

          <!-- Tab Switcher -->
          <div class="tabs" role="tablist" aria-label="Login and registration">
            <button class="tab-btn {{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? '' : 'active' }}"
                    id="loginTab" type="button" role="tab" aria-selected="{{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? 'false' : 'true' }}">
              Login
            </button>
            <button class="tab-btn {{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? 'active' : '' }}"
                    id="registerTab" type="button" role="tab" aria-selected="{{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? 'true' : 'false' }}">
              Register
            </button>
          </div>

          <!-- Flash Messages -->
          @if (session('success'))
            <div class="alert alert-success" role="alert">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              {{ session('success') }}
            </div>
          @endif

          <!-- ========== Login Form ========== -->
          <form class="form {{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? '' : 'active' }}"
                id="loginForm" action="{{ route('login') }}" method="POST" novalidate>
            @csrf

            <div class="form-grid">
              <div class="field">
                <label for="loginEmail">Email address <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                  </span>
                  <input id="loginEmail" name="email" type="email" autocomplete="email"
                         value="{{ old('email') }}" placeholder="name@example.com" required />
                </div>
                <div class="error" id="loginEmailError" role="alert">
                  @error('email') {{ $message }} @enderror
                </div>
              </div>

              <div class="field">
                <label for="loginPassword">Password <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                  </span>
                  <input id="loginPassword" name="password" type="password" autocomplete="current-password"
                         placeholder="Enter your password" required />
                  <button class="password-toggle" type="button" data-target="loginPassword" aria-label="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="error" id="loginPasswordError" role="alert">
                  @error('password') {{ $message }} @enderror
                </div>
              </div>
            </div>

            <!-- General login error -->
            @if ($errors->has('login'))
              <div class="alert alert-error" role="alert" style="margin-top: 12px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ $errors->first('login') }}
              </div>
            @endif

            <div class="form-row">
              <label class="check">
                <input type="checkbox" name="remember" />
                <span>Remember me</span>
              </label>
              <a class="link" href="{{ route('password.forgot') }}">Forgot password?</a>
            </div>

            <!-- Google reCAPTCHA v2 -->
            <div style="margin-bottom:18px;display:flex;justify-content:center;">
              <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaExpired"></div>
            </div>
            <input type="hidden" name="g-recaptcha-response" id="recaptchaResponse" />

            <button class="submit-btn" type="submit" id="loginSubmit" disabled>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
              </svg>
              Sign in to FoodShare
            </button>

            <p class="form-footer">
              By signing in, you agree to our <a class="link" href="#">Terms of Service</a> and <a class="link" href="#">Privacy Policy</a>
            </p>
          </form>

          <!-- ========== Register Form ========== -->
          <form class="form {{ $errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email') ? 'active' : '' }}"
                id="registerForm" action="{{ route('register') }}" method="POST" novalidate>
            @csrf

            <div class="form-grid two-col">
              <div class="field">
                <label for="firstname">First name <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                  </span>
                  <input id="firstname" name="firstname" type="text" autocomplete="given-name"
                         value="{{ old('firstname') }}" placeholder="First name" required />
                </div>
                <div class="error" id="firstnameError" role="alert">@error('firstname') {{ $message }} @enderror</div>
              </div>

              <div class="field">
                <label for="lastname">Last name <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                  </span>
                  <input id="lastname" name="lastname" type="text" autocomplete="family-name"
                         value="{{ old('lastname') }}" placeholder="Last name" required />
                </div>
                <div class="error" id="lastnameError" role="alert">@error('lastname') {{ $message }} @enderror</div>
              </div>
            </div>

            <div class="form-grid" style="margin-top: 16px;">
              <div class="field">
                <label for="phone">Phone number</label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                  </span>
                  <input id="phone" name="phone" type="tel" autocomplete="tel"
                         value="{{ old('phone') }}" placeholder="Enter your phone number" />
                </div>
                <div class="error" id="phoneError" role="alert">@error('phone') {{ $message }} @enderror</div>
              </div>

              <div class="field">
                <label for="registerEmail">Email address <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                  </span>
                  <input id="registerEmail" name="email" type="email" autocomplete="email"
                         value="{{ old('email') }}" placeholder="name@example.com" required />
                </div>
                <div class="error" id="registerEmailError" role="alert">@error('email') {{ $message }} @enderror</div>
              </div>

              <div class="field">
                <label for="role">User role <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                  </span>
                  <select id="role" name="role" required>
                    <option value="">Select a role</option>
                    <option value="donor" {{ old('role') == 'donor' ? 'selected' : '' }}>Donor — Food Donor</option>
                    <option value="recipient" {{ old('role') == 'recipient' ? 'selected' : '' }}>Recipient — Food Recipient</option>
                  </select>
                </div>
                <p class="role-help">For production systems, regular users should not be able to register directly as an Admin.</p>
                <div class="error" id="roleError" role="alert">@error('role') {{ $message }} @enderror</div>
              </div>

              <div class="field">
                <label for="registerPassword">Password <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                  </span>
                  <input id="registerPassword" name="password" type="password" autocomplete="new-password"
                         placeholder="At least 8 characters" required />
                  <button class="password-toggle" type="button" data-target="registerPassword" aria-label="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="error" id="registerPasswordError" role="alert">@error('password') {{ $message }} @enderror</div>
                <ul class="req-list" id="pwdReqs">
                  <li data-req="length"><span class="dot"></span> At least 8 characters</li>
                  <li data-req="lower"><span class="dot"></span> One lowercase letter</li>
                  <li data-req="upper"><span class="dot"></span> One uppercase letter</li>
                  <li data-req="digit"><span class="dot"></span> One number</li>
                </ul>
              </div>

              <div class="field">
                <label for="passwordConfirmation">Confirm password <span class="required">*</span></label>
                <div class="input-wrap">
                  <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                  </span>
                  <input id="passwordConfirmation" name="confirm_password" type="password" autocomplete="new-password"
                         placeholder="Re-enter your password" required />
                  <button class="password-toggle" type="button" data-target="passwordConfirmation" aria-label="Show password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
                <div class="error" id="passwordConfirmationError" role="alert">@error('confirm_password') {{ $message }} @enderror</div>
                <p id="confirmMatchHint" style="display:none;color:var(--danger);font-size:0.78rem;margin-top:4px;">Passwords do not match</p>
              </div>
            </div>

            <div class="form-row">
              <label class="check">
                <input id="agree" type="checkbox" required />
                <span>I agree to the Terms of Service and Privacy Policy</span>
              </label>
            </div>

            <button class="submit-btn" type="submit" id="registerSubmit" disabled>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 19 14 23 10"/>
              </svg>
              Create account
            </button>
          </form>
        </div>
      </section>

    </section>
  </main>

  <!-- Toast -->
  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
    (function () {
      const loginTab    = document.getElementById("loginTab");
      const registerTab = document.getElementById("registerTab");
      const loginForm   = document.getElementById("loginForm");
      const registerForm = document.getElementById("registerForm");
      const formTitle   = document.getElementById("formTitle");
      const formSubtitle = document.getElementById("formSubtitle");
      const toast       = document.getElementById("toast");

      /* ---- Tab Switching ---- */
      function switchForm(type) {
        const isLogin = type === "login";
        loginTab.classList.toggle("active", isLogin);
        registerTab.classList.toggle("active", !isLogin);
        loginTab.setAttribute("aria-selected", String(isLogin));
        registerTab.setAttribute("aria-selected", String(!isLogin));
        loginForm.classList.toggle("active", isLogin);
        registerForm.classList.toggle("active", !isLogin);

        if (isLogin) {
          formTitle.textContent = "Welcome back";
          formSubtitle.textContent = "Sign in to continue making a difference through food donation.";
        } else {
          formTitle.textContent = "Join FoodShare";
          formSubtitle.textContent = "Create an account and start spreading kindness through sharing.";
        }
      }

      loginTab.addEventListener("click", function () { switchForm("login"); });
      registerTab.addEventListener("click", function () { switchForm("register"); });

      // Auto-switch to register tab if there are register validation errors
      @if ($errors->has('firstname') || $errors->has('confirm_password') || $errors->has('email'))
        switchForm("register");
      @endif

      /* ---- Password Toggle ---- */
      document.querySelectorAll(".password-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const input = document.getElementById(this.dataset.target);
          if (!input) return;
          const isPassword = input.type === "password";
          input.type = isPassword ? "text" : "password";
          const svg = this.querySelector("svg");
          if (svg) {
            if (isPassword) {
              svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="m14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
              svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
          }
          this.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        });
      });

      /* ---- Toast ---- */
      function showToast(message, cls) {
        toast.textContent = message;
        toast.className = "toast " + (cls || "");
        toast.classList.add("show");
        window.setTimeout(function () { toast.classList.remove("show"); }, 3000);
      }

      // Auto-show server flash messages
      @if (session('success'))
        showToast("{{ session('success') }}", "toast-success");
      @endif
      @if ($errors->has('login'))
        showToast("{{ $errors->first('login') }}", "toast-error");
      @endif

      /* ---- Login Validation (Real-time) ---- */
      var loginEmailInp = document.getElementById("loginEmail");
      var loginPwdInp   = document.getElementById("loginPassword");
      var loginBtn      = document.getElementById("loginSubmit");
      var recaptchaDone = false;

      function isValidEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

      function checkLogin() {
        var val = loginEmailInp.value.trim();
        var emailOk = isValidEmail(val);
        var localOk = val.length > 0 && emailOk && loginPwdInp.value.length > 0;
        loginEmailInp.classList.toggle("input-valid", val && emailOk);
        loginEmailInp.classList.toggle("input-invalid", val && !emailOk);
        // 按钮启用仅依赖本地验证，reCAPTCHA 在提交时检查
        // Button enable depends only on local validation; reCAPTCHA is checked on submit
        loginBtn.disabled = !localOk;
      }

      // reCAPTCHA 回调
      // reCAPTCHA callback
      window.onRecaptchaSuccess = function(token) {
        recaptchaDone = true;
        document.getElementById("recaptchaResponse").value = token;
      };
      window.onRecaptchaExpired = function() {
        recaptchaDone = false;
        document.getElementById("recaptchaResponse").value = "";
      };

      loginEmailInp.addEventListener("input", checkLogin);
      loginPwdInp.addEventListener("input", checkLogin);
      checkLogin();

      loginForm.addEventListener("submit", function (event) {
        if (loginBtn.disabled) { event.preventDefault(); return; }
        // 先做本地验证，再检查 reCAPTCHA
        // Run local validation first, then check reCAPTCHA
        if (!recaptchaDone || !document.getElementById("recaptchaResponse").value) {
          event.preventDefault();
          showToast("Please complete the reCAPTCHA verification.", "toast-error");
          return;
        }
        loginBtn.disabled = true; loginBtn.textContent = "Signing in...";
      });

      /* ---- Register Validation (Real-time) ---- */
      var regFirstname  = document.getElementById("firstname");
      var regLastname   = document.getElementById("lastname");
      var regEmail      = document.getElementById("registerEmail");
      var regRole       = document.getElementById("role");
      var regPassword   = document.getElementById("registerPassword");
      var regConfirm    = document.getElementById("passwordConfirmation");
      var regAgree      = document.getElementById("agree");
      var regBtn        = document.getElementById("registerSubmit");
      var pwdReqs       = document.getElementById("pwdReqs");

      function checkRegister() {
        var fn = regFirstname.value.trim().length > 0;
        var ln = regLastname.value.trim().length > 0;
        var em = isValidEmail(regEmail.value.trim());
        var rl = regRole.value !== "";
        var pwLen = regPassword.value.length >= 8;
        var pwLow = /[a-z]/.test(regPassword.value);
        var pwUp  = /[A-Z]/.test(regPassword.value);
        var pwDig = /[0-9]/.test(regPassword.value);
        var pwOk  = pwLen && pwLow && pwUp && pwDig;
        var cfOk  = regConfirm.value.length > 0 && regPassword.value === regConfirm.value;
        var agOk  = regAgree.checked;
        var allOk = fn && ln && em && rl && pwOk && cfOk && agOk;

        // 密码条件指示器
        // Password requirement indicators
        if (pwdReqs && regPassword.value.length > 0) {
          pwdReqs.querySelector("[data-req=length]").className = pwLen ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=lower]").className  = pwLow ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=upper]").className  = pwUp  ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=digit]").className  = pwDig ? "met" : "unmet";
        } else if (pwdReqs) {
          pwdReqs.querySelectorAll("li").forEach(function(li){ li.className = ""; });
        }

        // 输入框边框反馈
        // Input field border feedback
        regEmail.classList.toggle("input-valid", em);
        regEmail.classList.toggle("input-invalid", regEmail.value.trim() && !em);
        regPassword.classList.toggle("input-valid", pwOk);
        regPassword.classList.toggle("input-invalid", regPassword.value && !pwOk);
        regConfirm.classList.toggle("input-valid", cfOk);
        regConfirm.classList.toggle("input-invalid", regConfirm.value && !cfOk);
        var matchHint = document.getElementById("confirmMatchHint");
        if (matchHint) {
          matchHint.style.display = (regConfirm.value && regPassword.value !== regConfirm.value) ? "block" : "none";
        }

        regBtn.disabled = !allOk;
      }

      [regFirstname, regLastname, regEmail, regPassword, regConfirm, regAgree].forEach(function(el){
        el.addEventListener("input", checkRegister);
      });
      regRole.addEventListener("change", checkRegister);
      regAgree.addEventListener("change", checkRegister);
      checkRegister();

      registerForm.addEventListener("submit", function (event) {
        if (regBtn.disabled) { event.preventDefault(); return; }
        regBtn.disabled = true; regBtn.textContent = "Creating account...";
      });
    })();
  </script>

  <!-- Google reCAPTCHA -->
  <script src="https://www.recaptcha.net/recaptcha/api.js" async defer></script>
</body>
</html>
