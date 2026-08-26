<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

// ── Home — Foodio Modern SaaS Promotional Landing Page ────────
Route::get('/', function () {
    return view('landing');
})->name('landing');

// ── Dedicated Owner Sign In Page ──────────────────────────────
Route::get('/login', function () {
    return view('auth.login');
})->name('landing.owner-login-page');

Route::get('/owner/login', function () {
    return redirect()->route('landing.owner-login-page');
});

// ── Owner Authentication Handler (searches by restaurant name, email, or phone) ──
$ownerLoginHandler = function (\Illuminate\Http\Request $req) {
    $req->validate([
        'restaurant_name' => 'required|string|max:255',
        'password'        => 'required|string',
    ]);

    $input  = trim((string) $req->restaurant_name);
    $digits = preg_replace('/[^0-9]/', '', $input);

    // Flexible multi-field lookup:
    // 1. Exact or partial restaurant name
    // 2. Exact email
    // 3. Exact or partial WhatsApp / phone number
    $r = \App\Models\Restaurant::where(function($q) use ($input, $digits) {
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
            WHEN is_active = 1 AND LOWER(name) LIKE ? THEN 2
            WHEN is_active = 1 THEN 3
            ELSE 4 END", 
            [strtolower($input), strtolower($input) . '%']
        )
        ->orderByRaw('LENGTH(name) ASC')
        ->first();

    if (!$r || !\App\Http\Controllers\DashboardController::passwordMatches(
        (string) $req->password,
        (string) $r->owner_password
    )) {
        return back()
            ->withInput($req->only('restaurant_name'))
            ->withErrors(['password' => 'Wrong restaurant name or password. Please check and try again.'], 'owner');
    }

    if ($r->status === 'pending' || ($r->status !== 'active' && in_array($r->registration_status, ['pending_review', 'pending_plan', 'pending_payment']))) {
        return redirect()->route('onboarding.status', $r->id);
    }

    if ($r->status === 'rejected') {
        return back()
            ->withInput($req->only('restaurant_name'))
            ->withErrors(['password' => 'Your application was rejected. Reason: ' . ($r->rejection_reason ?: 'Contact support.')], 'owner');
    }

    if (!$r->is_active) {
        return back()
            ->withInput($req->only('restaurant_name'))
            ->withErrors(['password' => 'This restaurant account has been deactivated. Contact support.'], 'owner');
    }

    if ($r->status === 'active' && $r->registration_status !== 'approved') {
        $r->registration_status = 'approved';
        $r->save();
    }

    // Upgrade plaintext password to bcrypt hash on first login
    if (!\App\Http\Controllers\DashboardController::isHashed((string) $r->owner_password)) {
        $r->owner_password = \Illuminate\Support\Facades\Hash::make(trim($req->password));
        $r->save();
    }

    $req->session()->regenerate();
    $req->session()->put("restaurant_{$r->id}", true);
    $req->session()->put("restaurant_{$r->id}_login_time", now()->toIso8601String());

    return redirect()->route('dashboard.orders', $r->id);
};

Route::post('/login',       $ownerLoginHandler)->middleware('throttle:5,1');
Route::post('/login/owner', $ownerLoginHandler)->middleware('throttle:5,1')->name('landing.owner-login');

// ── Forgot Password Flow ────────────────────────────────────────
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('owner.forgot-password');

Route::post('/forgot-password', function (\Illuminate\Http\Request $req) {
    $req->validate([
        'restaurant_name' => 'required|string|max:255',
        'email'           => 'required|email|max:255',
        'phone'           => 'required|string|max:20',
    ]);

    // Look up the restaurant to give a vague but honest response
    // (always return success to prevent enumeration attacks)
    $r = \App\Models\Restaurant::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($req->restaurant_name)) . '%'])
        ->where('email', trim($req->email))
        ->first();

    if ($r) {
        // Log a reset request in audit log for admin action
        try {
            \App\Models\AuditLog::log(
                'owner.password_reset_request',
                "Password reset requested for restaurant: {$r->name} (ID: {$r->id}). Contact: {$req->phone}"
            );
        } catch (\Throwable $e) {}
    }

    // Always show success — do not reveal whether the restaurant/email exists
    return back()->with('reset_sent', true);
})->middleware('throttle:3,5')->name('owner.forgot-password.submit');

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
        'has_live_gps'   => $order->hasLiveGps(),
        'rider_lat'      => $order->hasLiveGps() ? (float) $order->rider_lat : null,
        'rider_lng'      => $order->hasLiveGps() ? (float) $order->rider_lng : null,
        'rider_updated'  => $order->rider_location_updated_at?->diffForHumans(),
    ])->header('Cache-Control', 'no-store');
})->middleware('throttle:60,1')->name('order.track.status');

