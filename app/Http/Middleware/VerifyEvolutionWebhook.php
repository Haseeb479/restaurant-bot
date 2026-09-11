<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that incoming EvolutionAPI webhook requests carry the correct API key.
 *
 * EvolutionAPI sends every webhook with an `apikey` header equal to the
 * EVOLUTION_API_KEY you configured in its own settings. We compare the
 * inbound header against our own copy of that key using constant-time
 * comparison to prevent timing-based enumeration.
 *
 * Routes that skip CSRF (withoutMiddleware([PreventRequestForgery])) MUST
 * apply this middleware instead to prevent unauthenticated callers from
 * injecting fake WhatsApp events.
 */
class VerifyEvolutionWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = trim((string) config('services.evolution.api_key', ''));

        // If the key is not configured, block all webhook traffic —
        // better to go offline than to process forged messages.
        if ($expectedKey === '') {
            abort(503, 'Webhook authentication is not configured on this server.');
        }

        $incomingKey = trim((string) (
            $request->header('apikey')
            ?? $request->header('x-api-key')
            ?? $request->query('apikey')
            ?? ''
        ));

        if ($incomingKey === '' && $request->hasHeader('Authorization')) {
            $auth = trim((string) $request->header('Authorization'));
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                $incomingKey = trim(substr($auth, 7));
            }
        }

        if (! hash_equals($expectedKey, $incomingKey)) {
            \Illuminate\Support\Facades\Log::warning('EvolutionAPI webhook rejected: invalid API key', [
                'ip'            => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'incoming_key'  => substr($incomingKey, 0, 4) . '****',
            ]);
            abort(403, 'Invalid webhook API key.');
        }

        return $next($request);
    }
}
