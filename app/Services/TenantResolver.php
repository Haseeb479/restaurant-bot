<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    /**
     * Meta sends the phone_number_id of YOUR number that received the message.
     * We look that up in our restaurants table to find which restaurant it is.
     * Cached for 5 minutes to avoid hitting the DB on every message.
     */
    public static function resolve(string $phoneNumberId): ?Restaurant
    {
        $cacheKey = "restaurant_phone_{$phoneNumberId}";

        return Cache::remember($cacheKey, 300, function () use ($phoneNumberId) {
            return Restaurant::where('wa_phone_id', $phoneNumberId)
                ->where('is_active', true)
                ->first();
        });
    }

    public static function clearCache(Restaurant $r): void
    {
        Cache::forget("restaurant_phone_{$r->wa_phone_id}");
    }
}
