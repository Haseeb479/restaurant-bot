<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
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

    // ── Main dashboard — all restaurants at a glance ───────
    public function dashboard()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems'])
            ->withCount(['orders as today_orders_count' => fn($q) => $q->whereDate('created_at', today())])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->with([
                'orders' => fn($q) => $q->whereDate('created_at', today())->select('id','restaurant_id','total','status'),
                'conversations' => fn($q) => $q->latest()->take(3)->select('id','restaurant_id','customer_phone','state','last_message_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        // Platform-wide stats (excluding cancelled orders for revenue)
        $totalRevenue      = Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total');
        $totalOrders       = Order::whereDate('created_at', today())->count();
        $monthOrders       = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $monthRevenue      = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', '!=', 'cancelled')->sum('total');
        $activeRestaurants = $restaurants->where('is_active', true)->count();
        $botConnected      = $restaurants->where('bot_status', 'connected')->count();
        $needsAttention    = $restaurants->whereIn('bot_status', ['qr_expired', 'disconnected'])->where('is_active', true)->count();

        // Recent failed messages (from bot error log per restaurant)
        $errorRestaurants = $restaurants->whereNotNull('last_error')->where('is_active', true);

        return view('admin.dashboard', compact(
            'restaurants',
            'totalRevenue', 'totalOrders',
            'monthOrders', 'monthRevenue',
            'activeRestaurants', 'botConnected', 'needsAttention',
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

        return redirect()
            ->route('dashboard.connect-whatsapp', $r->id)
            ->with('success', "🎉 Restaurant {$r->name} created! Scan the QR code below to connect WhatsApp.");
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