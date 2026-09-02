<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodRequest extends Model
{
    use HasUuids;

    protected $table = 'requests';

    protected $fillable = ['recipient_id', 'preferred_donation_id', 'category', 'quantity_requested', 'quantity_matched', 'preferred_pickup_at', 'status', 'client_request_id', 'request_fingerprint', 'matched_at'];

    protected $hidden = ['request_fingerprint'];

    protected function casts(): array
    {
        return ['preferred_pickup_at' => 'datetime', 'matched_at' => 'datetime'];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function preferredDonation(): BelongsTo
    {
        return $this->belongsTo(Donation::class, 'preferred_donation_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class, 'request_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(MatchNotification::class, 'request_id');
    }
}
