<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'sort_order', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->where('is_available', true)->orderBy('sort_order');
    }
}