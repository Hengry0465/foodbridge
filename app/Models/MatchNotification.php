<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchNotification extends Model
{
    protected $fillable = ['user_id', 'request_id', 'type', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function foodRequest(): BelongsTo
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }
}
