<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Stub Donation model - minimal structure for demonstration
 */
class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function matches()
    {
        return $this->hasMany(FoodMatch::class);
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class);
    }
}
