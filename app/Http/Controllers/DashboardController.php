<?php
namespace App\Http\Controllers;

use App\Models\{Restaurant, Order, Category, MenuItem, Rider};
use App\Rules\SafeWebhookUrl;
use App\Support\BotControlClient;
use App\Support\CsvSanitizer;
use App\Support\WebhookUrlValidator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * Extensions accepted for menu uploads.
     *
     * The stored filename is always rebuilt from this allow-list and never from
     * the client-supplied name, so an `evil.php` (or `.svg`/`.html`) upload can
     * never be written into the web root. Previously the extension came straight
     * from `getClientOriginalExtension()` into `public/uploads/menus`, which meant
     * an authenticated owner could drop an executable PHP file under the document
     * root — remote code execution.
     */
    private const ALLOWED_MENU_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'tsv',
        'jpg', 'jpeg', 'jfif', 'jpe', 'png', 'webp', 'gif', 'bmp',
    ];

    /**
     * Content-sniffed allow-list for the `mimes:` rule. Laravel derives this from
     * the file's actual bytes (finfo), not its name, so it is the second
     * independent gate alongside ALLOWED_MENU_EXTENSIONS. `txt` is included
     * because plain CSV files are commonly detected as `text/plain`.
     */
    private const ALLOWED_MENU_MIMES = 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,webp,gif,bmp';

    // ── Login ──────────────────────────────────────────────
    public function loginForm(string $slug)
    {
        $r = Restaurant::where('id', $slug)->firstOrFail();
        return view('dashboard.login', compact('r'));
    }

    public function login(Request $request, string $slug)
    {
        $r        = Restaurant::findOrFail($slug);
        $password = (string) $request->input('password', '');

        if (! self::passwordMatches($password, $r->owner_password)) {
            return back()->withErrors(['password' => 'Wrong password']);
        }

        // Legacy rows stored the password in plaintext; upgrade to a hash the
        // first time the owner logs in successfully. This is what lets the
        // plaintext→hash migration happen without locking anyone out.
        $stored = (string) $r->owner_password;
        if (! self::isHashed($stored) || Hash::needsRehash($stored)) {
            $r->owner_password = Hash::make($password);
            $r->save();
        }

        // New session ID on privilege change (prevents session fixation).
        $request->session()->regenerate();

        session(["restaurant_{$r->id}" => true]);
        // Anchors the "Current Login Session" reporting period (reports() reads
        // this key; nothing set it before, so that filter silently fell back to
        // start-of-day).
        session(["restaurant_{$r->id}_login_time" => now()->toIso8601String()]);

        return redirect()->route('dashboard.orders', $r->id);
    }

    /**
     * True when $stored is a real password hash (bcrypt/argon) rather than a
     * legacy plaintext value. Guard for Hash::check(), which throws a
     * RuntimeException on non-bcrypt input when hashing.verify is enabled.
     */
    public static function isHashed(?string $stored): bool
    {
        $stored = (string) $stored;

        return $stored !== '' && password_get_info($stored)['algoName'] !== 'unknown';
    }

    /**
     * Verify a submitted password against either a hash (current) or a
     * plaintext value (legacy rows created before hashing was unified).
     */
    public static function passwordMatches(string $plain, ?string $stored): bool
    {
        $stored = (string) $stored;

        if ($plain === '' || $stored === '') {
            return false;
        }

        if (self::isHashed($stored)) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }

    public function logout(string $id)
    {
        session()->forget("restaurant_{$id}");
        return redirect('/');
    }

    // ── Orders page (live dashboard) ─────────────────────────
    public function orders(string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::withCount(['menuItems' => fn($q) => $q->where('is_available', true)])->findOrFail($id);

        $orders      = $r->orders()->with('items')->orderBy('created_at', 'desc')->paginate(20);
        $todayOrders = $r->todayOrders()->with('items')->get();
        $riders      = $r->riders()->get();
        $menuItems   = $r->menuItems()->get();

        $selectedOrderId = request('order_id');
        $selectedOrder   = $selectedOrderId 
            ? $orders->firstWhere('id', $selectedOrderId) 
            : ($orders->first() ?? null);

        // KPI calculations
        $liveOrders        = $todayOrders->whereIn('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery']);
        $liveOrdersCount   = $liveOrders->count();
        $todayRevenue      = (float) $todayOrders->where('status', '!=', 'cancelled')->sum('total');
        $activeRidersCount = $riders->where('is_active', true)->count();
        $totalOrdersToday  = $todayOrders->count();
        $menuItemsCount    = $r->menu_items_count ?? $menuItems->where('is_available', true)->count();

        // Status breakdown
        $statusCounts = [
            'delivered' => $todayOrders->where('status', 'delivered')->count(),
            'preparing' => $todayOrders->where('status', 'preparing')->count(),
            'confirmed' => $todayOrders->where('status', 'confirmed')->count(),
            'pending'   => $todayOrders->where('status', 'pending')->count(),
            'cancelled' => $todayOrders->where('status', 'cancelled')->count(),
        ];
        $totalStatusSum    = max(1, array_sum($statusCounts));
        $statusPercentages = array_map(fn($c) => round(($c / $totalStatusSum) * 100), $statusCounts);

        // Weekly trend (last 7 days)
        $weeklyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $dayCount = $r->orders()->whereDate('created_at', $d->toDateString())->count();
            $weeklyTrend[] = [
                'day'   => $d->format('D'),
                'date'  => $d->format('M d'),
                'count' => $dayCount,
            ];
        }

        // Top selling items
        $topSellingItems = \App\Models\OrderItem::whereHas('order', fn($q) => $q->where('restaurant_id', $r->id))
            ->selectRaw('name, sum(quantity) as total_qty')
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // Recent activity feed
        $recentActivity = $orders->take(6);

        return view('dashboard.orders', [
            'restaurant'        => $r,
            'orders'            => $orders,
            'today'             => $todayOrders,
            'liveOrders'        => $liveOrders,
            'liveOrdersCount'   => $liveOrdersCount,
            'todayRevenue'      => $todayRevenue,
            'activeRidersCount' => $activeRidersCount,
            'totalOrdersToday'  => $totalOrdersToday,
            'menuItemsCount'    => $menuItemsCount,
            'riders'            => $riders,
            'menuItems'         => $menuItems,
            'selectedOrder'     => $selectedOrder,
            'statusCounts'      => $statusCounts,
            'statusPercentages' => $statusPercentages,
            'weeklyTrend'       => $weeklyTrend,
            'topSellingItems'   => $topSellingItems,
            'recentActivity'    => $recentActivity,
            'pendingCount'      => $todayOrders->where('status', 'pending')->count(),
        ]);
    }

    // ── Live JSON Feed for Real-Time Dashboard Updates ────
    public function liveOrdersFeed(string $id)
    {
        $this->authCheck($id);
        $r      = Restaurant::findOrFail($id);
        $orders = $r->orders()->with('items')->orderBy('created_at', 'desc')->paginate(20);
        $today  = $r->todayOrders()->get();

        $activeRevenue = (float) $today->where('status', '!=', 'cancelled')->sum('total');

        return response()->json([
            'success'         => true,
            'today_count'     => $today->count(),
            'pending_count'   => $today->where('status', 'pending')->count(),
            'revenue'         => $activeRevenue,
            'active_count'    => $today->whereIn('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery'])->count(),
            'delivered_count' => $today->where('status', 'delivered')->count(),
            'latest_order_id' => $orders->first()?->id ?? 0,
        ]);
    }

    // ── Update order status & assign rider (Automated WhatsApp Notification) ──
    public function updateStatus(Request $request, string $id, Order $order)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        abort_if($order->restaurant_id !== $r->id, 403);

        // `status` was previously taken straight from the request and written to
        // an ENUM column: an arbitrary value either threw a 500 (strict mode) or
        // silently stored '' (non-strict), breaking every status filter, report
        // and tracking page afterwards.
        $validated = $request->validate([
            'status'            => ['required', 'string', Rule::in(Order::STATUSES)],
            'rider_name'        => ['nullable', 'string', 'max:100'],
            'rider_phone'       => ['nullable', 'string', 'max:32'],
            'rider_notes'       => ['nullable', 'string', 'max:500'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        $status     = $validated['status'];
        $updateData = ['status' => $status];

        foreach (['rider_name', 'rider_phone', 'rider_notes'] as $field) {
            if ($request->filled($field)) {
                $updateData[$field] = $validated[$field];
            }
        }
        if ($request->filled('estimated_minutes')) {
            $updateData['estimated_minutes'] = (int) $validated['estimated_minutes'];
        }

        $order->update($updateData);

        // Build Live Web Tracking Link
        $trackingUrl = url('/track/' . $order->tracking_code);

        // Build Rich WhatsApp Message
        $riderInfo = '';
        if ($order->rider_name || $order->rider_phone) {
            $name  = $order->rider_name ?: 'Assigned Rider';
            $phone = $order->rider_phone ? " ({$order->rider_phone})" : '';
            $riderInfo = "\n🛵 *Rider:* {$name}{$phone}";
        }

        $etaText = $order->estimated_minutes ? "\n⏱️ *Estimated Delivery:* ~{$order->estimated_minutes} mins" : "\n⏱️ *Estimated Delivery:* ~20-30 mins";

        $messages = [
            'confirmed' => "✅ *Order Confirmed!*\n\nYour order *{$order->tracking_code}* has been accepted by *{$r->name}*!\n\n📍 *Live Tracking:* {$trackingUrl}",
            'preparing' => "👨‍🍳 *Preparing Your Food!*\n\nOur kitchen is preparing your order *{$order->tracking_code}* fresh.\n\n📍 *Live Tracking:* {$trackingUrl}",
            'out_for_delivery' => "🛵 *Order Dispatched & On The Way!*\n\nYour order *{$order->tracking_code}* has been dispatched by *{$r->name}*!{$riderInfo}{$etaText}\n💰 *Total to Pay:* Rs. " . number_format($order->total, 0) . " (" . ucwords(str_replace('_', ' ', $order->payment_method ?: 'COD')) . ")\n\n📍 *Live Tracking:* {$trackingUrl}",
            'delivered' => "🎉 *Order Delivered!*\n\nYour order *{$order->tracking_code}* has been delivered. Enjoy your meal! Thank you for ordering from *{$r->name}*! 🙏",
            'cancelled' => "❌ *Order Cancelled*\n\nYour order *{$order->tracking_code}* was cancelled. Please call us directly for details.",
        ];

        if (isset($messages[$status])) {
            BotControlClient::sendMessage($order->customer_phone, $messages[$status], [
                'restaurant_id' => $r->id,
                'order_id'      => $order->id,
                'recipient'     => 'customer',
            ]);
        }

        // Live Google Sheet Webhook Push (if configured)
        //
        // Re-checked here even though the write path validates it: the value can
        // also come from a row saved before validation existed, or from the
        // GOOGLE_SHEET_WEBHOOK environment variable, neither of which passed
        // through form validation. Without this the owner-controlled URL is an
        // SSRF sink — it would happily POST order PII to cloud metadata
        // (169.254.169.254) or to the bot's own control server on 127.0.0.1:3000.
        $sheetWebhook = $r->google_sheet_webhook ?: env('GOOGLE_SHEET_WEBHOOK');
        if ($sheetWebhook) {
            $rejection = WebhookUrlValidator::validate($sheetWebhook);

            if ($rejection !== null) {
                \Log::warning('Refused to push to unsafe Google Sheet webhook', [
                    'restaurant_id' => $r->id,
                    'reason'        => $rejection,
                ]);
            } else {
                try {
                    \Illuminate\Support\Facades\Http::timeout(5)
                        // Do not follow redirects: a public URL that 302s to
                        // 127.0.0.1 would otherwise bypass the check above.
                        ->withOptions(['allow_redirects' => false])
                        ->post($sheetWebhook, [
                            'timestamp'     => now()->toIso8601String(),
                            'event'         => 'status_updated',
                            'tracking_code' => $order->tracking_code,
                            'status'        => $order->status,
                            'customer_name' => $order->customer_name,
                            'customer_phone'=> $order->customer_phone,
                            'total'         => $order->total,
                            'rider_name'    => $order->rider_name,
                            'rider_phone'   => $order->rider_phone,
                        ]);
                } catch (\Exception $e) {
                    \Log::warning('Google Sheet update push failed: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('dashboard.orders', ['id' => $r->id, 'order_id' => $order->id])
            ->with('success', "Order #{$order->tracking_code} marked as " . ucwords(str_replace('_', ' ', $status)) . "!");
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

        $this->invalidateBotCache($r);

        return back()->with('success', 'Item added!');
    }

    public function toggleItem(string $id, MenuItem $item)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($item->restaurant_id !== $r->id, 403);
        $item->update(['is_available' => !$item->is_available]);

        $this->invalidateBotCache($r);

        $statusText = $item->is_available ? 'marked In Stock' : 'marked Out of Stock';
        return back()->with('success', "Item {$item->name} {$statusText}!");
    }

    public function deleteItem(string $id, MenuItem $item)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($item->restaurant_id !== $r->id, 403);
        $item->delete();

        $this->invalidateBotCache($r);

        return back()->with('success', 'Item deleted!');
    }

    // ── Rider Management (Owner Dashboard) ─────────────────
    public function riders(string $id)
    {
        $this->authCheck($id);
        $r      = Restaurant::findOrFail($id);
        $riders = $r->riders()->get();

        return view('dashboard.riders', ['restaurant' => $r, 'riders' => $riders]);
    }

    public function storeRider(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:50',
        ]);

        $r->riders()->create([
            'name'      => trim($request->input('name')),
            'phone'     => trim($request->input('phone')),
            'is_active' => true,
        ]);

        return back()->with('success', 'Rider added successfully!');
    }

    public function toggleRider(string $id, Rider $rider)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($rider->restaurant_id !== $r->id, 403);

        $rider->update(['is_active' => !$rider->is_active]);
        return back()->with('success', 'Rider status updated!');
    }

    public function deleteRider(string $id, Rider $rider)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        abort_if($rider->restaurant_id !== $r->id, 403);

        $rider->delete();
        return back()->with('success', 'Rider deleted!');
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
        $r = Restaurant::findOrFail($id); // scoped: only the authenticated restaurant

        // Quick toggle for is_open status banner
        if ($request->has('toggle_open_only')) {
            $newState = $request->has('is_open') || $request->input('toggle_open_only') === 'open';
            $r->update(['is_open' => $newState]);
            $this->invalidateBotCache($r);
            $msg = $newState ? '🟢 Restaurant is now OPEN for orders!' : '⏸️ Restaurant is now CLOSED.';
            return back()->with('success', $msg);
        }

        // Previously this took `$request->only([...])` with no validation at all,
        // so a duplicate whatsapp_number hit the UNIQUE constraint as a 500 and
        // google_sheet_webhook was an unchecked SSRF target.
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'whatsapp_number'      => ['required', 'string', 'max:32', Rule::unique('restaurants', 'whatsapp_number')->ignore($r->id)],
            'owner_phone'          => ['nullable', 'string', 'max:32'],
            'manager_phone'        => ['nullable', 'string', 'max:32'],
            'address'              => ['nullable', 'string', 'max:500'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'delivery_areas'       => ['nullable', 'string', 'max:1000'],
            'delivery_charge'      => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'minimum_order'        => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'greeting_message'     => ['nullable', 'string', 'max:1000'],
            'hours'                => ['nullable', 'string', 'max:255'],
            'google_sheet_webhook' => ['nullable', 'string', 'max:2048', new SafeWebhookUrl],
        ], [
            'whatsapp_number.unique' => 'That WhatsApp number is already linked to another restaurant.',
        ]);

        $data = array_intersect_key($validated, array_flip([
            'name', 'whatsapp_number', 'owner_phone', 'manager_phone', 'address', 'city',
            'delivery_areas', 'delivery_charge', 'minimum_order', 'greeting_message',
            'google_sheet_webhook', 'hours',
        ]));

        $data['is_open'] = $request->has('is_open');

        $r->update($data);

        // These settings (opening state, delivery charge, minimum order) are read
        // by the bot on every message, so push the change through instead of
        // waiting on its cache.
        $this->invalidateBotCache($r);
        return back()->with('success', 'Settings saved!');
    }

    // ── Connect WhatsApp (Web QR Screen) ──────────────────
    public function connectWhatsapp(string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        return view('dashboard.connect-whatsapp', ['restaurant' => $r]);
    }

    /**
     * Same-origin proxy for the bot's pairing status and QR code.
     *
     * The connect page used to have the browser fetch `http://<host>:3000/qr-status`
     * directly, which forced the control server to be world-reachable with
     * `Access-Control-Allow-Origin: *` and no auth — anyone who could load that
     * port got a QR code they could scan to seize the WhatsApp account. The QR now
     * only ever leaves the server through this authenticated route.
     */
    public function botStatus(string $id)
    {
        $this->authCheck($id);

        $status = BotControlClient::status();

        if ($status === null) {
            return response()->json([
                'success' => false,
                'status'  => 'unreachable',
                'message' => 'The WhatsApp bot process is not running.',
            ], 503);
        }

        // One bot process serves one WhatsApp account. If it is already paired to
        // another restaurant, this owner must not see its QR (scanning it would
        // hijack that account) and must not be able to restart it out from under
        // them. The super-admin is exempt because they operate the process.
        if (! $this->botBelongsTo($status, $id)) {
            return response()->json([
                'success' => false,
                'status'  => 'linked_elsewhere',
                'message' => 'This bot process is currently linked to another restaurant. Contact support to get your own bot instance.',
            ], 409);
        }

        return response()->json([
            'success'    => true,
            'status'     => $status['status'] ?? 'unknown',
            // `qr` is the rendered data-URL image. The raw QR payload the bot also
            // held is deliberately not exposed anywhere.
            'qr'         => $status['qr'] ?? null,
            'bot_number' => $status['bot_number'] ?? null,
        ]);
    }

    /**
     * Ask the bot to drop its session and show a fresh pairing QR.
     */
    public function botRestart(string $id)
    {
        $this->authCheck($id);

        $status = BotControlClient::status();

        if ($status !== null && ! $this->botBelongsTo($status, $id)) {
            return response()->json([
                'success' => false,
                'status'  => 'linked_elsewhere',
                'message' => 'This bot process is linked to another restaurant, so it cannot be restarted from here.',
            ], 409);
        }

        $ok = BotControlClient::restart();

        return response()->json([
            'success' => $ok,
            'message' => $ok
                ? 'Bot restart requested. A fresh QR code will appear shortly.'
                : 'Could not reach the WhatsApp bot process.',
        ], $ok ? 200 : 503);
    }

    /**
     * True when the running bot is unpaired (free to claim) or already paired to
     * this restaurant. Super-admins always pass.
     *
     * @param  array<string, mixed>  $status
     */
    private function botBelongsTo(array $status, string $id): bool
    {
        if (session('admin_logged_in') === true) {
            return true;
        }

        $boundTo = $status['restaurant_id'] ?? null;

        return $boundTo === null || (int) $boundTo === (int) $id;
    }

    // ── Bulk Upload Menu via CSV / Excel ───────────────────
    public function uploadMenuCsv(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'csv_file' => ['required', 'file', 'max:20480', self::ALLOWED_MENU_MIMES],
        ], [
            'csv_file.mimes' => 'Only spreadsheet files (.csv, .xls, .xlsx) are accepted here.',
        ]);

        $file         = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();

        $stored = $this->storeMenuUpload($file, $r);
        if ($stored === null) {
            return back()->withErrors(['csv_file' => 'That file type is not allowed. Please upload a .csv, .xls or .xlsx file.']);
        }

        $items         = $this->extractMenuItemsFromFile($stored['fullPath'], $stored['extension']);
        $importedCount = $this->importItemsToDatabase($r, $items);

        // Update restaurant record with menu_file path for bot access
        $r->update([
            'menu_file'      => $stored['relativePath'],
            'menu_file_name' => $originalName,
            'menu_file_type' => in_array($stored['extension'], ['xls', 'xlsx', 'csv'], true) ? 'excel' : 'document',
        ]);
        $this->invalidateBotCache($r);

        return back()->with('success', "🎉 Successfully imported {$importedCount} menu items! All items are now categorized and visible on your menu page below.");
    }

    // ── Upload Menu File / Poster / Document (PDF, Excel, Images, Docs) ──
    public function uploadMenuFile(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'menu_file' => ['required', 'file', 'max:20480', self::ALLOWED_MENU_MIMES],
        ], [
            'menu_file.mimes' => 'Unsupported file type. Allowed: PDF, Word, Excel/CSV, or an image (JPG, PNG, WEBP, GIF).',
        ]);

        $file         = $request->file('menu_file');
        $originalName = $file->getClientOriginalName();

        $stored = $this->storeMenuUpload($file, $r);
        if ($stored === null) {
            return back()->withErrors(['menu_file' => 'That file type is not allowed. Allowed: PDF, Word, Excel/CSV, or an image.']);
        }

        $extension    = $stored['extension'];
        $relativePath = $stored['relativePath'];

        // Classify file type
        $fileType = 'document';
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'jpe', 'bmp'], true)) {
            $fileType = 'image';
        } elseif ($extension === 'pdf') {
            $fileType = 'pdf';
        } elseif (in_array($extension, ['xls', 'xlsx', 'csv', 'tsv', 'txt'], true)) {
            $fileType = 'excel';
        }

        $updateData = [
            'menu_file'      => $relativePath,
            'menu_file_name' => $originalName,
            'menu_file_type' => $fileType,
        ];

        // If it's an image, also keep menu_image updated
        if ($fileType === 'image') {
            $updateData['menu_image'] = $relativePath;
        } elseif ($fileType === 'excel') {
            // If it's an Excel/CSV file, also automatically import items into the database!
            $items = $this->extractMenuItemsFromFile($stored['fullPath'], $extension);
            $this->importItemsToDatabase($r, $items);
        }

        $r->update($updateData);
        $this->invalidateBotCache($r);

        return back()->with('success', "🎉 Menu file ({$originalName}) uploaded successfully! Items are now active on your menu.");
    }

    /**
     * Move an uploaded menu file into `public/uploads/menus` under a safe,
     * server-generated name, and drop the restaurant's previous menu file.
     *
     * Returns null when the extension is not in ALLOWED_MENU_EXTENSIONS.
     *
     * The `menu_{id}_` prefix is load-bearing: the bot locates menu files by
     * scanning this directory for that prefix (bot/src/handlers/ChatHandler.js).
     * The random suffix replaces the old `time()` suffix so filenames can't be
     * guessed or collide within the same second.
     *
     * @return array{relativePath: string, fullPath: string, extension: string}|null
     */
    private function storeMenuUpload(UploadedFile $file, Restaurant $r): ?array
    {
        // Extension comes from the client, so it is only ever *selected from* the
        // allow-list — never trusted as-is. A disallowed extension is a hard
        // reject: falling back to the content-sniffed type here would silently
        // rename `evil.php` to `.txt` and accept it, which is confusing at best.
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '') {
            // No extension supplied at all — derive one from the file's content.
            $extension = strtolower((string) $file->guessExtension());
        }

        if (! in_array($extension, self::ALLOWED_MENU_EXTENSIONS, true)) {
            return null;
        }

        $destPath = public_path('uploads/menus');
        if (! is_dir($destPath)) {
            // 0755, not 0777 — the web server never needs world-writable dirs.
            mkdir($destPath, 0755, true);
        }

        $previous = [$r->menu_file, $r->menu_image];

        $fileName = 'menu_' . $r->id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($destPath, $fileName);

        // Remove the superseded file(s). The bot picks the *first* prefix match it
        // finds while scanning the directory, so leaving stale uploads behind can
        // make it serve an old menu — and they accumulate on disk forever.
        $this->deletePreviousMenuFiles($previous, $fileName);

        return [
            'relativePath' => 'uploads/menus/' . $fileName,
            'fullPath'     => $destPath . DIRECTORY_SEPARATOR . $fileName,
            'extension'    => $extension,
        ];
    }

    /**
     * Delete previously-stored menu files, refusing anything that isn't a plain
     * filename directly inside `public/uploads/menus` — these values come from the
     * database, so they are treated as untrusted for path-traversal purposes.
     *
     * @param  array<int, string|null>  $paths
     */
    private function deletePreviousMenuFiles(array $paths, string $keepFileName): void
    {
        $menusDir = realpath(public_path('uploads/menus'));
        if ($menusDir === false) {
            return;
        }

        foreach (array_unique(array_filter($paths)) as $path) {
            $basename = basename((string) $path);

            if ($basename === '' || $basename === $keepFileName) {
                continue;
            }

            // Only ever touch files we generated, inside the menus directory.
            if ($path !== 'uploads/menus/' . $basename) {
                continue;
            }

            $target = realpath($menusDir . DIRECTORY_SEPARATOR . $basename);
            if ($target === false || ! str_starts_with($target, $menusDir . DIRECTORY_SEPARATOR) || ! is_file($target)) {
                continue;
            }

            @unlink($target);
        }
    }

    // ── Helper: Extract menu items from CSV or Excel file ──────
    private function extractMenuItemsFromFile(string $fullPath, string $extension): array
    {
        $items = [];

        if (in_array($extension, ['xlsx', 'xls'])) {
            // Use Node.js script with SheetJS to parse Excel files
            $scriptPath = base_path('bot/src/services/parse_excel_to_json.js');
            $command = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($fullPath);
            $output = shell_exec($command);

            if ($output) {
                $decoded = json_decode($output, true);
                if (is_array($decoded)) {
                    $items = $decoded;
                }
            }
        } else {
            // CSV / TSV / TXT parsing
            $handle = fopen($fullPath, 'r');
            if ($handle) {
                // Try reading header
                $header = fgetcsv($handle);
                $colMap = ['category' => 0, 'name' => 1, 'price' => 2, 'sizes' => 3, 'desc' => 4];

                if ($header) {
                    $headerLower = array_map(fn($h) => strtolower(trim((string)$h)), $header);
                    foreach ($headerLower as $idx => $col) {
                        if (str_contains($col, 'cat') || str_contains($col, 'section') || str_contains($col, 'type')) $colMap['category'] = $idx;
                        elseif (str_contains($col, 'item') || str_contains($col, 'name') || str_contains($col, 'dish') || str_contains($col, 'product')) $colMap['name'] = $idx;
                        elseif (str_contains($col, 'price') || str_contains($col, 'rate') || str_contains($col, 'rs') || str_contains($col, 'amount')) $colMap['price'] = $idx;
                        elseif (str_contains($col, 'size') || str_contains($col, 'variant') || str_contains($col, 'portion')) $colMap['sizes'] = $idx;
                        elseif (str_contains($col, 'desc') || str_contains($col, 'detail') || str_contains($col, 'info')) $colMap['desc'] = $idx;
                    }
                }

                $currentCategory = 'General';
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row) || count($row) < 2) continue;

                    $catCell   = isset($row[$colMap['category']]) ? trim((string)$row[$colMap['category']]) : '';
                    $nameCell  = isset($row[$colMap['name']]) ? trim((string)$row[$colMap['name']]) : '';
                    $priceCell = isset($row[$colMap['price']]) ? trim((string)$row[$colMap['price']]) : '';
                    $sizesCell = isset($row[$colMap['sizes']]) ? trim((string)$row[$colMap['sizes']]) : '';
                    $descCell  = isset($row[$colMap['desc']]) ? trim((string)$row[$colMap['desc']]) : '';

                    if (empty($nameCell)) continue;
                    if (!empty($catCell)) $currentCategory = $catCell;

                    $basePrice = (float) preg_replace('/[^0-9.]/', '', $priceCell);

                    $sizes = null;
                    if (!empty($sizesCell)) {
                        $parts = preg_split('/[,|\/]/', $sizesCell);
                        $parsedSizes = [];
                        foreach ($parts as $part) {
                            if (str_contains($part, ':')) {
                                [$sName, $sPrice] = explode(':', $part, 2);
                                $cleanPrice = (float) preg_replace('/[^0-9.]/', '', $sPrice);
                                if ($cleanPrice > 0) {
                                    $parsedSizes[] = [
                                        'size'  => strtoupper(trim($sName)),
                                        'price' => $cleanPrice,
                                    ];
                                }
                            }
                        }
                        if (!empty($parsedSizes)) {
                            $sizes = $parsedSizes;
                            if ($basePrice <= 0 && !empty($sizes[0]['price'])) {
                                $basePrice = $sizes[0]['price'];
                            }
                        }
                    }

                    $items[] = [
                        'category'    => $currentCategory,
                        'name'        => $nameCell,
                        'price'       => $basePrice,
                        'sizes'       => $sizes,
                        'description' => $descCell ?: null,
                    ];
                }
                fclose($handle);
            }
        }

        return $items;
    }

    // ── Helper: Save items & auto-create categories in database ─
    private function importItemsToDatabase(Restaurant $restaurant, array $items): int
    {
        if (empty($items)) return 0;

        $imported = 0;
        $categoryCache = [];

        foreach ($items as $itemData) {
            $catName = trim($itemData['category'] ?? 'General');
            if (empty($catName)) $catName = 'General';

            $itemName = trim($itemData['name'] ?? '');
            if (empty($itemName)) continue;

            $price       = (float) ($itemData['price'] ?? 0);
            $sizes       = $itemData['sizes'] ?? null;
            $description = $itemData['description'] ?? null;

            // Find or create Category
            $catKey = strtolower($catName);
            if (!isset($categoryCache[$catKey])) {
                $category = $restaurant->categories()->firstOrCreate(
                    ['name' => $catName],
                    ['sort_order' => count($categoryCache) + 1]
                );
                $categoryCache[$catKey] = $category->id;
            }
            $categoryId = $categoryCache[$catKey];

            // Create or update item
            $restaurant->menuItems()->updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'name'        => $itemName,
                ],
                [
                    'price'        => $price,
                    'sizes'        => $sizes,
                    'description'  => $description,
                    'is_available' => true,
                ]
            );

            $imported++;
        }

        return $imported;
    }

    // ── Download Sample CSV Template ───────────────────────
    public function downloadSampleCsv()
    {
        $csvContent = "Category,Item Name,Price,Sizes,Description\n" .
                      "Burgers,Zinger Burger,350,\"M:350, L:450\",Crispy fried chicken fillet with spicy mayo\n" .
                      "Burgers,Beef Burger,400,,Juicy grilled beef patty with cheese\n" .
                      "Biryani & Rice,Chicken Biryani,280,,Fragrant basmati rice with tender chicken\n" .
                      "Drinks,Mango Juice,150,\"M:150, L:250\",Fresh seasonal mango juice\n" .
                      "Drinks,Pepsi 500ml,80,,Chilled cold drink\n";

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="menu_sample_template.csv"',
        ]);
    }

    // ── Orders History (Dedicated search & archive) ────────
    public function history(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $query = $r->orders()->with('items')->latest();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function($q) use ($s) {
                $q->where('tracking_code', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate(25)->withQueryString();
        $pendingCount = $r->orders()->where('status', 'pending')->whereDate('created_at', today())->count();

        return view('dashboard.history', compact('r', 'orders', 'pendingCount'));
    }

    // ── Customers Directory (Persistent Database & Broadcasts) ───
    public function customers(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        // Auto-sync past orders into customers table if empty
        if ($r->customers()->count() === 0 && $r->orders()->count() > 0) {
            $pastCustomers = Order::where('restaurant_id', $r->id)
                ->selectRaw('customer_phone, MAX(customer_name) as name, MAX(delivery_address) as address, COUNT(*) as total_orders, SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END) as total_spent, MAX(created_at) as last_order_at')
                ->groupBy('customer_phone')
                ->get();

            foreach ($pastCustomers as $pc) {
                \App\Models\Customer::updateOrCreate(
                    ['restaurant_id' => $r->id, 'phone' => $pc->customer_phone],
                    [
                        'name'         => $pc->name ?: 'Customer',
                        'address'      => $pc->address,
                        'total_orders' => (int) $pc->total_orders,
                        'total_spent'  => (float) $pc->total_spent,
                        'tag'          => $pc->total_orders >= 5 ? 'VIP' : ($pc->total_orders >= 2 ? 'Frequent' : 'New'),
                        'last_order_at'=> $pc->last_order_at,
                    ]
                );
            }
        }

        $query = $r->customers();

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tag')) {
            $query->where('tag', $request->input('tag'));
        }

        $customers = $query->paginate(25)->withQueryString();
        $totalCustomers = $r->customers()->count();
        $vipCount = $r->customers()->where('tag', 'VIP')->count();
        $marketingOptInCount = $r->customers()->where('opt_in_marketing', true)->count();
        $pendingCount = $r->orders()->where('status', 'pending')->whereDate('created_at', today())->count();

        return view('dashboard.customers', compact(
            'r',
            'customers',
            'totalCustomers',
            'vipCount',
            'marketingOptInCount',
            'pendingCount'
        ));
    }

    // ── Send Broadcast Promotion / Deal via WhatsApp Bot ─────
    public function broadcastDeal(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'message' => 'required|string|min:5',
            'target'  => 'required|in:all,vip,frequent',
        ]);

        $query = $r->customers()->where('opt_in_marketing', true);
        if ($request->target === 'vip') {
            $query->where('tag', 'VIP');
        } elseif ($request->target === 'frequent') {
            $query->whereIn('tag', ['VIP', 'Frequent']);
        }

        $targetCustomers = $query->get();
        $dealMessage = trim($request->input('message'));
        $sentCount = 0;

        foreach ($targetCustomers as $c) {
            $sent = BotControlClient::sendMessage(
                $c->phone,
                "🎉 *Special Offer from {$r->name}!*\n\n{$dealMessage}\n\n_Reply *menu* anytime to order!_",
                ['restaurant_id' => $r->id, 'customer_id' => $c->id, 'recipient' => 'broadcast']
            );

            // Only count what actually went out — the old code incremented even
            // when the send failed, so the owner was told "dispatched to 40" for
            // a bot that was offline.
            if ($sent) {
                $sentCount++;
            }
        }

        if ($sentCount === 0 && $targetCustomers->isNotEmpty()) {
            return back()->with('error', 'Nothing was sent — the WhatsApp bot is not reachable. Connect it and try again.');
        }

        return back()->with('success', "🚀 Promotional deal dispatched to {$sentCount} customers via WhatsApp bot!");
    }

    // ── Export Customers CSV ─────────────────────────────────
    public function exportCustomersCsv(string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $customers = $r->customers()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_' . $r->id . '_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Customer Name', 'Phone Number', 'Delivery Address', 'Total Orders', 'Total Spent (PKR)', 'Customer Tag', 'Marketing Opt-In', 'Last Order Date']);

            foreach ($customers as $c) {
                // Name/address/phone come from WhatsApp, so they are attacker
                // controlled — see App\Support\CsvSanitizer.
                fputcsv($file, CsvSanitizer::row([
                    $c->name ?: 'Customer',
                    $c->phone,
                    $c->address ?: 'N/A',
                    $c->total_orders,
                    $c->total_spent,
                    $c->tag,
                    $c->opt_in_marketing ? 'Yes' : 'No',
                    $c->last_order_at ? $c->last_order_at->format('Y-m-d H:i:s') : 'N/A',
                ]));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Reports & Analytics (Daily & Session-Based) ─────────
    public function reports(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $period = $request->input('period', 'today'); // 'session', 'today', 'yesterday', 'this_week', 'this_month', 'all'
        $sessionLoginTime = session("restaurant_{$r->id}_login_time", now()->startOfDay()->toIso8601String());

        $query = $r->orders()->with('items');

        switch ($period) {
            case 'session':
                $query->where('created_at', '>=', $sessionLoginTime);
                $periodLabel = 'Current Login Session (since ' . \Carbon\Carbon::parse($sessionLoginTime)->format('h:i A') . ')';
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                $periodLabel = 'Yesterday (' . today()->subDay()->format('d M Y') . ')';
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                $periodLabel = 'This Week (' . now()->startOfWeek()->format('d M') . ' – ' . now()->endOfWeek()->format('d M') . ')';
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $periodLabel = 'This Month (' . now()->format('F Y') . ')';
                break;
            case 'all':
                $periodLabel = 'All-Time Records';
                break;
            case 'today':
            default:
                $query->whereDate('created_at', today());
                $periodLabel = 'Today (' . today()->format('d M Y') . ')';
                break;
        }

        $orders = $query->orderBy('created_at', 'desc')->get();
        $delivered = $orders->where('status', 'delivered');
        $cancelled = $orders->where('status', 'cancelled');
        $foodRevenue = (float) $orders->where('status', '!=', 'cancelled')->sum('subtotal');
        $deliveryFees = (float) $orders->where('status', '!=', 'cancelled')->sum('delivery_charge');
        $netTotalRevenue = (float) $orders->where('status', '!=', 'cancelled')->sum('total');

        // 7-day revenue trend for chart
        $chartLabels = [];
        $chartRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $chartRevenue[] = (float) $r->orders()->whereDate('created_at', $date)->where('status', '!=', 'cancelled')->sum('total');
        }

        // Top ordered items in selected period
        $topItems = \App\Models\OrderItem::whereIn('order_id', $orders->pluck('id'))
            ->selectRaw('name, COUNT(*) as order_count, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('name')
            ->orderByDesc('total_qty')
            ->take(8)
            ->get();

        $pendingCount = $r->orders()->where('status', 'pending')->whereDate('created_at', today())->count();

        return view('dashboard.reports', compact(
            'r',
            'orders',
            'delivered',
            'cancelled',
            'foodRevenue',
            'deliveryFees',
            'netTotalRevenue',
            'period',
            'periodLabel',
            'sessionLoginTime',
            'chartLabels',
            'chartRevenue',
            'topItems',
            'pendingCount'
        ));
    }

    // ── Export Daily / Session Sales Report to CSV ──────────
    public function exportSalesReportCsv(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $period = $request->input('period', 'today');
        $sessionLoginTime = session("restaurant_{$r->id}_login_time", now()->startOfDay()->toIso8601String());

        $query = $r->orders()->with('items');

        switch ($period) {
            case 'session':
                $query->where('created_at', '>=', $sessionLoginTime);
                $fileSuffix = 'session_' . date('Ymd_His');
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                $fileSuffix = 'yesterday_' . today()->subDay()->format('Ymd');
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                $fileSuffix = 'week_' . now()->format('Y_W');
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $fileSuffix = 'month_' . now()->format('Y_m');
                break;
            case 'all':
                $fileSuffix = 'all_time_' . date('Ymd');
                break;
            case 'today':
            default:
                $query->whereDate('created_at', today());
                $fileSuffix = 'daily_' . today()->format('Ymd');
                break;
        }

        $orders = $query->orderBy('created_at', 'asc')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_report_' . $r->id . '_' . $fileSuffix . '.csv"',
        ];

        $callback = function () use ($orders, $r) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Tracking Code', 'Placed Date', 'Placed Time', 'Customer Name', 'Customer Phone', 'Delivery Address', 'Items Count', 'Food Subtotal (PKR)', 'Delivery Fee (PKR)', 'Total Amount (PKR)', 'Payment Method', 'Rider Name', 'Order Status']);

            foreach ($orders as $o) {
                // Customer name, address and rider name are attacker controlled.
                fputcsv($file, CsvSanitizer::row([
                    $o->id,
                    $o->tracking_code,
                    $o->created_at->format('Y-m-d'),
                    $o->created_at->format('h:i A'),
                    $o->customer_name ?: 'Customer',
                    $o->customer_phone,
                    $o->delivery_address ?: 'N/A',
                    $o->items->count(),
                    $o->subtotal,
                    $o->delivery_charge,
                    $o->total,
                    ucwords(str_replace('_', ' ', $o->payment_method ?: 'COD')),
                    $o->rider_name ?: 'Unassigned',
                    ucfirst(str_replace('_', ' ', $o->status)),
                ]));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Instant Bot Cache Invalidation ─────────────────────
    private function invalidateBotCache(Restaurant $r): void
    {
        BotControlClient::invalidateCache($r->id, $r->whatsapp_number);
    }

    // ── Auth helper (Access Isolation enforced at query level) ─
    private function authCheck(string $id): void
    {
        $isSuperAdmin = session('admin_logged_in') === true;
        $isOwner      = session("restaurant_{$id}") === true;

        // Not logged in → redirect to unified login landing page
        if (! $isSuperAdmin && ! $isOwner) {
            redirect('/')->throwResponse();
        }

        // 2. Restaurant must exist in the database
        $r = \App\Models\Restaurant::find($id);
        abort_if(!$r, 404, 'Restaurant not found.');

        // 3. If standard restaurant owner login (not super admin), block if deactivated
        if (!$isSuperAdmin) {
            abort_if(!$r->is_active, 403, 'This restaurant account has been deactivated. Please contact the platform admin.');
        }
    }
}