<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'phone',
        'address',
        'total_orders',
        'total_spent',
        'tag',
        'opt_in_marketing',
        'notes',
        'last_order_at',
    ];

    protected $casts = [
        'total_spent'      => 'decimal:2',
        'total_orders'     => 'integer',
        'opt_in_marketing' => 'boolean',
        'last_order_at'    => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
