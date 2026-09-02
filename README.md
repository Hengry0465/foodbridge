# FoodShare — Food Donation Platform / 食物捐赠平台

> **English** | [跳转到中文版本](#中文版本)

A Laravel-based web platform that connects **donors** (people with surplus food), **recipients** (people or organizations in need), and **administrators** (platform operators) — secured with email 2FA, Google reCAPTCHA, and seven HTTP-layer security headers.

一个基于 Laravel 的 Web 食物捐赠平台，连接**捐赠者**（有多余食物的人）、**受助者**（需要食物的个人或组织）和**管理员**（平台运营者）——集成邮箱 2FA、Google reCAPTCHA 和七层 HTTP 安全头部防护。

---

## English Version

### 1. Project Overview / What is FoodShare?

**FoodShare** is a web-based food donation platform that connects **donors**, **recipients**, and **administrators** to reduce food waste and feed communities.

**Core Problem:** Surplus food is routinely discarded while many families and charities struggle to access nutritious meals. FoodShare bridges this gap by providing a safe, verified platform where food can be donated and received.

**Target Users:**

| Role | Description |
|------|-------------|
| **Donor** | Individuals or businesses who want to donate surplus food |
| **Recipient** | Individuals, families, or charities who need food assistance |
| **Admin** | Platform administrators who manage users and oversee operations |

**Core Features:**
- User registration with email-based **Two-Factor Authentication (2FA)** via 6-digit verification code
- Secure login with **Google reCAPTCHA v2** (uses `recaptcha.net` mirror for mainland-China accessibility)
- Password reset via email verification code (two-stage: email → code + new password)
- **Role-based access control** (Admin / Donor / Recipient) with three login strategies
- **Profile management**: change name and phone
- **Password change** for authenticated users (requires current-password confirmation)
- Real-time form validation with visual password strength indicators
- Personalized homepage with user profile loaded from database
- Seven security response headers (CSP, XSS, MIME-sniff, clickjacking, Referrer-Policy, Permissions-Policy, X-Powered-By removal)

**Technology Stack:**

| Technology | Version | Role in This Project |
|------------|---------|---------------------|
| **PHP** | `^8.0` (tested on `8.3.32`) | The programming language — runs all backend logic |
| **Laravel Framework** | `9.52.*` (installed: `9.52.22`) | The web framework — routing, Eloquent ORM, authentication, sessions, middleware |
| **MySQL** | `5.7+` / `8.0+` (port 8080 in this project) | The database — user accounts, hashed passwords, verification codes |
| **Blade** | Bundled with Laravel | Template engine — renders HTML with `{{ }}` syntax that auto-escapes for XSS protection |
| **Native HTML/CSS/JS** | — | The frontend — no React, Vue, or build step needed |
| **Google reCAPTCHA v2** | — | "I'm not a robot" checkbox — prevents automated bot abuse of login |
| **Third-party Email API (qzqi.com)** | — | Sends HTML verification codes via HTTP API (avoids SMTP firewall issues) |

**How Frontend, Backend, and Database Work Together:**

```
[Browser / HTML+CSS+JS]  ←HTTP→  [Laravel / PHP Backend]  ←SQL→  [MySQL Database]
   What users see                  Business logic + routing        Where data lives
   and interact with               + data processing               permanently
```

---

### 2. Complete Directory Structure

```
food_donation/
│
├── app/                              # Core application code (业务核心代码)
│   ├── Factories/
│   │   └── UserFactory.php           # Factory Pattern: creates user data arrays by role
│   ├── Http/
│   │   ├── Controllers/              # Controllers: receive HTTP requests, return responses
│   │   │   ├── AuthController.php    #   Login & registration (showLogin, login, register, logout)
│   │   │   ├── Controller.php        #   Base controller (all controllers extend this)
│   │   │   ├── ForgotPasswordController.php  # Password reset flow (sendCode, reset)
│   │   │   ├── HomeController.php    #   Homepage after login
│   │   │   ├── ProfileController.php #   Edit profile / change password (auth required)
│   │   │   └── TwoFAController.php   #   2FA verification (show, verify, resend)
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php    #   Checks if user has the required role
│   │       └── SecurityHeaders.php   #   Adds 7 security headers (CSP, XSS, etc.)
│   ├── Models/
│   │   └── User.php                  # Eloquent Model: one row in the "users" table
│   ├── Providers/
│   │   └── AppServiceProvider.php    # Binds Repository Interface → Implementation
│   ├── Repositories/                 # Repository Pattern: database access layer
│   │   ├── UserRepository.php        #   Concrete implementation (Eloquent queries)
│   │   └── UserRepositoryInterface.php # Contract/interface for user data access
│   ├── Services/                     # Service Layer: business logic
│   │   ├── AuthService.php           #   All auth logic (register, login, 2FA, password reset, profile update)
│   │   └── EmailService.php          #   Sends HTML emails via qzqi.com third-party API
│   └── Strategies/                   # Strategy Pattern: role-based post-login behavior
│       ├── AdminLoginStrategy.php    #   Where admins go after login
│       ├── DonorLoginStrategy.php    #   Where donors go after login
│       ├── LoginStrategyInterface.php #  Contract all strategies must implement
│       └── RecipientLoginStrategy.php # Where recipients go after login
│
├── bootstrap/
│   ├── app.php                       # Creates the Laravel application, registers middleware
│   ├── providers.php                 # Lists service providers to load
│   ├── cache/                        # Compiled config cache (auto-generated)
│   └── Console/                      # Console kernel bootstrap
│
├── config/                           # All configuration files
│   ├── app.php, auth.php, cache.php  #   Application, authentication, cache settings
│   ├── database.php                  #   ★ MySQL connection (reads from .env)
│   ├── mail.php, session.php         #   Email and session configuration
│   ├── recaptcha.php                 #   ★ Custom: Google reCAPTCHA site & secret keys
│   └── services.php                  #   Third-party service keys
│
├── database/                         # Database-related files
│   ├── migrations/                   #   ★ Database table definitions (5 files)
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_07_11_020834_create_user_table.php
│   │   ├── 2026_07_11_050839_add_is_verified_to_users_table.php
│   │   ├── 2026_07_11_082306_add_verification_token_to_users_table.php
│   │   └── 2026_07_12_000001_change_2fa_start_to_bigint.php
│   ├── seeders/DatabaseSeeder.php
│   └── factories/UserFactory.php     # Laravel model factory for testing
│
├── public/                           # ★ ONLY folder accessible from browser
│   ├── index.php                     #   ★★ ENTRY POINT: every HTTP request starts here
│   ├── .htaccess                     #   Apache URL rewriting
│   └── favicon.ico, robots.txt
│
├── resources/                        # Frontend views and assets
│   └── views/                        # Blade templates (HTML pages)
│       ├── auth/
│       │   ├── index.blade.php       #       ★ Main login & registration page (unified)
│       │   ├── forgot-password.blade.php  #   Step 1 — enter email form
│       │   ├── reset-password.blade.php   #   Step 2 — enter code + new password
│       │   ├── verify-2fa.blade.php  #       2FA verification — 6-digit code input
│       │   └── registered.blade.php  #       Registration success modal with confetti
│       ├── home.blade.php            #       ★ Homepage after login (greeting + profile card)
│       ├── profile/
│       │   ├── edit.blade.php        #       Edit profile (firstname / lastname / phone)
│       │   └── password.blade.php    #       Change password (current / new / retype)
│       └── welcome.blade.php         #       Landing page (root URL)
│
├── routes/
│   └── web.php                       # ★ ALL web route definitions (URL → Controller mapping)
│
├── storage/                          # Framework-generated files (DO NOT EDIT)
│   ├── app/, framework/              #   Cache, sessions, compiled views
│   └── logs/                         #   ★ Application logs (debugging)
│
├── tests/                            # Automated tests (Feature + Unit)
├── vendor/                           # Third-party PHP packages (Composer-managed, DO NOT EDIT)
│
├── .env                              # ★ Environment variables (DB password, API keys — KEEP SECRET)
├── .env.example                      # Safe template for .env (can commit to Git)
├── artisan                           # ★ Laravel CLI tool (run `php artisan` commands)
├── composer.json                     # PHP dependencies and project metadata
├── composer.lock                     # Exact dependency versions installed
├── package.json                      # Frontend dependencies (Node.js, minimal usage)
├── vite.config.js                    # Vite build tool configuration
├── README.md                         # ★ This file (bilingual)
└── README_EN.md                      # English-only version (snapshot)
```

**Directories You Should NOT Edit:**

| Directory | Why Not? |
|-----------|----------|
| `vendor/` | Third-party framework code managed by Composer. Any changes are overwritten by `composer install`. |
| `storage/framework/` | Auto-generated cache files. Laravel recreates them automatically. |
| `bootstrap/cache/` | Compiled config cache. Rebuilt with `php artisan optimize`. |

---

### 3. System Architecture

Think of FoodShare as a **restaurant kitchen** to understand how a request flows:

1. **You (customer)** look at the menu → browse a web page in your browser
2. **The waiter (Router)** takes your order → `routes/web.php` matches the URL to a controller
3. **The chef (Controller)** reads the order and orchestrates → validates input, calls helpers
4. **The sous-chef (Service)** handles complex steps → business logic in `AuthService`
5. **The pantry (Repository)** fetches ingredients → `UserRepository` queries MySQL
6. **The recipe book (Model)** defines ingredients → `User` model defines the `users` table structure
7. **The security guard (Middleware)** checks credentials at every door → `auth`, `role:`, `SecurityHeaders`
8. **The waiter** brings your dish → HTTP response (HTML page or redirect)

**Complete Login Flow (step by step):**

```
1. Browser → GET /login
   Route 'login' → AuthController::showLogin()
   Renders: resources/views/auth/index.blade.php (login tab active)

2. User fills email + password, checks reCAPTCHA, clicks "Sign in"

3. Browser → POST /login
   Route → AuthController::login()
   │
   ├── Step 1: Validate form fields (email format, password present, reCAPTCHA token)
   ├── Step 2: Send reCAPTCHA token to https://www.recaptcha.net/recaptcha/api/siteverify
   │           Uses config('recaptcha.secret_key') from .env
   ├── Step 3: AuthService::login(email, password)
   │   ├── UserRepository::findByEmail() → SELECT * FROM users WHERE email = ?
   │   ├── Hash::check(password, user.password_hash) → bcrypt verification
   │   ├── Check user.is_verified === 1 → blocks unverified accounts
   │   ├── Auth::login(user) → creates session, regenerates session ID
   │   └── LoginStrategy::handle(user) → returns route name 'home'
   └── Step 4: Redirect to /home → HomeController::index()
       └── Renders: home.blade.php with user data (name, email, phone, role)
```

**Request Chain for a Typical Authenticated Page (`GET /home`):**

```
Request → SecurityHeaders (adds 7 security headers)
       → EncryptCookies
       → AddQueuedCookiesToResponse
       → StartSession
       → ShareErrorsFromSession
       → VerifyCsrfToken
       → SubstituteBindings
       → auth middleware (checks session login, redirects to /login if absent)
       → HomeController@index()
       → Response with 7 security headers attached
```

**Component Responsibilities:**

| Component | Handles | Does NOT Handle |
|-----------|---------|----------------|
| **Route** (web.php) | Maps URL → Controller | Business logic, database |
| **Controller** | Receives input, validates, calls services, returns response | Direct SQL queries |
| **Service** (AuthService) | Business logic, password hashing, token generation, expiry checks | HTML rendering |
| **Repository** (UserRepository) | Database CRUD operations | Business rules |
| **Model** (User) | Table-column mapping, attribute casting, serialization hiding | Application logic |
| **Strategy** | Role-specific redirect target | General logic |
| **Factory** (UserFactory) | Creates user data arrays by role | Database operations |
| **Middleware** | Request filtering (auth check, role check, security headers) | Business logic |
| **View** (Blade) | HTML rendering, CSS styling, JavaScript interactivity | Data processing |

---

### 4. Core Feature Walkthroughs

#### 4.1 Registration with 2FA (Two-Factor Authentication)

**Full Flow:**
```
Register form (index.blade.php, register tab)
  → Real-time JS validates: name, email, password (8+ chars, upper+lower+digit), confirm match, role selected
  → Agree checkbox checked
  → Submit button enables
  ↓
POST /register → AuthController::register()
  ├── Validates all fields (server-side, Laravel validation)
  ├── Custom email rule: only blocks if email EXISTS AND is_verified=1
  │   (allows re-registration with same email if previous was unverified)
  ├── Role restricted to ['donor', 'recipient'] — cannot self-register as admin
  ├── UserFactory::make() → prepares user data array by role
  ├── Generates: 6-digit random code + SHA-256 verification_token
  ├── UserRepository::create() → INSERT INTO users (is_verified=0)
  ├── EmailService::buildVerificationEmail() → HTML email with code
  ├── EmailService::sendHtmlMail() → POST to api.qzqi.com with query string params
  └── Redirect → /verify-2fa (with email + verify_token in session flash)
  ↓
GET /verify-2fa → TwoFAController::showVerifyForm()
  ├── Checks session has email + verify_token (else redirect: "Please register first")
  ├── session()->keep(['verify_token', 'email']) ← preserves flash for POST
  └── Renders: verify-2fa.blade.php (6 digit inputs, 15-min countdown, resend link)
  ↓
User enters 6-digit code → POST /verify-2fa
  ├── TwoFAController::verify()
  ├── AuthService::verify2FA()
  │   ├── hash_equals(db.verification_token, session.verify_token) ← IDOR protection
  │   ├── Carbon::createFromTimestamp(db.2FA_start)->addMinutes(15) ← expiry check
  │   ├── db.two_factor_code === user_input_code ← code match
  │   └── On success: clears code + token, sets is_verified=1
  └── Redirect → /registered (shows success modal with confetti, auto-redirects 3s)
```

**Files involved:** AuthController, AuthService, UserFactory, UserRepository, EmailService, TwoFAController, index.blade.php, verify-2fa.blade.php, registered.blade.php

#### 4.2 Login with reCAPTCHA

```
User visits /login → reCAPTCHA script loads from www.recaptcha.net
User fills email + password
  → Real-time JS: email format check, password not empty
  → User checks "I'm not a robot" → reCAPTCHA callback sets hidden field
  → Login button enables
  ↓
POST /login → AuthController::login()
  ├── Validates email, password, g-recaptcha-response
  ├── Http::post('https://www.recaptcha.net/recaptcha/api/siteverify', [secret, response])
  ├── AuthService::login() → find user, check password, check verified
  ├── Auth::login() → session created, session ID regenerated
  └── LoginStrategy → returns route('home') → redirect to /home
```

#### 4.3 Password Reset (Two-Stage)

```
User clicks "Forgot password?" on login page
  ↓
GET /forgot-password → shows email input form (forgot-password.blade.php)
  ↓
User enters email → POST /forgot-password
  ├── AuthService::sendResetCode()
  │   ├── Finds verified user by email
  │   ├── Generates 6-digit code + SHA-256 token
  │   └── Updates DB: two_factor_code + 2FA_start + verification_token
  ├── EmailService sends code to user's email
  └── Redirect → /reset-password (with email + reset_token in session flash)
  ↓
GET /reset-password → ForgotPasswordController::showResetForm()
  ├── Checks session has email + reset_token
  ├── session()->keep(['reset_token', 'email']) ← preserves flash for POST
  └── Renders: reset-password.blade.php (6 code inputs + new password + confirm)
  ↓
User enters code + new password → POST /reset-password
  ├── ForgotPasswordController::reset()
  ├── AuthService::resetPassword()
  │   ├── hash_equals(db.verification_token, session.reset_token) ← IDOR protection
  │   ├── Carbon::createFromTimestamp(db.2FA_start)->addMinutes(15) ← expiry
  │   ├── Code match check
  │   └── On success: Hash::make(newPassword) → update password_hash, clear codes
  └── Redirect back with password_reset_done flag → shows green success modal
```

**Security note:** whether the email exists in the database, the system always returns the same "verification code sent" message to prevent **User Enumeration** attacks.

#### 4.4 Homepage (After Login)

```
After successful login → redirect to /home
  ↓
HomeController::index()
  ├── Gets current hour → time-based greeting (Good Morning/Afternoon/Evening)
  ├── Gets user role → role-specific subtitle message
  ├── Gets role label → Donor / Recipient / Administrator
  └── Renders home.blade.php with: user name, email, phone, role, avatar initials
```

#### 4.5 Profile Management (Edit Info / Change Password)

```
Authenticated user clicks "Profile" in navbar
  ↓
GET /profile → ProfileController::edit()
  ├── Reads current user from Auth::user()
  └── Renders: profile/edit.blade.php (firstname / lastname / phone editable; email + role read-only)
  ↓
User edits info, clicks "Save" → POST /profile
  ├── Validates fields (server-side)
  ├── AuthService::updateProfile(user, $data) → writes only firstname / lastname / phone
  └── Redirect back with success message
  ↓
User clicks "Change Password" → GET /profile/password
  └── Renders: profile/password.blade.php (current / new / retype)
  ↓
User fills form → POST /profile/password
  ├── Validates all three fields
  ├── AuthService::updatePasswordForAuthenticatedUser()
  │   ├── Hash::check(current, user.password_hash) ← constant-time
  │   ├── Hash::make(new) → update password_hash
  │   └── session()->regenerateToken() ← refresh CSRF token
  └── Redirect back with success message
```

**Routes involved:** `GET /profile`, `POST /profile`, `GET /profile/password`, `POST /profile/password` — all behind `auth` middleware.

---

### 5. Design Patterns

#### 5.1 MVC (Model-View-Controller) — Architectural Pattern

The foundational pattern of Laravel. Like a restaurant: Model = ingredients, View = plated dish, Controller = chef who coordinates.

| MVC Role | Project Files |
|----------|--------------|
| Model | `app/Models/User.php` |
| View | `resources/views/auth/*.blade.php`, `home.blade.php`, `profile/*.blade.php`, `welcome.blade.php` |
| Controller | `app/Http/Controllers/*.php` (Auth, TwoFA, ForgotPassword, Home, Profile) |

#### 5.2 Strategy Pattern — Role-Based Redirect After Login

**Problem:** After login, different user roles could go to different places. Without this, you'd have messy if/else chains.

**Real-life analogy:** After entering a shopping mall, a customer goes to stores, an employee goes to the staff room, a delivery person goes to the loading dock. Each role has a different "next step."

| File | Role in Pattern |
|------|----------------|
| `app/Strategies/LoginStrategyInterface.php` | **Interface** — defines `handle(User): string` contract |
| `app/Strategies/AdminLoginStrategy.php` | **Concrete Strategy** — returns `'home'` |
| `app/Strategies/DonorLoginStrategy.php` | **Concrete Strategy** — returns `'home'` |
| `app/Strategies/RecipientLoginStrategy.php` | **Concrete Strategy** — returns `'home'` |
| `app/Services/AuthService.php::getLoginStrategy()` | **Context** — selects strategy by role |

**Benefits:** Add a new role = add one new Strategy class. Each role's logic is isolated and testable.

#### 5.3 Factory Pattern — User Data Creation

**Problem:** Creating user data requires different defaults per role. Factory centralizes creation logic.

**Real-life analogy:** A car factory produces sedans, SUVs, and trucks from one assembly line. You specify the type, it builds the right vehicle.

**File:** `app/Factories/UserFactory.php` — `make($data)` → matches role → `makeAdmin()`, `makeDonor()`, or `makeRecipient()` → returns merged data array. The `ROLES` constant `['donor', 'recipient', 'admin']` defines the supported set.

#### 5.4 Repository Pattern — Database Access Abstraction

**Problem:** Database queries scattered everywhere make it hard to change databases or test without a real database.

**Real-life analogy:** A library's card catalog tells you where every book is. You don't search every shelf — you ask the catalog. If books are reorganized, the catalog interface stays the same.

| File | Role in Pattern |
|------|----------------|
| `app/Repositories/UserRepositoryInterface.php` | **Interface** — contract: `findByEmail`, `findById`, `create`, `emailExists`, `update` |
| `app/Repositories/UserRepository.php` | **Concrete Implementation** — actually queries MySQL via Eloquent |
| `app/Providers/AppServiceProvider.php` | **Binding** — `UserRepositoryInterface::class → UserRepository::class` |

**Benefits:** Swap MySQL for PostgreSQL = write one new Repository class. Tests can use a mock Repository with fake data.

#### 5.5 Service Layer Pattern

**Problem:** Controllers become bloated when they contain business logic.

| File | Responsibility |
|------|---------------|
| `app/Services/AuthService.php` | All auth business logic: register, login, 2FA verify, password reset, resend code, update profile, change password |
| `app/Services/EmailService.php` | Email HTML generation and third-party API communication |

#### 5.6 Middleware Pattern

**Real-life analogy:** Airport security checkpoints. You pass through security (middleware) before reaching your gate (controller).

**Request chain for `GET /home`:**
```
Request → SecurityHeaders (adds CSP, XSS, etc.) → EncryptCookies → AddQueuedCookiesToResponse → StartSession → ShareErrorsFromSession → VerifyCsrfToken → SubstituteBindings → auth (checks login) → HomeController
```

**Files:** `app/Http/Middleware/RoleMiddleware.php` (auth check + case-insensitive role match + `abort(403)`), `app/Http/Middleware/SecurityHeaders.php` (7 security headers — see § 6.2).

#### 5.7 Dependency Injection

Used throughout. Instead of `new UserRepository()` inside a class, the dependency is "injected" via constructor. Laravel's Service Container resolves and provides the implementation automatically. Example: `AuthService` receives `UserRepositoryInterface` + `UserFactory` via constructor.

---

### 6. Database

**Type:** MySQL (port `8080` in this project's `.env`)

#### `users` Table — the only business table

| Column | Type | Nullable | Default | Purpose |
|--------|------|----------|---------|---------|
| `id` | bigint PK auto-increment | NO | — | Unique identifier |
| `firstname` | varchar(100) | YES | NULL | First name |
| `lastname` | varchar(100) | YES | NULL | Last name |
| `phone` | varchar(100) | YES | NULL | Phone (optional) |
| `email` | varchar(100) UNIQUE | NO | — | Login identifier |
| `password_hash` | varchar(100) | NO | — | Bcrypt-hashed password (NEVER plain text) |
| `role` | varchar(100) | YES | NULL | `"admin"` / `"donor"` / `"recipient"` |
| `two_factor_code` | varchar(100) | YES | NULL | 6-digit code (temporary) |
| `2FA_start` | bigint | YES | NULL | Unix timestamp of code generation (15-min expiry) |
| `is_verified` | tinyint | YES | 0 | 0 = unverified, 1 = verified |
| `verification_token` | varchar(64) | YES | NULL | SHA-256 hash for IDOR protection (temporary) |

**Sensitive fields:** `password_hash` and `two_factor_code` are in the User Model's `$hidden` — never exposed to JSON/frontend.

**Model specifics** (`app/Models/User.php`):
- `$table = 'users'`
- `$timestamps = false` (no `created_at` / `updated_at` columns)
- `$fillable`: firstname, lastname, phone, email, password_hash, role, two_factor_code, 2FA_start, is_verified, verification_token
- `casts()`: `'2FA_start' => 'integer'`, `'is_verified' => 'boolean'`
- Overrides `getAuthPassword()` to return `$this->password_hash`

#### Other Tables (auto-managed by Laravel)

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`, `sessions` — created by the default Laravel migrations in `database/migrations/0001_01_01_*.php`.

#### Migration History (5 files)

| Date | File | What it does |
|------|------|--------------|
| Laravel default | `0001_01_01_000001_create_cache_table.php` | Creates `cache` + `cache_locks` |
| Laravel default | `0001_01_01_000002_create_jobs_table.php` | Creates `jobs` + `job_batches` + `failed_jobs` |
| 2026-07-11 | `2026_07_11_020834_create_user_table.php` | Creates `users` table (8 columns initially) |
| 2026-07-11 | `2026_07_11_050839_add_is_verified_to_users_table.php` | Adds `is_verified` tinyint(0) |
| 2026-07-11 | `2026_07_11_082306_add_verification_token_to_users_table.php` | Adds `verification_token` varchar(64) for IDOR protection |
| 2026-07-12 | `2026_07_12_000001_change_2fa_start_to_bigint.php` | Changes `2FA_start` from timestamp → bigInteger (Unix timestamp; requires `doctrine/dbal`) |

---

### 7. Security Architecture

This project layers **seven defensive mechanisms** — described below in two groups: input/auth security (Layer 1) and HTTP-response security headers (Layer 2).

#### 7.1 Input & Authentication Security

| Protection | Where | How |
|------------|-------|-----|
| **bcrypt password hashing** | `AuthService` + `UserRepository` | `Hash::make()` with cost=12 (`4096` rounds), random salt per user — same password produces different hashes |
| **Google reCAPTCHA v2** | `AuthController::login` | Server-side verify at `https://www.recaptcha.net/recaptcha/api/siteverify` (mirror for mainland-China accessibility) |
| **Email 2FA** | `TwoFAController` + `AuthService` | 6-digit code, 15-minute expiry, single-use, resend rate-limited |
| **IDOR protection** | `AuthService::verify2FA`, `resetPassword` | `hash_equals(db.verification_token, session.verify_token)` — constant-time comparison prevents timing attacks |
| **CSRF protection** | Laravel `web` middleware group | Every POST/PUT/DELETE must carry `_token`; rejects mismatches with HTTP 419 |
| **Session Fixation defense** | `AuthController::login`, `updatePasswordForAuthenticatedUser` | `session()->regenerate()` after login + `session()->regenerateToken()` after password change |
| **User Enumeration defense** | `ForgotPasswordController::sendCode` | Always returns "code sent" regardless of whether email exists |
| **XSS protection** | Blade templates | `{{ }}` auto-escapes all output |
| **SQL injection defense** | Eloquent ORM | PDO parameterized queries (prepared statements); no raw SQL concatenation anywhere |
| **Constant-time password check** | `AuthService::updatePasswordForAuthenticatedUser` | `Hash::check()` is internally constant-time (bcrypt algorithm design) — prevents timing attacks |

#### 7.2 HTTP Response Security Headers — Seven Layers

Set by `app/Http/Middleware/SecurityHeaders.php` on every response:

| # | Header | Value | Defends Against |
|---|--------|-------|-----------------|
| 1 | **Content-Security-Policy** | `default-src 'self'; script-src 'self' 'unsafe-inline' https://www.recaptcha.net https://www.gstatic.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://www.gstatic.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://images.unsplash.com; frame-src 'self' https://www.recaptcha.net; frame-ancestors 'none'; base-uri 'self'; form-action 'self'` | XSS, data injection, clickjacking, form-redirection attacks |
| 2 | **X-XSS-Protection** | `1; mode=block` | Reflected XSS (legacy browser defense) |
| 3 | **X-Content-Type-Options** | `nosniff` | MIME-sniffing → XSS via uploaded files |
| 4 | **X-Frame-Options** | `DENY` | Clickjacking (overlaying transparent iframe) |
| 5 | **Referrer-Policy** | `strict-origin-when-cross-origin` | URL/path leakage to third parties |
| 6 | **Permissions-Policy** | `camera=(), microphone=(), geolocation=()` | Unauthorized hardware/data access even if XSS succeeds |
| 7 | **(remove) X-Powered-By** | removed | Server-technology reconnaissance (defense-in-depth "information concealment") |

**Note on `'unsafe-inline'`:** The CSP allows inline scripts and styles because reCAPTCHA and Google Fonts need them. This is a known trade-off — for stricter security, migrate to external files with nonce-based CSP.

**Middleware ordering:** `SecurityHeaders` must be the outermost middleware so it wraps every response, including redirects from `auth` middleware. It is registered globally in `bootstrap/app.php`.

---

### 8. Configuration Files

| File | Controls | Must Configure? |
|------|----------|----------------|
| `.env` | DB connection, email API key, reCAPTCHA keys, app key | **YES** — the most important file |
| `config/recaptcha.php` | Reads `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY` from `.env` | Requires .env values |
| `config/auth.php` | Auth guard (session), provider (Eloquent User model), password broker | Defaults work |
| `config/database.php` | MySQL host/port/database/username/password from `.env` | Requires .env values |
| `composer.json` | PHP dependencies: `laravel/framework 9.52.*`, `php ^8.0` | Managed by Composer |

**Required `.env` keys** (see `.env.example` for the full template):
```dotenv
APP_KEY=                                  # Generated by `php artisan key:generate`
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8080                              # ← this project uses 8080
DB_DATABASE=food_donation
DB_USERNAME=root
DB_PASSWORD=...

RECAPTCHA_SITE_KEY=...
RECAPTCHA_SECRET_KEY=...

SMTP_HOST=smtp.qq.com
SMTP_PORT=465
SMTP_KEY=...                              # qzqi.com API key for sending emails
SEND_EMAIL=...                            # Sender address
```

**⚠️ `.env` MUST NEVER be committed to Git.** It contains secrets. Use `.env.example` as a template.

---

### 9. Installation & Setup

**Prerequisites:** PHP 8.0+ (8.3+ recommended), Composer, MySQL 5.7+ / 8.0+, Terminal (Git Bash recommended on Windows).

```bash
# 1. Enter project directory
cd Z:/food_donation

# 2. Install PHP dependencies (reads composer.json, downloads into vendor/)
composer install

# 3. Create your environment file from the template
cp .env.example .env       # (or: copy .env.example .env on Windows cmd)

# 4. Edit .env — set your MySQL credentials, email API key, reCAPTCHA keys

# 5. Generate application encryption key (required for sessions/cookies)
php artisan key:generate

# 6. Run database migrations (creates all tables in MySQL)
php artisan migrate

# 7. (Optional) Create an admin account (CLI-only — no UI by design)
php artisan tinker --execute="App\Models\User::create(['firstname'=>'Admin','lastname'=>'User','email'=>'admin@example.com','password_hash'=>Hash::make('123456'),'role'=>'admin','is_verified'=>1])"

# 8. Start development server
php artisan serve
# (defaults to http://127.0.0.1:8000)

# 9. Open browser → http://127.0.0.1:8000

# 10. Stop server: Ctrl+C
```

#### Common Startup Issues

| Problem | Likely Cause | Solution |
|---------|-------------|----------|
| "Could not connect to database" | Wrong port/host/password in `.env` | Verify MySQL port (this project uses `8080`, default is `3306`) |
| "Table not found" | Migrations not run | `php artisan migrate` |
| "No application encryption key" | `key:generate` not run | `php artisan key:generate` |
| reCAPTCHA not showing | Wrong site key or CSP blocking | Verify CSP allows `recaptcha.net` and `gstatic.com` |
| Emails not sending | Wrong `SMTP_KEY` or API connectivity | Check `storage/logs/laravel.log` |
| "419 CSRF token mismatch" | Session expired | Refresh page |
| "Please register first" | Session flash token expired | Restart registration from the beginning |
| Port 8000 in use | Another server running | `php artisan serve --port=8001` |

---

### 10. Routes Overview

All routes are defined in `routes/web.php`. Summary table:

| Method | URL | Controller@action | Middleware | Name |
|--------|-----|-------------------|------------|------|
| GET | `/login` | `AuthController@showLogin` | web | `login` |
| POST | `/login` | `AuthController@login` | web | — |
| GET | `/register` | `AuthController@showRegister` | web | `register` |
| POST | `/register` | `AuthController@register` | web | — |
| POST | `/logout` | `AuthController@logout` | web | `logout` |
| GET | `/verify-2fa` | `TwoFAController@showVerifyForm` | web | `verify2fa.form` |
| POST | `/verify-2fa` | `TwoFAController@verify` | web | `verify2fa.verify` |
| POST | `/verify-2fa/resend` | `TwoFAController@resend` | web | `verify2fa.resend` |
| GET | `/registered` | (closure) | web | `registered` |
| GET | `/forgot-password` | `ForgotPasswordController@showForm` | web | `password.forgot` |
| POST | `/forgot-password` | `ForgotPasswordController@sendCode` | web | `password.send-code` |
| GET | `/reset-password` | `ForgotPasswordController@showResetForm` | web | `password.reset.form` |
| POST | `/reset-password` | `ForgotPasswordController@reset` | web | `password.reset` |
| GET | `/` | (closure → `welcome.blade.php`) | web | — |
| GET | `/home` | `HomeController@index` | `auth` | `home` |
| GET | `/profile` | `ProfileController@edit` | `auth` | `profile.edit` |
| POST | `/profile` | `ProfileController@update` | `auth` | `profile.update` |
| GET | `/profile/password` | `ProfileController@showPasswordForm` | `auth` | `profile.password.form` |
| POST | `/profile/password` | `ProfileController@updatePassword` | `auth` | `profile.password.update` |

`POST /login`, `POST /register`, `POST /logout`, `POST /verify-2fa/*`, `POST /forgot-password`, `POST /reset-password`, `POST /profile`, `POST /profile/password` — all state-changing operations use POST (not GET) to defend against CSRF-assisted attacks and forced-logout patterns.

---

### 11. Recommended Reading Order for Beginners

```
1.  README.md (this file)                          — Project overview
2.  .env.example                                   — Required configuration
3.  routes/web.php                                  — All URLs and controller mappings
4.  public/index.php                                — Entry point
5.  bootstrap/app.php                               — Framework boot + middleware registration
6.  app/Models/User.php                             — Database table definition
7.  app/Http/Controllers/AuthController.php         — Login & registration logic
8.  app/Http/Controllers/ProfileController.php      — Profile edit & password change
9.  app/Services/AuthService.php                    — Core business logic
10. app/Repositories/UserRepositoryInterface.php    — Database access contract
11. app/Repositories/UserRepository.php             — Database query implementation
12. app/Factories/UserFactory.php                   — User data creation (Factory Pattern)
13. app/Strategies/LoginStrategyInterface.php        — Strategy pattern start
14. app/Strategies/*Strategy.php                     — Role-specific strategies
15. app/Http/Middleware/SecurityHeaders.php          — Seven security headers
16. app/Http/Middleware/RoleMiddleware.php           — Role-based access control
17. app/Services/EmailService.php                   — Third-party email sending
18. database/migrations/2026_07_11_020834_*.php     — Users table structure
19. resources/views/auth/index.blade.php             — Main auth page (HTML/CSS/JS)
20. resources/views/home.blade.php                   — Homepage
21. resources/views/profile/*.blade.php              — Profile edit / change password
```

---

### 12. FAQ

**Q: Why is the password column called `password_hash` instead of `password`?**
A: The project was designed this way. The User Model overrides `getAuthPassword()` to tell Laravel the password lives in the `password_hash` column.

**Q: What is IDOR and how does this project prevent it?**
A: IDOR (Insecure Direct Object Reference) is when attackers guess/manipulate identifiers to access others' data. FoodShare prevents it by generating a SHA-256 `verification_token` stored in both the database and session. The token must match exactly (using `hash_equals` to prevent timing attacks) before any verification proceeds.

**Q: How does XSS protection work?**
A: Three layers: (1) Blade `{{ }}` auto-escapes HTML output, (2) CSP header blocks inline scripts from unknown origins, (3) `X-XSS-Protection: 1; mode=block` enables browser XSS filter.

**Q: How does SQL injection protection work?**
A: Eloquent ORM uses PDO parameterized queries (prepared statements) by default. No raw SQL strings are concatenated with user input anywhere in the application.

**Q: Why use a third-party email API instead of SMTP?**
A: The qzqi.com API avoids SMTP server configuration complexity and may work through firewall restrictions that block SMTP ports (e.g., corporate / mainland-China networks).

**Q: Why are styles inlined in Blade templates instead of separate CSS files?**
A: Each page has a self-contained design system (organic biophilic theme with green/amber palette and Lora/Raleway fonts). Inline styles keep each page independent and easier to modify individually.

**Q: Why does CSP allow `unsafe-inline`?**
A: Because reCAPTCHA and Google Fonts need inline scripts/styles. This is a known trade-off — for stricter security, styles/scripts should be extracted to external files with CSP nonces.

**Q: Why no `created_at`/`updated_at` columns on `users`?**
A: The model sets `$timestamps = false` because the original schema omitted them. Consider adding them for audit trails.

**Q: How is admin created if there's no registration UI for it?**
A: Admin accounts must be created via `php artisan tinker` (or a future admin panel). This is intentional — preventing self-registration as admin is a security measure.

---

### 13. Known Issues & Improvement Suggestions

1. **No `created_at`/`updated_at` on `users` table:** Consider adding for audit trails.
2. **SSL verification disabled in `EmailService`:** Acceptable for development; enable in production with valid certificates.
3. **CSP `unsafe-inline`:** Allows inline scripts/styles. For stricter security, migrate to external files with nonce-based CSP.
4. **Admin creation via CLI only:** No UI for creating admin accounts — must use `php artisan tinker`. Intentional security measure.
5. **No resend-code rate limit enforced server-side:** Client shows a countdown, but the server does not currently throttle POST `/verify-2fa/resend` requests. Recommend adding Laravel's `throttle` middleware.
6. **`edit_profile/` and `edit_profile.zip` removed:** A prior development snapshot was deleted from the repository; current production files in `app/` and `resources/views/profile/` are the source of truth.

---

## 中文版本

### 1. 项目简介

**FoodShare（食物分享）** 是一个基于 Web 的食物捐赠平台，连接**捐赠者**（有多余食物的人）、**受助者**（需要食物的个人或组织）和**管理员**（平台运营者），致力于减少食物浪费、帮助有需要的群体。

**核心问题：** 剩余食物经常被浪费，而许多家庭和慈善机构难以获得营养餐食。FoodShare 通过提供安全、经过验证的捐赠 / 受赠平台来弥合这一差距。

**核心功能：**
- 邮箱 2FA 注册验证（6 位数字验证码，15 分钟过期，单次使用）
- Google reCAPTCHA v2 安全登录（使用 `recaptcha.net` 镜像，国内可访问）
- 邮箱验证码两阶段密码重置（先验证邮箱，再重置密码）
- 基于角色的访问控制（Admin / Donor / Recipient，三种登录策略）
- 资料管理：可修改姓名和电话
- 已登录用户修改密码（需输入当前密码验证本人）
- 实时表单验证（带可视化密码强度指示器）
- 个性化首页（从数据库加载用户资料）
- 七层 HTTP 安全响应头（CSP、XSS、MIME 嗅探、点击劫持、Referer、权限策略、X-Powered-By 移除）

**技术栈：**

| 技术 | 版本 | 项目角色 |
|------|------|---------|
| **PHP** | `^8.0`（实测 `8.3.32`） | 后端编程语言，运行所有业务逻辑 |
| **Laravel** | `9.52.*`（实测 `9.52.22`） | Web 框架，负责路由、Eloquent ORM、会话、中间件 |
| **MySQL** | `5.7+` / `8.0+`（本项目端口 `8080`） | 数据库，存储用户、密码哈希、验证码 |
| **Blade** | Laravel 内置 | 模板引擎，使用 `{{ }}` 自动转义防 XSS |
| **原生 HTML/CSS/JS** | — | 前端，无需 React / Vue / 构建步骤 |
| **Google reCAPTCHA v2** | — | "我不是机器人"复选框，防止机器人滥用登录 |
| **第三方邮件 API（qzqi.com）** | — | 通过 HTTP API 发送 HTML 验证码，绕过 SMTP 防火墙限制 |

**前后端与数据库协作方式：**

```
[浏览器 / HTML+CSS+JS]  ←HTTP→  [Laravel / PHP 后端]  ←SQL→  [MySQL 数据库]
   用户看到和操作的页面           业务逻辑 + 路由 + 数据处理       数据持久化存储
```

---

### 2. 系统架构

把 FoodShare 想象成一家**餐厅厨房**来理解请求流程：

1. **你（顾客）** 看菜单 → 在浏览器浏览网页
2. **服务员（路由）** 接受点单 → `routes/web.php` 将 URL 匹配到控制器
3. **厨师（控制器）** 读取订单 → 验证输入、调用辅助模块
4. **副厨（服务层）** 处理复杂步骤 → `AuthService` 中的业务逻辑
5. **储藏室（仓储）** 取食材 → `UserRepository` 查询 MySQL
6. **菜谱（模型）** 定义食材 → `User` 模型定义 `users` 表结构
7. **保安（中间件）** 在每道门前核验身份 → `auth`、`role:`、`SecurityHeaders`
8. **服务员** 上菜 → HTTP 响应（HTML 页面或重定向）

**典型请求链（`GET /home`）：**

```
请求 → SecurityHeaders（添加 7 个安全响应头）
    → EncryptCookies
    → AddQueuedCookiesToResponse
    → StartSession
    → ShareErrorsFromSession
    → VerifyCsrfToken
    → SubstituteBindings
    → auth 中间件（检查登录状态，未登录则 302 到 /login）
    → HomeController@index()
    → 响应对象（已附加 7 个安全头部）返回给浏览器
```

**组件职责：**

| 组件 | 负责 | 不负责 |
|------|------|--------|
| **路由**（web.php） | URL → 控制器映射 | 业务逻辑、数据库 |
| **控制器** | 接收输入、验证、调用服务、返回响应 | 直接 SQL 查询 |
| **服务层**（AuthService） | 业务逻辑、密码哈希、令牌生成、过期检查 | HTML 渲染 |
| **仓储**（UserRepository） | 数据库 CRUD 操作 | 业务规则 |
| **模型**（User） | 表字段映射、属性类型转换、字段隐藏 | 应用逻辑 |
| **策略** | 角色特定的跳转目标 | 通用逻辑 |
| **工厂**（UserFactory） | 按角色生成用户数据数组 | 数据库操作 |
| **中间件** | 请求过滤（登录、角色、安全头） | 业务逻辑 |
| **视图**（Blade） | HTML 渲染、CSS 样式、JS 交互 | 数据处理 |

---

### 3. 核心功能详解

#### 3.1 注册 + 邮箱 2FA 验证

```
注册表单（index.blade.php 注册标签）
  → 实时 JS 校验：姓名、邮箱、密码（≥8 位且包含大小写+数字）、确认密码、角色已选
  → 同意条款复选框选中
  → 提交按钮启用
  ↓
POST /register → AuthController::register()
  ├── 服务端校验所有字段
  ├── 自定义邮箱规则：仅当邮箱已存在且 is_verified=1 时拦截
  │   （之前未验证可重新注册同名邮箱）
  ├── 角色限定为 ['donor', 'recipient']，禁止自助注册为 admin
  ├── UserFactory::make() → 按角色准备用户数据
  ├── 生成 6 位随机验证码 + SHA-256 verification_token
  ├── UserRepository::create() → 插入 users 表（is_verified=0）
  ├── EmailService::buildVerificationEmail() → 构建 HTML 邮件
  ├── EmailService::sendHtmlMail() → POST 到 api.qzqi.com 发送邮件
  └── 重定向 → /verify-2fa（Session flash 携带 email + verify_token）
  ↓
GET /verify-2fa → TwoFAController::showVerifyForm()
  ├── 检查 Session 中有 email + verify_token
  ├── session()->keep(['verify_token', 'email']) ← 保留 flash 供 POST 使用
  └── 渲染 verify-2fa.blade.php（6 格数字输入框、15 分钟倒计时、重发链接）
  ↓
用户输入 6 位验证码 → POST /verify-2fa
  ├── TwoFAController::verify()
  ├── AuthService::verify2FA()
  │   ├── hash_equals(db.verification_token, session.verify_token) ← IDOR 防护
  │   ├── Carbon::createFromTimestamp(db.2FA_start)->addMinutes(15) ← 过期检查
  │   ├── db.two_factor_code === 用户输入 ← 验证码匹配
  │   └── 成功：清除 code + token，设置 is_verified=1
  └── 重定向 → /registered（成功弹窗 + 撒花动画，3 秒后自动跳转）
```

#### 3.2 登录 + reCAPTCHA 验证

```
用户访问 /login → reCAPTCHA 脚本从 www.recaptcha.net 加载
用户填写邮箱 + 密码
  → 实时 JS：邮箱格式校验、密码非空
  → 用户勾选"我不是机器人" → reCAPTCHA 回调写入隐藏字段
  → 登录按钮启用
  ↓
POST /login → AuthController::login()
  ├── 校验 email、password、g-recaptcha-response
  ├── Http::post('https://www.recaptcha.net/recaptcha/api/siteverify', [secret, response])
  ├── AuthService::login() → 查用户、校验密码、校验已验证
  ├── Auth::login() → 创建会话，重新生成 Session ID
  └── LoginStrategy → 返回 route('home') → 跳转到 /home
```

#### 3.3 密码重置（两阶段）

```
用户在登录页点击"忘记密码？"
  ↓
GET /forgot-password → 显示邮箱输入表单（forgot-password.blade.php）
  ↓
用户输入邮箱 → POST /forgot-password
  ├── AuthService::sendResetCode()
  │   ├── 查找已验证用户
  │   ├── 生成 6 位验证码 + SHA-256 令牌
  │   └── 更新数据库：two_factor_code + 2FA_start + verification_token
  ├── EmailService 发送验证码
  └── 重定向 → /reset-password（Session flash 携带 email + reset_token）
  ↓
GET /reset-password → ForgotPasswordController::showResetForm()
  ├── 检查 Session 有 email + reset_token
  ├── session()->keep(['reset_token', 'email'])
  └── 渲染 reset-password.blade.php（6 格验证码 + 新密码 + 确认密码）
  ↓
用户输入验证码 + 新密码 → POST /reset-password
  ├── ForgotPasswordController::reset()
  ├── AuthService::resetPassword()
  │   ├── hash_equals(db.verification_token, session.reset_token) ← IDOR 防护
  │   ├── Carbon::createFromTimestamp(db.2FA_start)->addMinutes(15) ← 过期
  │   ├── 验证码匹配
  │   └── 成功：Hash::make(newPassword) → 更新 password_hash，清除 code
  └── 返回 password_reset_done 标记 → 绿色成功弹窗
```

**安全要点：** 无论邮箱是否存在数据库中，系统都返回相同的"验证码已发送"提示，**防止用户枚举攻击**。

#### 3.4 首页（登录后）

```
登录成功 → 跳转到 /home
  ↓
HomeController::index()
  ├── 获取当前小时 → 时段问候（早上好 / 下午好 / 晚上好）
  ├── 获取用户角色 → 角色专属副标题
  ├── 获取角色显示名（Donor / Recipient / Administrator）
  └── 渲染 home.blade.php（姓名、邮箱、电话、角色、头像首字母）
```

#### 3.5 资料管理（编辑资料 / 修改密码）

```
已登录用户点击导航栏"Profile"
  ↓
GET /profile → ProfileController::edit()
  ├── 从 Auth::user() 读取当前用户
  └── 渲染 profile/edit.blade.php（firstname / lastname / phone 可编辑；email + role 只读）
  ↓
用户编辑资料，点击"保存" → POST /profile
  ├── 校验字段
  ├── AuthService::updateProfile(user, $data) → 仅写入 firstname / lastname / phone
  └── 返回成功消息
  ↓
用户点击"修改密码" → GET /profile/password
  └── 渲染 profile/password.blade.php（当前密码 / 新密码 / 确认新密码）
  ↓
用户填写表单 → POST /profile/password
  ├── 校验三字段
  ├── AuthService::updatePasswordForAuthenticatedUser()
  │   ├── Hash::check(current, user.password_hash) ← constant-time 比较
  │   ├── Hash::make(new) → 更新 password_hash
  │   └── session()->regenerateToken() ← 刷新 CSRF 令牌
  └── 返回成功消息
```

**相关路由：** `GET /profile`、`POST /profile`、`GET /profile/password`、`POST /profile/password` — 均挂载 `auth` 中间件。

---

### 4. 设计模式

#### 4.1 MVC（Model-View-Controller）— 基础架构模式

Laravel 的根本模式。如餐厅：Model = 食材，View = 装盘后的菜品，Controller = 协调工作的厨师。

| MVC 角色 | 项目文件 |
|---------|---------|
| Model | `app/Models/User.php` |
| View | `resources/views/auth/*.blade.php`、`home.blade.php`、`profile/*.blade.php`、`welcome.blade.php` |
| Controller | `app/Http/Controllers/*.php`（Auth、TwoFA、ForgotPassword、Home、Profile） |

#### 4.2 策略模式 — 登录后按角色跳转

**问题：** 登录后不同角色应去不同位置，否则会产生混乱的 if/else 链。

**类比：** 顾客进商场去店铺、员工去休息室、外卖员去卸货区 —— 不同角色有不同"下一步"。

| 文件 | 模式中的角色 |
|------|------------|
| `app/Strategies/LoginStrategyInterface.php` | **接口** — 定义 `handle(User): string` 契约 |
| `app/Strategies/AdminLoginStrategy.php` | **具体策略** — 返回 `'home'` |
| `app/Strategies/DonorLoginStrategy.php` | **具体策略** — 返回 `'home'` |
| `app/Strategies/RecipientLoginStrategy.php` | **具体策略** — 返回 `'home'` |
| `app/Services/AuthService.php::getLoginStrategy()` | **上下文** — 根据角色选择策略 |

**好处：** 新增角色只需新增一个 Strategy 类，每个角色的逻辑独立、可测试。

#### 4.3 工厂模式 — 用户数据创建

**问题：** 创建用户数据需按角色设置不同默认值，工厂集中创建逻辑。

**类比：** 汽车工厂用一条流水线生产轿车、SUV、卡车 —— 指定类型，它造出对应的车。

**文件：** `app/Factories/UserFactory.php` — `make($data)` → 匹配角色 → `makeAdmin()` / `makeDonor()` / `makeRecipient()` → 返回合并后的数据数组。`ROLES` 常量 `['donor', 'recipient', 'admin']` 定义支持的集合。

#### 4.4 仓储模式 — 数据库访问抽象

**问题：** 数据库查询散落各处，难以更换数据库或在没有真实数据库时测试。

**类比：** 图书馆的目录卡告诉你每本书在哪里，不用挨个书架找。书被重新组织后，目录接口不变。

| 文件 | 模式中的角色 |
|------|------------|
| `app/Repositories/UserRepositoryInterface.php` | **接口** — 契约：`findByEmail`、`findById`、`create`、`emailExists`、`update` |
| `app/Repositories/UserRepository.php` | **具体实现** — 通过 Eloquent 实际查询 MySQL |
| `app/Providers/AppServiceProvider.php` | **绑定** — `UserRepositoryInterface::class → UserRepository::class` |

**好处：** 把 MySQL 换成 PostgreSQL = 只需新写一个仓储类。测试时可使用带假数据的 mock 仓储。

#### 4.5 服务层模式

**问题：** 控制器包含业务逻辑后会臃肿。

| 文件 | 职责 |
|------|------|
| `app/Services/AuthService.php` | 所有认证业务逻辑：注册、登录、2FA 验证、密码重置、重发验证码、资料更新、修改密码 |
| `app/Services/EmailService.php` | 邮件 HTML 生成 + 第三方 API 通信 |

#### 4.6 中间件模式

**类比：** 机场安检。通过安检（中间件）才能到登机口（控制器）。

**`GET /home` 的请求链：**
```
请求 → SecurityHeaders（添加 CSP、XSS 等 7 个安全头）
    → EncryptCookies
    → AddQueuedCookiesToResponse
    → StartSession
    → ShareErrorsFromSession
    → VerifyCsrfToken
    → SubstituteBindings
    → auth（检查登录） → HomeController
```

**文件：** `app/Http/Middleware/RoleMiddleware.php`（登录检查 + 不区分大小写角色匹配 + 不匹配时 `abort(403)`），`app/Http/Middleware/SecurityHeaders.php`（7 个安全头部，见 § 5.2）。

#### 4.7 依赖注入

贯穿整个项目。不是在类内部 `new UserRepository()`，而是通过构造函数"注入"依赖。Laravel 的服务容器自动解析并提供实现。例如 `AuthService` 通过构造函数接收 `UserRepositoryInterface` + `UserFactory`。

---

### 5. 数据库

**类型：** MySQL（本项目 `.env` 中端口为 `8080`）

#### `users` 表 — 唯一的业务表

| 列名 | 类型 | 可空 | 默认值 | 说明 |
|------|------|------|--------|------|
| `id` | bigint 主键自增 | 否 | — | 唯一标识 |
| `firstname` | varchar(100) | 是 | NULL | 名 |
| `lastname` | varchar(100) | 是 | NULL | 姓 |
| `phone` | varchar(100) | 是 | NULL | 电话（可选） |
| `email` | varchar(100) UNIQUE | 否 | — | 登录账号 |
| `password_hash` | varchar(100) | 否 | — | bcrypt 哈希密码（绝不存明文） |
| `role` | varchar(100) | 是 | NULL | `"admin"` / `"donor"` / `"recipient"` |
| `two_factor_code` | varchar(100) | 是 | NULL | 6 位验证码（临时） |
| `2FA_start` | bigint | 是 | NULL | 验证码生成的 Unix 时间戳（15 分钟过期） |
| `is_verified` | tinyint | 是 | 0 | 0 = 未验证，1 = 已验证 |
| `verification_token` | varchar(64) | 是 | NULL | IDOR 防护的 SHA-256 哈希（临时） |

**敏感字段：** `password_hash` 和 `two_factor_code` 在 User Model 的 `$hidden` 中 — 永远不会暴露给 JSON / 前端。

**模型细节**（`app/Models/User.php`）：
- `$table = 'users'`
- `$timestamps = false`（没有 `created_at` / `updated_at` 列）
- `$fillable`：firstname、lastname、phone、email、password_hash、role、two_factor_code、2FA_start、is_verified、verification_token
- `casts()`：`'2FA_start' => 'integer'`，`'is_verified' => 'boolean'`
- 覆盖 `getAuthPassword()` 返回 `$this->password_hash`

#### 其他表（Laravel 自动管理）

`cache`、`cache_locks`、`jobs`、`job_batches`、`failed_jobs`、`migrations`、`sessions` — 由 Laravel 默认迁移 `database/migrations/0001_01_01_*.php` 创建。

#### 迁移历史（5 个文件）

| 日期 | 文件 | 作用 |
|------|------|------|
| Laravel 默认 | `0001_01_01_000001_create_cache_table.php` | 创建 `cache` + `cache_locks` |
| Laravel 默认 | `0001_01_01_000002_create_jobs_table.php` | 创建 `jobs` + `job_batches` + `failed_jobs` |
| 2026-07-11 | `2026_07_11_020834_create_user_table.php` | 创建 `users` 表（初始 8 列） |
| 2026-07-11 | `2026_07_11_050839_add_is_verified_to_users_table.php` | 添加 `is_verified` tinyint(0) |
| 2026-07-11 | `2026_07_11_082306_add_verification_token_to_users_table.php` | 添加 `verification_token` varchar(64)（IDOR 防护） |
| 2026-07-12 | `2026_07_12_000001_change_2fa_start_to_bigint.php` | 将 `2FA_start` 从 timestamp 改为 bigInteger（Unix 时间戳；需要 `doctrine/dbal`） |

---

### 6. 安全架构

本项目分层部署了**七层防御机制**，分两组说明：输入/认证安全（第一层）和 HTTP 响应安全头（第二层）。

#### 6.1 输入与认证安全

| 防护 | 位置 | 实现方式 |
|------|------|---------|
| **bcrypt 密码哈希** | `AuthService` + `UserRepository` | `Hash::make()`，cost=12（4096 轮），每用户随机盐值 — 相同密码产生不同哈希 |
| **Google reCAPTCHA v2** | `AuthController::login` | 服务端验证 `https://www.recaptcha.net/recaptcha/api/siteverify`（国内可访问的镜像） |
| **邮箱 2FA** | `TwoFAController` + `AuthService` | 6 位验证码，15 分钟过期，单次使用，重发频率限制 |
| **IDOR 防护** | `AuthService::verify2FA`、`resetPassword` | `hash_equals(db.verification_token, session.verify_token)` — 常量时间比较防止时序攻击 |
| **CSRF 防护** | Laravel `web` 中间件组 | 所有 POST/PUT/DELETE 必须携带 `_token`，不匹配返回 HTTP 419 |
| **会话固定防护** | `AuthController::login`、`updatePasswordForAuthenticatedUser` | 登录后 `session()->regenerate()`，改密后 `session()->regenerateToken()` |
| **用户枚举防护** | `ForgotPasswordController::sendCode` | 不论邮箱是否存在都返回"验证码已发送" |
| **XSS 防护** | Blade 模板 | `{{ }}` 自动转义所有输出 |
| **SQL 注入防护** | Eloquent ORM | PDO 参数化查询（预处理语句）；项目任何地方都不拼接用户输入到 SQL |
| **常量时间密码校验** | `AuthService::updatePasswordForAuthenticatedUser` | `Hash::check()` 内部是常量时间（bcrypt 算法设计），防止时序攻击 |

#### 6.2 HTTP 响应安全头 — 七层防护

由 `app/Http/Middleware/SecurityHeaders.php` 在每个响应上设置：

| # | 头部 | 值 | 防御目标 |
|---|------|---|---------|
| 1 | **Content-Security-Policy** | `default-src 'self'; script-src 'self' 'unsafe-inline' https://www.recaptcha.net https://www.gstatic.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://www.gstatic.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://images.unsplash.com; frame-src 'self' https://www.recaptcha.net; frame-ancestors 'none'; base-uri 'self'; form-action 'self'` | XSS、数据注入、点击劫持、表单重定向攻击 |
| 2 | **X-XSS-Protection** | `1; mode=block` | 反射型 XSS（旧浏览器防御） |
| 3 | **X-Content-Type-Options** | `nosniff` | MIME 嗅探 → 上传文件 XSS |
| 4 | **X-Frame-Options** | `DENY` | 点击劫持（透明 iframe 覆盖） |
| 5 | **Referrer-Policy** | `strict-origin-when-cross-origin` | URL/路径泄露给第三方 |
| 6 | **Permissions-Policy** | `camera=(), microphone=(), geolocation=()` | 即使 XSS 成功也禁止摄像头/麦克风/地理位置访问 |
| 7 | **（移除）X-Powered-By** | 已移除 | 服务端技术侦察（纵深防御中的"信息隐蔽"环节） |

**关于 `'unsafe-inline'` 的说明：** CSP 允许内联脚本和样式，因为 reCAPTCHA 和 Google Fonts 需要它们。这是已知权衡 — 若要更严格，应将样式/脚本提取到外部文件并使用基于 nonce 的 CSP。

**中间件顺序：** `SecurityHeaders` 必须是**最外层**中间件，这样它能包装包括 `auth` 重定向在内的所有响应。它在 `bootstrap/app.php` 中全局注册。

---

### 7. 配置文件

| 文件 | 控制内容 | 是否必须配置 |
|------|---------|------------|
| `.env` | 数据库连接、邮件 API key、reCAPTCHA 密钥、应用 key | **是** — 最重要的文件 |
| `config/recaptcha.php` | 从 `.env` 读取 `RECAPTCHA_SITE_KEY` 和 `RECAPTCHA_SECRET_KEY` | 需要 `.env` 有值 |
| `config/auth.php` | 认证 guard（session）、provider（Eloquent User model）、密码 broker | 默认即可 |
| `config/database.php` | 从 `.env` 读取 MySQL host/port/database/username/password | 需要 `.env` 有值 |
| `composer.json` | PHP 依赖：`laravel/framework 9.52.*`、`php ^8.0` | 由 Composer 管理 |

**必填的 `.env` 键**（完整模板见 `.env.example`）：
```dotenv
APP_KEY=                                  # 通过 `php artisan key:generate` 生成
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8080                              # ← 本项目使用 8080
DB_DATABASE=food_donation
DB_USERNAME=root
DB_PASSWORD=...

RECAPTCHA_SITE_KEY=...
RECAPTCHA_SECRET_KEY=...

SMTP_HOST=smtp.qq.com
SMTP_PORT=465
SMTP_KEY=...                              # qzqi.com API 密钥，用于发送邮件
SEND_EMAIL=...                            # 发件人地址
```

**⚠️ `.env` 严禁提交到 Git。** 它包含密钥。以 `.env.example` 为模板。

---

### 8. 安装步骤

**前置条件：** PHP 8.0+（推荐 8.3+）、Composer、MySQL 5.7+ / 8.0+、终端（Windows 上推荐 Git Bash）。

```bash
# 1. 进入项目目录
cd Z:/food_donation

# 2. 安装 PHP 依赖（读取 composer.json，下载到 vendor/）
composer install

# 3. 从模板创建环境文件
cp .env.example .env       # Windows cmd 下用：copy .env.example .env

# 4. 编辑 .env — 设置 MySQL 凭证、邮件 API key、reCAPTCHA 密钥

# 5. 生成应用加密密钥（用于 Session 和 Cookie）
php artisan key:generate

# 6. 运行数据库迁移（在 MySQL 中创建所有表）
php artisan migrate

# 7.（可选）创建管理员账户（仅命令行 — 无 UI 是设计上的安全考虑）
php artisan tinker --execute="App\Models\User::create(['firstname'=>'Admin','lastname'=>'User','email'=>'admin@example.com','password_hash'=>Hash::make('123456'),'role'=>'admin','is_verified'=>1])"

# 8. 启动开发服务器
php artisan serve
# （默认 http://127.0.0.1:8000）

# 9. 浏览器打开 → http://127.0.0.1:8000

# 10. 停止服务器：Ctrl+C
```

#### 常见启动问题

| 问题 | 可能原因 | 解决方案 |
|------|---------|---------|
| "Could not connect to database" | `.env` 中端口/主机/密码错误 | 确认 MySQL 端口（本项目 `8080`，默认 `3306`） |
| "Table not found" | 未运行迁移 | `php artisan migrate` |
| "No application encryption key" | 未运行 key:generate | `php artisan key:generate` |
| reCAPTCHA 不显示 | 站点密钥错误或 CSP 拦截 | 确认 CSP 允许 `recaptcha.net` 和 `gstatic.com` |
| 邮件发送失败 | `SMTP_KEY` 错误或 API 不通 | 查看 `storage/logs/laravel.log` |
| "419 CSRF token mismatch" | Session 过期 | 刷新页面 |
| "Please register first" | Session flash 令牌过期 | 从头开始重新注册 |
| 端口 8000 被占用 | 其他服务器在运行 | `php artisan serve --port=8001` |

---

### 9. 路由总览

所有路由定义在 `routes/web.php`：

| 方法 | URL | Controller@action | 中间件 | 路由名 |
|------|-----|-------------------|--------|--------|
| GET | `/login` | `AuthController@showLogin` | web | `login` |
| POST | `/login` | `AuthController@login` | web | — |
| GET | `/register` | `AuthController@showRegister` | web | `register` |
| POST | `/register` | `AuthController@register` | web | — |
| POST | `/logout` | `AuthController@logout` | web | `logout` |
| GET | `/verify-2fa` | `TwoFAController@showVerifyForm` | web | `verify2fa.form` |
| POST | `/verify-2fa` | `TwoFAController@verify` | web | `verify2fa.verify` |
| POST | `/verify-2fa/resend` | `TwoFAController@resend` | web | `verify2fa.resend` |
| GET | `/registered` | （闭包） | web | `registered` |
| GET | `/forgot-password` | `ForgotPasswordController@showForm` | web | `password.forgot` |
| POST | `/forgot-password` | `ForgotPasswordController@sendCode` | web | `password.send-code` |
| GET | `/reset-password` | `ForgotPasswordController@showResetForm` | web | `password.reset.form` |
| POST | `/reset-password` | `ForgotPasswordController@reset` | web | `password.reset` |
| GET | `/` | （闭包 → `welcome.blade.php`） | web | — |
| GET | `/home` | `HomeController@index` | `auth` | `home` |
| GET | `/profile` | `ProfileController@edit` | `auth` | `profile.edit` |
| POST | `/profile` | `ProfileController@update` | `auth` | `profile.update` |
| GET | `/profile/password` | `ProfileController@showPasswordForm` | `auth` | `profile.password.form` |
| POST | `/profile/password` | `ProfileController@updatePassword` | `auth` | `profile.password.update` |

`POST /login`、`POST /register`、`POST /logout`、`POST /verify-2fa/*`、`POST /forgot-password`、`POST /reset-password`、`POST /profile`、`POST /profile/password` — 所有"修改状态"操作都使用 POST（而非 GET），以防御 CSRF 辅助的攻击和"强制退出"模式。

---

### 10. 入门推荐阅读顺序

```
1.  README.md（本文件）                         — 项目总览
2.  .env.example                              — 必需配置
3.  routes/web.php                            — 所有 URL 与控制器映射
4.  public/index.php                          — 入口文件
5.  bootstrap/app.php                         — 框架启动 + 中间件注册
6.  app/Models/User.php                       — 数据库表定义
7.  app/Http/Controllers/AuthController.php   — 登录与注册逻辑
8.  app/Http/Controllers/ProfileController.php — 资料编辑与密码修改
9.  app/Services/AuthService.php              — 核心业务逻辑
10. app/Repositories/UserRepositoryInterface.php — 数据库访问契约
11. app/Repositories/UserRepository.php       — 数据库查询实现
12. app/Factories/UserFactory.php             — 用户数据创建（工厂模式）
13. app/Strategies/LoginStrategyInterface.php  — 策略模式起点
14. app/Strategies/*Strategy.php               — 角色专属策略
15. app/Http/Middleware/SecurityHeaders.php    — 七个安全响应头
16. app/Http/Middleware/RoleMiddleware.php     — 基于角色的访问控制
17. app/Services/EmailService.php             — 第三方邮件发送
18. database/migrations/2026_07_11_020834_*.php — users 表结构
19. resources/views/auth/index.blade.php       — 主登录/注册页（HTML/CSS/JS）
20. resources/views/home.blade.php             — 首页
21. resources/views/profile/*.blade.php        — 资料编辑 / 修改密码
```

---

### 11. 常见问题

**问：为什么密码字段叫 `password_hash` 而不是 `password`？**
答：项目如此设计。User Model 覆盖了 `getAuthPassword()` 方法，告知 Laravel 密码位于 `password_hash` 列。

**问：什么是 IDOR，本项目如何防护？**
答：IDOR（Insecure Direct Object Reference，不安全的直接对象引用）指攻击者猜测/操纵标识符访问他人数据。本项目通过生成 SHA-256 `verification_token` 同时存储在数据库和会话中防护；任何验证前必须用 `hash_equals`（常量时间比较）严格匹配令牌，防止时序攻击。

**问：XSS 防护如何工作？**
答：三层防护 — Blade `{{ }}` 自动转义 HTML 输出；CSP 头部限制脚本来源；`X-XSS-Protection: 1; mode=block` 启用浏览器 XSS 过滤器。

**问：SQL 注入防护如何工作？**
答：Eloquent ORM 默认使用 PDO 参数化查询（预处理语句）。项目任何地方都不将用户输入拼接到 SQL 字符串。

**问：为什么使用第三方邮件 API 而非 SMTP？**
答：qzqi.com API 无需复杂的 SMTP 服务器配置，且可绕过企业/国内网络封锁 SMTP 端口的防火墙限制。

**问：为什么样式内联在 Blade 模板中而不是独立 CSS 文件？**
答：每个页面自带一套独立设计系统（绿色/琥珀色调的有机生态风格 + Lora/Raleway 字体）。内联样式让各页面独立、便于单独修改。

**问：为什么 CSP 允许 `unsafe-inline`？**
答：因为 reCAPTCHA 和 Google Fonts 需要内联脚本/样式。这是已知权衡 — 若要更严格，应将样式/脚本提取到外部文件并使用基于 nonce 的 CSP。

**问：为什么 `users` 表没有 `created_at` / `updated_at` 列？**
答：模型设置了 `$timestamps = false`，因为原始 schema 未包含这些列。如需审计追踪，可考虑添加。

**问：如何创建管理员账户（既然没有注册 UI）？**
答：必须通过 `php artisan tinker` 创建（或未来通过管理面板）。这是有意为之 — 防止自助注册为管理员是一项安全措施。

---

### 12. 已知问题与改进建议

1. **`users` 表无 `created_at` / `updated_at`：** 建议添加以支持审计追踪。
2. **`EmailService` 禁用了 SSL 验证：** 开发环境可接受；生产环境应使用有效证书启用。
3. **CSP 允许 `unsafe-inline`：** 若需更严格，应将样式/脚本提取到外部文件并使用基于 nonce 的 CSP。
4. **仅可通过 CLI 创建管理员：** 需用 `php artisan tinker`。这是有意为之的安全措施。
5. **重发验证码无服务端频率限制：** 客户端展示倒计时，但服务端目前未对 `POST /verify-2fa/resend` 做节流，建议添加 Laravel `throttle` 中间件。
6. **`edit_profile/` 和 `edit_profile.zip` 已删除：** 之前的开发快照已从仓库移除；`app/` 和 `resources/views/profile/` 下的当前生产文件是唯一可信源。

---

*Last updated: 2026-08-31 — synchronized with the actual codebase.*