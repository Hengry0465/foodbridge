<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Migration for pickups table
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('food_matches')->onDelete('cascade');
            $table->foreignId('pickup_status_id')->constrained('pickup_statuses');
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('donation_id')->constrained('donations')->onDelete('cascade');
            $table->string('pickup_address');
            $table->dateTime('scheduled_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('donation_release_status')->nullable(); // pending, success, failed
            $table->dateTime('donation_released_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for performance and query optimization
            $table->index(['pickup_status_id']);
            $table->index(['donor_id']);
            $table->index(['recipient_id']);
            $table->index(['donation_id']);
            $table->index(['scheduled_at']);
            $table->index(['pickup_address', 'scheduled_at']); // For conflict detection
            $table->index(['created_at']);
            
            // Composite index for history queries
            $table->index(['donor_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
