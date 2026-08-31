<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Stub Request model - minimal structure for demonstration
 */
class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function matches()
    {
        return $this->hasMany(FoodMatch::class);
    }
}
