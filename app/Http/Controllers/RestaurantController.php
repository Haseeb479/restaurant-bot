<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
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
        $normalized = preg_replace('/[^0-9]/', '', $botNumber);

        $restaurant = Restaurant::where('whatsapp_number', $normalized)
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

        if (!$restaurant) {
            Log::info("Bot lookup: no restaurant for number {$normalized}");
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

    /**
     * GET /api/restaurant-by-phone/{phone}
     *
     * Backwards-compatible lookup by wa_phone_id (used by the Meta webhook path).
     */
    public function getByPhone(string $phone)
    {
        $restaurant = Restaurant::where('wa_phone_id', $phone)
            ->where('is_active', true)
            ->with(['menuItems', 'categories'])
            ->first();

        if (!$restaurant) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        return response()->json($restaurant);
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
            'owner_password'  => 'required|string|min:4',
            'city'            => 'nullable|string|max:100',
        ]);

        $restaurant = Restaurant::create([
            ...$request->only(['name', 'whatsapp_number', 'owner_phone', 'city', 'address']),
            'owner_password' => bcrypt($request->owner_password),
            'plan'           => 'trial',
            'is_active'      => true,
            'is_open'        => true,
        ]);

        // Automatically log owner into dashboard session
        session(["restaurant_{$restaurant->id}" => true]);

        return redirect()
            ->route('dashboard.connect-whatsapp', ['id' => $restaurant->id])
            ->with('success', '🎉 Restaurant registered! Please scan the QR code below to connect your WhatsApp bot.');
    }
}
