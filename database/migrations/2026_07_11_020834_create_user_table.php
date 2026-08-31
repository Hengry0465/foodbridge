<?php

/**
 * ============================================================
 * 数据库迁移：创建 users（用户）表
 * ============================================================
 *
 * 【文件作用】
 * 这是 FoodShare 平台最核心的数据库表定义文件。
 * 运行 `php artisan migrate` 时，up() 方法会在 MySQL 中创建 users 表。
 * 运行 `php artisan migrate:rollback` 时，down() 方法会删除该表。
 *
 * 【所属模块】认证系统（Authentication）
 *
 * 【业务流程位置】
 * 这是数据层的起点 — 所有用户注册、登录、2FA 验证、密码重置的数据
 * 最终都存储在这张表中。
 *
 * 【表设计说明】
 * - 没有使用 Laravel 默认的 timestamps()（created_at / updated_at）
 * - 没有使用 Laravel 默认的 password 字段，而是使用 password_hash
 * - email 字段有 UNIQUE 约束，确保同一邮箱只能注册一次
 * - 后续迁移文件会添加更多字段（is_verified、2FA_start、verification_token）
 *
 * 【依赖关系】
 * 被依赖方：此迁移是最先执行的用户相关迁移
 * 调用方：php artisan migrate 命令
 *
 * ============================================================
 * Database Migration: Create users table
 * ============================================================
 *
 * [File Purpose]
 * This is the core database table definition file for the FoodShare platform.
 * When running `php artisan migrate`, the up() method creates the users table
 * in MySQL. When running `php artisan migrate:rollback`, the down() method
 * drops the table.
 *
 * [Module] Authentication System
 *
 * [Workflow Position]
 * This is the starting point of the data layer — all user registration, login,
 * 2FA verification, and password reset data is ultimately stored in this table.
 *
 * [Table Design Notes]
 * - Does NOT use Laravel's default timestamps() (created_at / updated_at)
 * - Does NOT use Laravel's default password field; uses password_hash instead
 * - The email field has a UNIQUE constraint to ensure no duplicate registrations
 * - Subsequent migration files will add more fields (is_verified, 2FA_start,
 *   verification_token)
 *
 * [Dependencies]
 * Depended by: This migration is the first user-related migration to run
 * Called by: php artisan migrate command
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移：创建 users 表
     * 该方法在 `php artisan migrate` 时被调用
     *
     * Run the migration: create the users table.
     * This method is called when running `php artisan migrate`.
     */
    public function up(): void
    {
        // Schema::create() 在数据库中创建一张新表
        // Schema::create() creates a new table in the database
        Schema::create('users', function (Blueprint $table) {
            // id() = bigint 自增主键，Laravel 自动命名为 "id"
            // id() = bigint auto-increment primary key, auto-named "id" by Laravel
            $table->id();

            // 用户的名和姓，允许为空（某些场景可能只需要邮箱）
            // User's first and last name, nullable (some scenarios only require email)
            $table->string('firstname', 100)->nullable();
            $table->string('lastname', 100)->nullable();

            // 电话号码，可选字段
            // Phone number, optional field
            $table->string('phone', 100)->nullable();

            // ★ 邮箱地址 — 作为登录账号使用，UNIQUE 确保不重复
            // ★ Email address — used as login account, UNIQUE prevents duplicates
            $table->string('email', 100)->unique();

            // ★ 密码哈希 — 使用 Bcrypt 加密，绝不存储明文密码
            // ★ Password hash — encrypted with Bcrypt, plaintext passwords are never stored
            $table->string('password_hash', 100);

            // 用户角色：admin（管理员）、donor（捐赠者）、recipient（受助者）
            // User role: admin, donor, recipient
            $table->string('role', 100)->nullable();

            // 双因素认证（2FA）验证码 — 6位数字，验证通过后清除
            // Two-factor authentication (2FA) code — 6 digits, cleared after verification
            $table->string('two_factor_code', 100)->nullable();
        });
    }

    /**
     * 回滚迁移：删除 users 表
     * 该方法在 `php artisan migrate:rollback` 时被调用
     * 注意：这会永久删除表中所有数据！
     *
     * Rollback the migration: drop the users table.
     * This method is called when running `php artisan migrate:rollback`.
     * Warning: This will permanently delete all data in the table!
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
