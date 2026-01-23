<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class StorageServer extends Model
{
    protected $fillable = [
        'name',
        'region',
        'api_token',
        'total_capacity_gb',
        'used_capacity_gb',
        'status',
    ];

    protected $casts = [
        'total_capacity_gb' => 'integer',
        'used_capacity_gb' => 'integer',
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
}
