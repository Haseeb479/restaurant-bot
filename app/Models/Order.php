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
        'rider_token',
        'rider_lat',
        'rider_lng',
        'rider_location_updated_at',
    ];

    protected $casts = [
        'is_paid'                   => 'boolean',
        'owner_notified'            => 'boolean',
        'customer_notified'         => 'boolean',
        'subtotal'                  => 'decimal:2',
        'delivery_charge'           => 'decimal:2',
        'total'                     => 'decimal:2',
        'rider_lat'                 => 'decimal:7',
        'rider_lng'                 => 'decimal:7',
        'rider_location_updated_at' => 'datetime',
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
     * Canonical order statuses. Mirrors the `orders.status` enum, so anything
     * outside this list is either a hard DB error or silent corruption.
     */
    public const STATUSES = [
        'pending',
        'confirmed',
        'preparing',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    /**
     * Generate a short, human-friendly tracking code like `FZ1234` or `ORD5821`.
     *
     * Format: {2–3 letter prefix}{4-digit padded number} — total 6–7 characters.
     * The number is the restaurant's current order count + 1, offset by a small
     * per-restaurant constant (derived from the restaurant id) to avoid starting
     * every new restaurant at 0001 and revealing order volume.
     *
     * Collision resistance: the UNIQUE constraint on `tracking_code` combined with
     * the retry loop handles the rare case where two orders are created at the
     * exact same millisecond. If all 4-digit slots are taken (>9999 orders) the
     * suffix widens to 5 digits automatically.
     */
    public static function generateTrackingCode(Restaurant $restaurant, ?int $orderId = null): string
    {
        $prefix = static::trackingPrefix($restaurant->name ?? '');

        // Count this restaurant's existing orders so each gets a sequential number.
        $existingCount = static::where('restaurant_id', $restaurant->id)->count();

        // Offset by a small scramble derived from restaurant id to avoid 0001.
        $offset = (($restaurant->id * 37) % 100) + 10;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $num  = $existingCount + $offset + $attempt + 1;
            $code = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

            if (! static::where('tracking_code', $code)->exists()) {
                return $code;
            }
        }

        // Safety fallback: append a 2-digit random suffix if all sequential slots
        // happen to be taken (extremely unlikely but handled gracefully).
        return $prefix . ($existingCount + $offset + random_int(10, 99));
    }

    /**
     * Up to 3 A–Z initials from the restaurant name, for human recognisability.
     * Falls back to ORD when the name has no usable Latin letters.
     */
    private static function trackingPrefix(string $name): string
    {
        $initials = '';

        foreach (preg_split('/\s+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            if (preg_match('/[A-Z]/', $word, $m) === 1) {
                $initials .= $m[0];
            }
        }

        $initials = substr($initials, 0, 3);

        return $initials !== '' ? $initials : 'ORD';
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

    // ─── Public Tracking Page Redaction ───────────────────────────────────────
    //
    // /track has no login: the tracking code is the only thing protecting the
    // order, and those URLs get forwarded, pasted into group chats and left in
    // browser history. So the public page shows the least that still lets the
    // customer recognise their own order, and the accessors below are the only
    // shape in which address and rider details may appear there. The owner's
    // dashboard keeps using the raw columns.

    /**
     * Enough of the address to confirm "yes, that's my order", without
     * publishing the doorstep to whoever ends up holding the link.
     *
     * "House 12, Street 4, Block B, Gulberg, Lahore" → "••• Gulberg, Lahore"
     */
    public function getMaskedDeliveryAddressAttribute(): string
    {
        $address = trim((string) $this->delivery_address);

        if ($address === '') {
            return '';
        }

        $segments = array_values(array_filter(
            array_map('trim', explode(',', $address)),
            static fn (string $segment): bool => $segment !== ''
        ));

        // Always drop at least one segment, and never show more than the two
        // broadest (which are the area and city in the order customers type).
        $keep = min(2, count($segments) - 1);

        if ($keep > 0) {
            return '••• ' . implode(', ', array_slice($segments, -$keep));
        }

        // A single run-on line with no commas has no structure to trim safely —
        // slicing by character or word is as likely to expose the house number
        // as the area. Hide all of it.
        return '••• hidden';
    }

    /**
     * Riders are staff, not a party to the order — the customer needs to know
     * who is at the gate, not their full legal name.
     */
    public function getRiderDisplayNameAttribute(): ?string
    {
        $name = trim((string) $this->rider_name);

        if ($name === '') {
            return null;
        }

        return preg_split('/\s+/', $name)[0];
    }

    /**
     * Return the customer's real diallable phone number for display on bills
     * and the dashboard. WhatsApp JIDs come in two shapes from wwebjs:
     *
     *   "923001234567@c.us"  → "0300 1234567"
     *   "923001234567"       → "0300 1234567"  (already stripped)
     *
     * Numbers that are NOT Pakistani (no 92 prefix) are returned cleaned but
     * unchanged so international numbers still appear correctly.
     */
    public function getFormattedCustomerPhoneAttribute(): string
    {
        // Strip everything after @ (WhatsApp JID suffix like @c.us or @lid)
        $raw = explode('@', (string) $this->customer_phone)[0];
        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === '') {
            return $this->customer_phone ?? '';
        }

        // Pakistani number formats:
        // 1. 00923XXXXXXXXX (14 digits) -> 03XXXXXXXXX
        if (str_starts_with($digits, '0092') && strlen($digits) === 14) {
            $digits = '0' . substr($digits, 4);
        }
        // 2. 923XXXXXXXXX (12 digits) -> 03XXXXXXXXX
        elseif (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        // 3. 3XXXXXXXXX (10 digits) -> 03XXXXXXXXX
        elseif (str_starts_with($digits, '3') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        // If standard 11-digit Pakistani mobile (03XXXXXXXXX):
        if (str_starts_with($digits, '03') && strlen($digits) === 11) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4);
        }

        // If standard 10-digit or other format, return clean digits or spaced
        if (strlen($digits) >= 7 && strlen($digits) <= 15) {
            return $digits;
        }

        return $raw;
    }

    /**
     * The rider's phone is only useful while they are actually on the way, so it
     * stops being published the moment the order is closed.
     */
    public function showsRiderContact(): bool
    {
        return $this->status === 'out_for_delivery' && trim((string) $this->rider_phone) !== '';
    }

    /**
     * Generate a cryptographically secure token for rider mobile web portal.
     */
    public static function generateRiderToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /**
     * Check if the order has active GPS coordinates streamed recently (within last 30 minutes).
     */
    public function hasLiveGps(): bool
    {
        return ! is_null($this->rider_lat) &&
               ! is_null($this->rider_lng) &&
               ! is_null($this->rider_location_updated_at) &&
               $this->rider_location_updated_at->gt(now()->subMinutes(30));
    }
}