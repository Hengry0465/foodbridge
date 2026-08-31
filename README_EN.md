# Food Donation Platform — Login & Registration System

> A user authentication system built with Laravel 13 + Vanilla HTML/CSS/JS for a food donation platform.
> Implements three design patterns: **Factory**, **Strategy**, and **Repository**.

---

## 1. Project Overview

This is the authentication module of a food donation platform, supporting three user roles:

| Role | Description |
|------|-------------|
| **Admin** | Manage users, review donations, view statistics |
| **Donor** | Post food donations, manage donation records |
| **Recipient** | Browse available food, claim donations |

Features: user registration, login, role-based routing, and access control.

---

## 2. Directory Structure

```
food_donation/
├── app/
│   ├── Factories/
│   │   └── UserFactory.php                  # Factory Pattern — role-based user data factory
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php            # Auth controller (login/register/logout)
│   │   │   └── DashboardController.php       # Dashboard controller
│   │   └── Middleware/
│   │       └── RoleMiddleware.php            # Role-based access control middleware
│   ├── Models/
│   │   └── User.php                          # User model
│   ├── Providers/
│   │   └── AppServiceProvider.php            # Service container bindings
│   ├── Repositories/
│   │   ├── UserRepositoryInterface.php       # Repository interface
│   │   └── UserRepository.php                # Repository implementation
│   ├── Services/
│   │   └── AuthService.php                   # Authentication service layer
│   └── Strategies/
│       ├── LoginStrategyInterface.php        # Strategy interface
│       ├── AdminLoginStrategy.php            # Admin login strategy
│       ├── DonorLoginStrategy.php            # Donor login strategy
│       └── RecipientLoginStrategy.php        # Recipient login strategy
├── bootstrap/
│   └── app.php                               # Middleware registration
├── database/
│   └── migrations/
│       └── 2026_07_11_020834_create_user_table.php
├── resources/views/
│   ├── auth/
│   │   ├── login.blade.php                   # Login page
│   │   └── register.blade.php                # Registration page
│   ├── dashboard/
│   │   ├── admin.blade.php                   # Admin dashboard
│   │   ├── donor.blade.php                   # Donor dashboard
│   │   └── recipient.blade.php               # Recipient dashboard
│   └── layouts/
│       └── app.blade.php                     # Base layout template
├── routes/
│   └── web.php                                # Route definitions
├── .env                                       # Environment configuration
└── README_EN.md                               # This document
```

---

## 3. Database Configuration

### 3.1 Connection Settings

Edit the `.env` file in the project root:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8080
DB_DATABASE=food_donation
DB_USERNAME=root
DB_PASSWORD=thresh1462
```

### 3.2 Table Schema

Table name: `users`

```sql
CREATE TABLE `users` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname`        VARCHAR(100)    DEFAULT NULL,
  `lastname`         VARCHAR(100)    DEFAULT NULL,
  `phone`            VARCHAR(100)    DEFAULT NULL,
  `email`            VARCHAR(100)    NOT NULL,
  `password_hash`    VARCHAR(100)    NOT NULL,
  `role`             VARCHAR(100)    DEFAULT NULL,
  `two_factor_code`  VARCHAR(100)    DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 4. User Model

**File:** `app/Models/User.php`

```php
class User extends Authenticatable
{
    protected $table = 'users';          // Custom table name
    public $timestamps = false;          // No timestamp columns in table

    protected $fillable = [
        'firstname', 'lastname', 'phone',
        'email', 'password_hash', 'role', 'two_factor_code',
    ];

    protected $hidden = ['password_hash', 'two_factor_code'];

    // Override: use password_hash instead of the default password column
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
```

---

## 5. Design Patterns

### 5.1 Factory Pattern

**Purpose:** Avoid complex `if-else` role checks in Controllers. The factory centralizes role-specific user data construction.

**File:** `app/Factories/UserFactory.php`

```php
class UserFactory
{
    const ROLES = ['donor', 'recipient', 'admin'];

