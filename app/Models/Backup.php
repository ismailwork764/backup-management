<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'status',
        'message',
        'size_gb',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'size_gb' => 'decimal:2',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
