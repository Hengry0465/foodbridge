{{--
  ============================================================
  首页（登录后可见）— 显示用户个人信息和欢迎内容
  ============================================================

  Homepage (visible after login) — displays user profile and welcome content.

  【页面作用】
  用户登录后看到的第一个页面。顶部导航栏显示用户名、角色和退出按钮。
  主体部分包含绿色渐变欢迎横幅和个人信息卡片。

  [Page Purpose]
  The first page users see after logging in. The top navbar shows the username,
  role, and logout button. The main area contains a green gradient welcome
  banner and a profile information card.

  【数据来源】HomeController@index → 从数据库加载当前登录用户的信息

  [Data Source] HomeController@index → loads the current logged-in user's
  information from the database.

  【需要认证】此页面受 auth 中间件保护，未登录用户会被重定向到登录页

  [Authentication Required] This page is protected by the auth middleware;
  unauthenticated users are redirected to the login page.

  【页面结构】
    1. 导航栏（sticky）：Logo | 用户名 + 角色 | 头像 | 退出按钮
    2. 欢迎横幅（Hero Card）：绿色渐变背景 + 时段问候语 + 角色副标题
    3. 个人信息卡（Profile Card）：头像、姓名、角色标签、邮箱、电话
    4. 页脚（Footer）：版权信息

  [Page Structure]
    1. Navbar (sticky): Logo | Username + Role | Avatar | Logout button
    2. Welcome Banner (Hero Card): Green gradient background + time-based
       greeting + role subtitle
    3. Profile Card: Avatar, name, role badge, email, phone
    4. Footer: Copyright information

  【设计系统】与登录页一致：Organic Biophilic, Lora+Raleway, #059669+#D97706

  [Design System] Consistent with the login page: Organic Biophilic,
  Lora + Raleway, #059669 + #D97706
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
    .nav-user{
      display:flex;align-items:center;gap:14px;
    }
    .nav-user-info{text-align:right}
    .nav-user-info .name{font-weight:600;font-size:0.92rem;color:var(--text)}
    .nav-user-info .role{
      font-size:0.75rem;color:var(--muted);
      text-transform:capitalize;
    }
    .nav-avatar{
      width:42px;height:42px;border-radius:50%;
      background:linear-gradient(135deg,var(--accent),var(--primary));
      display:grid;place-items:center;
      color:#fff;font-weight:700;font-size:1.1rem;
      box-shadow:0 4px 12px rgba(5,150,105,0.20);
    }
    .nav-logout{
      display:flex;align-items:center;gap:6px;
      padding:8px 14px;border:1.5px solid var(--line);border-radius:10px;
      background:#fff;color:var(--muted);font-family:var(--font-body);
      font-size:0.84rem;font-weight:600;cursor:pointer;
      text-decoration:none;transition:all var(--transition-fast);
    }
    .nav-logout:hover{color:var(--danger);border-color:var(--danger);background:var(--danger-soft)}

    /* ===== Nav Link（普通导航入口，如 Profile；与 nav-logout 区分，hover 用 primary 绿） ===== */
    /* Nav link (e.g. Profile) — distinct from nav-logout; hovers with the
       primary green palette to signal "navigate" rather than "destructive". */
    .nav-link{
      display:flex;align-items:center;gap:6px;
      padding:8px 14px;border:1.5px solid var(--line);border-radius:10px;
      background:#fff;color:var(--text);font-family:var(--font-body);
      font-size:0.84rem;font-weight:600;cursor:pointer;
      text-decoration:none;transition:all var(--transition-fast);
    }
    .nav-link:hover{color:var(--primary-dark);border-color:var(--primary);background:var(--primary-soft)}
    /* 当前页高亮（route()->is('profile.edit') / 'profile.password.*'） */
    /* Current-page highlight via request()->routeIs(...) ===> .active class. */
    .nav-link.active{color:var(--primary-dark);border-color:var(--primary);background:var(--primary-soft)}

    /* ===== Main Content ===== */
    .main{max-width:1200px;margin:0 auto;padding:40px 32px 60px}

    /* ===== Hero Banner ===== */
    .hero{
      display:grid;grid-template-columns:1fr 0.7fr;gap:28px;
      margin-bottom:36px;
    }
    .hero-card{
      background:linear-gradient(155deg,rgba(4,120,87,0.98),rgba(5,150,105,0.90)),#047857;
      border-radius:var(--radius-xl);padding:40px 36px;color:#fff;
      position:relative;overflow:hidden;
      box-shadow:var(--shadow-lg);
    }
    .hero-card::before{
      content:"";position:absolute;
      width:240px;height:240px;right:-80px;top:-80px;
      border-radius:var(--radius-organic);
      background:rgba(255,255,255,0.06);
      animation:blobBreathe 10s ease-in-out infinite;
    }
    .hero-card>*{position:relative;z-index:1}
    .hero-eyebrow{
      display:inline-flex;align-items:center;gap:8px;
      margin-bottom:16px;padding:7px 14px;
      border:1px solid rgba(255,255,255,0.22);
      border-radius:999px;background:rgba(255,255,255,0.10);
      font-size:0.78rem;font-weight:600;letter-spacing:0.4px;
    }
    .hero-eyebrow .dot{
      width:6px;height:6px;border-radius:50%;
      background:#A7F3D0;box-shadow:0 0 8px rgba(167,243,208,0.6);
    }
    .hero-card h1{
      font-family:var(--font-heading);
      font-size:clamp(1.6rem,3vw,2.2rem);
      line-height:1.2;letter-spacing:-0.6px;margin-bottom:12px;
    }
    .hero-card p{color:rgba(255,255,255,0.78);font-size:0.92rem;line-height:1.6;max-width:480px}

    /* ===== Profile Card ===== */
    .profile-card{
      background:var(--surface);border-radius:var(--radius-xl);
      padding:32px 28px;box-shadow:var(--shadow);
      border:1px solid rgba(5,150,105,0.06);
      display:flex;flex-direction:column;align-items:center;
      text-align:center;gap:16px;
    }
    .profile-avatar{
      width:72px;height:72px;border-radius:50%;
      background:linear-gradient(135deg,var(--accent),var(--primary));
      display:grid;place-items:center;
      color:#fff;font-family:var(--font-heading);
      font-size:2rem;font-weight:700;
      box-shadow:0 8px 24px rgba(5,150,105,0.22);
    }
    .profile-card h3{font-family:var(--font-heading);font-size:1.2rem}
    .profile-role{
      display:inline-block;padding:5px 14px;border-radius:999px;
      font-size:0.78rem;font-weight:600;text-transform:capitalize;
    }
    .profile-role.admin{background:var(--accent-soft);color:var(--accent-dark)}
    .profile-role.donor{background:var(--primary-soft);color:var(--primary-dark)}
    .profile-role.recipient{background:#EEF2FF;color:#4338CA}

    .profile-details{width:100%;display:grid;gap:12px;margin-top:4px}
    .profile-detail{
      display:flex;align-items:center;gap:10px;
      padding:10px 14px;border-radius:var(--radius-sm);
      background:#FAFAF9;font-size:0.86rem;
    }
    .profile-detail .icon{color:var(--primary);flex-shrink:0}
    .profile-detail .label{color:var(--muted);font-size:0.75rem}
    .profile-detail .value{color:var(--text);font-weight:600}

    /* ===== Footer ===== */
    .footer{
      text-align:center;padding:32px;
      color:var(--muted);font-size:0.82rem;
      border-top:1px solid var(--line);
    }
    .footer a{color:var(--primary);font-weight:600;text-decoration:none}

    @keyframes blobBreathe{
      0%,100%{border-radius:40% 60% 60% 40%/40% 40% 60% 60%}
      33%{border-radius:55% 45% 40% 60%/50% 55% 45% 50%}
      66%{border-radius:45% 55% 55% 45%/55% 45% 55% 45%}
    }

    @media(max-width:768px){
      .hero{grid-template-columns:1fr}
      .navbar{padding:0 16px}
      .main{padding:24px 16px 40px}
    }
  </style>
</head>
<body>

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
          <div class="name">{{ $user->firstname }} {{ $user->lastname }}</div>
          <div class="role">{{ $user->role }}</div>
        </div>
        <div class="nav-avatar">{{ strtoupper(substr($user->firstname, 0, 1)) }}</div>
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

    {{-- Hero + Profile --}}
    <section class="hero">
      <div class="hero-card">
        <div class="hero-eyebrow">
          <span class="dot"></span> Welcome Back
        </div>
        <h1>{{ $greeting }}, {{ $user->firstname }}!</h1>
        <p>{{ $greetingSub }}</p>
      </div>

      <div class="profile-card">
        <div class="profile-avatar">{{ strtoupper(substr($user->firstname, 0, 1)) }}{{ strtoupper(substr($user->lastname, 0, 1)) }}</div>
        <h3>{{ $user->firstname }} {{ $user->lastname }}</h3>
        <span class="profile-role {{ $user->role }}">{{ $roleLabel }}</span>

        <div class="profile-details">
          <div class="profile-detail">
            <svg class="icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <div>
              <div class="label">Email</div>
              <div class="value">{{ $user->email }}</div>
            </div>
          </div>
          @if ($user->phone)
          <div class="profile-detail">
            <svg class="icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <div>
              <div class="label">Phone</div>
              <div class="value">{{ $user->phone }}</div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </section>

  </main>

  <footer class="footer">
    &copy; 2026 <a href="{{ route('home') }}">FoodShare</a> — Let Every Meal Reach Someone in Need
  </footer>

</body>
</html>
