<?php

use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// THIS FILE IS NOT REGISTERED. `bootstrap/app.php` routes only `web:`,
// `commands:` and `health:`, so nothing below is reachable (finding H-05).
//
// It is kept as the starting point for the eventual SaaS API. Before adding
// `api:` to bootstrap/app.php, every route here needs:
//   · token (or Sanctum) auth — `/orders/create` currently accepts an order for
//     ANY restaurant_id from ANY caller, and sets its own totals;
//   · per-object authorization — `/orders/phone/{phone}` and
//     `/restaurant/{id}/orders` dump another tenant's customer PII by id alone;
//   · input validation, including the status enum on the PATCH route.
//
// The Meta WhatsApp Cloud API routes that used to sit here (`GET|POST /webhook`,
// `POST /bot`, `POST /admin/notify-order`) are gone along with their controllers:
// the product runs on the whatsapp-web.js bot, and that stack had been dead code
// with a hardcoded fake menu and a schema that no longer matches the database.
//
// The bot no longer calls anything here either. Its former HTTP fallbacks
// (`/orders/create`, `/orders/track/{code}`, `/restaurant-by-bot/{number}`) went
// to this unregistered file, so they could only ever 404 — one of them reported
// a tracking code for an order that had not been saved. The bot now talks to
// MySQL directly and fails loudly instead.
// ─────────────────────────────────────────────────────────────────────────────

// ─── Restaurant Lookup ────────────────────────────────────────────────────────
Route::get('/restaurant-by-bot/{botNumber}', [RestaurantController::class, 'getByBotNumber']);

// ─── Order Management ─────────────────────────────────────────────────────────
Route::post('/orders/create',                      [OrderController::class, 'create']);
Route::get('/orders/phone/{phone}',                [OrderController::class, 'getByPhone']);
Route::get('/orders/track/{trackingCode}',         [OrderController::class, 'track']);
Route::get('/restaurant/{restaurantId}/orders',    [OrderController::class, 'getRestaurantOrders']);
Route::patch('/orders/{orderId}/status',           [OrderController::class, 'updateStatus']);

// ─── Deals ────────────────────────────────────────────────────────────────────
// Returns only deals currently valid (day + time filtered) for a restaurant
Route::get('/restaurant/{restaurantId}/deals/active', function ($restaurantId) {
    $restaurant = \App\Models\Restaurant::findOrFail($restaurantId);
    return response()->json($restaurant->activeDeals()->get());
});
