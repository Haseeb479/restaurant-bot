<?php
namespace App\Services;

use App\Models\{Restaurant, Conversation, Order};
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(private WhatsAppService $wa) {}

    public function placeOrder(Restaurant $r, Conversation $conv): Order
    {
        $subtotal = $conv->cartTotal();
        $delivery = (float) $r->delivery_charge;
        $total    = $subtotal + $delivery;

        // Save order to database
        $order = Order::create([
            'restaurant_id'   => $r->id,
            'conversation_id' => $conv->id,
            'customer_phone'  => $conv->customer_phone,
            'customer_name'   => $conv->customer_name,
            'customer_address'=> $conv->customer_address,
            'items'           => $conv->cart,
            'subtotal'        => $subtotal,
            'delivery_charge' => $delivery,
            'total'           => $total,
            'payment_method'  => $conv->payment_method ?? 'cod',
            'status'          => 'new',
        ]);

        $payLabel = match($order->payment_method) {
            'jazzcash'  => 'JazzCash',
            'easypaisa' => 'EasyPaisa',
            default     => 'Cash on Delivery',
        };

        // ── Confirm to CUSTOMER ────────────────────────────
        $this->wa->sendText($r, $conv->customer_phone,
            "✅ *Order #{$order->id} Confirmed!*\n\n" .
            "{$conv->cartSummary()}\n\n" .
            "─────────────\n" .
            "Total: *Rs." . number_format($total, 0) . "*\n" .
            "Payment: {$payLabel}\n\n" .
            "🕐 Your order is being prepared!\n" .
            "We'll deliver to: {$conv->customer_address}\n\n" .
            "Thank you for ordering from *{$r->name}*! 🙏"
        );

        // ── Notify RESTAURANT OWNER ────────────────────────
        $deliveryLine = $delivery > 0
            ? "\nDelivery: Rs." . number_format($delivery, 0)
            : '';

        $this->wa->sendText($r, $r->owner_phone,
            "🔔 *NEW ORDER #{$order->id}*\n\n" .
            "👤 {$conv->customer_name} ({$conv->customer_phone})\n" .
            "📍 {$conv->customer_address}\n\n" .
            "{$conv->cartSummary()}\n\n" .
            "─────────────\n" .
            "Subtotal: Rs." . number_format($subtotal, 0) .
            $deliveryLine . "\n" .
            "*TOTAL: Rs." . number_format($total, 0) . "*\n" .
            "Payment: {$payLabel}\n\n" .
            "Reply to this message or go to your dashboard to update status."
        );

        // Reset conversation for next order
        $conv->clearCart();

        Log::info("Order placed", ['order_id' => $order->id, 'restaurant' => $r->name]);

        return $order;
    }
}
