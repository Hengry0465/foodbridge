<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    protected $fillable = ['donor_id', 'food_name', 'category', 'quantity_available', 'unit', 'expires_at', 'pickup_address', 'status', 'version'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }
}
