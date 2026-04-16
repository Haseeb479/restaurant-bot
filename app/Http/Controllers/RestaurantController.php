<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function showRegistrationForm()
    {
        return view('restaurant.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'whatsapp_number' => 'required|string|unique:restaurants',
            'owner_phone'     => 'required|string',
            'owner_password'  => 'required|string|min:4',
            'city'            => 'nullable|string|max:255',
            'address'         => 'nullable|string|max:255',
        ]);

        $restaurant = Restaurant::create([
            'name'             => $request->input('name'),
            'whatsapp_number'  => $request->input('whatsapp_number'),
            'owner_phone'      => $request->input('owner_phone'),
            'owner_password'   => bcrypt($request->input('owner_password')),
            'city'             => $request->input('city'),
            'address'          => $request->input('address'),
            'plan'             => 'trial',
            'plan_expires_at'  => null,
            'is_active'        => true,
            'is_open'          => true,
            'delivery_charge'  => 0,
            'minimum_order'    => 0,
            'greeting_message' => 'Welcome! How can I help you today?',
        ]);

        return redirect()->route('restaurant.register')
            ->with('success', "Restaurant '{$restaurant->name}' registered! Login at /dashboard/{$restaurant->id}/login");
    }

    // ─── API: Get restaurant by BOT WhatsApp number (msg.to from bot) ────────
    public function getByBotNumber($botNumber)
    {
        $normalized = preg_replace('/[^0-9]/', '', $botNumber);
        
        // Take the last 10 digits as the core number to ignore variations like 92, +92, or 0.
        $coreNumber = substr($normalized, -10);

        $restaurant = Restaurant::where('whatsapp_number', 'like', '%' . $coreNumber)
            ->where('is_active', true)
            ->with([
                'categories' => fn($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'menuItems'  => fn($q) => $q->where('is_available', true)->orderBy('sort_order'),
            ])
            ->first();

        if (!$restaurant) {
            return response()->json([
                'error'  => 'Restaurant not found for this number',
                'number' => $normalized
            ], 404);
        }

        if (!$restaurant->is_open) {
            return response()->json([
                'error'   => 'Restaurant is currently closed',
                'is_open' => false,
                'name'    => $restaurant->name,
            ], 200);
        }

        return response()->json([
            'id'               => $restaurant->id,
            'name'             => $restaurant->name,
            'owner_phone'      => $restaurant->owner_phone,
            'whatsapp_number'  => $restaurant->whatsapp_number,
            'address'          => $restaurant->address,
            'city'             => $restaurant->city,
            'delivery_charge'  => $restaurant->delivery_charge,
            'minimum_order'    => $restaurant->minimum_order,
            'is_open'          => $restaurant->is_open,
            'greeting_message' => $restaurant->greeting_message,
            'hours'            => $restaurant->hours ?? '10 AM - 11 PM',
            'menu_items'       => $restaurant->menuItems->map(fn($item) => [
                'id'          => $item->id,
                'name'        => $item->name,
                'description' => $item->description,
                'price'       => $item->price,
                'sizes'       => $item->sizes,
            ]),
        ]);
    }

    // ─── API: Get restaurant by owner phone (backwards compat) ───────────────
    public function getByPhone($phone)
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        $restaurant = Restaurant::where('owner_phone', $normalized)
            ->orWhere('whatsapp_number', $normalized)
            ->with(['menuItems' => fn($q) => $q->where('is_available', true)])
            ->first();

        if (!$restaurant) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        return response()->json([
            'id'              => $restaurant->id,
            'name'            => $restaurant->name,
            'owner_phone'     => $restaurant->owner_phone,
            'whatsapp_number' => $restaurant->whatsapp_number,
            'address'         => $restaurant->address,
            'delivery_charge' => $restaurant->delivery_charge,
            'minimum_order'   => $restaurant->minimum_order,
            'is_open'         => $restaurant->is_open,
            'menu_items'      => $restaurant->menuItems->map(fn($item) => [
                'id'          => $item->id,
                'name'        => $item->name,
                'description' => $item->description,
                'price'       => $item->price,
                'sizes'       => $item->sizes,
            ]),
        ]);
    }
}