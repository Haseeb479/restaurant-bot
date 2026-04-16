<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order, Category, MenuItem};
use App\Services\TenantResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // ── Login ──────────────────────────────────────────────
    public function loginForm(string $slug)
    {
        $r = Restaurant::where('id', $slug)->firstOrFail();
        return view('dashboard.login', compact('r'));
    }

    public function login(Request $request, string $slug)
    {
        $r        = Restaurant::findOrFail($slug);
        $password = $request->input('password');

        if ($password !== $r->owner_password) {
            return back()->withErrors(['password' => 'Wrong password']);
        }

        session(["restaurant_{$r->id}" => true]);
        return redirect()->route('dashboard.orders', $r->id);
    }

    public function logout(string $id)
    {
        session()->forget("restaurant_{$id}");
        return redirect()->route('dashboard.login', $id);
    }

    // ── Orders page (live) ─────────────────────────────────
    public function orders(string $id)
    {
        $this->authCheck($id);
        $r      = Restaurant::findOrFail($id);
        $orders = $r->orders()->with('items')->paginate(20);
        $today  = $r->todayOrders()->get();

        return view('dashboard.orders', ['restaurant' => $r, 'orders' => $orders, 'today' => $today]);
    }

    // ── Update order status ────────────────────────────────
    public function updateStatus(Request $request, string $id, Order $order)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        abort_if($order->restaurant_id !== $r->id, 403);

        $order->update(['status' => $request->input('status')]);

        // Notify customer of status change
        $messages = [
            'confirmed'        => "✅ Your order #{$order->tracking_code} has been *confirmed* by {$r->name}!",
            'preparing'        => "👨‍🍳 Your order #{$order->tracking_code} is being *prepared*!",
            'out_for_delivery' => "🛵 Your order #{$order->tracking_code} is *on the way*! Get ready!",
            'delivered'        => "🎉 Your order #{$order->tracking_code} has been *delivered*. Enjoy your meal! Thank you! 🙏",
            'cancelled'        => "❌ Sorry, your order #{$order->tracking_code} was *cancelled*. Please call us for details.",
        ];

        if (isset($messages[$order->status])) {
            // Send via internal bot API
            try {
                \Illuminate\Support\Facades\Http::timeout(5)
                    ->post(config('app.bot_internal_api', 'http://localhost:3000') . '/send-message', [
                        'to'      => $order->customer_phone,
                        'message' => $messages[$order->status],
                    ]);
            } catch (\Exception $e) {
                \Log::warning('Could not notify customer: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Status updated!');
    }

    // ── Menu management ────────────────────────────────────
    public function menu(string $id)
    {
        $this->authCheck($id);
        $r          = Restaurant::findOrFail($id);
        $categories = $r->categories()->with('items')->get();
        return view('dashboard.menu', ['restaurant' => $r, 'categories' => $categories]);
    }

    public function storeCategory(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        $r->categories()->create([
            'name'       => $request->input('name'),
            'sort_order' => $request->input('sort_order', 0),
        ]);
        return back()->with('success', 'Category added!');
    }

    // ── Store Item (supports size variants M/L/etc.) ───────
    public function storeItem(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $hasSizes = $request->has('sizes') && is_array($request->input('sizes'));

        // Filter out empty size rows
        $sizes = null;
        if ($hasSizes) {
            $sizes = collect($request->input('sizes'))
                ->filter(fn($s) => !empty($s['size']) && !empty($s['price']))
                ->values()
                ->map(fn($s) => [
                    'size'  => strtoupper(trim($s['size'])),
                    'price' => (float) $s['price'],
                ])
                ->toArray();

            if (empty($sizes)) $sizes = null;
        }

        // Base price: first size price if sizes exist, else single price field
        $basePrice = ($sizes && !empty($sizes[0]['price'])) ? $sizes[0]['price'] : ($request->input('price') ?? 0);

        $r->menuItems()->create([
            'category_id' => $request->input('category_id'),
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'price'       => $basePrice,
            'sizes'       => $sizes, // null if no size variants
        ]);

        return back()->with('success', 'Item added!');
    }

    public function toggleItem(string $id, MenuItem $item)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($item->restaurant_id !== $r->id, 403);
        $item->update(['is_available' => !$item->is_available]);
        return back()->with('success', 'Item updated!');
    }

    public function deleteItem(string $id, MenuItem $item)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($item->restaurant_id !== $r->id, 403);
        $item->delete();
        return back()->with('success', 'Item deleted!');
    }

    // ── Settings ───────────────────────────────────────────
    public function settings(string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        return view('dashboard.settings', ['restaurant' => $r]);
    }

    public function updateSettings(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $data = $request->only([
            'name', 'address', 'delivery_areas', 'delivery_charge',
            'minimum_order', 'greeting_message',
        ]);
        $data['is_open'] = $request->has('is_open');

        $r->update($data);
        TenantResolver::clearCache($r);
        return back()->with('success', 'Settings saved!');
    }

    // ── Auth helper ────────────────────────────────────────
    private function authCheck(string $id): void
    {
        abort_unless(session("restaurant_{$id}"), 403, 'Please login first.');
    }
}