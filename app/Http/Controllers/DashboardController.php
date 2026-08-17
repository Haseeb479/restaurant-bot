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

    // ── Update order status & assign rider ─────────────────
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

        $messages = [
            'confirmed' => "✅ *Order Confirmed!*\n\nYour order *{$order->tracking_code}* has been accepted by *{$r->name}*!\n\n📍 *Live Tracking:* {$trackingUrl}",
            'preparing' => "👨‍🍳 *Preparing Your Food!*\n\nOur kitchen is currently preparing your order *{$order->tracking_code}*.\n\n📍 *Live Tracking:* {$trackingUrl}",
            'out_for_delivery' => "🛵 *Order Out for Delivery!*\n\nYour order *{$order->tracking_code}* is on its way!{$riderInfo}\n💰 *Total to Pay:* Rs. " . number_format($order->total, 0) . " ({$order->payment_method})\n\n📍 *Live Tracking:* {$trackingUrl}",
            'delivered' => "🎉 *Order Delivered!*\n\nYour order *{$order->tracking_code}* has been delivered. Enjoy your meal! Thank you for ordering from *{$r->name}*! 🙏",
            'cancelled' => "❌ *Order Cancelled*\n\nYour order *{$order->tracking_code}* was cancelled. Please call us directly for details.",
        ];

        if (isset($messages[$status])) {
            try {
                \Illuminate\Support\Facades\Http::timeout(5)
                    ->post(config('app.bot_internal_api', 'http://localhost:3000') . '/send-message', [
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

        return back()->with('success', 'Order status & rider updated!');
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
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Could not read CSV file.']);
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The uploaded file is empty.']);
        }

        $imported = 0;
        $categoryCache = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3 || empty(trim($row[0])) || empty(trim($row[1]))) {
                continue; // skip empty or invalid rows
            }

            $categoryName = trim($row[0]);
            $itemName     = trim($row[1]);
            $basePrice    = (float) preg_replace('/[^0-9.]/', '', $row[2] ?? 0);
            $sizesRaw     = trim($row[3] ?? '');
            $description  = trim($row[4] ?? '');

            // Find or create Category
            if (!isset($categoryCache[strtolower($categoryName)])) {
                $category = $r->categories()->firstOrCreate(
                    ['name' => $categoryName],
                    ['sort_order' => count($categoryCache) + 1]
                );
                $categoryCache[strtolower($categoryName)] = $category->id;
            }
            $categoryId = $categoryCache[strtolower($categoryName)];

            // Parse Sizes (e.g. "M:150, L:250" or "Small:200 / Large:350")
            $sizes = null;
            if (!empty($sizesRaw)) {
                $parts = preg_split('/[,|\/]/', $sizesRaw);
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

            // Create or update item
            $r->menuItems()->updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'name'        => $itemName,
                ],
                [
                    'price'        => $basePrice,
                    'sizes'        => $sizes,
                    'description'  => $description ?: null,
                    'is_available' => true,
                ]
            );

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "🎉 Successfully imported {$imported} menu items from CSV!");
    }

    // ── Upload Menu File / Poster / Document (PDF, Excel, Images, Docs) ──
    public function uploadMenuFile(Request $request, string $id)
    {
        $this->authCheck($id);
        $r = Restaurant::findOrFail($id);

        $request->validate([
            'menu_file' => 'required|file|mimes:jpeg,png,jpg,webp,gif,pdf,xls,xlsx,csv,doc,docx|max:20480',
        ]);

        $file         = $request->file('menu_file');
        $extension    = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $fileName     = 'menu_' . $r->id . '_' . time() . '.' . $extension;

        // Classify file type
        $fileType = 'document';
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
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

        $updateData = [
            'menu_file'      => $relativePath,
            'menu_file_name' => $originalName,
            'menu_file_type' => $fileType,
        ];

        // If it's an image, also keep menu_image updated
        if ($fileType === 'image') {
            $updateData['menu_image'] = $relativePath;
        }

        $r->update($updateData);
        TenantResolver::clearCache($r);

        return back()->with('success', "🎉 Menu document ({$originalName}) uploaded! The bot will automatically share this file with customers when they ask for the menu.");
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