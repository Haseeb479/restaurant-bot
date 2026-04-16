<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function handle(Request $request)
    {
        $message = strtolower(trim($request->input('message')));
        $from    = $request->input('from');

        // Simple menu logic — expand as needed
        if (in_array($message, ['hi', 'hello', 'start'])) {
            $reply = "Welcome to our Restaurant!\n\nReply with:\n1. Menu\n2. Place Order\n3. Track Order";
        } elseif ($message === '1' || $message === 'menu') {
            $reply = "Our Menu\n\nPizza - Rs. 800\nBurger - Rs. 400\nPasta - Rs. 600\n\nReply with item name to order!";
        } elseif ($message === '2' || str_contains($message, 'order')) {
            $reply = "Please tell us what you'd like to order and your address!";
        } else {
            $reply = "Sorry, I didn't understand that. Type 'hi' to see the menu.";
        }

        return response()->json(['reply' => $reply]);
    }

    // Notify admin about new order from WhatsApp bot
    public function notifyAdminOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_phone' => 'required|string',
            'restaurant_id' => 'required|integer',
            'restaurant_name' => 'required|string',
            'items' => 'required|array',
            'total' => 'required|numeric',
            'address' => 'nullable|string',
            'timestamp' => 'required|string'
        ]);

        try {
            $restaurant = Restaurant::find($validated['restaurant_id']);

            // Log order notification
            Log::info("New order from {$validated['customer_phone']} at {$restaurant->name}");

            // Send email to restaurant admin
            if ($restaurant && $restaurant->owner_email) {
                try {
                    $order_summary = implode(', ', $validated['items']);
                    
                    $adminMessage = "
                        New Order Alert!
                        
                        Customer Phone: {$validated['customer_phone']}
                        Restaurant: {$validated['restaurant_name']}
                        Items: {$order_summary}
                        Total: Rs. {$validated['total']}
                        Address: {$validated['address']}
                        Time: {$validated['timestamp']}
                        
                        Login to your dashboard to confirm this order.
                    ";

                    Mail::send([], [], function ($message) use ($restaurant, $adminMessage) {
                        $message->to($restaurant->owner_email)
                            ->subject('New WhatsApp Order - ' . $restaurant->name)
                            ->text($adminMessage);
                    });

                    Log::info("Email notification sent to {$restaurant->owner_email}");
                } catch (\Exception $e) {
                    Log::warning("Could not send email: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Admin notified about new order',
                'restaurant' => $restaurant->name ?? 'Unknown'
            ]);

        } catch (\Exception $e) {
            Log::error("Error notifying admin: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}