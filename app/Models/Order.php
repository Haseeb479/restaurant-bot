<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'daily_order_number',
        'order_type',
        'table_number',
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
        'delivery_lat',
        'delivery_lng',
        'delivery_distance_km',
        'delivery_place_name',
        'delivery_place_id',
        'location_source',
    ];

    protected $casts = [
        'daily_order_number'        => 'integer',
        'is_paid'                   => 'boolean',
        'owner_notified'            => 'boolean',
        'customer_notified'         => 'boolean',
        'subtotal'                  => 'decimal:2',
        'delivery_charge'           => 'decimal:2',
        'total'                     => 'decimal:2',
        'rider_lat'                 => 'decimal:7',
        'rider_lng'                 => 'decimal:7',
        'rider_location_updated_at' => 'datetime',
        'delivery_lat'              => 'decimal:7',
        'delivery_lng'              => 'decimal:7',
        'delivery_distance_km'      => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->daily_order_number) && !empty($order->restaurant_id)) {
                $date = $order->created_at ? $order->created_at->toDateString() : today()->toDateString();
                $maxDaily = static::where('restaurant_id', $order->restaurant_id)
                    ->whereDate('created_at', $date)
                    ->max('daily_order_number');
                $order->daily_order_number = ($maxDaily ? (int)$maxDaily : 0) + 1;
            }
        });

        static::saved(function (Order $order) {
            // 1. Sync Customer database record for CRM & future deals
            $order->syncCustomerProfile();

            // 2. Automatically save/update order record in the day's file archive
            try {
                if ($order->restaurant_id) {
                    $date = $order->created_at ? $order->created_at->toDateString() : today()->toDateString();
                    $restaurant = $order->restaurant ?? Restaurant::find($order->restaurant_id);
                    if ($restaurant) {
                        \App\Services\OrderArchiveService::generateDayCsv($restaurant, $date);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Order archive file sync error: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get clean daily order display number, e.g., "#1", "#2", etc.
     */
    public function getDisplayNumberAttribute(): string
    {
        return '#' . ($this->daily_order_number ?: $this->id);
    }

    /**
     * Automatically update or create customer record in CRM so owner can broadcast future deals.
     */
    public function syncCustomerProfile(): void
    {
        if (empty($this->customer_phone) || empty($this->restaurant_id)) {
            return;
        }

        try {
            $c = Customer::firstOrNew([
                'restaurant_id' => $this->restaurant_id,
                'phone'         => $this->customer_phone,
            ]);

            if (!empty($this->customer_name) && (empty($c->name) || $c->name === 'Customer' || $c->name === 'Guest')) {
                $c->name = $this->customer_name;
            } elseif (empty($c->name)) {
                $c->name = $this->customer_name ?: 'Customer';
            }

            if (!empty($this->delivery_address)) {
                $c->address = $this->delivery_address;
            }

            $stats = static::where('restaurant_id', $this->restaurant_id)
                ->where('customer_phone', $this->customer_phone)
                ->selectRaw("COUNT(*) as total_cnt, SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) as spent, MAX(created_at) as last_order")
                ->first();

            $c->total_orders  = (int) ($stats->total_cnt ?? 1);
            $c->total_spent   = (float) ($stats->spent ?? $this->total);
            $c->last_order_at = $stats->last_order ?? now();
            $c->tag           = $c->total_orders >= 5 ? 'VIP' : ($c->total_orders >= 2 ? 'Frequent' : 'New');

            if (!isset($c->opt_in_marketing)) {
                $c->opt_in_marketing = true;
            }

            $c->save();
        } catch (\Throwable $e) {
            \Log::warning('Customer profile sync error: ' . $e->getMessage());
        }
    }

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
        'ready',
        'served',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    /**
     * Strict state machine defining valid forward and terminal transitions.
     * Prevents invalid backwards transitions (e.g. delivered -> pending) (C3).
     */
    public const ALLOWED_TRANSITIONS = [
        'pending'          => ['confirmed', 'preparing', 'ready', 'served', 'cancelled'],
        'confirmed'        => ['preparing', 'ready', 'served', 'out_for_delivery', 'delivered', 'cancelled'],
        'preparing'        => ['ready', 'served', 'out_for_delivery', 'delivered', 'cancelled'],
        'ready'            => ['served', 'out_for_delivery', 'delivered', 'cancelled'],
        'served'           => ['delivered', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered'        => [],
        'cancelled'        => [],
    ];

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }


    /**
     * Crockford Base32 — omits I, L, O and U so a code can't be misread (1/I,
     * 0/O) or spell something unfortunate. 32 symbols = 5 bits each.
     */
    public const TRACKING_CODE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /** 16 symbols x 5 bits = 80 bits of entropy. */
    public const TRACKING_CODE_LENGTH = 16;

    /**
     * Generate a unique, cryptographically secure random tracking code,
     * e.g. `FZ-7K2MQX9P4TVBNH3R` or `ORD-8X9K2M1PQ4TVBNH3`.
     *
     * The tracking code is a bearer token: anyone holding it can check order status
     * and details via /track or WhatsApp. Predictable or sequential codes allow
     * unauthorized enumeration of customer orders. Using a CSPRNG with 80 bits
     * of entropy (16 characters from Crockford Base32) makes brute-forcing impossible.
     *
     * $orderId is accepted for signature compatibility with existing callers.
     */
    public static function generateTrackingCode(Restaurant $restaurant, ?int $orderId = null): string
    {
        $prefix = static::trackingPrefix($restaurant->name ?? '');

        // 80 bits makes a collision practically impossible (~10^24 combinations),
        // but retry loop guarantees uniqueness against the UNIQUE database column.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $prefix . '-' . static::randomTrackingSuffix();

            if (! static::where('tracking_code', $code)->exists()) {
                return $code;
            }
        }

        // Safety fallback: widen suffix if attempts are exhausted
        return $prefix . '-' . static::randomTrackingSuffix() . static::randomTrackingSuffix();
    }

    /**
     * Cryptographically secure random suffix. `random_int` is a CSPRNG.
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
            'ready'            => '🍽️ Ready / Prepared',
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
            'ready'            => 'Your order is ready and prepared! 🍽️',
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

        // A single run-on line with no commas — show prefix with actual area name
        return '••• ' . (strlen($address) > 35 ? substr($address, -28) : $address);
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

        // If current customer_phone looks like an internal WhatsApp LID (e.g. 4449... or non-Pakistani synthetic id)
        // Check if the customer gave a real phone number in notes/chat:
        if (! str_starts_with($digits, '03') && ! str_starts_with($digits, '923') && ! str_starts_with($digits, '0092')) {
            if (! empty($this->notes) && preg_match('/(?:Phone|Contact|Mobile|Cell|Number|Rabta)\s*[:：]\s*([0-9+\- ]{10,16})/i', $this->notes, $m)) {
                $notesDigits = preg_replace('/\D/', '', $m[1]);
                if (str_starts_with($notesDigits, '923') && strlen($notesDigits) === 12) {
                    $notesDigits = '0' . substr($notesDigits, 2);
                } elseif (str_starts_with($notesDigits, '3') && strlen($notesDigits) === 10) {
                    $notesDigits = '0' . $notesDigits;
                }
                if (str_starts_with($notesDigits, '03') && strlen($notesDigits) === 11) {
                    return substr($notesDigits, 0, 4) . ' ' . substr($notesDigits, 4);
                }
            }
        }

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

    /**
     * Check if this is a dine-in order.
     */
    public function isDineIn(): bool
    {
        if (($this->order_type ?? '') === 'dine_in') {
            return true;
        }
        if (!empty($this->table_number) || !empty($this->table_id)) {
            return true;
        }
        return str_contains(strtolower(trim((string)$this->delivery_address)), 'table')
            || str_contains(strtolower(trim((string)$this->customer_phone)), 'dine-in')
            || str_contains(strtolower(trim((string)$this->notes)), 'dine-in');
    }

    /**
     * Get resolved table number (e.g. "4", "Table 4").
     */
    public function getResolvedTableNumber(): ?string
    {
        if (!empty($this->table_number)) {
            return (string) $this->table_number;
        }
        if (preg_match('/table\s*#?\s*([a-z0-9\-]+)/i', (string) $this->delivery_address, $m)) {
            return $m[1];
        }
        if (preg_match('/table\s*#?\s*([a-z0-9\-]+)/i', (string) $this->notes, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Scope for Dine-In orders.
     */
    public function scopeDineIn($query)
    {
        return $query->where(function ($q) {
            $q->where('order_type', 'dine_in')
              ->orWhereNotNull('table_number')
              ->orWhere('delivery_address', 'LIKE', 'Table %')
              ->orWhere('notes', 'LIKE', '%DINE-IN%');
        });
    }
}