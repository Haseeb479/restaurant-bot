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
// Throttled: tracking codes are the only secret protecting order details, so
// this endpoint must not be usable to enumerate them. The page itself is
// redacted — see Order::getMaskedDeliveryAddressAttribute().
Route::get('track/{code?}', function (?string $code = null) {
    $orderCode = $code ?? request('code');
    $order = null;
    if ($orderCode) {
        $order = \App\Models\Order::where('tracking_code', strtoupper(trim($orderCode)))
            ->with(['restaurant', 'items'])
            ->first();
    }

    // The tracking code sits in the URL, so a shared or proxy cache holding this
    // page would serve one customer's order to the next visitor.
    return response()
        ->view('tracking.live', compact('order'))
        ->header('Cache-Control', 'no-store, no-cache, private, max-age=0');
})->middleware('throttle:20,1')->name('order.track.live');

// Status-only endpoint the tracking page polls. It used to poll
// `/api/orders/track/{code}`, which returns the full order *and* lives in
// routes/api.php — a file that is not registered, so live updates never worked.
// This returns just the status, keeping PII out of a response that is fetched
// every few seconds.
Route::get('track/{code}/status', function (string $code) {
    $order = \App\Models\Order::where('tracking_code', strtoupper(trim($code)))->first();

    if (! $order) {
        return response()->json(['error' => 'not_found'], 404);
    }

    return response()->json([
        'status'         => $order->status,
        'status_label'   => $order->status_label,
        'status_message' => $order->status_message,
    ])->header('Cache-Control', 'no-store');
})->middleware('throttle:60,1')->name('order.track.status');

// ── Restaurant self-service onboarding ───────────────────────
Route::get('restaurant/register', [RestaurantController::class, 'showRegistrationForm'])
    ->name('restaurant.register');
Route::post('restaurant/register', [RestaurantController::class, 'register'])
    ->middleware('throttle:5,10');

// ── Restaurant owner dashboard ─────────────────────────────
Route::prefix('dashboard/{id}')->group(function () {
    Route::get('login',                        [DashboardController::class, 'loginForm'])->name('dashboard.login');
    Route::post('login',                       [DashboardController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout',                      [DashboardController::class, 'logout'])->name('dashboard.logout');
    Route::get('connect-whatsapp',             [DashboardController::class, 'connectWhatsapp'])->name('dashboard.connect-whatsapp');
    // Same-origin proxy to the bot's control server. The connect page polls these
    // instead of talking to port 3000 from the browser, which is what lets that
    // server bind to loopback only. See App\Support\BotControlClient.
    Route::get('bot/status',                   [DashboardController::class, 'botStatus'])->middleware('throttle:60,1')->name('dashboard.bot-status');
    Route::post('bot/restart',                 [DashboardController::class, 'botRestart'])->middleware('throttle:6,1')->name('dashboard.bot-restart');
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
    Route::get('riders',                       [DashboardController::class, 'riders'])->name('dashboard.riders');
    Route::post('riders',                      [DashboardController::class, 'storeRider'])->name('dashboard.store-rider');
    Route::delete('riders/{rider}',            [DashboardController::class, 'deleteRider'])->name('dashboard.delete-rider');
    Route::get('history',                      [DashboardController::class, 'history'])->name('dashboard.history');
    Route::get('customers',                    [DashboardController::class, 'customers'])->name('dashboard.customers');
    Route::post('customers/broadcast',         [DashboardController::class, 'broadcastDeal'])->name('dashboard.broadcast-deal');
    Route::get('customers/export-csv',         [DashboardController::class, 'exportCustomersCsv'])->name('dashboard.export-customers-csv');
    Route::get('reports',                      [DashboardController::class, 'reports'])->name('dashboard.reports');
    Route::get('reports/export-csv',           [DashboardController::class, 'exportSalesReportCsv'])->name('dashboard.export-sales-report-csv');
    Route::get('settings',                     [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('settings',                    [DashboardController::class, 'updateSettings'])->name('dashboard.update-settings');
});

// ── Super admin panel ──────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('login',                        [AdminController::class, 'loginForm'])->name('admin.login');
    Route::post('login',                       [AdminController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout',                      [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/',                            [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('restaurants',                  [AdminController::class, 'restaurants'])->name('admin.restaurants');
    Route::get('restaurant/create',            [AdminController::class, 'createRestaurant'])->name('admin.create-restaurant');
    Route::post('restaurant',                  [AdminController::class, 'storeRestaurant'])->name('admin.store-restaurant');
    Route::post('restaurant/{r}/toggle',       [AdminController::class, 'toggleRestaurant'])->name('admin.toggle-restaurant');
    Route::post('restaurant/{r}/plan',         [AdminController::class, 'extendPlan'])->name('admin.extend-plan');
    Route::post('restaurant/{r}/clear-error',  [AdminController::class, 'clearError'])->name('admin.clear-error');
    Route::get('analytics',                    [AdminController::class, 'analytics'])->name('admin.analytics');
    Route::get('system-health',                [AdminController::class, 'systemHealth'])->name('admin.system-health');
    Route::get('orders',                       [AdminController::class, 'allOrders'])->name('admin.orders');
    Route::get('users',                        [AdminController::class, 'users'])->name('admin.users');
    Route::get('logs',                         [AdminController::class, 'logs'])->name('admin.logs');
    Route::get('settings',                     [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('settings',                    [AdminController::class, 'updateSettings'])->name('admin.update-settings');
});