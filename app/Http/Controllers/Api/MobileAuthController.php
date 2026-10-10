<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantApiToken;
use App\Support\AccountLockoutService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    /**
     * Mobile login endpoint.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'password'        => 'required|string',
            'device_name'     => 'nullable|string|max:100',
        ]);

        $input  = trim((string) $validated['restaurant_name']);
        $digits = preg_replace('/[^0-9]/', '', $input);

        // Check per-account lockout
        $lockRemaining = AccountLockoutService::isLocked($input);
        if ($lockRemaining > 0) {
            $minutes = ceil($lockRemaining / 60);
            AuditLog::log('auth.mobile_login_blocked', "Mobile login blocked for [{$input}] from IP [{$request->ip()}].");
            return response()->json([
                'success' => false,
                'message' => "Too many failed attempts. Locked for {$minutes} minute(s).",
            ], 429);
        }

        // Search restaurant
        $restaurant = Restaurant::where(function ($q) use ($input, $digits) {
            $q->whereRaw('LOWER(name) = ?', [strtolower($input)])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($input) . '%'])
              ->orWhere('email', $input);
            if (strlen($digits) >= 7) {
                $q->orWhere('whatsapp_number', 'LIKE', "%{$digits}%")
                  ->orWhere('owner_phone', 'LIKE', "%{$digits}%");
            }
        })
        ->orderByRaw("CASE 
            WHEN LOWER(name) = ? THEN 1 
            WHEN is_active = true AND LOWER(name) LIKE ? THEN 2
            WHEN is_active = true THEN 3
            ELSE 4 END", 
            [strtolower($input), strtolower($input) . '%']
        )
        ->orderByRaw('LENGTH(name) ASC')
        ->first();

        // Constant-time check guard
        if (!$restaurant) {
            AccountLockoutService::recordFailedAttempt($input, $request->ip());
            Hash::check($validated['password'], '$2y$12$3LRFjLZoNuOz4cOHEaLM4ugFgYIYCu0PBPSSKcnBW7QFtmR5n2eAS');
            return response()->json([
                'success' => false,
                'message' => 'Wrong restaurant identifier or password.',
            ], 401);
        }

        // Verify password
        if (!\App\Http\Controllers\DashboardController::passwordMatches($validated['password'], $restaurant->owner_password)) {
            $lockSeconds = AccountLockoutService::recordFailedAttempt($input, $request->ip());
            $msg = $lockSeconds > 0
                ? "Too many failed attempts. Account locked for " . ceil($lockSeconds / 60) . " minute(s)."
                : "Wrong restaurant identifier or password.";
            return response()->json(['success' => false, 'message' => $msg], 401);
        }

        AccountLockoutService::resetAttempts($input);

        if (!$restaurant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This restaurant account is deactivated. Contact support.',
            ], 403);
        }

        // Generate Bearer token
        $plainToken = Str::random(60);
        $hashedToken = hash('sha256', $plainToken);

        RestaurantApiToken::create([
            'restaurant_id' => $restaurant->id,
            'name'          => $validated['device_name'] ?? 'Mobile Device',
            'token'         => $hashedToken,
            'last_used_at'  => now(),
            'expires_at'    => now()->addDays(90),
        ]);

        return response()->json([
            'success' => true,
            'token'   => $plainToken,
            'restaurant' => [
                'id'              => $restaurant->id,
                'name'            => $restaurant->name,
                'owner_name'      => $restaurant->owner_name,
                'email'           => $restaurant->email,
                'whatsapp_number' => $restaurant->whatsapp_number,
                'owner_phone'     => $restaurant->owner_phone,
                'city'            => $restaurant->city,
                'address'         => $restaurant->address,
                'is_open'         => (bool) $restaurant->is_open,
                'is_active'       => (bool) $restaurant->is_active,
                'delivery_charge' => (float) $restaurant->delivery_charge,
                'minimum_order'   => (float) $restaurant->minimum_order,
                'bot_status'      => $restaurant->bot_status ?? 'disconnected',
            ],
        ]);
    }

    /**
     * Get authenticated restaurant session profile.
     */
    public function me(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        return response()->json([
            'success' => true,
            'restaurant' => [
                'id'              => $restaurant->id,
                'name'            => $restaurant->name,
                'owner_name'      => $restaurant->owner_name,
                'email'           => $restaurant->email,
                'whatsapp_number' => $restaurant->whatsapp_number,
                'owner_phone'     => $restaurant->owner_phone,
                'city'            => $restaurant->city,
                'address'         => $restaurant->address,
                'is_open'         => (bool) $restaurant->is_open,
                'is_active'       => (bool) $restaurant->is_active,
                'delivery_charge' => (float) $restaurant->delivery_charge,
                'minimum_order'   => (float) $restaurant->minimum_order,
                'bot_status'      => $restaurant->bot_status ?? 'disconnected',
            ],
        ]);
    }

    /**
     * Mobile logout endpoint (revokes current token).
     */
    public function logout(Request $request)
    {
        $apiToken = $request->attributes->get('api_token');
        if ($apiToken) {
            $apiToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
