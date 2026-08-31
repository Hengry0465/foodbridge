<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformStat extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'period_start',
        'period_end',
        'metrics',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'metrics' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
