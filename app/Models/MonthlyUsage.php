<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyUsage extends Model
{
    protected $table = 'monthly_usage';

    protected $fillable = [
        'client_id',
        'year',
        'month',
        'max_used_gb',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'max_used_gb' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
