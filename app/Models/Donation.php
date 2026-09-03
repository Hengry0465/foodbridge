<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'donor_id',
        'category_id',
        'donation_type',
        'food_name',
        'description',
        'quantity',
        'quantity_reserved',
        'unit',
        'expiry_date',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'image_url',
        'status',
        'version',
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'quantity' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function images()
    {
        return $this->hasMany(DonationImage::class);
    }

    public function reservations()
    {
        return $this->hasMany(DonationReservation::class);
    }
}
