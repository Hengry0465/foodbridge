<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['donation_id', 'image_url', 'sort_order'];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }
}