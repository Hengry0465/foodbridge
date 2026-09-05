{{--
  ============================================================
  个人资料编辑页 — Profile Edit
  ============================================================

  Profile Edit Page — Profile Edit.

  【页面作用】
  登录用户在此页面编辑自己的基本信息（firstname / lastname / phone）。
  email 与 role 以只读形式展示，明确告知这两个字段不可自助修改。
  顶部 navbar 提供 Profile 与 Change Password 之间的切换入口。

  [Page Purpose]
  Authenticated users edit their basic information (firstname / lastname /
  phone) on this page. email and role are displayed read-only, with a clear
  notice that these fields cannot be self-edited. The top navbar provides
  shortcuts between Profile and Change Password.

  【数据来源】ProfileController@edit → 从 Auth::user() 加载当前登录用户
  【数据提交】POST /profile → ProfileController@update → AuthService::updateProfile

  [Data Source] ProfileController@edit → loads the current authenticated
                user via Auth::user().
  [Submission]  POST /profile → ProfileController@update →
                AuthService::updateProfile.

  【需要认证】此页面受 auth 中间件保护，未登录用户会被重定向到登录页。

  [Authentication Required] Protected by the auth middleware; unauthenticated
                            users are redirected to the login page.

  【页面结构】
    1. Navbar（sticky）：Logo | 用户名 + 角色 | 头像 | Profile | Sign Out
    2. 主卡片：标题 + 副标题 + flash 提示条
    3. 表单卡片：firstname / lastname / phone + 错误提示
    4. 只读区：email + role（不可改）+ 提示文案
    5. 提交区：Cancel 链接 + Save Changes 按钮
    6. Footer：版权信息

  [Page Structure]
    1. Navbar (sticky): Logo | Username + Role | Avatar | Profile | Sign Out
    2. Main card: title + subtitle + flash banner
    3. Form card: firstname / lastname / phone + inline errors
    4. Read-only zone: email + role (not editable) + notice text
    5. Submit zone: Cancel link + Save Changes button
    6. Footer: copyright info

  【设计系统】与 home.blade.php 完全一致：Organic Biophilic、Lora+Raleway、
                #059669+#D97706；新增 .profile-form-card 等表单专用样式。

  [Design System] Fully consistent with home.blade.php: Organic Biophilic,
                  Lora + Raleway, #059669 + #D97706; new .profile-form-card
                  and form-specific styles added.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="description" content="FoodShare — Edit your profile" />
    <title>FoodBridge | Edit Profile</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <style>
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

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth
        }

        body {
            min-height: 100dvh;
            font-family: var(--font-body);
            color: var(--text);
            background:
                radial-gradient(ellipse 80% 60% at 12% 8%, rgba(217, 119, 6, 0.06), transparent 35%),
                radial-gradient(ellipse 70% 50% at 88% 92%, rgba(5, 150, 105, 0.08), transparent 35%),
                linear-gradient(175deg, #FEFAF5 0%, #F8F5F0 40%, #F0F5F1 100%);
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Navbar (与 home 一致，仅新增 .nav-link) ===== */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(5, 150, 105, 0.08);
            padding: 0 32px;
        }

        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-heading);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-decoration: none;
        }

        .nav-logo-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .nav-user-info {
            text-align: right
        }

        .nav-user-info .name {
            font-weight: 600;
            font-size: 0.92rem;
            color: var(--text)
        }

        .nav-user-info .role {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: capitalize
        }

        .nav-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.20);
        }

        /* 新增：普通导航链接（与 nav-logout 风格区分，hover 用 primary 绿） */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font-family: var(--font-body);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .nav-link:hover {
            color: var(--primary-dark);
            border-color: var(--primary);
            background: var(--primary-soft)
        }

        .nav-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--muted);
            font-family: var(--font-body);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .nav-logout:hover {
            color: var(--danger);
            border-color: var(--danger);
            background: var(--danger-soft)
        }

        /* ===== Main Content ===== */
        .main {
            max-width: 760px;
            margin: 0 auto;
            padding: 40px 32px 60px
        }

        /* ===== Page Header ===== */
        .page-header {
            margin-bottom: 28px
        }

        .page-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 6px 14px;
            border: 1px solid var(--primary-soft);
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.4px;
        }

        .page-eyebrow .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 8px var(--primary-glow);
        }

        .page-header h1 {
            font-family: var(--font-heading);
            font-size: clamp(1.6rem, 3vw, 2.1rem);
            line-height: 1.2;
            letter-spacing: -0.6px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 560px
        }

        /* ===== Form Card ===== */
        .profile-form-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(5, 150, 105, 0.06);
            margin-bottom: 24px;
        }

        .form-section {
            margin-bottom: 28px
        }

        .form-section:last-child {
            margin-bottom: 0
        }

        .section-title {
            font-family: var(--font-heading);
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .section-subtitle {
            font-size: 0.84rem;
            color: var(--muted);
            margin-bottom: 18px
        }

        /* ===== Form Fields ===== */
        .field {
            margin-bottom: 18px
        }

        .field:last-child {
            margin-bottom: 0
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .input-wrap {
            position: relative
        }

        .input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--text);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: all var(--transition-fast);
        }

        .input::placeholder {
            color: var(--muted)
        }

        .input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .input.is-invalid {
            border-color: var(--danger);
            background: var(--danger-soft)
        }

        .error {
            margin-top: 6px;
            font-size: 0.82rem;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .error::before {
            content: "";
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            background: currentColor;
            opacity: 0.9;
            mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg>") center/contain no-repeat;
            -webkit-mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg>") center/contain no-repeat;
        }

        /* ===== Read-only zone ===== */
        .readonly-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .readonly-tile {
            padding: 14px 16px;
            border-radius: 12px;
            background: #FAFAF9;
            border: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .readonly-tile .label {
            font-size: 0.72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .readonly-tile .value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
            word-break: break-word
        }

        .readonly-tile .value.capitalize {
            text-transform: capitalize
        }

        .readonly-notice {
            margin-top: 14px;
            padding: 10px 14px;
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-size: 0.82rem;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .readonly-notice svg {
            flex-shrink: 0;
            margin-top: 2px
        }

        /* ===== Password CTA — 从资料页跳转到修改密码页 ===== */
        /* Password CTA — shortcut from the profile page to the change-password page. */
        .password-cta {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            background: #FAFAF9;
            border: 1.5px solid var(--line);
            color: var(--text);
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .password-cta:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .password-cta-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.20);
        }

        .password-cta-body {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0
        }

        .password-cta-title {
            font-weight: 600;
            font-size: 0.92rem;
            color: var(--text)
        }

        .password-cta-sub {
            font-size: 0.78rem;
            color: var(--muted)
        }

        .password-cta-arrow {
            color: var(--muted);
            transition: all var(--transition-fast);
            flex-shrink: 0
        }

        .password-cta:hover .password-cta-arrow {
            color: var(--primary-dark);
            transform: translateX(2px)
        }

        /* ===== Alert ===== */
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid rgba(5, 150, 105, 0.18)
        }

        .alert-error {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.18)
        }

        /* ===== Form Actions ===== */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
        }

        .cancel-link {
            color: var(--muted);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all var(--transition-fast);
        }

        .cancel-link:hover {
            color: var(--text);
            background: #FAFAF9
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            padding: 0 28px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 12px 24px rgba(5, 150, 105, 0.22);
            transition: all var(--transition-fast);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(5, 150, 105, 0.30);
        }

        .submit-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none
        }

        /* ===== Footer ===== */
        .footer {
            text-align: center;
            padding: 32px;
            color: var(--muted);
            font-size: 0.82rem;
            border-top: 1px solid var(--line);
        }

        .footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none
        }

        @media(max-width:768px) {
            .navbar {
                padding: 0 16px
            }

            .main {
                padding: 24px 16px 40px
            }

            .profile-form-card {
                padding: 24px 20px
            }

            .readonly-grid {
                grid-template-columns: 1fr
            }

            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch
            }

            .submit-btn {
                width: 100%
            }

            .cancel-link {
                text-align: center
            }
        }
    </style>
