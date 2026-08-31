<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationReservation extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = 'reserved_at';

    protected $fillable = [
        'donation_id',
        'recipient_id',
        'quantity_reserved',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
