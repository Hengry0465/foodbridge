<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('recipient')->index();
        });

        // Integration copy of Module 2 availability data for this standalone demo.
        Schema::create('donations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();
            $table->string('food_name');
            $table->string('category')->index();
            $table->unsignedInteger('quantity_available');
            $table->string('unit')->default('portions');
            $table->timestamp('expires_at')->index();
            $table->string('pickup_address');
            $table->string('status')->default('available')->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('category')->index();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_matched')->default(0);
            $table->timestamp('preferred_pickup_at');
            $table->string('status')->default('pending')->index();
            $table->string('client_request_id')->nullable()->unique();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();
            $table->index(['category', 'status', 'created_at']);
        });

        Schema::create('matches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('donation_id')->constrained('donations')->restrictOnDelete();
            $table->unsignedInteger('quantity_allocated');
            $table->string('status')->default('confirmed');
            $table->timestamps();
            $table->unique(['request_id', 'donation_id']);
        });

        Schema::create('match_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('type');
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_notifications');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('donations');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
