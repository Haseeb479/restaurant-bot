<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'restaurant_id',
        'user_name',
        'user_phone',
        'rating',
        'comment',
        'category',
        'is_reviewed',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_reviewed' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
