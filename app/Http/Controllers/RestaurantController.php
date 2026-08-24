<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    /**
     * GET /api/restaurant-by-bot/{botNumber}
     *
     * Used by the Node.js WhatsApp bot to identify which restaurant owns
     * the incoming message, then loads its menu items and active deals.
     * The bot caches this result for 5 minutes, so keep the response lean.
     */
    public function getByBotNumber(string $botNumber)
    {
        // Strip all non-digits first
        $digits = preg_replace('/[^0-9]/', '', $botNumber);

        /**
         * WhatsApp Web (wid.user) returns the number in international format
         * WITHOUT leading + but WITH country code: e.g. 923293647476
         * Restaurant owners may register as: 03293647476, +923293647476, 923293647476
         *
         * Build a list of all candidate forms to match any stored format.
         */
        $candidates = [$digits]; // raw form: e.g. 923293647476

        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            // 923293647476 → 03293647476 (local Pakistani format)
            $candidates[] = '0' . substr($digits, 2); // 03293647476
            $candidates[] = '+92' . substr($digits, 2); // +923293647476
            $candidates[] = substr($digits, 2); // 3293647476 (no leading 0)
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            // 03293647476 → 923293647476 (international without +)
            $candidates[] = '92' . substr($digits, 1); // 923293647476
            $candidates[] = '+92' . substr($digits, 1); // +923293647476
            $candidates[] = substr($digits, 1); // 3293647476
        } elseif (strlen($digits) === 10) {
            // 3293647476 → add leading 0 and country code
            $candidates[] = '0' . $digits; // 03293647476
            $candidates[] = '92' . $digits; // 923293647476
            $candidates[] = '+92' . $digits; // +923293647476
        }

        $restaurant = null;

        // 1. Try exact match against candidate formats
        foreach ($candidates as $candidate) {
            $restaurant = Restaurant::where('whatsapp_number', $candidate)
                ->where('is_active', true)
                ->with([
                    'menuItems' => fn($q) => $q
                        ->where('is_available', true)
                        ->orderBy('sort_order'),
                    'categories' => fn($q) => $q
                        ->where('is_active', true)
                        ->orderBy('sort_order'),
                ])
                ->first();

            if ($restaurant) {
                Log::info("Bot lookup matched: {$restaurant->name} via candidate: {$candidate}");
                break;
            }
        }

        // 2. Fallback: match by core 9-10 digits (matches across 0329... vs +92329... vs 92329...)
        if (!$restaurant && strlen($digits) >= 9) {
            $last10 = substr($digits, -10);
            $last9  = substr($digits, -9);

            $restaurant = Restaurant::where('is_active', true)
                ->where(function ($q) use ($last10, $last9) {
                    $q->where('whatsapp_number', 'LIKE', "%{$last10}")
                      ->orWhere('whatsapp_number', 'LIKE', "%{$last9}");
                })
                ->with([
                    'menuItems' => fn($q) => $q
                        ->where('is_available', true)
                        ->orderBy('sort_order'),
                    'categories' => fn($q) => $q
                        ->where('is_active', true)
                        ->orderBy('sort_order'),
                ])
                ->first();

            if ($restaurant) {
                Log::info("Bot lookup matched: {$restaurant->name} via core digits: {$last10}");
            }
        }

        // 3. Smart auto-binding fallback: If only 1 active restaurant exists in DB, bind it automatically!
        if (!$restaurant && Restaurant::where('is_active', true)->count() === 1) {
            $restaurant = Restaurant::where('is_active', true)
                ->with([
                    'menuItems' => fn($q) => $q
                        ->where('is_available', true)
                        ->orderBy('sort_order'),
                    'categories' => fn($q) => $q
                        ->where('is_active', true)
                        ->orderBy('sort_order'),
                ])
                ->first();

            if ($restaurant) {
                $restaurant->update(['whatsapp_number' => $digits]);
                Log::info("Bot lookup auto-bound sole restaurant '{$restaurant->name}' to bot number {$digits}");
            }
        }

        if (!$restaurant) {
            Log::warning("Bot lookup: no restaurant found for botNumber={$botNumber}, tried: " . implode(', ', $candidates));
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        // Active deals filtered by current day + time
        $activeDeals = $restaurant->activeDeals()->get();

        return response()->json([
            ...$restaurant->toArray(),
            'active_deals' => $activeDeals,
            'is_open'      => $restaurant->is_open,
        ]);
    }

    // ── Web Routes (self-service restaurant registration) ──────────────────────

    /**
     * GET /restaurant/register
     */
    public function showRegistrationForm()
    {
        return view('restaurant.register');
    }

    /**
     * POST /restaurant/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'whatsapp_number' => 'required|string|unique:restaurants',
            'owner_phone'     => 'required|string',
            'owner_password'  => 'required|string|min:12',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
        ]);

        $restaurant = new Restaurant(
            $request->only(['name', 'whatsapp_number', 'owner_phone', 'city', 'address'])
        );
        $restaurant->plan           = 'trial';
        $restaurant->is_active      = true;
        $restaurant->is_open        = true;
        $restaurant->owner_password = Hash::make($request->input('owner_password'));
        $restaurant->save();

        // Automatically log owner into dashboard session
        $request->session()->regenerate();
        session(["restaurant_{$restaurant->id}" => true]);
        session(["restaurant_{$restaurant->id}_login_time" => now()->toIso8601String()]);

        return redirect()
            ->route('dashboard.connect-whatsapp', ['id' => $restaurant->id])
            ->with('success', '🎉 Restaurant registered! Please scan the QR code below to connect your WhatsApp bot.');
    }
}
