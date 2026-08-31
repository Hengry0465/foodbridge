<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\DonationMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonationMatch extends Model
{
    /** @use HasFactory<DonationMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'donation_id',
        'request_id',
        'status',
        'matched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'matched_at' => 'datetime',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function foodRequest(): BelongsTo
    {
        return $this->belongsTo(FoodRequest::class, 'request_id');
    }

    public function pickup(): HasOne
    {
        return $this->hasOne(Pickup::class, 'match_id');
    }
}
