<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Stub FoodMatch model - minimal structure for demonstration
 * Note: "Match" is a reserved keyword in PHP 8+, using FoodMatch instead
 */
class FoodMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'recipient_id',
        'donation_id',
        'request_id',
        'status',
    ];

    protected $table = 'food_matches';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function pickup()
    {
        return $this->hasOne(Pickup::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }
}
