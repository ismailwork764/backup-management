<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notifications_log';

    protected $fillable = [
        'type',
        'reference_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
