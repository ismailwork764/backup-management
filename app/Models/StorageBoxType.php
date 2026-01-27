<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageBoxType extends Model
{
    protected $fillable = [
        'hetzner_id',
        'name',
        'description',
        'size_bytes',
        'prices',
    ];

    protected $casts = [
        'prices' => 'array',
    ];
}