    public function make(array $data): array
    {
        return match (strtolower($data['role'])) {
            'admin'     => $this->makeAdmin($data),
            'donor'     => $this->makeDonor($data),
            'recipient' => $this->makeRecipient($data),
        };
    }
    // Each role method can have unique defaults or processing logic
}
```

**Benefits:**
- Centralizes role-related creation logic
- Adding a new role only requires a new `make*()` method
- Keeps Controller code clean and role-agnostic

### 5.2 Strategy Pattern

**Purpose:** Different roles execute different post-login behavior (routing, initialization).

**Interface:** `app/Strategies/LoginStrategyInterface.php`

```php
interface LoginStrategyInterface
{
    public function handle(User $user): string;
}
```

**Implementations:**
- `AdminLoginStrategy` → returns `admin.dashboard`
- `DonorLoginStrategy` → returns `donor.dashboard`
- `RecipientLoginStrategy` → returns `recipient.dashboard`

**Benefits:**
- Each role has an independent strategy class (Single Responsibility)
- Adding a new role requires only a new strategy — no changes to existing code (Open/Closed Principle)
- Strategies can be dynamically selected and injected into the Service layer

### 5.3 Repository Pattern

**Purpose:** Encapsulate all database operations. Controllers never write raw SQL or Eloquent queries directly.

**Interface:** `app/Repositories/UserRepositoryInterface.php`

```php
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function create(array $data): User;
    public function emailExists(string $email): bool;
    public function update(User $user, array $data): bool;
}
```

**Implementation:** `app/Repositories/UserRepository.php`

**Binding (in `AppServiceProvider.php`):**

```php
$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
```

**Benefits:**
- Controller depends on the interface, not the concrete implementation (Dependency Inversion)
- Easy to swap database implementations (e.g., add a Redis cache layer)
- Easy to mock for unit testing

---

## 6. Controllers

### 6.1 AuthController

**File:** `app/Http/Controllers/AuthController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `showLogin()` | `GET /login` | Display login form |
| `login()` | `POST /login` | Handle login request |
| `showRegister()` | `GET /register` | Display registration form |
| `register()` | `POST /register` | Handle registration request |
| `logout()` | `POST /logout` | Handle logout |

**Registration validation rules:**
- Required: firstname, lastname, email, password, role
- Password: min 8 chars, must contain uppercase, lowercase, and digits
- Email uniqueness check against `users` table
- Password confirmation must match

### 6.2 DashboardController

**File:** `app/Http/Controllers/DashboardController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `admin()` | `GET /admin/dashboard` | Admin dashboard |
| `donor()` | `GET /donor/dashboard` | Donor dashboard |
| `recipient()` | `GET /recipient/dashboard` | Recipient dashboard |

---

## 7. Service Layer

**File:** `app/Services/AuthService.php`

Integrates all three design patterns:

```
AuthService
├── Uses UserRepositoryInterface (Repository Pattern)
├── Uses UserFactory (Factory Pattern)
├── register() → Factory builds data → Repository writes to DB
├── login() → Repository query → Hash verification → Strategy determines redirect
└── logout() → Clear session
```

## 8. Routes

**File:** `routes/web.php`

```php
// === Public Routes ===
GET   /login              → AuthController@showLogin      (name: login)
POST  /login              → AuthController@login
GET   /register           → AuthController@showRegister   (name: register)
POST  /register           → AuthController@register
POST  /logout             → AuthController@logout         (name: logout)

// === Protected Routes (auth + role) ===
GET   /admin/dashboard    → DashboardController@admin     (name: admin.dashboard)
       Middleware: auth + role:admin

GET   /donor/dashboard    → DashboardController@donor     (name: donor.dashboard)
       Middleware: auth + role:donor

GET   /recipient/dashboard → DashboardController@recipient (name: recipient.dashboard)
       Middleware: auth + role:recipient
