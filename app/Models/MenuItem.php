<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'sizes',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sizes'        => 'array',
        'is_available' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function hasSizes(): bool
    {
        return !empty($this->sizes);
    }

    public function getPriceDisplayAttribute(): string
    {
        if ($this->hasSizes()) {
            return collect($this->sizes)
                ->map(fn($s) => "{$s['size']}: Rs.{$s['price']}")
                ->implode(' / ');
        }
        return 'Rs.' . number_format($this->price, 0);
    }
}