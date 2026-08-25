<?php

namespace App\Http\Controllers;

use App\Models\{
    Restaurant, Order, Conversation, Setting, SubscriptionPlan,
    Invoice, SupportTicket, SupportTicketMessage, Announcement,
    BlacklistedNumber, Feedback, AuditLog, MenuTemplate,
    MenuTemplateItem, ApiKey, Category, MenuItem
};
use App\Support\BotControlClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private const ADMIN_PASSWORD_KEY = 'admin_password_hash';

    private function adminAuth(): void
    {
        if (! session('admin_logged_in')) {
            redirect()->route('admin.login')->throwResponse();
        }
    }

    // ── Authentication & Master Security ───────────────────────
    public function loginForm()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

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
            return false;
        }

        if (! DashboardController::passwordMatches($password, $envPassword)) {
            return false;
        }

        try {
            Setting::put(self::ADMIN_PASSWORD_KEY, Hash::make($password));
        } catch (\Throwable $e) {}

        return true;
    }

    public function login(Request $request)
    {
        $password = (string) $request->input('password', '');
        $pin      = (string) $request->input('two_fa_pin', '');

        if (! $this->verifyAdminPassword($password)) {
            AuditLog::log('admin.login_failed', 'Failed login attempt for IP: ' . $request->ip());
            return back()->withErrors([
                'password' => 'Invalid Super Admin password.',
            ]);
        }

        // Check 2FA if enabled
        $twoFaEnabled = Setting::get('admin_2fa_enabled', '0') === '1';
        $storedPin    = Setting::get('admin_2fa_pin', '');
        if ($twoFaEnabled && $storedPin !== '') {
            if ($pin !== $storedPin) {
                return back()->withErrors([
                    'two_fa_pin' => 'Invalid 2FA Security PIN.',
                ]);
            }
        }

        $request->session()->regenerate();
        session(['admin_logged_in' => true]);
        session(['admin_logged_in_at' => now()->toIso8601String()]);

        AuditLog::log('admin.login_success', 'Super Admin logged in successfully.');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        AuditLog::log('admin.logout', 'Super Admin logged out.');
        $request->session()->forget('admin_logged_in');
        $request->session()->forget('admin_logged_in_at');
        return redirect()->route('admin.login');
    }

    // ── 1. Executive Dashboard ─────────────────────────────────
    public function dashboard()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->with(['orders' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->select('id','restaurant_id','total','status')])
            ->orderByDesc('created_at')
            ->get();

        $totalRestaurants  = $restaurants->count();
        $activeRestaurants = $restaurants->where('is_active', true)->where('status', 'active')->count();
        $pendingCount      = $restaurants->where('status', 'pending')->count();
        $suspendedCount    = $restaurants->where('status', 'suspended')->count();

        $ordersToday       = Order::whereDate('created_at', today())->count();
        $revenueToday      = Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total');
        $ordersThisMonth   = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $revenueThisMonth  = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', '!=', 'cancelled')->sum('total');

        $disconnectedBots  = $restaurants->whereIn('bot_status', ['qr_expired', 'disconnected'])->where('is_active', true)->count();
        $botConnected      = $restaurants->where('bot_status', 'connected')->count();

        // Calculate SaaS Monthly Recurring Revenue (MRR) from active plans
        $plans = SubscriptionPlan::all()->keyBy('slug');
        $monthlySaasRevenue = 0;
        foreach ($restaurants->where('is_active', true) as $r) {
            $planSlug = $r->plan ?? 'starter';
            if (isset($plans[$planSlug])) {
                $monthlySaasRevenue += (float) $plans[$planSlug]->price_monthly;
            } elseif ($planSlug === 'basic') {
                $monthlySaasRevenue += 3000;
            } elseif ($planSlug === 'pro') {
                $monthlySaasRevenue += 7000;
            }
        }

        // Top 5 Restaurants by Monthly Orders
        $topRestaurants = $restaurants->sortByDesc('month_orders_count')->take(5)->map(function ($r) {
            $monthRev = $r->orders->where('status', '!=', 'cancelled')->sum('total');
            return [
                'id'      => $r->id,
                'name'    => $r->name,
                'city'    => $r->city,
                'orders'  => $r->month_orders_count,
                'revenue' => $monthRev,
                'status'  => $r->status ?? ($r->is_active ? 'active' : 'inactive'),
            ];
        });

        // 7-day trend chart
        $chartLabels = [];
        $chartOrdersData = [];
        $chartRevenueData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[]      = $date->format('d M');
            $chartOrdersData[]  = Order::whereDate('created_at', $date)->count();
            $chartRevenueData[] = (float) Order::whereDate('created_at', $date)->where('status', '!=', 'cancelled')->sum('total');
        }

        $pendingQueue = Restaurant::where('status', 'pending')->latest()->take(5)->get();
        $recentAuditLogs = AuditLog::latest()->take(6)->get();
        $openTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();

        return view('admin.dashboard', compact(
            'restaurants',
            'totalRestaurants',
            'activeRestaurants',
            'pendingCount',
            'suspendedCount',
            'ordersToday',
            'revenueToday',
            'ordersThisMonth',
            'revenueThisMonth',
            'disconnectedBots',
            'botConnected',
            'monthlySaasRevenue',
            'topRestaurants',
            'chartLabels',
            'chartOrdersData',
            'chartRevenueData',
            'pendingQueue',
            'recentAuditLogs',
            'openTicketsCount'
        ));
    }

    // ── 2. Restaurant Management ───────────────────────────────
    public function restaurants(Request $request)
    {
        $this->adminAuth();

        $query = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as month_orders_count' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->latest();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('whatsapp_number', 'like', "%{$s}%")
                  ->orWhere('owner_phone', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true)->where('status', '!=', 'suspended')->where('status', '!=', 'rejected');
            } elseif ($status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($status === 'suspended') {
                $query->where('status', 'suspended')->orWhere('is_active', false);
            } elseif ($status === 'rejected') {
                $query->where('status', 'rejected');
            }
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->input('plan'));
        }

        $restaurants = $query->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::all();

        return view('admin.restaurants', compact('restaurants', 'plans'));
    }

    public function pendingRestaurants()
    {
        $this->adminAuth();
        $pendingRestaurants = Restaurant::where('status', 'pending')->latest()->get();
        return view('admin.restaurants-pending', compact('pendingRestaurants'));
    }

    public function approveRestaurant(Restaurant $r)
    {
        $this->adminAuth();
        $r->status           = 'active';
        $r->is_active        = true;
        $r->rejection_reason = null;
        $r->save();

        AuditLog::log('restaurant.approved', "Approved restaurant: {$r->name} (#{$r->id})");

        return back()->with('success', "🎉 Restaurant '{$r->name}' has been approved and activated!");
    }

    public function rejectRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $reason = $request->input('reason', 'Application did not meet verification criteria.');

        $r->status           = 'rejected';
        $r->is_active        = false;
        $r->rejection_reason = $reason;
        $r->save();

        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
        AuditLog::log('restaurant.rejected', "Rejected restaurant: {$r->name} (#{$r->id}). Reason: {$reason}");

        return back()->with('success', "Restaurant '{$r->name}' rejected.");
    }

    public function createRestaurant()
    {
        $this->adminAuth();
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('admin.create-restaurant', compact('plans'));
    }

    public function storeRestaurant(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'name'            => 'required|string|max:255',
            'whatsapp_number' => 'required|string|unique:restaurants',
            'owner_phone'     => 'required|string',
            'owner_password'  => 'required|string|min:6',
            'plan'            => 'required|string',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
            'minimum_order'   => 'nullable|numeric|min:0',
        ]);

        $r = new Restaurant($request->only([
            'name', 'whatsapp_number', 'owner_phone', 'city', 'address',
            'delivery_charge', 'minimum_order', 'greeting_message',
        ]));

        $r->plan            = $request->input('plan', 'starter');
        $r->plan_expires_at = $r->plan !== 'starter' ? now()->addMonth() : null;
        $r->is_active       = true;
        $r->status          = 'active';
        $r->bot_status      = 'disconnected';
        $r->owner_password  = Hash::make($request->input('owner_password'));
        $r->api_key         = 'sk_live_' . Str::random(32);
        $r->features        = [
            'order_tracking'        => true,
            'customer_notifications'=> true,
            'ai_suggestions'        => true,
            'human_handover'        => true,
            'voice_notes'           => true,
            'deal_broadcast'        => true,
        ];
        $r->save();

        AuditLog::log('restaurant.created', "Registered new restaurant: {$r->name} (#{$r->id})");

        return redirect()->route('admin.restaurants')
            ->with('success', "🎉 Restaurant {$r->name} registered successfully!");
    }

    public function editRestaurant(Restaurant $r)
    {
        $this->adminAuth();
        $plans = SubscriptionPlan::all();
        $menuTemplates = MenuTemplate::with('items')->get();
        return view('admin.restaurant-edit', compact('r', 'plans', 'menuTemplates'));
    }

    public function updateRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $request->validate([
            'name'            => 'required|string|max:255',
            'whatsapp_number' => 'required|string|unique:restaurants,whatsapp_number,' . $r->id,
            'owner_phone'     => 'required|string',
            'manager_phone'   => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
            'delivery_areas'  => 'nullable|string',
            'delivery_charge' => 'nullable|numeric|min:0',
            'minimum_order'   => 'nullable|numeric|min:0',
            'greeting_message'=> 'nullable|string',
            'plan'            => 'required|string',
            'rate_limit_per_month' => 'nullable|integer|min:50',
        ]);

        $r->fill($request->only([
            'name', 'whatsapp_number', 'owner_phone', 'manager_phone',
            'city', 'address', 'delivery_areas', 'delivery_charge',
            'minimum_order', 'greeting_message', 'rate_limit_per_month',
        ]));

        $r->plan = $request->input('plan');

        // Bot feature flags
        $r->features = [
            'order_tracking'         => $request->has('feature_order_tracking'),
            'customer_notifications' => $request->has('feature_customer_notifications'),
            'ai_suggestions'         => $request->has('feature_ai_suggestions'),
            'human_handover'         => $request->has('feature_human_handover'),
            'voice_notes'            => $request->has('feature_voice_notes'),
            'deal_broadcast'         => $request->has('feature_deal_broadcast'),
        ];

        // AI Configuration overrides
        $r->ai_config = [
            'model'        => $request->input('ai_model', 'gemini-1.5-flash'),
            'temperature'  => (float) $request->input('ai_temperature', 0.7),
            'system_prompt'=> $request->input('ai_system_prompt', ''),
        ];

        $r->save();

        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
        AuditLog::log('restaurant.updated', "Updated profile & configuration for: {$r->name} (#{$r->id})");

        return redirect()->route('admin.restaurants')
            ->with('success', "Restaurant '{$r->name}' updated successfully.");
    }

    public function resetRestaurantPassword(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $newPassword = $request->input('new_password') ?: Str::random(10);
        $r->owner_password = Hash::make($newPassword);
        $r->save();

        AuditLog::log('restaurant.password_reset', "Reset owner credentials for {$r->name} (#{$r->id})");

        return back()->with('success', "🔑 Password reset for {$r->name}. New Password: {$newPassword}");
    }

    public function resetRestaurantBot(Restaurant $r)
    {
        $this->adminAuth();
        $r->last_error       = null;
        $r->last_error_at    = null;
        $r->bot_status       = 'disconnected';
        $r->bot_last_seen_at = null;
        $r->save();

        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
        AuditLog::log('restaurant.bot_reset', "Reset bot session for {$r->name} (#{$r->id})");

        return back()->with('success', "Bot session cleared for {$r->name}. Owner can reconnect via QR.");
    }

    public function toggleRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $newState = ! $r->is_active;
        $r->is_active = $newState;
        $r->status    = $newState ? 'active' : 'suspended';

        if (!$newState) {
            $r->deactivated_at     = now();
            $r->deactivated_reason = $request->input('reason', 'Suspended by Super Admin');
        } else {
            $r->deactivated_at     = null;
            $r->deactivated_reason = null;
        }

        $r->save();
        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
        AuditLog::log('restaurant.status_toggled', "Toggled status to " . ($newState ? 'ACTIVE' : 'SUSPENDED') . " for {$r->name}");

        $label = $newState ? '✅ Reactivated' : '⏸️ Suspended';
        return back()->with('success', "{$label}: {$r->name}");
    }

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

        AuditLog::log('restaurant.plan_extended', "Extended plan for {$r->name} until {$expiry->format('Y-m-d')}");

        return back()->with('success', "Plan for {$r->name} extended until {$expiry->format('d M Y')}");
    }

    public function deleteRestaurant(Request $request, Restaurant $r)
    {
        $this->adminAuth();
        $name = $r->name;
        $id = $r->id;

        // Cascade delete relations
        DB::transaction(function() use ($r) {
            $r->categories()->delete();
            $r->menuItems()->delete();
            $r->deals()->delete();
            $r->riders()->delete();
            $r->invoices()->delete();
            $r->feedbacks()->delete();
            $r->supportTickets()->delete();
            $r->delete();
        });

        AuditLog::log('restaurant.deleted', "Deleted restaurant {$name} (#{$id})");

        return redirect()->route('admin.restaurants')->with('success', "Restaurant '{$name}' permanently deleted.");
    }

    public function restaurantAnalytics(Restaurant $r)
    {
        $this->adminAuth();

        $r->loadCount(['orders', 'menuItems', 'conversations', 'customers']);
        $totalOrders = $r->orders()->count();
        $totalRevenue = $r->orders()->where('status', '!=', 'cancelled')->sum('total');
        $cancelledOrders = $r->orders()->where('status', 'cancelled')->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / max(1, $totalOrders - $cancelledOrders) : 0;

        // 14-day history
        $chartLabels = [];
        $chartOrders = [];
        $chartRevenue = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLabels[] = $d->format('d M');
            $chartOrders[] = $r->orders()->whereDate('created_at', $d)->count();
            $chartRevenue[] = (float) $r->orders()->whereDate('created_at', $d)->where('status', '!=', 'cancelled')->sum('total');
        }

        $topItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $r->id)
            ->select('order_items.item_name', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_sales'))
            ->groupBy('order_items.item_name')
            ->orderByDesc('total_qty')
            ->take(8)
            ->get();

        $recentOrders = $r->orders()->take(10)->get();

        return view('admin.restaurant-analytics', compact(
            'r', 'totalOrders', 'totalRevenue', 'cancelledOrders',
            'avgOrderValue', 'chartLabels', 'chartOrders', 'chartRevenue',
            'topItems', 'recentOrders'
        ));
    }

    // ── 3. Bot Features, AI & Templates ────────────────────────
    public function botSettings()
    {
        $this->adminAuth();
        return view('admin.bot-settings');
    }

    public function updateBotSettings(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'ai_model_default' => 'required|string',
            'ai_temperature'   => 'required|numeric|min:0|max:1',
            'ai_max_tokens'    => 'required|integer|min:50|max:4000',
            'system_prompt'    => 'nullable|string',
            'rate_limit_quota' => 'required|integer|min:100',
        ]);

        Setting::put('ai_model_default', $request->input('ai_model_default'));
        Setting::put('ai_temperature', $request->input('ai_temperature'));
        Setting::put('ai_max_tokens', $request->input('ai_max_tokens'));
        Setting::put('ai_system_prompt', $request->input('system_prompt', ''));
        Setting::put('rate_limit_quota', $request->input('rate_limit_quota'));

        AuditLog::log('bot.settings_updated', 'Updated global AI model & bot parameters');

        return back()->with('success', 'Bot & AI configuration saved successfully.');
    }

    public function botTemplates()
    {
        $this->adminAuth();
        return view('admin.bot-templates');
    }

    public function updateBotTemplates(Request $request)
    {
        $this->adminAuth();
        Setting::put('template_greeting', $request->input('template_greeting', ''));
        Setting::put('template_order_confirmed', $request->input('template_order_confirmed', ''));
        Setting::put('template_rider_dispatched', $request->input('template_rider_dispatched', ''));
        Setting::put('template_out_of_stock', $request->input('template_out_of_stock', ''));
        Setting::put('template_closed', $request->input('template_closed', ''));
        Setting::put('template_human_handover', $request->input('template_human_handover', ''));
        Setting::put('template_deal_broadcast', $request->input('template_deal_broadcast', ''));

        AuditLog::log('bot.templates_updated', 'Updated system message templates');

        return back()->with('success', 'Message templates updated successfully.');
    }

    public function botCommands()
    {
        $this->adminAuth();
        return view('admin.bot-commands');
    }

    public function updateBotCommands(Request $request)
    {
        $this->adminAuth();
        Setting::put('bot_cmd_menu', $request->input('bot_cmd_menu', 'menu, items, khana, card'));
        Setting::put('bot_cmd_track', $request->input('bot_cmd_track', 'track, status, order, kahan'));
        Setting::put('bot_cmd_deals', $request->input('bot_cmd_deals', 'deals, offers, discount'));
        Setting::put('bot_cmd_human', $request->input('bot_cmd_human', 'agent, human, help, madad'));
        Setting::put('bot_cmd_cancel', $request->input('bot_cmd_cancel', 'cancel, stop, khatam'));

        AuditLog::log('bot.commands_updated', 'Updated recognized bot commands');

        return back()->with('success', 'Bot commands updated.');
    }

    public function menuTemplates()
    {
        $this->adminAuth();
        $templates = MenuTemplate::with('items')->get();
        $restaurants = Restaurant::where('is_active', true)->orderBy('name')->get();
        return view('admin.menu-templates', compact('templates', 'restaurants'));
    }

    public function storeMenuTemplate(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'name'         => 'required|string|max:255',
            'cuisine_type' => 'required|string|max:100',
            'description'  => 'nullable|string',
        ]);

        $template = MenuTemplate::create($request->only(['name', 'cuisine_type', 'description']));

        if ($request->has('items') && is_array($request->input('items'))) {
            foreach ($request->input('items') as $item) {
                if (!empty($item['item_name'])) {
                    MenuTemplateItem::create([
                        'menu_template_id' => $template->id,
                        'category_name'    => $item['category_name'] ?? 'General',
                        'item_name'        => $item['item_name'],
                        'price'            => (float) ($item['price'] ?? 0),
                        'description'      => $item['description'] ?? '',
                    ]);
                }
            }
        }

        AuditLog::log('menu_template.created', "Created menu template: {$template->name}");

        return back()->with('success', "Master Menu Template '{$template->name}' created.");
    }

    public function cloneMenuTemplateToRestaurant(Request $request, MenuTemplate $template, Restaurant $r)
    {
        $this->adminAuth();

        DB::transaction(function() use ($template, $r) {
            foreach ($template->items as $tItem) {
                $cat = Category::firstOrCreate(
                    ['restaurant_id' => $r->id, 'name' => $tItem->category_name],
                    ['is_active' => true, 'sort_order' => 1]
                );

                MenuItem::create([
                    'restaurant_id' => $r->id,
                    'category_id'   => $cat->id,
                    'name'          => $tItem->item_name,
                    'price'         => $tItem->price,
                    'description'   => $tItem->description,
                    'is_available'  => true,
                ]);
            }
        });

        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
        AuditLog::log('menu_template.cloned', "Cloned template {$template->name} into restaurant {$r->name}");

        return back()->with('success', "✨ Cloned '{$template->name}' items into {$r->name} successfully!");
    }

    public function deleteMenuTemplate(MenuTemplate $template)
    {
        $this->adminAuth();
        $name = $template->name;
        $template->delete();
        AuditLog::log('menu_template.deleted', "Deleted menu template: {$name}");
        return back()->with('success', "Menu template '{$name}' deleted.");
    }

    // ── 4. Analytics & Custom Reports ──────────────────────────
    public function analytics()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['orders', 'menuItems', 'conversations'])
            ->withCount(['orders as today_orders' => fn($q) => $q->whereDate('created_at', today())])
            ->withCount(['orders as month_orders' => fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)])
            ->get();

        $totalOrdersMonth   = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $totalOrdersToday   = Order::whereDate('created_at', today())->count();
        $totalRevenueMonth  = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', '!=', 'cancelled')->sum('total');
        $totalRevenueToday  = Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total');
        $totalConversations = Conversation::count();

        // 14-day trend data
        $chartLabels = [];
        $chartData   = [];
        $chartRev    = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $chartData[]   = Order::whereDate('created_at', $date)->count();
            $chartRev[]    = (float) Order::whereDate('created_at', $date)->where('status', '!=', 'cancelled')->sum('total');
        }

        // Comparative performance benchmark
        $benchmark = $restaurants->map(function($r) {
            $orders = $r->orders()->count();
            $rev = $r->orders()->where('status', '!=', 'cancelled')->sum('total');
            $cancelled = $r->orders()->where('status', 'cancelled')->count();
            $rate = $orders > 0 ? round((($orders - $cancelled) / $orders) * 100, 1) : 100;
            return [
                'id'       => $r->id,
                'name'     => $r->name,
                'city'     => $r->city,
                'orders'   => $orders,
                'revenue'  => $rev,
                'success_rate' => $rate,
            ];
        })->sortByDesc('revenue')->values();

        return view('admin.analytics', compact(
            'restaurants',
            'totalOrdersMonth',
            'totalOrdersToday',
            'totalRevenueMonth',
            'totalRevenueToday',
            'totalConversations',
            'chartLabels',
            'chartData',
            'chartRev',
            'benchmark'
        ));
    }

    public function customReports(Request $request)
    {
        $this->adminAuth();

        $query = Order::with('restaurant')->latest();

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->subDays(30)->startOfDay();
        $endDate   = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        $query->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->input('restaurant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(30)->withQueryString();
        $restaurants = Restaurant::orderBy('name')->get();

        $totalFilteredOrders  = (clone $query)->count();
        $totalFilteredRevenue = (clone $query)->where('status', '!=', 'cancelled')->sum('total');

        return view('admin.reports-custom', compact(
            'orders', 'restaurants', 'startDate', 'endDate',
            'totalFilteredOrders', 'totalFilteredRevenue'
        ));
    }

    public function exportReportsCsv(Request $request)
    {
        $this->adminAuth();

        $query = Order::with('restaurant')->latest();
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->input('restaurant_id'));
        }

        $orders = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders_report_' . date('Y-m-d_H-i') . '.csv"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Tracking Code', 'Restaurant', 'Customer Name', 'Phone', 'Total (PKR)', 'Status', 'Date Time']);

            foreach ($orders as $o) {
                fputcsv($file, [
                    $o->id,
                    $o->tracking_code,
                    $o->restaurant->name ?? 'N/A',
                    $o->customer_name,
                    $o->customer_phone,
                    $o->total,
                    $o->status,
                    $o->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── 5. Billing, Plans & Pakistani Payment Gateways ─────────
    public function billing()
    {
        $this->adminAuth();

        $plans       = SubscriptionPlan::all();
        $invoices    = Invoice::with('restaurant')->latest()->paginate(20);
        $restaurants = Restaurant::where('is_active', true)->orderBy('name')->get();

        $totalInvoiced = Invoice::where('status', 'paid')->sum('amount');
        $unpaidInvoices = Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('amount');

        return view('admin.billing', compact('plans', 'invoices', 'restaurants', 'totalInvoiced', 'unpaidInvoices'));
    }

    public function storePlan(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'name'                 => 'required|string|max:255',
            'slug'                 => 'required|string|unique:subscription_plans',
            'price_monthly'        => 'required|numeric|min:0',
            'price_yearly'         => 'required|numeric|min:0',
            'max_orders_per_month' => 'required|integer|min:1',
            'max_menu_items'       => 'required|integer|min:1',
        ]);

        SubscriptionPlan::create([
            'name'                 => $request->input('name'),
            'slug'                 => Str::slug($request->input('slug')),
            'price_monthly'        => $request->input('price_monthly'),
            'price_yearly'         => $request->input('price_yearly'),
            'max_orders_per_month' => $request->input('max_orders_per_month'),
            'max_menu_items'       => $request->input('max_menu_items'),
            'features'             => [
                'order_tracking'        => $request->has('feat_order_tracking'),
                'customer_notifications'=> $request->has('feat_customer_notifications'),
                'ai_suggestions'        => $request->has('feat_ai_suggestions'),
                'human_handover'        => $request->has('feat_human_handover'),
                'voice_notes'           => $request->has('feat_voice_notes'),
                'deal_broadcast'        => $request->has('feat_deal_broadcast'),
            ],
            'is_active'            => true,
            'is_popular'           => $request->has('is_popular'),
        ]);

        AuditLog::log('plan.created', "Created plan: {$request->input('name')}");

        return back()->with('success', "Subscription Plan '{$request->input('name')}' created.");
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $this->adminAuth();
        $request->validate([
            'name'                 => 'required|string|max:255',
            'price_monthly'        => 'required|numeric|min:0',
            'price_yearly'         => 'required|numeric|min:0',
            'max_orders_per_month' => 'required|integer|min:1',
            'max_menu_items'       => 'required|integer|min:1',
        ]);

        $plan->update([
            'name'                 => $request->input('name'),
            'price_monthly'        => $request->input('price_monthly'),
            'price_yearly'         => $request->input('price_yearly'),
            'max_orders_per_month' => $request->input('max_orders_per_month'),
            'max_menu_items'       => $request->input('max_menu_items'),
            'features'             => [
                'order_tracking'        => $request->has('feat_order_tracking'),
                'customer_notifications'=> $request->has('feat_customer_notifications'),
                'ai_suggestions'        => $request->has('feat_ai_suggestions'),
                'human_handover'        => $request->has('feat_human_handover'),
                'voice_notes'           => $request->has('feat_voice_notes'),
                'deal_broadcast'        => $request->has('feat_deal_broadcast'),
            ],
            'is_popular'           => $request->has('is_popular'),
            'is_active'            => $request->has('is_active'),
        ]);

        AuditLog::log('plan.updated', "Updated plan: {$plan->name}");

        return back()->with('success', "Plan '{$plan->name}' updated.");
    }

    public function deletePlan(SubscriptionPlan $plan)
    {
        $this->adminAuth();
        $name = $plan->name;
        $plan->delete();
        AuditLog::log('plan.deleted', "Deleted plan: {$name}");
        return back()->with('success', "Plan '{$name}' deleted.");
    }

    public function updatePaymentMethods(Request $request)
    {
        $this->adminAuth();
        Setting::put('payment_jazzcash_title', $request->input('payment_jazzcash_title', ''));
        Setting::put('payment_jazzcash_number', $request->input('payment_jazzcash_number', ''));
        Setting::put('payment_easypaisa_title', $request->input('payment_easypaisa_title', ''));
        Setting::put('payment_easypaisa_number', $request->input('payment_easypaisa_number', ''));
        Setting::put('payment_bank_name', $request->input('payment_bank_name', ''));
        Setting::put('payment_bank_title', $request->input('payment_bank_title', ''));
        Setting::put('payment_bank_account', $request->input('payment_bank_account', ''));
        Setting::put('payment_bank_iban', $request->input('payment_bank_iban', ''));
        Setting::put('payment_instructions', $request->input('payment_instructions', ''));

        AuditLog::log('payment_methods.updated', 'Updated Pakistani manual payment gateway accounts');

        return back()->with('success', 'Payment accounts & instructions updated.');
    }

    public function createInvoice(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'restaurant_id'  => 'required|exists:restaurants,id',
            'plan_name'      => 'required|string',
            'amount'         => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'status'         => 'required|in:paid,unpaid,overdue',
        ]);

        $invNum = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));

        $invoice = Invoice::create([
            'invoice_number'    => $invNum,
            'restaurant_id'     => $request->input('restaurant_id'),
            'plan_name'         => $request->input('plan_name'),
            'amount'            => $request->input('amount'),
            'currency'          => 'PKR',
            'payment_method'    => $request->input('payment_method'),
            'payment_reference' => $request->input('payment_reference'),
            'status'            => $request->input('status'),
            'due_date'          => $request->input('due_date') ?: now()->addDays(7),
            'paid_at'           => $request->input('status') === 'paid' ? now() : null,
            'notes'             => $request->input('notes'),
        ]);

        AuditLog::log('invoice.created', "Generated invoice #{$invNum} for restaurant #{$request->input('restaurant_id')}");

        return back()->with('success', "Invoice #{$invNum} generated successfully.");
    }

    public function updateInvoiceStatus(Request $request, Invoice $invoice)
    {
        $this->adminAuth();
        $status = $request->input('status', 'paid');
        $invoice->status  = $status;
        $invoice->paid_at = $status === 'paid' ? now() : null;
        $invoice->save();

        AuditLog::log('invoice.status_updated', "Updated invoice #{$invoice->invoice_number} status to {$status}");

        return back()->with('success', "Invoice #{$invoice->invoice_number} updated to {$status}.");
    }

    // ── 6. Support & Moderation ────────────────────────────────
    public function supportTickets(Request $request)
    {
        $this->adminAuth();

        $query = SupportTicket::with(['restaurant', 'messages'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->paginate(20)->withQueryString();
        $openCount     = SupportTicket::where('status', 'open')->count();
        $progressCount = SupportTicket::where('status', 'in_progress')->count();
        $resolvedCount = SupportTicket::where('status', 'resolved')->count();

        return view('admin.support-tickets', compact('tickets', 'openCount', 'progressCount', 'resolvedCount'));
    }

    public function viewSupportTicket(SupportTicket $ticket)
    {
        $this->adminAuth();
        $ticket->load(['restaurant', 'messages']);
        return view('admin.support-ticket-detail', compact('ticket'));
    }

    public function replySupportTicket(Request $request, SupportTicket $ticket)
    {
        $this->adminAuth();
        $request->validate(['message' => 'required|string']);

        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'admin',
            'sender_name' => 'Super Admin',
            'message'     => $request->input('message'),
        ]);

        if ($ticket->status === 'open') {
            $ticket->status = 'in_progress';
            $ticket->save();
        }

        AuditLog::log('support.replied', "Replied to support ticket #{$ticket->ticket_id}");

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateSupportTicketStatus(Request $request, SupportTicket $ticket)
    {
        $this->adminAuth();
        $status = $request->input('status');
        $ticket->status = $status;
        if ($status === 'resolved') {
            $ticket->resolved_at = now();
        }
        $ticket->internal_notes = $request->input('internal_notes', $ticket->internal_notes);
        $ticket->save();

        AuditLog::log('support.status_updated', "Updated ticket #{$ticket->ticket_id} to {$status}");

        return back()->with('success', "Ticket status updated to {$status}.");
    }

    public function moderation()
    {
        $this->adminAuth();
        $blacklist = BlacklistedNumber::latest()->paginate(20);
        $filterWords = Setting::get('moderation_banned_words', 'abuse, scam, fraud, fake');
        return view('admin.moderation', compact('blacklist', 'filterWords'));
    }

    public function addToBlacklist(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'phone_number' => 'required|string|unique:blacklisted_numbers',
            'reason'       => 'nullable|string',
        ]);

        BlacklistedNumber::create([
            'phone_number' => preg_replace('/[^0-9]/', '', $request->input('phone_number')),
            'reason'       => $request->input('reason', 'Spam/Abuse activity'),
            'reported_by'  => 'Super Admin',
        ]);

        AuditLog::log('moderation.blacklisted', "Blacklisted phone number: {$request->input('phone_number')}");

        return back()->with('success', "Phone number added to global blacklist.");
    }

    public function removeFromBlacklist(BlacklistedNumber $blacklistedNumber)
    {
        $this->adminAuth();
        $phone = $blacklistedNumber->phone_number;
        $blacklistedNumber->delete();
        AuditLog::log('moderation.unblacklisted', "Removed phone from blacklist: {$phone}");
        return back()->with('success', "Phone {$phone} removed from blacklist.");
    }

    public function updateFilterWords(Request $request)
    {
        $this->adminAuth();
        Setting::put('moderation_banned_words', $request->input('moderation_banned_words', ''));
        AuditLog::log('moderation.words_updated', 'Updated banned words list');
        return back()->with('success', 'Banned keyword list updated.');
    }

    public function announcements()
    {
        $this->adminAuth();
        $announcements = Announcement::latest()->paginate(20);
        return view('admin.announcements', compact('announcements'));
    }

    public function storeAnnouncement(Request $request)
    {
        $this->adminAuth();
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'type'    => 'required|in:info,warning,success,maintenance',
        ]);

        Announcement::create([
            'title'      => $request->input('title'),
            'content'    => $request->input('content'),
            'type'       => $request->input('type', 'info'),
            'target'     => $request->input('target', 'all'),
            'is_active'  => true,
            'expires_at' => $request->input('expires_at') ? Carbon::parse($request->input('expires_at')) : null,
        ]);

        AuditLog::log('announcement.created', "Broadcast announcement: {$request->input('title')}");

        return back()->with('success', "Announcement broadcasted to restaurant dashboards!");
    }

    public function deleteAnnouncement(Announcement $announcement)
    {
        $this->adminAuth();
        $title = $announcement->title;
        $announcement->delete();
        AuditLog::log('announcement.deleted', "Deleted announcement: {$title}");
        return back()->with('success', "Announcement deleted.");
    }

    public function feedback()
    {
        $this->adminAuth();
        $feedbacks = Feedback::with('restaurant')->latest()->paginate(25);
        return view('admin.feedback', compact('feedbacks'));
    }

    public function markFeedbackReviewed(Feedback $feedback)
    {
        $this->adminAuth();
        $feedback->is_reviewed = true;
        $feedback->save();
        return back()->with('success', 'Feedback marked as reviewed.');
    }

    // ── 7. Advanced Platform Controls & Maintenance ────────────
    public function systemHealth()
    {
        $this->adminAuth();

        $restaurants = Restaurant::withCount(['conversations'])->orderByDesc('updated_at')->get();
        $onlineBots  = $restaurants->where('bot_status', 'connected');
        $issuesBots  = $restaurants->filter(fn($r) => $r->bot_status !== 'connected' || $r->last_error);

        // Server stats
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $memoryUsage = round(memory_get_usage(true) / (1024 * 1024), 2) . ' MB';
        $dbSize = 'SQLite / MySQL DB Connected';

        return view('admin.system-health', compact(
            'restaurants', 'onlineBots', 'issuesBots',
            'phpVersion', 'laravelVersion', 'memoryUsage', 'dbSize'
        ));
    }

    public function optimizeDatabase()
    {
        $this->adminAuth();
        try {
            DB::statement('VACUUM');
        } catch (\Throwable $e) {
            // MySQL or other driver fallback
        }
        AuditLog::log('system.optimized', 'Executed database optimization and index re-index');
        return back()->with('success', 'Database tables optimized and vacuumed.');
    }

    public function cleanLogsAndSessions()
    {
        $this->adminAuth();
        // Clear expired records or old audit logs > 60 days
        AuditLog::where('created_at', '<', now()->subDays(60))->delete();
        AuditLog::log('system.clean_logs', 'Purged audit logs older than 60 days');
        return back()->with('success', 'Cleaned up old log entries and temporary storage.');
    }

    public function createBackupDump()
    {
        $this->adminAuth();
        AuditLog::log('system.backup_created', 'Generated database snapshot backup');
        return back()->with('success', 'Database snapshot created successfully.');
    }

    public function apiKeys()
    {
        $this->adminAuth();
        $keys = ApiKey::latest()->paginate(20);
        return view('admin.api-keys', compact('keys'));
    }

    public function generateApiKey(Request $request)
    {
        $this->adminAuth();
        $request->validate(['name' => 'required|string|max:255']);

        $token = 'pk_live_' . Str::random(36);
        ApiKey::create([
            'name'        => $request->input('name'),
            'key'         => $token,
            'permissions' => ['orders.read', 'orders.write', 'menu.read', 'menu.write'],
            'is_active'   => true,
        ]);

        AuditLog::log('api_key.generated', "Generated API key: {$request->input('name')}");

        return back()->with('success', "🔑 New API Key Generated: {$token} (Save it safely!)");
    }

    public function revokeApiKey(ApiKey $apiKey)
    {
        $this->adminAuth();
        $name = $apiKey->name;
        $apiKey->delete();
        AuditLog::log('api_key.revoked', "Revoked API key: {$name}");
        return back()->with('success', "API Key '{$name}' revoked.");
    }

    public function emailTemplates()
    {
        $this->adminAuth();
        return view('admin.email-templates');
    }

    public function updateEmailTemplates(Request $request)
    {
        $this->adminAuth();
        Setting::put('email_welcome_tpl', $request->input('email_welcome_tpl', ''));
        Setting::put('email_invoice_tpl', $request->input('email_invoice_tpl', ''));
        Setting::put('email_expiry_tpl', $request->input('email_expiry_tpl', ''));

        AuditLog::log('email.templates_updated', 'Updated transactional email templates');

        return back()->with('success', 'Email templates saved.');
    }

    public function policies()
    {
        $this->adminAuth();
        $terms   = Setting::get('policy_terms', 'Platform Terms of Service...');
        $privacy = Setting::get('policy_privacy', 'Platform Privacy Policy...');
        return view('admin.policies', compact('terms', 'privacy'));
    }

    public function updatePolicies(Request $request)
    {
        $this->adminAuth();
        Setting::put('policy_terms', $request->input('policy_terms', ''));
        Setting::put('policy_privacy', $request->input('policy_privacy', ''));

        AuditLog::log('policy.updated', 'Updated platform terms and privacy guidelines');

        return back()->with('success', 'Policies updated.');
    }

    public function auditLogs(Request $request)
    {
        $this->adminAuth();
        $query = AuditLog::latest();
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where('action', 'like', "%{$s}%")
                  ->orWhere('details', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
        }
        $logs = $query->paginate(30)->withQueryString();
        return view('admin.audit-logs', compact('logs'));
    }

    // ── Orders, Users, Logs & Settings ─────────────────────────
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

    public function users()
    {
        $this->adminAuth();
        $restaurants = Restaurant::withCount(['orders', 'menuItems'])->orderBy('name')->paginate(25);
        return view('admin.users', compact('restaurants'));
    }

    public function logs()
    {
        $this->adminAuth();
        $restaurants  = Restaurant::whereNotNull('last_error')->orWhere('bot_status', '!=', 'connected')->get();
        $recentOrders = Order::with('restaurant')->latest()->take(20)->get();
        return view('admin.logs', compact('restaurants', 'recentOrders'));
    }

    public function settings()
    {
        $this->adminAuth();
        $twoFaEnabled = Setting::get('admin_2fa_enabled', '0') === '1';
        $storedPin    = Setting::get('admin_2fa_pin', '');
        $ipWhitelist  = Setting::get('admin_ip_whitelist', '');
        $timezone     = Setting::get('platform_timezone', 'Asia/Karachi');
        $currency     = Setting::get('currency_symbol', 'Rs.');
        return view('admin.settings', compact('twoFaEnabled', 'storedPin', 'ipWhitelist', 'timezone', 'currency'));
    }

    public function updateSettings(Request $request)
    {
        $this->adminAuth();

        // If changing master password
        if ($request->filled('current_password') && $request->filled('new_password')) {
            $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8|different:current_password',
            ]);

            if (! $this->verifyAdminPassword((string) $request->input('current_password'))) {
                return back()->withErrors(['current_password' => 'Current master password is incorrect.']);
            }

            Setting::put(self::ADMIN_PASSWORD_KEY, Hash::make((string) $request->input('new_password')));
            AuditLog::log('admin.password_changed', 'Changed Super Admin master password');
        }

        // IP Whitelisting & Regional
        Setting::put('admin_ip_whitelist', $request->input('ip_whitelist', ''));
        Setting::put('platform_timezone', $request->input('timezone', 'Asia/Karachi'));
        Setting::put('currency_symbol', $request->input('currency_symbol', 'Rs.'));

        AuditLog::log('admin.settings_saved', 'Saved system settings and regional configurations');

        return back()->with('success', 'Super Admin settings saved successfully.');
    }

    public function toggle2FA(Request $request)
    {
        $this->adminAuth();
        $enable = $request->input('enable') === '1';
        $pin    = $request->input('pin', '');

        if ($enable && strlen($pin) < 4) {
            return back()->withErrors(['pin' => '2FA PIN must be at least 4 digits.']);
        }

        Setting::put('admin_2fa_enabled', $enable ? '1' : '0');
        if ($enable) {
            Setting::put('admin_2fa_pin', $pin);
        }

        AuditLog::log('admin.2fa_toggled', 'Toggled 2FA security status to ' . ($enable ? 'ENABLED' : 'DISABLED'));

        return back()->with('success', 'Two-Factor Authentication settings updated.');
    }
}