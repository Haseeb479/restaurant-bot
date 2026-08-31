<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for communicating with the local EvolutionAPI v2 service.
 *
 * Each restaurant is an isolated WhatsApp instance ("rest_{id}"), guaranteeing
 * complete multi-tenant isolation, distinct phone numbers, and zero crosstalk.
 */
class BotEvolutionClient
{
    private const API_KEY_HEADER = 'apikey';
    private const TIMEOUT = 8;

    public static function instanceName(Restaurant|int|string $restaurant): string
    {
        $id = $restaurant instanceof Restaurant ? $restaurant->id : $restaurant;
        return 'rest_' . $id;
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return trim((string) config('services.evolution.api_key', env('EVOLUTION_API_KEY', 'foodio_evolution_secret_key_2026')));
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('services.evolution.base_url', env('EVOLUTION_BASE_URL', 'http://127.0.0.1:8080')), '/');
    }

    /**
     * Create or retrieve an isolated WhatsApp instance for a restaurant.
     *
     * @return array<string, mixed>|null
     */
    public static function createInstance(Restaurant $restaurant): ?array
    {
        $instanceName = self::instanceName($restaurant);

        $response = self::send('post', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ]);

        if ($response && ($response->successful() || $response->status() === 403 || $response->status() === 409)) {
            // Instance exists or created
            $restaurant->update([
                'evolution_instance_id' => $instanceName,
            ]);

            // Set up webhook for incoming messages and status changes
            self::configureWebhook($restaurant);

            return $response->json();
        }

        return null;
    }

    /**
     * Set up webhook routing from EvolutionAPI to Foodio Laravel backend.
     */
    public static function configureWebhook(Restaurant $restaurant): bool
    {
        $instanceName = self::instanceName($restaurant);
        $webhookUrl   = url('/webhook/whatsapp');

        $response = self::send('post', "/webhook/set/{$instanceName}", [
            'webhook' => [
                'enabled'   => true,
                'url'       => $webhookUrl,
                'byEvents'  => false,
                'base64'    => true,
                'events'    => [
                    'QRCODE_UPDATED',
                    'MESSAGES_UPSERT',
                    'CONNECTION_UPDATE',
                ],
            ],
        ]);

        return $response !== null && $response->successful();
    }

    /**
     * Get pairing code (e.g. 8-digit code for phone linking without QR camera).
     *
     * @return array{pairingCode: ?string, code: ?string}|null
     */
    public static function getPairingCode(Restaurant $restaurant, string $phoneNumber): ?array
    {
        $instanceName = self::instanceName($restaurant);
        $cleanNumber  = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Normalize Pakistani format
        if (str_starts_with($cleanNumber, '03') && strlen($cleanNumber) === 11) {
            $cleanNumber = '92' . substr($cleanNumber, 1);
        } elseif (str_starts_with($cleanNumber, '3') && strlen($cleanNumber) === 10) {
            $cleanNumber = '92' . $cleanNumber;
        }

        self::createInstance($restaurant);

        $response = self::send('get', "/instance/connect/{$instanceName}?number={$cleanNumber}");

        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $code = $data['pairingCode'] ?? $data['code'] ?? null;

        return [
            'pairingCode' => $code,
            'code'        => $code,
        ];
    }

    /**
     * Get QR code for web scanning.
     *
     * @return array{base64: ?string, code: ?string, pairingCode: ?string}|null
     */
    public static function getQrCode(Restaurant $restaurant): ?array
    {
        $instanceName = self::instanceName($restaurant);
        self::createInstance($restaurant);

        $response = self::send('get', "/instance/connect/{$instanceName}");

        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return [
            'base64'      => $data['base64'] ?? $data['qrcode']['base64'] ?? null,
            'code'        => $data['code'] ?? $data['qrcode']['code'] ?? null,
            'pairingCode' => $data['pairingCode'] ?? null,
        ];
    }

    /**
     * Get connection state ('open', 'close', 'connecting', 'disconnected').
     *
     * @return array{state: string, instanceName: string}|null
     */
    public static function getConnectionState(Restaurant $restaurant): ?array
    {
        $instanceName = self::instanceName($restaurant);
        $response     = self::send('get', "/instance/connectionState/{$instanceName}");

        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data  = $response->json();
        $state = $data['instance']['state'] ?? $data['state'] ?? 'close';

        // Update restaurant database status
        $statusMap = [
            'open'       => 'connected',
            'connecting' => 'qr_pending',
            'close'      => 'disconnected',
        ];

        $botStatus = $statusMap[$state] ?? 'disconnected';
        if ($restaurant->evolution_status !== $botStatus) {
            $restaurant->update([
                'evolution_status' => $botStatus,
                'bot_status'       => $botStatus,
            ]);
        }

        return [
            'state'        => $state,
            'instanceName' => $instanceName,
        ];
    }

    /**
     * Send a WhatsApp message through the restaurant's dedicated instance.
     *
     * @param  array<string, mixed>  $logContext
     */
    public static function sendMessage(Restaurant $restaurant, string $to, string $message, array $logContext = []): bool
    {
        if ($to === '' || $message === '') {
            return false;
        }

        $instanceName = self::instanceName($restaurant);

        if (str_ends_with($to, '@s.whatsapp.net')) {
            $digits = preg_replace('/[^0-9]/', '', explode('@', $to)[0]);
            if (str_starts_with($digits, '03') && strlen($digits) === 11) {
                $targetRecipient = '92' . substr($digits, 1);
            } elseif (str_starts_with($digits, '3') && strlen($digits) === 10) {
                $targetRecipient = '92' . $digits;
            } else {
                $targetRecipient = $digits;
            }
        } elseif (str_contains($to, '@')) {
            $targetRecipient = $to;
        } else {
            $digits = preg_replace('/[^0-9]/', '', $to);
            if (str_starts_with($digits, '03') && strlen($digits) === 11) {
                $targetRecipient = '92' . substr($digits, 1);
            } elseif (str_starts_with($digits, '3') && strlen($digits) === 10) {
                $targetRecipient = '92' . $digits;
            } else {
                $targetRecipient = $digits;
            }
        }

        $response = self::send('post', "/message/sendText/{$instanceName}", [
            'number'  => $targetRecipient,
            'text'    => $message,
            'options' => [
                'delay'       => 1000,
                'presence'    => 'composing',
                'linkPreview' => true,
            ],
        ]);

        if ($response === null || ! $response->successful()) {
            Log::warning("EvolutionAPI: Failed sending WhatsApp message from [{$instanceName}] to [{$cleanNumber}]", $logContext + [
                'http_status' => $response?->status(),
                'response'    => $response?->json(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Restart instance to reset session and produce fresh QR / pairing code.
     */
    public static function restartInstance(Restaurant $restaurant): bool
    {
        $instanceName = self::instanceName($restaurant);
        $response     = self::send('post', "/instance/restart/{$instanceName}");

        return $response !== null && $response->successful();
    }

    /**
     * Delete instance from EvolutionAPI when restaurant is deleted or reset.
     */
    public static function deleteInstance(Restaurant $restaurant): bool
    {
        $instanceName = self::instanceName($restaurant);
        $response     = self::send('delete', "/instance/delete/{$instanceName}");

        return $response !== null && $response->successful();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function send(string $method, string $path, array $payload = [], ?int $timeout = null): ?\Illuminate\Http\Client\Response
    {
        try {
            $request = Http::timeout($timeout ?? self::TIMEOUT)
                ->withHeaders([self::API_KEY_HEADER => self::apiKey()])
                ->withOptions(['allow_redirects' => false])
                ->acceptJson();

            $url = self::baseUrl() . $path;

            if (strtolower($method) === 'get') {
                return $request->get($url, $payload);
            }

            return $request->{$method}($url, $payload);
        } catch (\Throwable $e) {
            Log::debug("EvolutionAPI call failed [{$method} {$path}]: " . $e->getMessage());

            return null;
        }
    }
}
