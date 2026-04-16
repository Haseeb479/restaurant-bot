<?php
namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ============================================================
// TenantResolver — finds which restaurant owns an incoming message
// ============================================================
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

    // Clear cache when restaurant settings change
    public static function clearCache(Restaurant $r): void
    {
        Cache::forget("restaurant_phone_{$r->wa_phone_id}");
    }
}


// ============================================================
// WhatsAppService — sends all message types via Meta Cloud API
// ============================================================
class WhatsAppService
{
    private string $baseUrl = 'https://graph.facebook.com/v19.0';

    // ── Plain text message ─────────────────────────────────
    public function sendText(Restaurant $r, string $to, string $message): void
    {
        $this->post($r, [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $message],
        ]);
    }

    // ── Interactive LIST (shows scrollable menu) ───────────
    // sections = [['title' => 'Burgers', 'rows' => [['id'=>'item_1','title'=>'Zinger','description'=>'Rs.350']]]]
    public function sendList(
        Restaurant $r,
        string $to,
        string $header,
        string $body,
        string $footer,
        string $buttonText,
        array $sections
    ): void {
        $this->post($r, [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'header' => ['type' => 'text', 'text' => $header],
                'body'   => ['text' => $body],
                'footer' => ['text' => $footer],
                'action' => [
                    'button'   => $buttonText,
                    'sections' => $sections,
                ],
            ],
        ]);
    }

    // ── Reply buttons (max 3 buttons) ──────────────────────
    // buttons = [['id' => 'btn_confirm', 'title' => 'Confirm Order']]
    public function sendButtons(
        Restaurant $r,
        string $to,
        string $body,
        array $buttons,
        string $header = '',
        string $footer = ''
    ): void {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'button',
                'body'   => ['text' => $body],
                'action' => [
                    'buttons' => array_map(fn($b) => [
                        'type'  => 'reply',
                        'reply' => ['id' => $b['id'], 'title' => $b['title']],
                    ], $buttons),
                ],
            ],
        ];
        if ($header) $payload['interactive']['header'] = ['type' => 'text', 'text' => $header];
        if ($footer) $payload['interactive']['footer'] = ['text' => $footer];
        $this->post($r, $payload);
    }

    // ── Internal HTTP call to Meta ─────────────────────────
    private function post(Restaurant $r, array $payload): void
    {
        try {
            $response = Http::withToken($r->wa_access_token)
                ->timeout(10)
                ->post("{$this->baseUrl}/{$r->wa_phone_id}/messages", $payload);

            if (!$response->successful()) {
                Log::error('WhatsApp API error', [
                    'restaurant' => $r->name,
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                    'payload'    => $payload,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);
        }
    }
}