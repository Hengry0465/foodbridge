<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\FoodCategory;
use App\Enums\FoodRegion;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'region',
        'quantity',
        'unit',
        'status',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => FoodCategory::class,
            'region' => FoodRegion::class,
            'status' => DonationStatus::class,
            'quantity' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(DonationMatch::class);
    }
}
