<?php

namespace App\Models;

use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use App\Enums\FoodRequestStatus;
use Database\Factories\FoodRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodRequest extends Model
{
    /** @use HasFactory<FoodRequestFactory> */
    use HasFactory;

    protected $table = 'requests';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'donation_id',
        'category',
        'region',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => FoodCategory::class,
            'region' => FoodRegion::class,
            'status' => FoodRequestStatus::class,
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DonationMatch::class, 'request_id');
    }
}
