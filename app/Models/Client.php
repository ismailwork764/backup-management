<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'storage_server_id',
        'hetzner_subaccount_id',
        'registration_key',
        'is_active',
        'quota_gb',
        'hetzner_username',
        'hetzner_password',
        'reachable_externally',
        'ssh_enabled',
        'readonly',
        'webdav_enabled',
        'samba_enabled'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function backupLogs()
    {
        return $this->hasManyThrough(
            BackupLog::class,
            Agent::class,
            'client_id',
            'agent_id',
            'id',
            'id'
        );
    }

    public function storageServer()
    {
        return $this->belongsTo(StorageServer::class, 'storage_server_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function monthlyUsage(): HasMany
    {
        return $this->hasMany(MonthlyUsage::class);
    }
}
