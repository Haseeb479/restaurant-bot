<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order, Conversation, Setting};
use App\Support\BotControlClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ── SaaS Plan Pricing (Bot Service Platform Packages) ───
    public const PLAN_PRICES = [
        'trial' => 0,
        'basic' => 3000,
        'pro'   => 7000,
    ];

    /** Settings key holding the bcrypt hash of the super-admin master password. */
    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    private function adminAuth(): void
    {
        abort_unless(session('admin_logged_in'), 403, 'Admin access required.');
    }

    public function loginForm()
    {
        return view('admin.login');
    }

    /**
     * Verify a submitted master password.
     *
     * Precedence:
     *   1. Hash stored in the `settings` table (set via Admin → Settings).
     *   2. ADMIN_PASSWORD from the environment — accepted as either a hash or a
     *      plaintext value, and migrated into the hashed store on first use.
     *
     * There is deliberately NO default. If neither source is configured, no
     * password can log in (previously `admin123` was a working fallback).
     */
    private function verifyAdminPassword(string $password): bool
    {
        if ($password === '') {
            return false;
        }

        $storedHash = Setting::get(self::ADMIN_PASSWORD_KEY);

        if ($storedHash !== null && DashboardController::isHashed($storedHash)) {
            return Hash::check($password, $storedHash);
        }

        $envPassword = (string) config('app.admin_password', '');

        if ($envPassword === '') {
            return false; // Not configured — refuse everything.
        }

        if (! DashboardController::passwordMatches($password, $envPassword)) {
            return false;
        }

        // Correct, but only backed by .env — persist it hashed so it can be
        // rotated from the panel and never sits in plaintext at rest.
        try {
            Setting::put(self::ADMIN_PASSWORD_KEY, Hash::make($password));
        } catch (\Throwable $e) {
            // Store unavailable (e.g. pre-migration): login still succeeds.
        }

        return true;
    }

    public function login(Request $request)
    {
        $password = (string) $request->input('password', '');

        if (! $this->verifyAdminPassword($password)) {
            return back()->withErrors([
                'password' => (string) config('app.admin_password', '') === ''
                    && Setting::get(self::ADMIN_PASSWORD_KEY) === null
                        ? 'No admin password is configured. Set ADMIN_PASSWORD in .env first.'
                        : 'Wrong password',
            ]);
        }

        // New session ID on privilege change (prevents session fixation).
        $request->session()->regenerate();
        session(['admin_logged_in' => true]);

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        session()->forget('admin_logged_in');
        return redirect()->route('admin.login');
    }

    // ── 1. Main Dashboard Overview ─────────────────────────
    public function dashboard()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->with(['orders' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->select('id','restaurant_id','total','status')])
            ->orderByDesc('created_at')
            ->get();

        // Top Row Summary Metrics
        $totalRestaurants  = $restaurants->count();
        $activeRestaurants = $restaurants->where('is_active', true)->count();
        $ordersToday       = Order::whereDate('created_at', today())->count();
        $ordersThisMonth   = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $disconnectedBots  = $restaurants->whereIn('bot_status', ['qr_expired', 'disconnected'])->where('is_active', true)->count();
        $botConnected      = $restaurants->where('bot_status', 'connected')->count();

        // Plan Breakdown
        $trialCount = $restaurants->where('plan', 'trial')->where('is_active', true)->count();
        $basicCount = $restaurants->where('plan', 'basic')->where('is_active', true)->count();
        $proCount   = $restaurants->where('plan', 'pro')->where('is_active', true)->count();
        $monthlySaasRevenue = ($basicCount * self::PLAN_PRICES['basic']) + ($proCount * self::PLAN_PRICES['pro']);

        // System Health & Error Logs
        $systemHealth = $restaurants->where('is_active', true);
        $recentErrors = $restaurants->whereNotNull('last_error')->where('is_active', true);

        // Top Restaurants by Activity
        $topRestaurants = $restaurants->sortByDesc('month_orders_count')->take(5)->map(function ($r) {
            $monthRev = $r->orders->where('status', '!=', 'cancelled')->sum('total');
            return [
                'name'    => $r->name,
                'orders'  => $r->month_orders_count,
                'revenue' => $monthRev,
            ];
        });

        // 7-Day Chart Data for Orders Overview.
        // Two real series over the last 7 days: this week vs the same 7 days one
        // month ago (previously the comparison line was faked with rand()).
        $chartLabels = [];
        $chartTodayData = [];
        $chartMonthData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[]    = $date->format('d M');
            $chartTodayData[] = Order::whereDate('created_at', $date)->count();
            $chartMonthData[] = Order::whereDate('created_at', Carbon::today()->subDays($i)->subMonthNoOverflow())->count();
        }

        return view('admin.dashboard', compact(
            'restaurants',
            'totalRestaurants',
            'activeRestaurants',
            'ordersToday',
            'ordersThisMonth',
            'disconnectedBots',
            'botConnected',
            'trialCount',
            'basicCount',
            'proCount',
            'monthlySaasRevenue',
            'systemHealth',
            'recentErrors',
            'topRestaurants',
            'chartLabels',
            'chartTodayData',
            'chartMonthData'
        ));
    }

    // ── 2. Dedicated Restaurants Management Page ───────────
    public function restaurants()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.restaurants', compact('restaurants'));
    }

    // ── 3. Dedicated Platform Analytics Page ───────────────
    public function analytics()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as today_orders' => fn($q) => $q->whereDate('created_at', today())])
            ->withCount(['orders as month_orders' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->get();

        $totalOrdersMonth = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $totalOrdersToday = Order::whereDate('created_at', today())->count();
        $totalConversations = Conversation::count();

        $basicCount = $restaurants->where('plan', 'basic')->where('is_active', true)->count();
        $proCount   = $restaurants->where('plan', 'pro')->where('is_active', true)->count();
        $trialCount = $restaurants->where('plan', 'trial')->where('is_active', true)->count();
        $mrr = ($basicCount * self::PLAN_PRICES['basic']) + ($proCount * self::PLAN_PRICES['pro']);

        // 14-day trend data
        $chartLabels = [];
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $chartData[] = Order::whereDate('created_at', $date)->count();
        }

        return view('admin.analytics', compact(
            'restaurants',
            'totalOrdersMonth',
            'totalOrdersToday',
            'totalConversations',
            'mrr',
            'basicCount',
            'proCount',
            'trialCount',
            'chartLabels',
            'chartData'
        ));
    }

    // ── 4. Dedicated System Health & Bot Monitoring Page ───
    public function systemHealth()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['conversations'])->orderByDesc('updated_at')->get();
        $onlineBots  = $restaurants->where('bot_status', 'connected');
        $issuesBots  = $restaurants->filter(fn($r) => $r->bot_status !== 'connected' || $r->last_error);

        return view('admin.system-health', compact('restaurants', 'onlineBots', 'issuesBots'));
    }

    // ── 5. Dedicated Orders Log Page ───────────────────────
    public function allOrders(Request $request)
    {
        $this->adminAuth();

        $query = Order::with(['restaurant', 'items'])->latest();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function($q) use ($s) {
                $q->where('tracking_code', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->input('restaurant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(30)->withQueryString();
        $restaurants = Restaurant::orderBy('name')->get();

        return view('admin.orders', compact('orders', 'restaurants'));
    }

    // ── 6. Dedicated Users / Restaurant Owners Page ────────
    public function users()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems'])->orderBy('name')->get();

        return view('admin.users', compact('restaurants'));
    }

    // ── 7. Dedicated Logs & Security Audit Page ────────────
    public function logs()
    {
        $this->adminAuth();

        $restaurants = Restaurant::whereNotNull('last_error')->orWhere('bot_status', '!=', 'connected')->get();
        $recentOrders = Order::with('restaurant')->latest()->take(20)->get();

        return view('admin.logs', compact('restaurants', 'recentOrders'));
    }

    // ── 8. Dedicated Super Admin Settings Page ─────────────
    public function settings()
    {
        $this->adminAuth();
        return view('admin.settings');
    }

    /**
     * Change the super-admin master password.
     *
     * Was a no-op stub that reported success without changing anything. Now
     * verifies the current password and persists a bcrypt hash of the new one to
     * the settings store, which login() reads first.
     */
    public function updateSettings(Request $request)
    {
        $this->adminAuth();

        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:12|different:current_password',
        ], [
            'new_password.min' => 'The new master password must be at least 12 characters.',
        ]);

        if (! $this->verifyAdminPassword((string) $request->input('current_password'))) {
            return back()->withErrors(['current_password' => 'Current master password is incorrect.']);
        }

        Setting::put(self::ADMIN_PASSWORD_KEY, Hash::make((string) $request->input('new_password')));

        // Force re-authentication with the new credential.
        session()->forget('admin_logged_in');
        $request->session()->regenerate();

        return redirect()->route('admin.login')
            ->with('success', 'Master password updated. Please log in again with your new password.');
    }

    // ── Create a new restaurant ────────────────────────────
    public function createRestaurant()
    {
        $this->adminAuth();
        return view('admin.create-restaurant');
    }

    public function storeRestaurant(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'name'            => 'required|string|max:255',
            'whatsapp_number' => 'required|string|unique:restaurants',
            'owner_phone'     => 'required|string',
            'owner_password'  => 'required|string|min:6',
            'plan'            => 'required|in:trial,basic,pro',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
            'minimum_order'   => 'nullable|numeric|min:0',
        ]);

        // NOTE: owner_password and admin state attributes (is_active, plan) are
        // intentionally absent from fillable. They are assigned explicitly below.
        $r = new Restaurant($request->only([
            'name', 'whatsapp_number',
            'owner_phone', 'city', 'address',
            'delivery_charge', 'minimum_order', 'greeting_message',
        ]));

        $r->plan            = $request->input('plan', 'trial');
        $r->plan_expires_at = $request->plan !== 'trial' ? now()->addMonth() : null;
        $r->is_active       = true;
        $r->bot_status      = 'disconnected';
        $r->owner_password  = Hash::make($request->input('owner_password'));
        $r->save();

        return redirect()
            ->route('admin.restaurants')
            ->with('success', "🎉 Restaurant {$r->name} registered! Click QR Code anytime to link WhatsApp.");
    }

    // ── Toggle restaurant active/inactive (soft delete) ────
    public function toggleRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();

        $newState = !$r->is_active;
        $r->is_active = $newState;

        if (!$newState) {
            $r->deactivated_at     = now();
            $r->deactivated_reason = $request->input('reason', 'Admin deactivation');
        } else {
            $r->deactivated_at     = null;
            $r->deactivated_reason = null;
        }

        $r->save();

        // Deactivating must take effect on the bot too, not just in the panel.
        // This used to call TenantResolver::clearCache(), which only forgot a
        // cache key derived from the (now removed) Meta `wa_phone_id` — nothing
        // populated it, so it was a no-op and a deactivated restaurant kept
        // taking orders until the bot's own TTL expired.
        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);

        $label = $newState ? '✅ Reactivated' : '⏸️ Deactivated';
        return back()->with('success', "{$label}: {$r->name}");
    }

    // ── Extend plan ────────────────────────────────────────
    public function extendPlan(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $months = (int) $request->input('months', 1);
        $expiry = $r->plan_expires_at && $r->plan_expires_at->isFuture()
            ? $r->plan_expires_at->addMonths($months)
            : now()->addMonths($months);

        $r->plan_expires_at = $expiry;
        if ($request->filled('plan')) {
            $r->plan = $request->input('plan');
        }
        $r->save();

        return back()->with('success', "Plan extended until {$expiry->format('d M Y')}");
    }

    // ── Clear last error for a restaurant ─────────────────
    public function clearError(Restaurant $r)
    {
        $this->adminAuth();
        $r->last_error    = null;
        $r->last_error_at = null;
        $r->save();
        return back()->with('success', "Error log cleared for {$r->name}");
    }
}