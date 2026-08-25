<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedNumber extends Model
{
    protected $fillable = [
        'phone_number',
        'reason',
        'reported_by',
    ];
}
