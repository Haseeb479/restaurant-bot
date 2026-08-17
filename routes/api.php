<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// ─── Webhook (WhatsApp Cloud API) ────────────────────────────────────────────
Route::get('/webhook',  [WebhookController::class, 'verify'])->name('webhook.verify');
Route::post('/webhook', [WebhookController::class, 'handle'])->name('webhook.handle');

// ─── Bot API ──────────────────────────────────────────────────────────────────
Route::post('/bot', [BotController::class, 'handle']);

// ─── Restaurant Lookup ────────────────────────────────────────────────────────
// NEW: Bot identifies restaurant by its OWN WhatsApp number (msg.to)
// Each restaurant registers their WhatsApp number — bot uses this to load correct menu
Route::get('/restaurant-by-bot/{botNumber}', [RestaurantController::class, 'getByBotNumber']);

// KEPT: backwards compatible
Route::get('/restaurant-by-phone/{phone}', [RestaurantController::class, 'getByPhone']);

// ─── Order Management ─────────────────────────────────────────────────────────
Route::post('/orders/create',                      [OrderController::class, 'create']);
Route::get('/orders/phone/{phone}',                [OrderController::class, 'getByPhone']);
Route::get('/orders/track/{trackingCode}',         [OrderController::class, 'track']);        // now uses tracking_code
Route::get('/restaurant/{restaurantId}/orders',    [OrderController::class, 'getRestaurantOrders']);
Route::patch('/orders/{orderId}/status',           [OrderController::class, 'updateStatus']);

// ─── Admin Notification ───────────────────────────────────────────────────────
Route::post('/admin/notify-order', [BotController::class, 'notifyAdminOrder']);

// ─── Deals ────────────────────────────────────────────────────────────────────
// Returns only deals currently valid (day + time filtered) for a restaurant
Route::get('/restaurant/{restaurantId}/deals/active', function ($restaurantId) {
    $restaurant = \App\Models\Restaurant::findOrFail($restaurantId);
    return response()->json($restaurant->activeDeals()->get());
});

// ─── Live Bot QR & Connection Status ──────────────────────────────────────────
Route::get('/bot/qr-status', function () {
    try {
        $internalUrl = config('app.bot_internal_api', env('BOT_INTERNAL_API', 'http://127.0.0.1:3000')) . '/qr-status';
        $res = \Illuminate\Support\Facades\Http::timeout(1)->get($internalUrl);
        if ($res->ok()) {
            return response()->json($res->json());
        }
    } catch (\Exception $e) {
        // Bot is not currently running
    }
    return response()->json([
        'success'    => false,
        'status'     => 'offline',
        'message'    => 'Bot process is not currently running. Please launch "node bot/index.js"',
        'qr'         => null,
        'bot_number' => null,
    ]);
});