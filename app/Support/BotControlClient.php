<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The only way Laravel talks to the Node bot's internal control server.
 *
 * That server is a privileged surface: `/qr-status` hands out the WhatsApp
 * pairing QR (whoever scans it gains full control of the restaurant's WhatsApp
 * account), `/send-message` sends messages as the restaurant, and `/restart`
 * drops the current session. It used to listen on 0.0.0.0 with
 * `Access-Control-Allow-Origin: *` and no authentication at all, and the
 * dashboard page had the *browser* fetch it directly on port 3000 — so anyone
 * who could reach the host could take over the account.
 *
 * It now binds to loopback and requires this shared secret, so every call has to
 * come from server-side code with the token. Centralising the calls here means
 * the header cannot be forgotten at a new call site.
 */
class BotControlClient
{
    private const HEADER = 'X-Bot-Token';

    /** Short: these calls sit in a request path, and the bot is local. */
    private const TIMEOUT = 5;

    public static function isConfigured(): bool
    {
        return self::token() !== '';
    }

    /**
     * Live bot status, or null if the control server could not be reached.
     *
     * @return array<string, mixed>|null
     */
    public static function status(): ?array
    {
        $response = self::send('get', '/qr-status');

        if ($response === null || ! $response->successful()) {
            return null;
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : null;
    }

    /** Ask the bot to drop its session and produce a fresh pairing QR. */
    public static function restart(): bool
    {
        $response = self::send('post', '/restart');

        return $response !== null && $response->successful();
    }

    /**
     * Send a WhatsApp message through the bot.
     *
     * @param  array<string, mixed>  $logContext
     */
    public static function sendMessage(string $to, string $message, array $logContext = []): bool
    {
        if ($to === '' || $message === '') {
            return false;
        }

        $response = self::send('post', '/send-message', [
            'to'      => $to,
            'message' => $message,
        ]);

        if ($response === null || ! $response->successful()) {
            Log::warning('Bot could not send WhatsApp message', $logContext + [
                'http_status' => $response?->status(),
            ]);

            return false;
        }

        return true;
    }

    /** Drop the bot's cached restaurant/menu data after a dashboard edit. */
    public static function invalidateCache(int $restaurantId, ?string $botNumber = null): void
    {
        // Non-blocking by design: the bot also expires this cache on a short TTL,
        // so a miss here only delays the update, it does not lose it.
        self::send('post', '/invalidate-cache', [
            'restaurant_id' => $restaurantId,
            'bot_number'    => $botNumber,
        ], 1);
    }

    private static function token(): string
    {
        return trim((string) config('app.bot_internal_token', ''));
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config('app.bot_internal_api', 'http://127.0.0.1:3000'), '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function send(string $method, string $path, array $payload = [], ?int $timeout = null): ?\Illuminate\Http\Client\Response
    {
        if (! self::isConfigured()) {
            // Warn rather than throw: a missing token must not turn a status poll
            // into a 500, and the bot rejects the call anyway.
            Log::warning('BOT_INTERNAL_TOKEN is not set — the bot will refuse this call.', ['path' => $path]);
        }

        try {
            return Http::timeout($timeout ?? self::TIMEOUT)
                ->withHeaders([self::HEADER => self::token()])
                // The target is loopback; a redirect could only send the token
                // somewhere it does not belong.
                ->withOptions(['allow_redirects' => false])
                ->acceptJson()
                ->{$method}(self::baseUrl() . $path, $payload);
        } catch (\Throwable $e) {
            // The bot not running is a normal state, not an error worth a stack
            // trace on every poll.
            Log::debug('Bot control call failed: ' . $e->getMessage(), ['path' => $path]);

            return null;
        }
    }
}
