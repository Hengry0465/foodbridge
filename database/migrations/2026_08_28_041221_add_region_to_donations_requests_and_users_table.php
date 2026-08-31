<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('region')->default('kuala_lumpur')->after('category');
            $table->index('region');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->string('region')->default('kuala_lumpur')->after('category');
            $table->index('region');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('region')->default('kuala_lumpur')->after('email');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropColumn('region');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropColumn('region');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropColumn('region');
        });
    }
};
