<?php

/**
 * ============================================================
 * 数据库迁移：为 users 表添加 verification_token（验证令牌）字段
 * ============================================================
 *
 * Database migration: Add verification_token field to the users table.
 *
 * 【文件作用】
 * 添加一个随机生成的 SHA-256 哈希令牌，用于防止 IDOR 攻击。
 * 每次注册或密码重置时都会生成新的令牌，验证完成后清除。
 *
 * 【Purpose】
 * Adds a randomly generated SHA-256 hash token to prevent IDOR attacks.
 * A new token is generated on each registration or password reset,
 * and cleared after verification is complete.
 *
 * 【什么是 IDOR 攻击？】
 * IDOR = Insecure Direct Object Reference（不安全的直接对象引用）
 * 攻击者可能通过篡改 URL 中的用户标识或 session 来操作他人的验证流程。
 * 使用随机令牌后，攻击者无法猜测或伪造他人的令牌，从而保护了验证流程的安全。
 *
 * 【What is an IDOR attack?】
 * IDOR = Insecure Direct Object Reference.
 * An attacker may manipulate another user's verification flow by tampering
 * with user identifiers in URLs or sessions. Using a random token prevents
 * attackers from guessing or forging another user's token, securing the
 * verification flow.
 *
 * 【令牌生命周期】
 * 1. 生成：注册或密码重置时由 AuthService 生成
 * 2. 存储：同时保存在数据库（users 表）和用户会话（session）中
 * 3. 验证：使用 hash_equals() 比较两者（防时序攻击的安全比较）
 * 4. 清除：验证成功后立即设为 null，令牌一次性使用
 *
 * 【Token Lifecycle】
 * 1. Generate: Created by AuthService on registration or password reset
 * 2. Store: Saved in both the database (users table) and user session
 * 3. Verify: Compared using hash_equals() (timing-attack-safe comparison)
 * 4. Clear: Set to null immediately after successful verification (one-time use)
 *
 * 【字段说明】
 * - 类型：string(64) — SHA-256 哈希固定 64 字符
 * - 可空：未进行验证流程时为 null
 * - 位置：放在 is_verified 字段之后
 *
 * 【Field Details】
 * - Type: string(64) — SHA-256 hash is always 64 characters
 * - Nullable: null when no verification flow is in progress
 * - Position: placed after the is_verified column
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * 执行迁移：添加 verification_token 字段
	 * 用于存储随机 SHA-256 令牌，与 session 中的令牌比对以防止 IDOR 攻击
	 *
	 * Run the migration: add the verification_token column.
	 * Stores a random SHA-256 token, compared against the session token
	 * to prevent IDOR attacks.
	 */
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			// 64 字符长度 = SHA-256 哈希的标准输出长度
			// 64 characters = standard output length of a SHA-256 hash
			$table->string('verification_token', 64)->nullable()->after('is_verified');
		});
	}

	/**
	 * 回滚迁移：删除 verification_token 字段
	 *
	 * Rollback the migration: drop the verification_token column.
	 */
	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('verification_token');
		});
	}
};
