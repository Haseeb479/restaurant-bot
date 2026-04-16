<?php
namespace App\Http\Controllers;

use App\Services\{TenantResolver, BotEngine};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private BotEngine $bot) {}

    /**
     * GET /api/webhook
     * Meta calls this once when you set up your webhook URL.
     * Must return the hub.challenge value to verify ownership.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', ['token' => $token]);
        return response('Forbidden', 403);
    }

    /**
     * POST /api/webhook
     * Meta calls this for EVERY incoming message across ALL your restaurants.
     * Must return 200 quickly — heavy work should go to queue.
     */
    public function handle(Request $request)
    {
        $data = $request->json()->all();

        // Always return 200 immediately — Meta retries if you're slow
        // Process message synchronously for now (add queuing later)
        try {
            $this->processPayload($data);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return response('OK', 200);
    }

    private function processPayload(array $data): void
    {
        $entry  = $data['entry'][0] ?? null;
        $change = $entry['changes'][0]['value'] ?? null;

        if (!$change) return;

        // Skip delivery status updates (we only care about messages)
        if (isset($change['statuses'])) return;

        $message  = $change['messages'][0] ?? null;
        if (!$message) return;

        // Skip non-customer messages (e.g. our own sent messages)
        if (($message['from'] ?? '') === ($change['metadata']['display_phone_number'] ?? '')) return;

        $fromPhone  = $message['from'];                                  // customer's number
        $toPhoneId  = $change['metadata']['phone_number_id'];           // your restaurant's Meta phone ID
        $msgType    = $message['type'];                                  // text, interactive, image, etc.
        $text       = $message['text']['body'] ?? '';

        // For interactive messages (button/list replies), extract the text for display
        if ($msgType === 'interactive') {
            $text = $message['interactive']['button_reply']['title']
                 ?? $message['interactive']['list_reply']['title']
                 ?? '';
        }

        Log::info('Incoming WA message', [
            'from'     => $fromPhone,
            'phone_id' => $toPhoneId,
            'type'     => $msgType,
            'text'     => substr($text, 0, 100),
        ]);

        // Find which restaurant this message belongs to
        $restaurant = TenantResolver::resolve($toPhoneId);
        if (!$restaurant) {
            Log::warning('No restaurant found for phone_id', ['phone_id' => $toPhoneId]);
            return;
        }

        // Pass to bot brain
        $this->bot->handle($restaurant, $fromPhone, $text, $message);
    }
}