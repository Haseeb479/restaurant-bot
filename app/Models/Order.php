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
     * Crockford Base32 — omits I, L, O and U so a code can't be misread (1/I,
     * 0/O) or spell something unfortunate. 32 symbols = 5 bits each.
     */
    public const TRACKING_CODE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /** 16 symbols x 5 bits = 80 bits of entropy. */
    public const TRACKING_CODE_LENGTH = 16;

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
     * Generate a unique tracking code, e.g. `JC-7K2MQX9P4TVBNH3R`.
     *
     * The tracking code is a bearer token: anyone holding it can read the
     * customer's name, phone, address and order contents via /track. The old
     * format was `{initials}-{year}-{zero-padded order id}` — strictly
     * sequential, so knowing one code exposed every other order.
     *
     * Entropy note: this is 80 bits rather than the 128 originally sketched.
     * The code has to survive being read aloud and typed into WhatsApp by a
     * customer, and 128 bits means a 26-character string. At 80 bits, combined
     * with the rate limit on /track, guessing is not a practical attack
     * (~10^24 attempts), while the code stays a manageable length.
     *
     * $orderId is accepted for signature compatibility with existing callers but
     * is deliberately unused — deriving any part of the code from it is what
     * made the old format enumerable.
     */
    public static function generateTrackingCode(Restaurant $restaurant, ?int $orderId = null): string
    {
        $prefix = static::trackingPrefix($restaurant->name ?? '');

        // 80 bits makes a collision effectively impossible, but `tracking_code`
        // is UNIQUE, so a clash would be a failed order for a real customer.
        // One cheap existence check removes that class of failure entirely.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = $prefix . '-' . static::randomTrackingSuffix();

            if (! static::where('tracking_code', $code)->exists()) {
                return $code;
            }
        }

        // Effectively unreachable; widen rather than return a known-taken code.
        return $prefix . '-' . static::randomTrackingSuffix() . static::randomTrackingSuffix();
    }

    /**
     * Up to 3 A–Z initials from the restaurant name, for human recognisability.
     * Falls back to ORD when the name has no usable Latin letters (the old
     * `$w[0]` indexing could also slice a multi-byte character in half).
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

    /**
     * Cryptographically secure random suffix. `random_int` is a CSPRNG; the
     * previous generators used a sequential id (Laravel) and `Math.random()`
     * with only 9,000 possible values (the bot).
     */
    private static function randomTrackingSuffix(): string
    {
        $alphabet = self::TRACKING_CODE_ALPHABET;
        $max      = strlen($alphabet) - 1;
        $code     = '';

        for ($i = 0; $i < self::TRACKING_CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
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
}