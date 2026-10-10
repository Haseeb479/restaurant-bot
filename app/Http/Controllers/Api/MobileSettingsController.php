<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MobileSettingsController extends Controller
{
    /**
     * Get restaurant settings and WhatsApp bot status.
     */
    public function show(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        return response()->json([
            'success'  => true,
            'settings' => [
                'name'               => $restaurant->name,
                'email'              => $restaurant->email,
                'owner_phone'        => $restaurant->owner_phone,
                'whatsapp_number'    => $restaurant->whatsapp_number,
                'city'               => $restaurant->city,
                'address'            => $restaurant->address,
                'is_open'            => (bool) $restaurant->is_open,
                'delivery_charge'    => (float) $restaurant->delivery_charge,
                'minimum_order'      => (float) $restaurant->minimum_order,
                'delivery_radius_km' => (float) ($restaurant->delivery_radius_km ?? 5.0),
                'bot_status'         => $restaurant->bot_status ?? 'disconnected',
            ],
        ]);
    }

    /**
     * Update restaurant settings safely (no secret leaks).
     */
    public function update(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $validated = $request->validate([
            'address'            => 'sometimes|string|max:255',
            'city'               => 'sometimes|string|max:100',
            'delivery_charge'    => 'sometimes|numeric|min:0',
            'minimum_order'      => 'sometimes|numeric|min:0',
            'delivery_radius_km' => 'sometimes|numeric|min:0.5|max:50',
            'is_open'            => 'sometimes|boolean',
        ]);

        $restaurant->update($validated);

        return response()->json([
            'success'  => true,
            'message'  => 'Settings updated successfully.',
            'settings' => [
                'name'               => $restaurant->name,
                'email'              => $restaurant->email,
                'owner_phone'        => $restaurant->owner_phone,
                'whatsapp_number'    => $restaurant->whatsapp_number,
                'city'               => $restaurant->city,
                'address'            => $restaurant->address,
                'is_open'            => (bool) $restaurant->is_open,
                'delivery_charge'    => (float) $restaurant->delivery_charge,
                'minimum_order'      => (float) $restaurant->minimum_order,
                'delivery_radius_km' => (float) ($restaurant->delivery_radius_km ?? 5.0),
                'bot_status'         => $restaurant->bot_status ?? 'disconnected',
            ],
        ]);
    }
}
