<?php

namespace App\Models;

use App\Enums\PickupStatus;
use Database\Factories\PickupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pickup extends Model
{
    /** @use HasFactory<PickupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'match_id',
        'scheduled_at',
        'status',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PickupStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function donationMatch(): BelongsTo
    {
        return $this->belongsTo(DonationMatch::class, 'match_id');
    }
}
