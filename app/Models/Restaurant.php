<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'whatsapp_number',
        'wa_phone_id',
        'owner_phone',
        'owner_password',
        'city',
        'address',
        'delivery_areas',
        'delivery_charge',
        'minimum_order',
        'is_active',
        'is_open',
        'plan',
        'plan_expires_at',
        'greeting_message',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_open'         => 'boolean',
        'delivery_charge' => 'decimal:2',
        'minimum_order'   => 'decimal:2',
        'plan_expires_at' => 'datetime',
    ];

    protected $hidden = ['owner_password']; // removed wa_access_token

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function todayOrders(): HasMany
    {
        return $this->hasMany(Order::class)->whereDate('created_at', today());
    }

    public function isPlanActive(): bool
    {
        if ($this->plan === 'trial') return true;
        return $this->plan_expires_at && $this->plan_expires_at->isFuture();
    }
}