<?php

use App\Http\Controllers\Api\MobileAuthController;
use Illuminate\Support\Facades\Route;

// ── Mobile API v1 Routes ──────────────────────────────────────────
Route::prefix('v1')->group(function () {
    // Public Auth, Store Info & Dine-In Ordering
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/public/restaurant', [\App\Http\Controllers\Api\DineInPublicController::class, 'restaurantInfo']);
    Route::get('/dine-in/menu', [\App\Http\Controllers\Api\DineInPublicController::class, 'menu']);
    Route::post('/dine-in/orders', [\App\Http\Controllers\Api\DineInPublicController::class, 'placeOrder']);

    // Authenticated Mobile Routes
    Route::middleware(\App\Http\Middleware\AuthenticateRestaurantToken::class)->group(function () {
        Route::get('/auth/me',     [MobileAuthController::class, 'me']);
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

        // Home Command Center
        Route::get('/dashboard/command-center', [\App\Http\Controllers\Api\MobileDashboardController::class, 'commandCenter']);
        Route::post('/dashboard/toggle-open',   [\App\Http\Controllers\Api\MobileDashboardController::class, 'toggleOpen']);

        // Orders
        Route::get('/orders',                       [\App\Http\Controllers\Api\MobileOrderController::class, 'index']);
        Route::get('/orders/{order}',                [\App\Http\Controllers\Api\MobileOrderController::class, 'show']);
        Route::patch('/orders/{order}/status',       [\App\Http\Controllers\Api\MobileOrderController::class, 'updateStatus']);

        // POS & Owner Counter Billing
        Route::get('/pos/catalog',                  [\App\Http\Controllers\Api\MobilePosController::class, 'catalog']);
        Route::get('/pos/summary',                  [\App\Http\Controllers\Api\MobilePosController::class, 'summary']);
        Route::post('/pos/orders',                   [\App\Http\Controllers\Api\MobilePosController::class, 'createOrder']);
        Route::post('/pos/orders/{order}/void',      [\App\Http\Controllers\Api\MobilePosController::class, 'voidOrder']);

        // Menu Management
        Route::get('/menu',                          [\App\Http\Controllers\Api\MobileMenuController::class, 'index']);
        Route::post('/menu/items',                   [\App\Http\Controllers\Api\MobileMenuController::class, 'storeItem']);
        Route::post('/menu/categories',              [\App\Http\Controllers\Api\MobileMenuController::class, 'storeCategory']);
        Route::post('/menu/items/{item}/toggle',     [\App\Http\Controllers\Api\MobileMenuController::class, 'toggleItem']);
        Route::patch('/menu/items/{item}',           [\App\Http\Controllers\Api\MobileMenuController::class, 'updateItem']);
        Route::delete('/menu/items/{item}',          [\App\Http\Controllers\Api\MobileMenuController::class, 'deleteItem']);
        Route::post('/menu/upload',                  [\App\Http\Controllers\Api\MobileMenuController::class, 'uploadMenuFile']);

        // Customers
        Route::get('/customers',                    [\App\Http\Controllers\Api\MobileCustomerController::class, 'index']);
        Route::get('/customers/{customer}',          [\App\Http\Controllers\Api\MobileCustomerController::class, 'show']);
        Route::post('/customers/broadcast',         [\App\Http\Controllers\Api\MobileCustomerController::class, 'broadcast']);

        // Delivery & Rider Fleet
        Route::get('/delivery',                        [\App\Http\Controllers\Api\MobileDeliveryController::class, 'index']);
        Route::post('/delivery/orders/{order}/assign', [\App\Http\Controllers\Api\MobileDeliveryController::class, 'assignRider']);
        Route::post('/delivery/riders',                [\App\Http\Controllers\Api\MobileDeliveryController::class, 'storeRider']);
        Route::delete('/delivery/riders/{rider}',      [\App\Http\Controllers\Api\MobileDeliveryController::class, 'deleteRider']);

        // Reports
        Route::get('/reports',                      [\App\Http\Controllers\Api\MobileReportsController::class, 'index']);

        // Settings
        Route::get('/settings',                     [\App\Http\Controllers\Api\MobileSettingsController::class, 'show']);
        Route::patch('/settings',                   [\App\Http\Controllers\Api\MobileSettingsController::class, 'update']);

        // Cash & Close End of Day
        Route::get('/closing/summary',              [\App\Http\Controllers\Api\MobileDailyClosingController::class, 'summary']);
        Route::get('/closing/archive',              [\App\Http\Controllers\Api\MobileDailyClosingController::class, 'downloadArchive']);

        // Owner Profile & WhatsApp Bot Connectivity
        Route::get('/profile',                      [\App\Http\Controllers\Api\MobileProfileController::class, 'show']);
        Route::get('/profile/bot-qr',               [\App\Http\Controllers\Api\MobileProfileController::class, 'botQr']);
        Route::post('/profile/bot-restart',         [\App\Http\Controllers\Api\MobileProfileController::class, 'botRestart']);
        Route::get('/profile/conversations',        [\App\Http\Controllers\Api\MobileProfileController::class, 'conversations']);
        Route::post('/profile/conversations/{conv}/pause', [\App\Http\Controllers\Api\MobileProfileController::class, 'toggleConversationPause']);
        Route::post('/profile/password',            [\App\Http\Controllers\Api\MobileProfileController::class, 'updatePassword']);
    });
});
