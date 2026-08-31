<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationStatusHistory extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = 'changed_at';

    protected $fillable = [
        'donation_id', 'old_status', 'new_status', 'changed_by',
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }
}