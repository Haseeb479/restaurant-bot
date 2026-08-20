<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order, Conversation};
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ── SaaS Plan Pricing (Bot Service Platform Packages) ───
    public const PLAN_PRICES = [
        'trial' => 0,
        'basic' => 3000,
        'pro'   => 7000,
    ];

    private function adminAuth(): void
    {
        abort_unless(session('admin_logged_in'), 403, 'Admin access required.');
    }

    public function loginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        if ($request->input('password') === config('app.admin_password', 'admin123')) {
            session(['admin_logged_in' => true]);
            return redirect()->route('admin.dashboard');
        }
        return back()->withErrors(['password' => 'Wrong password']);
    }

    public function logout()
    {
        session()->forget('admin_logged_in');
        return redirect()->route('admin.login');
    }

    // ── Main dashboard — SaaS Bot Platform Overview ───────
    public function dashboard()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->orderByDesc('created_at')
            ->get();

        // ── SaaS Bot Service Package Revenue & Subscription Stats ──
        $activeRestaurants = $restaurants->where('is_active', true);
        $totalSubscribers  = $restaurants->count();
        $activeCount       = $activeRestaurants->count();
        $trialCount        = $activeRestaurants->where('plan', 'trial')->count();
        $basicCount        = $activeRestaurants->where('plan', 'basic')->count();
        $proCount          = $activeRestaurants->where('plan', 'pro')->count();

        // Monthly Recurring Revenue (MRR) from Bot Subscriptions / Plans
        $monthlySaasRevenue = ($basicCount * self::PLAN_PRICES['basic']) + ($proCount * self::PLAN_PRICES['pro']);

        // WhatsApp Bot activity & conversations platform-wide
        $totalConversations = Conversation::count();
        $monthConversations = Conversation::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // Bot Health
        $botConnected   = $restaurants->where('bot_status', 'connected')->count();
        $needsAttention = $restaurants->whereIn('bot_status', ['qr_expired', 'disconnected'])->where('is_active', true)->count();
        $errorRestaurants = $restaurants->whereNotNull('last_error')->where('is_active', true);

        return view('admin.dashboard', compact(
            'restaurants',
            'totalSubscribers',
            'activeCount',
            'monthlySaasRevenue',
            'trialCount',
            'basicCount',
            'proCount',
            'totalConversations',
            'monthConversations',
            'botConnected',
            'needsAttention',
            'errorRestaurants'
        ));
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
            'wa_phone_id'     => 'nullable|string',
            'owner_phone'     => 'required|string',
            'owner_password'  => 'required|string|min:4',
            'plan'            => 'required|in:trial,basic,pro',
        ]);

        $r = Restaurant::create(array_merge(
            $request->only([
                'name', 'whatsapp_number', 'wa_phone_id', 'wa_access_token',
                'owner_phone', 'owner_password', 'city', 'address', 'plan',
                'delivery_charge', 'minimum_order', 'greeting_message',
            ]),
            [
                'plan_expires_at' => $request->plan !== 'trial'
                    ? now()->addMonth()
                    : null,
                'is_active'  => true,
                'bot_status' => 'disconnected',
            ]
        ));

        // Pre-authorize session so redirect directly loads without 403/404
        session(["restaurant_{$r->id}" => true]);

        return redirect()
            ->route('dashboard.connect-whatsapp', $r->id)
            ->with('success', "🎉 Restaurant {$r->name} registered! Scan the QR code below to connect WhatsApp.");
    }

    // ── Toggle restaurant active/inactive (soft — never hard delete) ──
    public function toggleRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();

        $newState = !$r->is_active;
        $updateData = ['is_active' => $newState];

        if (!$newState) {
            // Deactivating: record when and reason (optional)
            $updateData['deactivated_at']     = now();
            $updateData['deactivated_reason'] = $request->input('reason', 'Admin deactivation');
        } else {
            // Reactivating: clear deactivation timestamp
            $updateData['deactivated_at']     = null;
            $updateData['deactivated_reason'] = null;
        }

        $r->update($updateData);

        try {
            \App\Services\TenantResolver::clearCache($r);
        } catch (\Throwable $e) {}

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

        $r->update(['plan_expires_at' => $expiry, 'plan' => $request->input('plan', $r->plan)]);
        return back()->with('success', "Plan extended until {$expiry->format('d M Y')}");
    }

    // ── Clear last error for a restaurant ─────────────────
    public function clearError(Restaurant $r)
    {
        $this->adminAuth();
        $r->update(['last_error' => null, 'last_error_at' => null]);
        return back()->with('success', "Error log cleared for {$r->name}");
    }

    // ── All orders across all restaurants ─────────────────
    public function allOrders()
    {
        $this->adminAuth();
        $orders = Order::with('restaurant')->latest()->paginate(50);
        return view('admin.orders', compact('orders'));
    }
}