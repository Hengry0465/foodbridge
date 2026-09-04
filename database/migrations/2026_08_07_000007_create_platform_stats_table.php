<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_stats', function (Blueprint $table) {
            $table->id();
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->json('metrics');
            $table->timestamp('created_at')->useCurrent();

            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_stats');
    }
};
