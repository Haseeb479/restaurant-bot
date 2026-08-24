<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Simple platform-wide key/value store (see the create_settings_table migration).
 *
 * Used for values that must be changeable at runtime from the super-admin panel
 * rather than baked into .env — currently the hashed admin master password.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'setting:';

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $value = Cache::remember(
                self::CACHE_PREFIX . $key,
                300,
                fn () => static::where('key', $key)->value('value')
            );
        } catch (\Throwable $e) {
            // Table may not exist yet (pre-migration) — fail open to the default.
            return $default;
        }

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}
