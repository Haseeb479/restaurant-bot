<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'permissions',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'permissions'  => 'array',
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
