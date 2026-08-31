<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Stub User model - minimal structure for demonstration
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function donations()
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    public function requests()
    {
        return $this->hasMany(Request::class, 'recipient_id');
    }

    public function donorMatches()
    {
        return $this->hasMany(FoodMatch::class, 'donor_id');
    }

    public function recipientMatches()
    {
        return $this->hasMany(FoodMatch::class, 'recipient_id');
    }

    public function pickupsAsDonor()
    {
        return $this->hasMany(Pickup::class, 'donor_id');
    }

    public function pickupsAsRecipient()
    {
        return $this->hasMany(Pickup::class, 'recipient_id');
    }

    public function createdPickups()
    {
        return $this->hasMany(Pickup::class, 'created_by');
    }
}
