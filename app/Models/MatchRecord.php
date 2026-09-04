<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchRecord extends Model
{
    use HasUuids;

    protected $table = 'matches';

    protected $fillable = ['request_id', 'donation_id', 'quantity_allocated', 'status'];

    public function foodRequest(): BelongsTo
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'confirmed';
    }
}
