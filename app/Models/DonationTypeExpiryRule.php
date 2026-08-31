<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationTypeExpiryRule extends Model
{
    protected $primaryKey = 'donation_type';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'donation_type',
        'default_shelf_life_hours',
        'expiring_soon_threshold_hours',
    ];
}