<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'client_id',
        'agent_id',
        'result',
        'message',
        'created_at',
    ];
}
