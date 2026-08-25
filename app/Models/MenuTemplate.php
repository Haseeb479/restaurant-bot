<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuTemplate extends Model
{
    protected $fillable = [
        'name',
        'cuisine_type',
        'description',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuTemplateItem::class);
    }
}
