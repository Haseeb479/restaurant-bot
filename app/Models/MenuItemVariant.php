<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemVariant extends Model
{
    protected $fillable = [
        'menu_item_id',
        'name',
        'price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
