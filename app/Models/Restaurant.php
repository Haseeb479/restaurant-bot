<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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
        'menu_image',
        'menu_file',
        'menu_file_name',
        'menu_file_type',
        'google_sheet_webhook',
        'manager_phone',
        'bot_status',
        'bot_last_seen_at',
        'deactivated_at',
        'deactivated_reason',
        'last_error',
        'last_error_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_open'          => 'boolean',
        'delivery_charge'  => 'decimal:2',
        'minimum_order'    => 'decimal:2',
        'plan_expires_at'  => 'datetime',
        'bot_last_seen_at' => 'datetime',
        'deactivated_at'   => 'datetime',
        'last_error_at'    => 'datetime',
    ];

    protected $hidden = ['owner_password'];

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

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class)->orderBy('name');
    }

    public function activeDeals(): HasMany
    {
        return $this->hasMany(Deal::class)->activeNow();
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

    /**
     * Human-readable bot status label and colour class
     */
    public function getBotStatusLabelAttribute(): string
    {
        return match($this->bot_status) {
            'connected'    => '🟢 Connected',
            'qr_pending'   => '🟡 Scan QR',
            'qr_expired'   => '🔴 QR Expired',
            'disconnected' => '⚪ Disconnected',
            default        => '⚪ Unknown',
        };
    }

    public function getBotStatusClassAttribute(): string
    {
        return match($this->bot_status) {
            'connected'    => 'bot-connected',
            'qr_pending'   => 'bot-qr',
            'qr_expired'   => 'bot-expired',
            default        => 'bot-disconnected',
        };
    }

    /**
     * Display-friendly status string (Active / Inactive / Deactivated)
     */
    public function getDisplayStatusAttribute(): string
    {
        if (!$this->is_active && $this->deactivated_at) return 'Deactivated';
        if (!$this->is_active) return 'Inactive';
        if (!$this->isPlanActive()) return 'Plan Expired';
        return 'Active';
    }

    public function getDisplayStatusClassAttribute(): string
    {
        return match($this->display_status) {
            'Active'       => 's-active',
            'Plan Expired' => 's-expired',
            default        => 's-inactive',
        };
    }
}