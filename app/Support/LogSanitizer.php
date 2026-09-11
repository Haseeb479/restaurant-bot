<?php

namespace App\Support;

class LogSanitizer
{
    /**
     * Mask phone numbers: e.g. 03293647476 -> 03*****476, +923293647476 -> +92*****476
     */
    public static function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '[NO_PHONE]';
        }

        $phone = trim($phone);
        $len = strlen($phone);

        if ($len <= 5) {
            return '***';
        }

        $prefix = substr($phone, 0, 3);
        $suffix = substr($phone, -3);

        return $prefix . str_repeat('*', max(3, $len - 6)) . $suffix;
    }

    /**
     * Mask emails: e.g. customer@example.com -> c******r@example.com
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return '[NO_EMAIL]';
        }

        [$local, $domain] = explode('@', $email, 2);
        $len = strlen($local);

        if ($len <= 2) {
            $maskedLocal = substr($local, 0, 1) . '*';
        } else {
            $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(3, $len - 2)) . substr($local, -1);
        }

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Redact free-text message bodies to prevent leaking customer PII in logs (Req 12).
     * Retains length and safe command keywords for observability without logging customer addresses/names.
     */
    public static function redactMessage(?string $message): string
    {
        if (empty($message)) {
            return '[EMPTY_MESSAGE]';
        }

        $trimmed = trim($message);

        // Check if message is a simple command (menu, track, status)
        if (preg_match('/^(menu|list|help|start|hi|hello|track|status|track\s+[A-Za-z0-9-]+)$/i', $trimmed)) {
            return "[COMMAND: {$trimmed}]";
        }

        return '[REDACTED MESSAGE ' . strlen($trimmed) . ' chars]';
    }

    /**
     * Redact postal addresses or GPS location strings.
     */
    public static function redactAddress(?string $address): string
    {
        if (empty($address)) {
            return '[NO_ADDRESS]';
        }

        return '[REDACTED ADDRESS]';
    }

    /**
     * Sanitize an entire context array of sensitive keys.
     */
    public static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'owner_password', 'current_password', 'new_password',
            'api_key', 'apikey', 'secret', 'token', 'bot_token', 'verification_token',
            'two_fa_pin', 'admin_2fa_pin'
        ];

        $sanitized = [];

        foreach ($context as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED SECRET]';
            } elseif (str_contains($lowerKey, 'phone') || str_contains($lowerKey, 'mobile')) {
                $sanitized[$key] = is_string($value) ? self::maskPhone($value) : $value;
            } elseif (str_contains($lowerKey, 'email')) {
                $sanitized[$key] = is_string($value) ? self::maskEmail($value) : $value;
            } elseif (str_contains($lowerKey, 'address')) {
                $sanitized[$key] = is_string($value) ? self::redactAddress($value) : $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
