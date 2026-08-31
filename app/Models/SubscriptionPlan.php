<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
        'max_orders_per_month',
        'max_menu_items',
        'features',
        'is_active',
        'is_popular',
    ];

    protected $casts = [
        'price_monthly'        => 'decimal:2',
        'price_yearly'         => 'decimal:2',
        'max_orders_per_month' => 'integer',
        'max_menu_items'       => 'integer',
        'features'             => 'array',
        'is_active'            => 'boolean',
        'is_popular'           => 'boolean',
    ];

    protected $appends = [
        'price_pkr',
    ];

    public function getPricePkrAttribute(): float
    {
        return (float) ($this->price_monthly ?: 0);
    }
}
