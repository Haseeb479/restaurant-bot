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
        'rider_name',
        'rider_phone',
        'rider_notes',
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
            'pending'          => '🕐 Pending Confirmation',
            'confirmed',
            'preparing'        => '👨‍🍳 Preparing in Kitchen',
            'out_for_delivery' => '🛵 Dispatched & On the Way',
            'delivered'        => '🎉 Delivered',
            'cancelled'        => '❌ Cancelled',
            default            => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusMessageAttribute(): string
    {
        return match($this->status) {
            'pending'          => 'Your order has been received and is waiting for confirmation from our team.',
            'confirmed',
            'preparing'        => 'Our kitchen is preparing your order fresh! 👨‍🍳',
            'out_for_delivery' => 'Your order has been dispatched and is on its way with our rider! 🛵',
            'delivered'        => 'Your order has been delivered. Enjoy your meal! 🎉',
            'cancelled'        => 'Your order was cancelled. Please contact us directly for assistance.',
            default            => 'Your order is in progress.',
        };
    }
}