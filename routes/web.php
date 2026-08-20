<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

// ── Home redirect ──────────────────────────────────────────
Route::get('/', function () {
    return redirect('/admin/login');
});

// ── Live Order Tracking (Public Web Portal - Free) ─────────
Route::get('track/{code?}', function (?string $code = null) {
    $orderCode = $code ?? request('code');
    $order = null;
    if ($orderCode) {
        $order = \App\Models\Order::where('tracking_code', strtoupper(trim($orderCode)))
            ->with(['restaurant', 'items'])
            ->first();
    }
    return view('tracking.live', compact('order'));
})->name('order.track.live');

// ── Restaurant self-service onboarding ───────────────────────
Route::get('restaurant/register', [RestaurantController::class, 'showRegistrationForm'])
    ->name('restaurant.register');
Route::post('restaurant/register', [RestaurantController::class, 'register']);

// ── Restaurant owner dashboard ─────────────────────────────
Route::prefix('dashboard/{id}')->group(function () {
    Route::get('login',                        [DashboardController::class, 'loginForm'])->name('dashboard.login');
    Route::post('login',                       [DashboardController::class, 'login']);
    Route::post('logout',                      [DashboardController::class, 'logout'])->name('dashboard.logout');
    Route::get('connect-whatsapp',             [DashboardController::class, 'connectWhatsapp'])->name('dashboard.connect-whatsapp');
    Route::get('orders',                       [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('orders/live-feed',             [DashboardController::class, 'liveOrdersFeed'])->name('dashboard.orders.live-feed');
    Route::post('orders/{order}/status',       [DashboardController::class, 'updateStatus'])->name('dashboard.update-status');
    Route::get('menu',                         [DashboardController::class, 'menu'])->name('dashboard.menu');
    Route::post('menu/category',               [DashboardController::class, 'storeCategory'])->name('dashboard.store-category');
    Route::post('menu/item',                   [DashboardController::class, 'storeItem'])->name('dashboard.store-item');
    Route::post('menu/upload-csv',             [DashboardController::class, 'uploadMenuCsv'])->name('dashboard.upload-menu-csv');
    Route::post('menu/upload-file',            [DashboardController::class, 'uploadMenuFile'])->name('dashboard.upload-menu-file');
    Route::post('menu/upload-image',           [DashboardController::class, 'uploadMenuFile'])->name('dashboard.upload-menu-image');
    Route::get('menu/sample-csv',              [DashboardController::class, 'downloadSampleCsv'])->name('dashboard.sample-menu-csv');
    Route::post('menu/item/{item}/toggle',     [DashboardController::class, 'toggleItem'])->name('dashboard.toggle-item');
    Route::delete('menu/item/{item}',          [DashboardController::class, 'deleteItem'])->name('dashboard.delete-item');
    Route::get('settings',                     [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('settings',                    [DashboardController::class, 'updateSettings'])->name('dashboard.update-settings');
});

// ── Super admin panel ──────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('login',                        [AdminController::class, 'loginForm'])->name('admin.login');
    Route::post('login',                       [AdminController::class, 'login']);
    Route::post('logout',                      [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/',                            [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('restaurant/create',            [AdminController::class, 'createRestaurant'])->name('admin.create-restaurant');
    Route::post('restaurant',                  [AdminController::class, 'storeRestaurant'])->name('admin.store-restaurant');
    Route::post('restaurant/{r}/toggle',       [AdminController::class, 'toggleRestaurant'])->name('admin.toggle-restaurant');
    Route::post('restaurant/{r}/plan',         [AdminController::class, 'extendPlan'])->name('admin.extend-plan');
    Route::post('restaurant/{r}/clear-error',  [AdminController::class, 'clearError'])->name('admin.clear-error');
    Route::get('orders',                       [AdminController::class, 'allOrders'])->name('admin.orders');
});