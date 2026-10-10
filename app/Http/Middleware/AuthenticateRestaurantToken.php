<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\RestaurantApiToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRestaurantToken
{
    /**
     * Handle an incoming mobile/API request by verifying Bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing authentication token.',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = RestaurantApiToken::where('token', $hashedToken)->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or expired token.',
            ], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            $apiToken->delete();
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token has expired.',
            ], 401);
        }

        $restaurant = $apiToken->restaurant;

        if (!$restaurant || !$restaurant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account deactivated or unavailable.',
            ], 403);
        }

        // Touch last used timestamp
        $apiToken->update(['last_used_at' => now()]);

        // Authoritative tenant binding:
        // Expose $request->attributes->get('restaurant') and inject into user resolver
        $request->attributes->set('restaurant', $restaurant);
        $request->attributes->set('api_token', $apiToken);
        $request->setUserResolver(fn() => $restaurant);

        return $next($request);
    }
}
