<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuTemplateItem extends Model
{
    protected $fillable = [
        'menu_template_id',
        'category_name',
        'item_name',
        'price',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MenuTemplate::class, 'menu_template_id');
    }
}