```

---

## 9. Middleware

### 9.1 RoleMiddleware

**File:** `app/Http/Middleware/RoleMiddleware.php`

- Checks if the user is authenticated
- Verifies the user's role matches the required role for the route
- Returns 403 Forbidden on mismatch

**Alias registration in `bootstrap/app.php`:**

```php
$middleware->alias([
    'role' => \App\Http\Middleware\RoleMiddleware::class,
]);
```

---

## 10. Frontend Pages

All pages use vanilla HTML, CSS, and JavaScript — no Vue/React frameworks.

### 10.1 Base Layout (`resources/views/layouts/app.blade.php`)

- Warm color + green theme (primary orange #e87d22, secondary green #2ecc71)
- Responsive card layout
- Consistent header and footer
- Auto-display of error/success messages
- CSRF token in meta tag

### 10.2 Login Page (`auth/login.blade.php`)

- Email + Password form
- Client-side JS validation (empty check, email format)
- Server-side Laravel validation with CSRF protection
- Link to registration page

### 10.3 Registration Page (`auth/register.blade.php`)

- First Name / Last Name / Phone / Email / Password / Confirm Password / Role
- Client-side JS validation (password complexity, confirmation match)
- Server-side Laravel validation (uniqueness, password rules, role enum)
- Link to login page

### 10.4 Dashboard Pages

- **Admin:** System overview, user management, donation management, review management (stubs using `alert()`)
- **Donor:** My donations, completed, pending, people helped — statistic cards with action buttons
- **Recipient:** Browse food, my claims, favorites, messages — cards with action buttons

---

## 11. Security Measures

| Measure | Implementation |
|---------|---------------|
| CSRF Protection | `@csrf` directive + `meta[name="csrf-token"]` |
| Password Hashing | `Hash::make()` for storage, `Hash::check()` for verification |
| SQL Injection Prevention | Eloquent ORM parameterized queries |
| Form Validation | Laravel Validation (required, email format, password rules) |
| Email Uniqueness | `unique:users,email` validation rule |
| Session Fixation | `session()->regenerate()` after login |
| Session Cleanup | `session()->invalidate()` + `regenerateToken()` on logout |
| Role-Based Access | RoleMiddleware |
| Auth Protection | Laravel `auth` middleware |

---

## 12. Running the Project

### Prerequisites
- PHP 8.3+ (tested with 8.3.32)
- Composer 2.x
- MySQL 8.x (tested with 8.1.0, port 8080)

### Steps

```bash
# 1. Navigate to project directory
cd Z:/food_donation

# 2. Install PHP dependencies
composer install

# 3. Configure .env with database settings
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=8080
# DB_DATABASE=food_donation
# DB_USERNAME=root
# DB_PASSWORD=thresh1462

# 4. Generate application key
php artisan key:generate

# 5. Run database migrations
php artisan migrate:fresh

# 6. Start development server
php artisan serve --port=8000

# 7. Open browser at http://localhost:8000
```

---

## 13. Design Pattern Summary

```
Request Flow:

┌─────────────┐     ┌──────────────────┐     ┌──────────────────┐
│ AuthController │ →  │   AuthService    │ →  │ UserRepository   │
│ (Handle request)│    │ (Business logic)  │    │ (DB operations)   │
└─────────────┘     └──────────────────┘     └──────────────────┘
                           │                          ↑
                   ┌───────┼───────┐          Repository Pattern
                   ↓       ↓       ↓         Interface → Implementation
             UserFactory  LoginStrategy
             ───────────  ────────────
            Factory Pattern  Strategy Pattern
            Role-based data   Role-based behavior
```

| Pattern | Problem Solved | Project Location |
|---------|---------------|------------------|
| **Factory** | Avoids large `if-else` blocks for role handling | `app/Factories/UserFactory.php` |
| **Strategy** | Different post-login behavior per role | `app/Strategies/` |
| **Repository** | Decouples database operations from business logic | `app/Repositories/` |

---

## 14. Code Comments

All core code includes comments explaining design pattern usage and key logic points:

- `AuthService::register()` — "Factory Pattern: builds role-specific user data"
- `AuthService::login()` — "Strategy Pattern: determines redirect route by role"
- `UserRepository::create()` — "Hash::make() encrypts password"
- `RoleMiddleware::handle()` — "Restricts page access based on user role"
