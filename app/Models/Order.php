<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'customer_phone',
        'customer_name',
        'delivery_address',
        'tracking_code',
        'status',
        'payment_method',
        'is_paid',
        'subtotal',
        'delivery_charge',
        'total',
        'owner_notified',
        'customer_notified',
        'notes',
        'estimated_minutes',
    ];

    protected $casts = [
        'is_paid'            => 'boolean',
        'owner_notified'     => 'boolean',
        'customer_notified'  => 'boolean',
        'subtotal'           => 'decimal:2',
        'delivery_charge'    => 'decimal:2',
        'total'              => 'decimal:2',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Tracking Code Generator ──────────────────────────────────────────────

    /**
     * Generate unique tracking code
     * Format: JC-2026-00042 (prefix from restaurant initials + year + order id padded)
     */
    public static function generateTrackingCode(Restaurant $restaurant, int $orderId): string
    {
        // Get initials from restaurant name e.g. "Juice Corner" → "JC"
        $words    = explode(' ', strtoupper($restaurant->name));
        $initials = implode('', array_map(fn($w) => $w[0] ?? '', $words));
        $initials = substr($initials, 0, 3); // max 3 chars

        $year    = date('Y');
        $padded  = str_pad($orderId, 5, '0', STR_PAD_LEFT);

        return "{$initials}-{$year}-{$padded}";
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'          => '🕐 Pending',
            'confirmed'        => '✅ Confirmed',
            'preparing'        => '👨‍🍳 Preparing',
            'out_for_delivery' => '🚴 Out for Delivery',
            'delivered'        => '✅ Delivered',
            'cancelled'        => '❌ Cancelled',
            default            => $this->status,
        };
    }

    public function getStatusMessageAttribute(): string
    {
        return match($this->status) {
            'pending'          => 'Your order has been received and is waiting for confirmation.',
            'confirmed'        => 'Your order has been confirmed! We are getting it ready.',
            'preparing'        => 'Your order is being prepared in the kitchen 👨‍🍳',
            'out_for_delivery' => 'Your order is on the way! 🚴 Should arrive in ~15-20 mins.',
            'delivered'        => 'Your order has been delivered. Enjoy your meal! 🎉',
            'cancelled'        => 'Your order has been cancelled. Please contact us for help.',
            default            => 'Status unknown.',
        };
    }
}