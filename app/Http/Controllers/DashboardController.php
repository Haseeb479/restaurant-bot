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
        $orders = $r->orders()->with('items')->orderBy('created_at', 'desc')->paginate(20);
        $today  = $r->todayOrders()->get();

        return view('dashboard.orders', ['restaurant' => $r, 'orders' => $orders, 'today' => $today]);
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

        $status = $request->input('status');
        $updateData = ['status' => $status];

        if ($request->filled('rider_name')) {
            $updateData['rider_name'] = $request->input('rider_name');
        }
        if ($request->filled('rider_phone')) {
            $updateData['rider_phone'] = $request->input('rider_phone');
        }
        if ($request->filled('rider_notes')) {
            $updateData['rider_notes'] = $request->input('rider_notes');
        }
        if ($request->filled('estimated_minutes')) {
            $updateData['estimated_minutes'] = (int) $request->input('estimated_minutes');
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
            try {
                \Illuminate\Support\Facades\Http::timeout(5)
                    ->post(config('app.bot_internal_api', 'http://127.0.0.1:3000') . '/send-message', [
                        'to'      => $order->customer_phone,
                        'message' => $messages[$status],
                    ]);
            } catch (\Exception $e) {
                \Log::warning('Could not notify customer via WhatsApp: ' . $e->getMessage());
            }
        }

        // Live Google Sheet Webhook Push (if configured)
        $sheetWebhook = $r->google_sheet_webhook ?: env('GOOGLE_SHEET_WEBHOOK');
        if ($sheetWebhook) {
            try {
                \Illuminate\Support\Facades\Http::timeout(5)->post($sheetWebhook, [
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

        return back()->with('success', "Order #{$order->id} status updated to " . ucwords(str_replace('_', ' ', $status)) . "!");
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
        $data = $request->only([
            'name', 'whatsapp_number', 'owner_phone', 'manager_phone', 'address', 'city',
            'delivery_areas', 'delivery_charge', 'minimum_order', 'greeting_message', 'google_sheet_webhook',
        ]);
        $data['is_open'] = $request->has('is_open');

        $r->update($data);
        TenantResolver::clearCache($r);
        return back()->with('success', 'Settings saved!');
    }

    // ── Connect WhatsApp (Web QR Screen) ──────────────────
    public function connectWhatsapp(string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);
        return view('dashboard.connect-whatsapp', ['restaurant' => $r]);
    }

    // ── Bulk Upload Menu via CSV / Excel ───────────────────
    public function uploadMenuCsv(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'csv_file' => 'required|file|max:20480',
        ]);

        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        // Save a copy to public/uploads/menus
        $destPath = public_path('uploads/menus');
        if (!file_exists($destPath)) {
            mkdir($destPath, 0777, true);
        }
        $savedFileName = 'menu_' . $r->id . '_' . time() . '.' . $extension;
        $file->move($destPath, $savedFileName);
        $savedRelativePath = 'uploads/menus/' . $savedFileName;
        $fullPath = public_path($savedRelativePath);

        $items = $this->extractMenuItemsFromFile($fullPath, $extension);
        $importedCount = $this->importItemsToDatabase($r, $items);

        // Update restaurant record with menu_file path for bot access
        $r->update([
            'menu_file'      => $savedRelativePath,
            'menu_file_name' => $originalName,
            'menu_file_type' => in_array($extension, ['xls', 'xlsx', 'csv']) ? 'excel' : 'document',
        ]);
        TenantResolver::clearCache($r);

        return back()->with('success', "🎉 Successfully imported {$importedCount} menu items! All items are now categorized and visible on your menu page below.");
    }

    // ── Upload Menu File / Poster / Document (PDF, Excel, Images, Docs) ──
    public function uploadMenuFile(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'menu_file' => 'required|file|max:20480',
        ]);

        $file         = $request->file('menu_file');
        $extension    = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $fileName     = 'menu_' . $r->id . '_' . time() . '.' . $extension;

        // Classify file type
        $fileType = 'document';
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif'])) {
            $fileType = 'image';
        } elseif ($extension === 'pdf') {
            $fileType = 'pdf';
        } elseif (in_array($extension, ['xls', 'xlsx', 'csv'])) {
            $fileType = 'excel';
        }

        $destPath = public_path('uploads/menus');
        if (!file_exists($destPath)) {
            mkdir($destPath, 0777, true);
        }

        $file->move($destPath, $fileName);
        $relativePath = 'uploads/menus/' . $fileName;
        $fullPath = public_path($relativePath);

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
            $items = $this->extractMenuItemsFromFile($fullPath, $extension);
            $this->importItemsToDatabase($r, $items);
        }

        $r->update($updateData);
        TenantResolver::clearCache($r);

        return back()->with('success', "🎉 Menu file ({$originalName}) uploaded successfully! Items are now active on your menu.");
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

    // ── Auth helper ────────────────────────────────────────
    private function authCheck(string $id): void
    {
        abort_unless(session("restaurant_{$id}"), 403, 'Please login first.');
    }
}