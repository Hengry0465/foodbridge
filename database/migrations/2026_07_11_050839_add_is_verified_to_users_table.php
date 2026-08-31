<?php

/**
 * ============================================================
 * 数据库迁移：为 users 表添加 is_verified（邮箱验证状态）字段
 * ============================================================
 *
 * 【文件作用】
 * 在 users 表中新增一个标记字段，用于区分用户是否完成了邮箱 2FA 验证。
 * 这是防止未验证用户登录的关键字段。
 *
 * 【业务流程位置】
 * 用户注册 → is_verified = 0（未验证，无法登录）
 * 用户输入 2FA 验证码 → 验证通过 → is_verified = 1（已验证，可以登录）
 *
 * 【字段说明】
 * - 类型：tinyInteger（小整数，相当于 MySQL 的 TINYINT）
 * - 默认值：0（未验证）
 * - 位置：放在 two_factor_code 字段之后（逻辑上属于验证相关字段组）
 *
 * ============================================================
 * Database Migration: Add is_verified (email verification status)
 * field to the users table
 * ============================================================
 *
 * [Purpose]
 * Adds a flag field to the users table to track whether the user
 * has completed email 2FA verification. This is the key field for
 * preventing unverified users from logging in.
 *
 * [Business Flow]
 * User registers → is_verified = 0 (unverified, cannot log in)
 * User enters 2FA code → verification passes → is_verified = 1
 *   (verified, can log in)
 *
 * [Field Details]
 * - Type: tinyInteger (TINYINT in MySQL)
 * - Default: 0 (unverified)
 * - Position: placed after two_factor_code field (logically grouped
 *   with other verification-related fields)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移：添加 is_verified 字段
     * 注册时默认为 0（未验证），2FA 验证通过后更新为 1（已验证）
     *
     * Run migration: add the is_verified field.
     * Defaults to 0 (unverified) on registration; updated to 1 (verified)
     * after 2FA verification passes.
     */
    public function up(): void
    {
        // Schema::table() 修改已存在的表（区别于 Schema::create() 创建新表）
        // Schema::table() modifies an existing table (vs. Schema::create() for new tables)
        Schema::table('users', function (Blueprint $table) {
            // tinyInteger = TINYINT 类型，占用 1 字节，适合存储 0/1 布尔值
            // tinyInteger = TINYINT type, 1 byte, ideal for 0/1 boolean values
            // default(0) = 新注册用户默认未验证
            // default(0) = newly registered users default to unverified
            // after('two_factor_code') = 把字段放在验证码字段旁边，保持表结构清晰
            // after('two_factor_code') = places field next to the code field for a clean table structure
            $table->tinyInteger('is_verified')->default(0)->after('two_factor_code');
        });
    }

    /**
     * 回滚迁移：删除 is_verified 字段
     *
     * Rollback migration: drop the is_verified field.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }
};
