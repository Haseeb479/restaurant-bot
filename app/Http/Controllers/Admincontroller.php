<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order};
use Illuminate\Http\Request;

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
            ->with(['orders' => fn($q) => $q->whereDate('created_at', today())])
            ->get();

        $totalRevenue = Order::whereDate('created_at', today())->sum('total');
        $totalOrders  = Order::whereDate('created_at', today())->count();

        return view('admin.dashboard', compact('restaurants', 'totalRevenue', 'totalOrders'));
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
            'wa_phone_id'     => 'required|string|unique:restaurants',
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
                'is_active' => true,
            ]
        ));

        return redirect()->route('admin.dashboard')
            ->with('success', "Restaurant '{$r->name}' created! Dashboard: /dashboard/{$r->id}");
    }

    // ── Toggle restaurant active/inactive ─────────────────
    public function toggleRestaurant(Restaurant $r)
    {
        $this->adminAuth();
        $r->update(['is_active' => !$r->is_active]);
        \App\Services\TenantResolver::clearCache($r);
        return back()->with('success', "Restaurant {$r->name} " . ($r->is_active ? 'activated' : 'deactivated'));
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

    // ── All orders across all restaurants ─────────────────
    public function allOrders()
    {
        $this->adminAuth();
        $orders = Order::with('restaurant')->latest()->paginate(50);
        return view('admin.orders', compact('orders'));
    }
}