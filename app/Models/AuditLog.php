<?php
namespace App\Models;
use App\Enums\AuditActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'action_type',
        'target_table',
        'target_id',
        'before_value',
        'after_value',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'action_type' => AuditActionType::class,
        'before_value' => 'array',
        'after_value' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}