<?php
// Author: [Your Name]
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodCategory extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function donations()
    {
        return $this->hasMany(Donation::class, 'category_id');
    }
}