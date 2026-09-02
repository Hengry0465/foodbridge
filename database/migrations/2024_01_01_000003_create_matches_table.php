<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Stub migration for food_matches table - minimal structure for demonstration
 * Note: "matches" is a reserved word in some contexts, using food_matches
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('donation_id')->constrained('donations')->onDelete('cascade');
            $table->foreignId('request_id')->nullable()->constrained('requests')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, successful, cancelled
            $table->timestamps();
            
            $table->index(['donor_id', 'recipient_id']);
            $table->index(['donation_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_matches');
    }
};
