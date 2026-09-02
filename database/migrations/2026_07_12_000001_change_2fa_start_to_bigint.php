<?php

/**
 * ============================================================
 * 数据库迁移：将 2FA_start 字段类型从 timestamp 改为 bigInteger
 * ============================================================
 *
 * 【文件作用】
 * 修改 2FA_start 字段的数据类型。原先使用 MySQL 的 TIMESTAMP 类型
 * 存储日期时间（如 "2026-07-12 10:30:00"），改为使用 bigInteger
 * 存储 Unix 时间戳（如 1750000000，表示从 1970-01-01 起经过的秒数）。
 *
 * 【为什么要改？】
 * Unix 时间戳是一个简单的整数，不受时区影响，不依赖 MySQL 的日期函数，
 * 在任何编程语言中都能方便地比较和计算。对于"15 分钟过期"这种需求，
 * 直接比较两个整数比处理日期字符串更简单、更可靠。
 *
 * 【影响范围】
 * - User Model：casts 中 '2FA_start' => 'integer'（而非 'datetime'）
 * - AuthService：写入时使用 now()->timestamp，读取时使用 Carbon::createFromTimestamp()
 *
 * 【注意】
 * change() 方法需要安装 doctrine/dbal 依赖，否则无法修改列类型。
 *
 * ============================================================
 * Database Migration: Change 2FA_start field type from timestamp to bigInteger
 * ============================================================
 *
 * [Purpose]
 * Changes the data type of the 2FA_start field. Previously it used MySQL's
 * TIMESTAMP type to store date/time (e.g. "2026-07-12 10:30:00"); now it uses
 * bigInteger to store Unix timestamps (e.g. 1750000000, representing seconds
 * elapsed since 1970-01-01).
 *
 * [Why the change?]
 * A Unix timestamp is a simple integer, unaffected by timezone issues and
 * independent of MySQL date functions. It can be easily compared and computed
 * in any programming language. For the "15-minute expiry" requirement,
 * comparing two integers is simpler and more reliable than handling date strings.
 *
 * [Impact scope]
 * - User Model: casts '2FA_start' => 'integer' (instead of 'datetime')
 * - AuthService: uses now()->timestamp when writing, Carbon::createFromTimestamp() when reading
 *
 * [Note]
 * The change() method requires the doctrine/dbal dependency; otherwise the column type cannot be modified.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移：将 2FA_start 从 timestamp 改为 bigInteger
     * bigInteger 存储 Unix 时间戳（整数），比 timestamp 更简单可靠
     *
     * Run the migration: change 2FA_start from timestamp to bigInteger.
     * bigInteger stores Unix timestamps (integers), simpler and more reliable than timestamp.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // change() 方法修改已有列的类型而不删除列
            // The change() method modifies an existing column's type without dropping the column.
            $table->bigInteger('2FA_start')->nullable()->change();
        });
    }

    /**
     * 回滚迁移：将 2FA_start 恢复为 timestamp 类型
     *
     * Rollback the migration: restore 2FA_start to timestamp type.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('2FA_start')->nullable()->change();
        });
    }
};
