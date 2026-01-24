<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'message',
        'sent_at',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