// ── Rider Live GPS Delivery Portal ─────────────────────────
Route::prefix('rider/deliver/{token}')->group(function () {
    Route::get('/',              [\App\Http\Controllers\RiderPortalController::class, 'show'])->name('rider.deliver.show');
    Route::post('location',      [\App\Http\Controllers\RiderPortalController::class, 'updateLocation'])->middleware('throttle:120,1')->name('rider.deliver.location');
    Route::post('complete',      [\App\Http\Controllers\RiderPortalController::class, 'completeDelivery'])->name('rider.deliver.complete');
});

// ── SaaS Multi-Step Restaurant Onboarding & Payment Flow ───
Route::get('register',               [OnboardingController::class, 'step1Form'])->name('onboarding.signup');
Route::get('get-started',            [OnboardingController::class, 'step1Form'])->name('onboarding.get-started');
Route::get('restaurant/register',    [OnboardingController::class, 'step1Form'])->name('restaurant.register');
Route::post('register',              [OnboardingController::class, 'step1Submit'])->middleware('throttle:10,1')->name('onboarding.signup.submit');
Route::post('restaurant/register',   [OnboardingController::class, 'step1Submit'])->middleware('throttle:10,1');

Route::get('register/plan/{id}',     [OnboardingController::class, 'step2PlanForm'])->name('onboarding.plan');
Route::post('register/plan/{id}',    [OnboardingController::class, 'step2PlanSubmit'])->name('onboarding.plan.submit');

Route::get('register/payment/{id}',  [OnboardingController::class, 'step3PaymentForm'])->name('onboarding.payment');
Route::post('register/payment/{id}', [OnboardingController::class, 'step3PaymentSubmit'])->name('onboarding.payment.submit');

Route::get('register/status/{id}',   [OnboardingController::class, 'statusPage'])->name('onboarding.status');

