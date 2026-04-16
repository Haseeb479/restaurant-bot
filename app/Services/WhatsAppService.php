<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl = 'https://graph.facebook.com/v19.0';

    public function sendText(Restaurant $r, string $to, string $message): void
    {
        $this->post($r, [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $message],
        ]);
    }

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
