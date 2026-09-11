<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Restaurant extends Model
{
    /**
     * NOTE: `owner_password` is deliberately NOT mass-assignable. It must always
     * be set explicitly through a hash (see AdminController::storeRestaurant,
     * RestaurantController::register, DashboardController::login), so a future
     * Only safe, tenant-editable presentation and business details are permitted.
     * Privileged state (is_active, status, plan, plan_id, payment_status,
     * registration_status, api_key, features, evolution fields, verification tokens)
     * must NEVER be mass-assignable (Req 13).
     */
    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'whatsapp_number',
        'owner_phone',
        'manager_phone',
        'city',
        'address',
        'delivery_areas',
        'delivery_charge',
        'minimum_order',
        'is_open',
        'hours',
        'greeting_message',
        'menu_image',
        'menu_file',
        'menu_file_name',
        'menu_file_type',
        'google_sheet_webhook',
        'restaurant_lat',
        'restaurant_lng',
        'delivery_radius_km',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'is_open'              => 'boolean',
        'delivery_charge'      => 'decimal:2',
        'minimum_order'        => 'decimal:2',
        'delivery_radius_km'   => 'decimal:1',
        'plan_expires_at'      => 'datetime',
        'trial_started_at'     => 'datetime',
        'approved_at'          => 'datetime',
        'email_verified_at'    => 'datetime',
        'verification_sent_at' => 'datetime',
        'bot_last_seen_at'     => 'datetime',
        'deactivated_at'       => 'datetime',
        'last_error_at'        => 'datetime',
        'features'             => 'array',
        'ai_config'            => 'array',
        'rate_limit_per_month' => 'integer',
    ];

    public function isVerified(): bool
    {
        return ! empty($this->email_verified_at);
    }

    /**
     * Generate a cryptographic, single-use verification token (Req 9).
     * The raw token is returned to be sent via notification, while its SHA-256
     * hash is stored in the database.
     */
    public function generateVerificationToken(): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $this->verification_token_hash = hash('sha256', $rawToken);
        $this->verification_sent_at    = now();
        $this->save();

        return $rawToken;
    }

    /**
     * Validate a presented verification token using constant-time hash comparison (Req 9).
     */
    public function verifyWithToken(string $rawToken): bool
    {
        if (empty($this->verification_token_hash) || empty($this->verification_sent_at)) {
            return false;
        }

        // 24 hour expiration window
        if ($this->verification_sent_at->addHours(24)->isPast()) {
            return false;
        }

        $incomingHash = hash('sha256', trim($rawToken));

        if (! hash_equals($this->verification_token_hash, $incomingHash)) {
            return false;
        }

        // Mark verified and immediately invalidate the single-use token
        $this->email_verified_at        = now();
        $this->verification_token_hash  = null;
        $this->verification_sent_at     = null;
        $this->save();

        return true;
    }

    public function maxDeliveryRadiusKm(): float
    {
        return (float) ($this->delivery_radius_km ?: 5.0);
    }

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

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class)->orderByDesc('last_order_at');
    }

    public function activeDeals(): HasMany
    {
        return $this->hasMany(Deal::class)->activeNow();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class)->latest();
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class)->latest();
    }

    public function todayOrders(): HasMany
    {
        return $this->hasMany(Order::class)->whereDate('created_at', today());
    }

    public function subscriptionPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function isPlanActive(): bool
    {
        // 1. Check formal active subscription first (H2)
        $sub = $this->activeSubscription;
        if ($sub && (! $sub->expires_at || $sub->expires_at->isFuture())) {
            return true;
        }

        // 2. Enforce 14-day limit on trial accounts (H1)
        if ($this->plan === 'trial') {
            $started = $this->trial_started_at ?? $this->created_at;
            if ($started && $started->diffInDays(now()) > 14) {
                return false;
            }
            return true;
        }

        // 3. Fall back to plan_expires_at timestamp
        return (bool) ($this->plan_expires_at && $this->plan_expires_at->isFuture());
    }

    /**
     * Maximum allowed orders per calendar month for this restaurant's tier (C5).
     */
    public function maxMonthlyOrders(): int
    {
        if ($this->subscriptionPlan && $this->subscriptionPlan->max_orders_per_month > 0) {
            return (int) $this->subscriptionPlan->max_orders_per_month;
        }
        return (int) ($this->rate_limit_per_month ?: 500);
    }

    /**
     * Maximum allowed menu items for this restaurant's tier (C5).
     */
    public function maxMenuItems(): int
    {
        if ($this->subscriptionPlan && $this->subscriptionPlan->max_menu_items > 0) {
            return (int) $this->subscriptionPlan->max_menu_items;
        }
        return 50;
    }

    /**
     * Current month order volume.
     */
    public function monthlyOrdersCount(): int
    {
        return (int) $this->orders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function hasExceededMonthlyOrders(): bool
    {
        return $this->monthlyOrdersCount() >= $this->maxMonthlyOrders();
    }

    public function hasExceededMenuItems(): bool
    {
        return $this->menuItems()->count() >= $this->maxMenuItems();
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