// ── EvolutionAPI Webhook Receiver (Incoming WhatsApp Messages & Status Events) ──
Route::post('webhook/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])
    ->name('webhook.whatsapp');

// ── Restaurant owner dashboard ─────────────────────────────
Route::prefix('dashboard/{id}')->group(function () {
    Route::get('login',                        [DashboardController::class, 'loginForm'])->name('dashboard.login');
    Route::post('login',                       [DashboardController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout',                      [DashboardController::class, 'logout'])->name('dashboard.logout');
    Route::get('connect-whatsapp',             [DashboardController::class, 'connectWhatsapp'])->name('dashboard.connect-whatsapp');
    Route::get('bot/status',                   [DashboardController::class, 'botStatus'])->middleware('throttle:60,1')->name('dashboard.bot-status');
    Route::get('bot/qr',                       [DashboardController::class, 'botQrCode'])->middleware('throttle:60,1')->name('dashboard.bot-qr');
    Route::post('bot/pairing-code',            [DashboardController::class, 'botPairingCode'])->middleware('throttle:10,1')->name('dashboard.bot-pairing-code');
    Route::post('bot/restart',                 [DashboardController::class, 'botRestart'])->middleware('throttle:6,1')->name('dashboard.bot-restart');
    Route::get('orders',                       [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('orders/live-feed',             [DashboardController::class, 'liveOrdersFeed'])->name('dashboard.orders.live-feed');
    Route::get('orders/{order}/print-bill',     [DashboardController::class, 'printBill'])->name('dashboard.print-bill');
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
    // Force-logout clears any stale admin session, then redirects to the login form.
    // The "Superadmin" button on the landing page links here so no one auto-enters.
    Route::get('force-logout', function () {
        request()->session()->forget(['admin_logged_in', 'admin_logged_in_at']);
        request()->session()->regenerate();
        return redirect()->route('admin.login')->with('info', 'Please enter your credentials to continue.');
    })->name('admin.force-logout');

    Route::get('login',                                [AdminController::class, 'loginForm'])->name('admin.login');
    Route::post('login',                               [AdminController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout',                              [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/',                                    [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // 1. Restaurant Management
    Route::get('restaurants',                          [AdminController::class, 'restaurants'])->name('admin.restaurants');
    Route::get('restaurants/pending',                  [AdminController::class, 'pendingRestaurants'])->name('admin.restaurants.pending');
    Route::post('restaurant/{r}/approve',              [AdminController::class, 'approveRestaurant'])->name('admin.restaurant.approve');
    Route::post('restaurant/{r}/reject',               [AdminController::class, 'rejectRestaurant'])->name('admin.restaurant.reject');
    Route::get('restaurant/create',                    [AdminController::class, 'createRestaurant'])->name('admin.create-restaurant');
    Route::post('restaurant',                          [AdminController::class, 'storeRestaurant'])->name('admin.store-restaurant');
    Route::get('restaurant/{r}/edit',                  [AdminController::class, 'editRestaurant'])->name('admin.restaurant.edit');
    Route::post('restaurant/{r}/update',                [AdminController::class, 'updateRestaurant'])->name('admin.restaurant.update');
    Route::post('restaurant/{r}/reset-password',       [AdminController::class, 'resetRestaurantPassword'])->name('admin.restaurant.reset-password');
    Route::post('restaurant/{r}/reset-bot',            [AdminController::class, 'resetRestaurantBot'])->name('admin.restaurant.reset-bot');
    Route::post('restaurant/{r}/toggle',               [AdminController::class, 'toggleRestaurant'])->name('admin.toggle-restaurant');
    Route::post('restaurant/{r}/plan',                 [AdminController::class, 'extendPlan'])->name('admin.extend-plan');
    Route::delete('restaurant/{r}',                    [AdminController::class, 'deleteRestaurant'])->name('admin.restaurant.delete');
    Route::get('restaurant/{r}/analytics',             [AdminController::class, 'restaurantAnalytics'])->name('admin.restaurant.analytics');

    // 2. Bot Features & Settings
    Route::get('bot-settings',                         [AdminController::class, 'botSettings'])->name('admin.bot-settings');
    Route::post('bot-settings',                        [AdminController::class, 'updateBotSettings'])->name('admin.bot-settings.update');
    Route::get('bot-templates',                        [AdminController::class, 'botTemplates'])->name('admin.bot-templates');
    Route::post('bot-templates',                       [AdminController::class, 'updateBotTemplates'])->name('admin.bot-templates.update');
    Route::get('bot-commands',                         [AdminController::class, 'botCommands'])->name('admin.bot-commands');
    Route::post('bot-commands',                        [AdminController::class, 'updateBotCommands'])->name('admin.bot-commands.update');
    Route::get('menu-templates',                       [AdminController::class, 'menuTemplates'])->name('admin.menu-templates');
    Route::post('menu-templates',                      [AdminController::class, 'storeMenuTemplate'])->name('admin.menu-templates.store');
    Route::post('menu-templates/{template}/clone/{r}', [AdminController::class, 'cloneMenuTemplateToRestaurant'])->name('admin.menu-templates.clone');
    Route::delete('menu-templates/{template}',         [AdminController::class, 'deleteMenuTemplate'])->name('admin.menu-templates.delete');

    // 3. Analytics & Custom Reports
    Route::get('analytics',                            [AdminController::class, 'analytics'])->name('admin.analytics');
    Route::get('reports/custom',                       [AdminController::class, 'customReports'])->name('admin.reports.custom');
    Route::get('reports/export-csv',                   [AdminController::class, 'exportReportsCsv'])->name('admin.reports.export-csv');

    // 4. Billing, Plans & Pakistani Payment Gateways
    Route::get('billing',                              [AdminController::class, 'billing'])->name('admin.billing');
    Route::post('billing/plans',                       [AdminController::class, 'storePlan'])->name('admin.billing.plans.store');
    Route::post('billing/plans/{plan}/update',         [AdminController::class, 'updatePlan'])->name('admin.billing.plans.update');
    Route::delete('billing/plans/{plan}',              [AdminController::class, 'deletePlan'])->name('admin.billing.plans.delete');
    Route::post('billing/payment-methods',             [AdminController::class, 'updatePaymentMethods'])->name('admin.billing.payment-methods.update');
    Route::post('billing/invoices',                    [AdminController::class, 'createInvoice'])->name('admin.billing.invoices.store');
    Route::post('billing/invoices/{invoice}/status',   [AdminController::class, 'updateInvoiceStatus'])->name('admin.billing.invoices.status');

    // 5. Support & Moderation
    Route::get('support',                              [AdminController::class, 'supportTickets'])->name('admin.support');
    Route::get('support/{ticket}',                     [AdminController::class, 'viewSupportTicket'])->name('admin.support.detail');
    Route::post('support/{ticket}/reply',              [AdminController::class, 'replySupportTicket'])->name('admin.support.reply');
    Route::post('support/{ticket}/status',             [AdminController::class, 'updateSupportTicketStatus'])->name('admin.support.status');
    Route::get('moderation',                           [AdminController::class, 'moderation'])->name('admin.moderation');
    Route::post('moderation/blacklist',                [AdminController::class, 'addToBlacklist'])->name('admin.moderation.blacklist.store');
    Route::delete('moderation/blacklist/{blacklistedNumber}', [AdminController::class, 'removeFromBlacklist'])->name('admin.moderation.blacklist.delete');
    Route::post('moderation/filter-words',             [AdminController::class, 'updateFilterWords'])->name('admin.moderation.filter-words.update');
    Route::get('announcements',                        [AdminController::class, 'announcements'])->name('admin.announcements');
    Route::post('announcements',                       [AdminController::class, 'storeAnnouncement'])->name('admin.announcements.store');
    Route::delete('announcements/{announcement}',      [AdminController::class, 'deleteAnnouncement'])->name('admin.announcements.delete');
    Route::get('feedback',                             [AdminController::class, 'feedback'])->name('admin.feedback');
    Route::post('feedback/{feedback}/reviewed',        [AdminController::class, 'markFeedbackReviewed'])->name('admin.feedback.reviewed');

    // 6. Advanced Platform Controls & System
    Route::get('system-health',                        [AdminController::class, 'systemHealth'])->name('admin.system-health');
    Route::post('system/optimize',                     [AdminController::class, 'optimizeDatabase'])->name('admin.system.optimize');
    Route::post('system/clean-logs',                   [AdminController::class, 'cleanLogsAndSessions'])->name('admin.system.clean-logs');
    Route::post('system/backup',                       [AdminController::class, 'createBackupDump'])->name('admin.system.backup');
    Route::get('api-keys',                             [AdminController::class, 'apiKeys'])->name('admin.api-keys');
    Route::post('api-keys',                            [AdminController::class, 'generateApiKey'])->name('admin.api-keys.store');
    Route::delete('api-keys/{apiKey}',                 [AdminController::class, 'revokeApiKey'])->name('admin.api-keys.delete');
    Route::get('email-templates',                      [AdminController::class, 'emailTemplates'])->name('admin.email-templates');
    Route::post('email-templates',                     [AdminController::class, 'updateEmailTemplates'])->name('admin.email-templates.update');
    Route::get('policies',                             [AdminController::class, 'policies'])->name('admin.policies');
    Route::post('policies',                            [AdminController::class, 'updatePolicies'])->name('admin.policies.update');
    Route::get('audit-logs',                           [AdminController::class, 'auditLogs'])->name('admin.audit-logs');

    // Core Orders, Users, Logs & Settings
    Route::get('orders',                               [AdminController::class, 'allOrders'])->name('admin.orders');
    Route::get('users',                                [AdminController::class, 'users'])->name('admin.users');
    Route::get('logs',                                 [AdminController::class, 'logs'])->name('admin.logs');
    Route::get('settings',                             [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('settings',                            [AdminController::class, 'updateSettings'])->name('admin.update-settings');
    Route::post('settings/2fa',                        [AdminController::class, 'toggle2FA'])->name('admin.settings.2fa');
});