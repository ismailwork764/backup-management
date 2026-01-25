<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class StorageServer extends Model
{
    protected $fillable = [
        'hetzner_id',
        'name',
        'server_address',
        'region',
        'api_token',
        'total_capacity_gb',
        'used_capacity_gb',
        'status',
    ];

    protected $casts = [
        'total_capacity_gb' => 'decimal:2',
        'used_capacity_gb' => 'decimal:2',
    ];

    public function setApiTokenAttribute($value): void
    {
        $this->attributes['api_token'] = Crypt::encryptString($value);
    }

    public function getApiTokenAttribute($value): string
    {
        return Crypt::decryptString($value);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Format storage capacity to show MB or GB based on value
     * If below 1 GB, show in MB. If above 1 GB, show in GB with decimal
     */
    public function formatStorageCapacity($capacityInGb)
    {
        if ($capacityInGb < 1) {
            // Convert to MB
            $mb = $capacityInGb * 1024;
            return round($mb) . ' MB';
        } else {
            // Show in GB with 1 decimal place
            return number_format($capacityInGb, 1) . ' GB';
        }
    }

    /**
     * Get formatted used storage capacity
     */
    public function getFormattedUsedCapacity()
    {
        return $this->formatStorageCapacity($this->used_capacity_gb);
    }

    /**
     * Get formatted total storage capacity
     */
    public function getFormattedTotalCapacity()
    {
        return $this->formatStorageCapacity($this->total_capacity_gb);
    }
}
