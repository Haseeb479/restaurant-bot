<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\BotControlClient;
use App\Support\BotEvolutionClient;
use Illuminate\Http\Request;

class MobileCustomerController extends Controller
{
    /**
     * List customers with search, order counts, and total spending.
     */
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');
        $search = trim((string) $request->query('search', ''));

        $query = $restaurant->customers()->orderBy('last_order_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(25);

        return response()->json([
            'success'   => true,
            'customers' => $customers->items(),
            'pagination'=> [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }

    /**
     * View customer profile and past orders.
     */
    public function show(Request $request, $customerId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $customer = $restaurant->customers()->find($customerId);

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $orders = $restaurant->orders()
            ->where('customer_phone', $customer->phone)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'success'  => true,
            'customer' => $customer,
            'orders'   => $orders,
        ]);
    }

    /**
     * Send WhatsApp Deal Broadcast to customer audience (with optional deal image).
     */
    public function broadcast(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $request->validate([
            'message'    => 'required|string|min:4',
            'audience'   => 'nullable|string|in:all,last30',
            'deal_image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $audience = $request->input('audience', 'all');
        $query = $restaurant->customers()->where('opt_in_marketing', true);

        if ($audience === 'last30') {
            $query->where('last_order_at', '>=', now()->subDays(30));
        }

        $targetCustomers = $query->take(50)->get();

        if ($targetCustomers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No customers found in selected audience.',
            ], 422);
        }

        // Handle deal image upload
        $imagePath = null;
        if ($request->hasFile('deal_image')) {
            $imageFile = $request->file('deal_image');
            $imageName = 'broadcast_' . $restaurant->id . '_' . bin2hex(random_bytes(6)) . '.' . $imageFile->getClientOriginalExtension();
            $destPath = public_path('uploads/broadcasts');
            if (!is_dir($destPath)) {
                @mkdir($destPath, 0777, true);
            }
            $imageFile->move($destPath, $imageName);
            $imagePath = $destPath . DIRECTORY_SEPARATOR . $imageName;
        }

        $baseMessage = trim($request->input('message'));
        $sentCount = 0;

        foreach ($targetCustomers as $c) {
            $personalized = str_replace('{name}', $c->name ?: 'Valued Customer', $baseMessage);
            $fullText = "🎉 *Special Offer from {$restaurant->name}!*\n\n{$personalized}\n\n_Reply *menu* anytime to place your order!_";
            $sent = false;

            if ($imagePath && file_exists($imagePath)) {
                $sent = BotEvolutionClient::sendMedia($restaurant, $c->phone, $imagePath, $fullText, [
                    'restaurant_id' => $restaurant->id,
                    'customer_id'   => $c->id,
                    'recipient'     => 'broadcast',
                ]);
            }

            if (!$sent) {
                $sent = BotEvolutionClient::sendMessage($restaurant, $c->phone, $fullText, [
                    'restaurant_id' => $restaurant->id,
                    'customer_id'   => $c->id,
                    'recipient'     => 'broadcast',
                ]);
            }

            if (!$sent) {
                $sent = BotControlClient::sendMessage(
                    $c->phone,
                    $fullText,
                    ['restaurant_id' => $restaurant->id, 'customer_id' => $c->id, 'recipient' => 'broadcast']
                );
            }

            if ($sent) {
                $sentCount++;
                usleep(300_000); // 300ms anti-spam rate limiting
            }
        }

        return response()->json([
            'success'    => true,
            'sent_count' => $sentCount,
            'total'      => $targetCustomers->count(),
            'message'    => "Broadcast campaign queued: sent to {$sentCount} customers successfully.",
        ]);
    }
}
