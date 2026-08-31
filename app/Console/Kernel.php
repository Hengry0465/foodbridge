<?php

/**
 * ============================================================================
 * FoodShare Console 内核 (Console Kernel)
 * ============================================================================
 *
 * 【文件作用】
 * 本文件是 Laravel 9 风格的 Console 内核，负责注册应用的自定义 Artisan 命令
 * 以及定义定时任务调度计划。它替代了 Laravel 11+ 瘦启动架构中的
 * `bootstrap/app.php` 的 `withCommands()` 配置。
 *
 * 【执行时机】
 * 当通过 `php artisan xxx` 执行命令时，Laravel 会通过此内核加载所有
 * 已注册的命令。
 *
 * 【与 Laravel 11+ 的差异】
 * Laravel 11+ 中，`routes/console.php` 文件是 Artisan 命令的唯一注册入口；
 * Laravel 9 中，本文件既负责加载自定义命令（通过 commands() 方法），
 *   也负责定义调度任务（通过 schedule() 方法）。
 *
 * ============================================================================
 * FoodShare Console Kernel
 * ============================================================================
 *
 * [File Purpose]
 * This file is the Laravel 9-style Console kernel. It registers the application's
 * custom Artisan commands and defines scheduled task plans. It replaces the
 * `withCommands()` configuration in `bootstrap/app.php` from the Laravel 11+
 * slim-boot architecture.
 *
 * [Invocation Timing]
 * When executing commands via `php artisan xxx`, Laravel loads all registered
 * commands through this kernel.
 *
 * [Difference from Laravel 11+]
 * In Laravel 11+, `routes/console.php` is the sole entry for Artisan commands;
 * In Laravel 9, this file loads custom commands (via commands() method) and
 *   defines scheduled tasks (via schedule() method).
 */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Console 内核类
 *
 * 继承自 Laravel 框架的 Illuminate\Foundation\Console\Kernel，
 * 用于注册 Artisan 命令和调度任务。
 *
 * Console Kernel class.
 *
 * Extends Laravel's Illuminate\Foundation\Console\Kernel,
 * used for registering Artisan commands and scheduling tasks.
 */
class Kernel extends ConsoleKernel
{
    /**
     * ----------------------------------------------------------
     * Artisan 命令注册数组
     * ----------------------------------------------------------
     * 此处列出需要手动注册的自定义 Artisan 命令类。
     * 也可以通过 commands() 方法的 $this->load() 自动加载整个目录。
     *
     * 当前为空，所有命令都通过 routes/console.php 注册。
     *
     * Artisan command registration array.
     * ----------------------------------------------------------
     * Lists custom Artisan command classes to manually register.
     * You can also auto-load an entire directory via $this->load() in commands().
     *
     * Currently empty; all commands are registered via routes/console.php.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * ----------------------------------------------------------
     * 定义应用的任务调度计划
     * ----------------------------------------------------------
     * 此方法用于注册定时执行的任务（通过 Laravel 任务调度器）。
     * 实际执行由系统的 cron 触发：`* * * * * cd /path && php artisan schedule:run`
     *
     * FoodShare 当前没有定时任务需求，本方法体为空。
     *
     * Define the application's command schedule.
     * ----------------------------------------------------------
     * This method is used to register scheduled tasks (via Laravel's task scheduler).
     * Actual execution is triggered by system cron:
     *   `* * * * * cd /path && php artisan schedule:run`
     *
     * FoodShare currently has no scheduled task requirements; this method body is empty.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule  Laravel 调度器实例 / Laravel scheduler instance
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // 暂无定时任务
        // No scheduled tasks at present
        // $schedule->command('inspire')->hourly();
    }

    /**
     * ----------------------------------------------------------
     * 注册应用的所有命令
     * ----------------------------------------------------------
     * 此方法在 Console 内核启动时被自动调用，完成以下两件事：
     *   1. 通过 $this->load() 加载 app/Console/Commands 目录下的所有命令
     *   2. 通过 require 引入 routes/console.php 中基于闭包定义的命令
     *
     * Register the commands for the application.
     * ----------------------------------------------------------
     * This method is auto-called when the Console kernel boots, performing:
     *   1. Loading all commands from app/Console/Commands via $this->load()
     *   2. Including closure-based commands defined in routes/console.php via require
     *
     * @return void
     */
    protected function commands(): void
    {
        // 加载 app/Console/Commands 目录下所有命令类
        // Load all command classes from app/Console/Commands directory
        $this->load(__DIR__.'/Commands');

        // 包含 routes/console.php 中基于闭包的命令定义
        // Include closure-based command definitions from routes/console.php
        require base_path('routes/console.php');
    }
}