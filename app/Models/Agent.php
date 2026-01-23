<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Agent extends Model
{
    protected $fillable = [
        'client_id',
        'hostname',
        'api_token',
        'last_seen_at',
        'is_active',
        'last_backup_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_token',
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }
    public function backupLogs()
    {
        return $this->hasMany(BackupLog::class);
    }
}
