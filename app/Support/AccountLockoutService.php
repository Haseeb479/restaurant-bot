<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

class AccountLockoutService
{
    private const MAX_ATTEMPTS = 5;
    private const INITIAL_LOCK_MINUTES = 15;
    private const EXTENDED_LOCK_MINUTES = 60;

    /**
     * Normalize identifier (lowercased email, stripped phone digits, or clean name)
     * to ensure consistent lockout tracking regardless of whitespace or casing (Req 11).
     */
    public static function normalizeIdentifier(string $identifier): string
    {
        $clean = trim($identifier);
        $digits = preg_replace('/[^0-9]/', '', $clean);

        if (filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return 'email_' . strtolower($clean);
        }

        if (strlen($digits) >= 10) {
            return 'phone_' . substr($digits, -10);
        }

        return 'name_' . strtolower(preg_replace('/[^a-z0-9]/i', '', $clean));
    }

    private static function cacheKey(string $normalized): string
    {
        return 'account_lockout_' . sha1($normalized);
    }

    /**
     * Check if account is currently locked out.
     * Returns remaining lockout seconds, or 0 if not locked.
     */
    public static function isLocked(string $identifier): int
    {
        $key = self::cacheKey(self::normalizeIdentifier($identifier));
        $data = Cache::get($key);

        if (! is_array($data) || empty($data['locked_until'])) {
            return 0;
        }

        $remaining = $data['locked_until'] - time();
        if ($remaining > 0) {
            return $remaining;
        }

        return 0;
    }

    /**
     * Record a failed login attempt with progressive backoff.
     */
    public static function recordFailedAttempt(string $identifier, ?string $ip = null): int
    {
        $norm = self::normalizeIdentifier($identifier);
        $key = self::cacheKey($norm);
        $data = Cache::get($key, ['attempts' => 0, 'locked_until' => null, 'total_locks' => 0]);

        $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $totalLocks = (int) ($data['total_locks'] ?? 0) + 1;
            $data['total_locks'] = $totalLocks;
            // Progressive backoff: repeated lockouts get longer duration
            $lockDurationMinutes = $totalLocks > 1 ? self::EXTENDED_LOCK_MINUTES : self::INITIAL_LOCK_MINUTES;
            $data['locked_until'] = time() + ($lockDurationMinutes * 60);

            Cache::put($key, $data, now()->addMinutes($lockDurationMinutes + 5));

            AuditLog::log('auth.account_lockout', "Account lockout triggered for identifier [{$norm}] from IP [{$ip}] for {$lockDurationMinutes} mins.");

            return $lockDurationMinutes * 60;
        }

        Cache::put($key, $data, now()->addMinutes(15));
        return 0;
    }

    /**
     * Reset failed attempt counter upon successful authentication.
     */
    public static function resetAttempts(string $identifier): void
    {
        $key = self::cacheKey(self::normalizeIdentifier($identifier));
        Cache::forget($key);
    }
}
