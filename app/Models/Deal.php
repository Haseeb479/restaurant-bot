<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deal — a time-based promotion for a restaurant.
 *
 * Deals are injected into the AI system prompt so the bot can mention
 * them naturally when relevant to what the customer is asking.
 *
 * Fields:
 *  - title         : Short name, e.g. "Friday Zinger Deal"
 *  - description   : Full deal text, e.g. "2 Zingers + fries for Rs.1800"
 *  - discount_type : flat | percent | bundle
 *  - discount_value: Amount (flat Rs. off, or % off, or 0 for bundle deals)
 *  - valid_days    : JSON array of days, e.g. ["friday","saturday"]
 *  - valid_from    : Time string "HH:MM" or null (all day)
 *  - valid_until   : Time string "HH:MM" or null (all day)
 *  - is_active     : Toggle on/off without deleting
 */
class Deal extends Model
{
    protected $fillable = [
        'restaurant_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'valid_days',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_days'     => 'array',
        'is_active'      => 'boolean',
        'discount_value' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Scope: only deals that are currently active (day + time matching).
     * Used by RestaurantController and the bot API to inject live deals into prompt.
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        $now   = Carbon::now();
        $today = strtolower($now->format('l')); // e.g. "friday"
        $time  = $now->format('H:i');

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                // null valid_days = valid every day
                $q->whereNull('valid_days')
                  ->orWhereJsonContains('valid_days', $today);
            })
            ->where(function ($q) use ($time) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', $time);
            })
            ->where(function ($q) use ($time) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $time);
            });
    }
}