</head>

<body>
    @php
        $backRoute = match (auth()->user()->role) {
            'donor' => 'donor.dashboard',
            'recipient' => 'recipient.dashboard',
            'admin' => 'admin.dashboard',
            default => 'welcome',
        };
    @endphp

    {{-- Navbar --}}
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ route('home') }}" class="nav-logo">
                <span class="nav-logo-mark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z" />
                        <path d="M2 17l10 5 10-5" />
                        <path d="M2 12l10 5 10-5" />
                    </svg>
                </span>
                FoodBridge
            </a>

            <div class="nav-user">
                <div class="nav-user-info">
                    <div class="name">{{ $user->firstname }} {{ $user->lastname }}</div>
                    <div class="role">{{ $user->role }}</div>
                </div>
                <div class="nav-avatar">{{ strtoupper(substr($user->firstname, 0, 1)) }}</div>
                <a href="{{ route('profile.edit') }}"
                    class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" aria-label="Edit profile">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Profile
                </a>
                <a href="{{ route('profile.password.form') }}"
                    class="nav-link {{ request()->routeIs('profile.password.*') ? 'active' : '' }}"
                    aria-label="Change password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    Password
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display:contents">
                    @csrf
                    <button type="submit" class="nav-logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->has('profile'))
            <div class="alert alert-error" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                {{ $errors->first('profile') }}
            </div>
        @endif

        {{-- Page Header --}}
        <header class="page-header">
            <span class="page-eyebrow"><span class="dot"></span> Account Settings</span>
            <h1>Edit your profile</h1>
            <p>Update your basic information below. Your email and role are locked for security reasons.</p>
        </header>

        {{-- Form Card --}}
        <section class="profile-form-card">
            <form method="POST" action="{{ route('profile.update') }}" id="profileForm" novalidate>
                @csrf
                @method('POST')

                {{-- Section: Basic Info --}}
                <div class="form-section">
                    <h2 class="section-title">Basic information</h2>
                    <p class="section-subtitle">This information appears on your profile and food listings.</p>

                    <div class="field">
                        <label for="firstname">First name</label>
                        <div class="input-wrap">
                            <input id="firstname" name="firstname" type="text"
                                class="input @error('firstname') is-invalid @enderror"
                                value="{{ old('firstname', $user->firstname) }}" maxlength="100"
                                autocomplete="given-name" placeholder="Your first name" required />
                        </div>
                        @error('firstname')
                            <p class="error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="lastname">Last name</label>
                        <div class="input-wrap">
                            <input id="lastname" name="lastname" type="text"
                                class="input @error('lastname') is-invalid @enderror"
                                value="{{ old('lastname', $user->lastname) }}" maxlength="100"
                                autocomplete="family-name" placeholder="Your last name" required />
                        </div>
                        @error('lastname')
                            <p class="error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="phone">Phone <span
                                style="color:var(--muted);font-weight:400">(optional)</span></label>
                        <div class="input-wrap">
                            <input id="phone" name="phone" type="tel"
                                class="input @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $user->phone) }}" maxlength="100" autocomplete="tel"
                                placeholder="e.g. +1 555 123 4567" />
                        </div>
                        @error('phone')
                            <p class="error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section: Read-only Info --}}
                <div class="form-section">
                    <h2 class="section-title">Account information</h2>
                    <p class="section-subtitle">These fields are managed for security reasons and cannot be
                        self-edited.</p>

                    <div class="readonly-grid">
                        <div class="readonly-tile">
                            <span class="label">Email</span>
                            <span class="value">{{ $user->email }}</span>
                        </div>
                        <div class="readonly-tile">
                            <span class="label">Role</span>
                            <span class="value capitalize">{{ $user->role }}</span>
                        </div>
                    </div>

                    <div class="readonly-notice">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                        <span>To change your email or role, please contact support. This restriction helps protect your
                            account.</span>
                    </div>

                    {{-- 跳转到修改密码页面的快捷入口（让用户在不滚动到顶部的情况下也能找到） --}}
                    {{-- Shortcut to the change-password page (so the user can find it without scrolling back up). --}}
                    <a href="{{ route('profile.password.form') }}" class="password-cta">
                        <span class="password-cta-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </span>
                        <span class="password-cta-body">
                            <span class="password-cta-title">Want to change your password?</span>
                            <span class="password-cta-sub">You'll need to enter your current password to
                                confirm.</span>
                        </span>
                        <svg class="password-cta-arrow" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </a>
                </div>

                {{-- Form Actions --}}
                <div class="form-actions">
                    <a href="{{ route($backRoute) }}" class="cancel-link">Cancel</a>
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        <span id="submitText">Save changes</span>
                    </button>
                </div>
            </form>
        </section>

    </main>

    <footer class="footer">
        &copy; 2026 <a href="{{ route('home') }}">FoodBridge</a> — Let Every Meal Reach Someone in Need
    </footer>

    <script>
        // 提交时按钮禁用 + 改文字"防双击"
        // Disable the submit button on submit and change its label to prevent
        // accidental double-click.
        (function() {
            const form = document.getElementById('profileForm');
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('submitText');
            if (!form || !btn || !text) return;
            form.addEventListener('submit', function() {
                btn.disabled = true;
                text.textContent = 'Saving...';
                // 兜底：5 秒后若服务端没响应，恢复按钮
                // Fallback: re-enable after 5s in case the server does not respond.
                setTimeout(function() {
                    btn.disabled = false;
                    text.textContent = 'Save changes';
                }, 5000);
            });

            // 简单的实时校验：名字为空时给输入框加红色边框
            // Light real-time validation: mark inputs red when empty after blur.
            ['firstname', 'lastname'].forEach(function(name) {
                const el = document.getElementById(name);
                if (!el) return;
                el.addEventListener('blur', function() {
                    if (!el.value.trim()) el.classList.add('is-invalid');
                    else el.classList.remove('is-invalid');
                });
                el.addEventListener('input', function() {
                    if (el.value.trim()) el.classList.remove('is-invalid');
                });
            });
        })();
    </script>

</body>

</html>
