<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->string('request_fingerprint', 64)->nullable()->after('client_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->dropColumn('request_fingerprint');
        });
    }
};
