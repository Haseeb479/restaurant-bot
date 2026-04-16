<?php
namespace App\Services;

use App\Models\{Restaurant, Conversation, MenuItem};

/**
 * BotEngine — the brain of the entire product.
 *
 * Every incoming WhatsApp message flows through here.
 * State machine: greeting → name → browsing → cart → address → payment → confirm → done
 *
 * States explained:
 *   greeting  = First message, bot says hello, asks for name
 *   name      = Waiting for customer to type their name
 *   browsing  = Menu is shown, waiting for customer to pick items
 *   cart      = Customer added item(s), offering more/checkout/view cart
 *   address   = Asking for delivery address
 *   payment   = Asking COD or JazzCash
 *   confirm   = Showing order summary, asking to confirm
 *   done      = Order placed, reset for next session
 */
class BotEngine
{
    public function __construct(
        private WhatsAppService $wa,
        private OrderService    $orderService,
    ) {}

    // ── Entry point — called from WebhookController ────────
    public function handle(
        Restaurant $restaurant,
        string     $customerPhone,
        string     $text,
        array      $rawMessage
    ): void {
        // Always reset if restaurant is closed
        if (!$restaurant->is_open) {
            $this->wa->sendText($restaurant, $customerPhone,
                "Apologies, *{$restaurant->name}* is currently closed. Please try again later! 🙏"
            );
            return;
        }

        // Get or create this customer's conversation
        $conv = Conversation::firstOrCreate(
            ['restaurant_id' => $restaurant->id, 'customer_phone' => $customerPhone],
            ['state' => 'greeting', 'cart' => []]
        );
        $conv->update(['last_message_at' => now()]);

        // Global commands — work from any state
        $lower = strtolower(trim($text));
        if (in_array($lower, ['menu', 'مینو', '0', 'start', 'hello', 'hi', 'salam'])) {
            $this->sendMenu($restaurant, $conv);
            return;
        }
        if ($lower === 'cancel') {
            $conv->clearCart();
            $this->wa->sendText($restaurant, $customerPhone,
                "Order cancelled. Type *menu* or *hi* to start again. 😊"
            );
            return;
        }

        // Route to state handler
        match ($conv->state) {
            'greeting' => $this->handleGreeting($restaurant, $conv, $text),
            'name'     => $this->handleName($restaurant, $conv, $text),
            'browsing' => $this->handleBrowsing($restaurant, $conv, $text, $rawMessage),
            'cart'     => $this->handleCart($restaurant, $conv, $text, $rawMessage),
            'address'  => $this->handleAddress($restaurant, $conv, $text),
            'payment'  => $this->handlePayment($restaurant, $conv, $text, $rawMessage),
            'confirm'  => $this->handleConfirm($restaurant, $conv, $text, $rawMessage),
            default    => $this->sendMenu($restaurant, $conv),
        };
    }

    // ── State: greeting ────────────────────────────────────
    private function handleGreeting(Restaurant $r, Conversation $conv, string $text): void
    {
        $this->wa->sendText($r, $conv->customer_phone,
            "{$r->greeting_message}\n\n" .
            "Welcome to *{$r->name}*! 🍽️\n\n" .
            "Please tell us your *name* to get started:"
        );
        $conv->update(['state' => 'name']);
    }

    // ── State: name ────────────────────────────────────────
    private function handleName(Restaurant $r, Conversation $conv, string $text): void
    {
        $name = ucwords(trim($text));
        if (strlen($name) < 2 || strlen($name) > 50) {
            $this->wa->sendText($r, $conv->customer_phone, "Please enter your real name:");
            return;
        }
        $conv->update(['customer_name' => $name, 'state' => 'browsing']);
        $this->sendMenu($r, $conv, "Nice to meet you, *{$name}*! 😊 Here's our menu:");
    }

    // ── Send the interactive menu list ────────────────────
    private function sendMenu(Restaurant $r, Conversation $conv, string $intro = ''): void
    {
        $categories = $r->categories()->with('items')->where('is_active', true)->get();
        $sections   = [];

        foreach ($categories as $cat) {
            $rows = [];
            foreach ($cat->items as $item) {
                $rows[] = [
                    'id'          => "item_{$item->id}",
                    'title'       => mb_substr($item->name, 0, 24),           // WA limit: 24 chars
                    'description' => 'Rs. ' . number_format($item->price, 0), // WA limit: 72 chars
                ];
            }
            if (!empty($rows)) {
                $sections[] = ['title' => mb_substr($cat->name, 0, 24), 'rows' => $rows];
            }
        }

        if (empty($sections)) {
            $this->wa->sendText($r, $conv->customer_phone,
                "Sorry, our menu is being updated. Please call us directly!"
            );
            return;
        }

        $cartInfo = count($conv->cart ?? []) > 0
            ? "\n\n🛒 Cart: " . count($conv->cart) . " item(s) — Rs." . number_format($conv->cartTotal(), 0)
            : '';

        $this->wa->sendList(
            r:          $r,
            to:         $conv->customer_phone,
            header:     $r->name,
            body:       ($intro ?: "Select items to add to your cart:") . $cartInfo,
            footer:     'Reply *cart* to view | *cancel* to reset',
            buttonText: 'View Menu',
            sections:   $sections,
        );
        $conv->update(['state' => 'browsing']);
    }

    // ── State: browsing — customer picks from menu ─────────
    private function handleBrowsing(Restaurant $r, Conversation $conv, string $text, array $raw): void
    {
        // Handle list reply (interactive selection)
        if (isset($raw['interactive']['list_reply'])) {
            $itemId = $raw['interactive']['list_reply']['id'];         // "item_5"
            $numId  = (int) str_replace('item_', '', $itemId);
            $item   = MenuItem::where('id', $numId)
                ->where('restaurant_id', $r->id)
                ->where('is_available', true)
                ->first();

            if (!$item) {
                $this->wa->sendText($r, $conv->customer_phone, "Item not available. Please choose another.");
                return;
            }

            $conv->addToCart($item);
            $total = $conv->cartTotal();

            $this->wa->sendButtons($r, $conv->customer_phone,
                body: "✅ *{$item->name}* added!\n\n" .
                      "🛒 Cart total: Rs." . number_format($total, 0),
                buttons: [
                    ['id' => 'btn_more',     'title' => 'Add More Items'],
                    ['id' => 'btn_cart',     'title' => 'View Cart'],
                    ['id' => 'btn_checkout', 'title' => 'Checkout'],
                ],
            );
            $conv->update(['state' => 'cart']);
            return;
        }

        // Text shortcuts
        $lower = strtolower(trim($text));
        if ($lower === 'cart') { $this->showCart($r, $conv); return; }

        $this->wa->sendText($r, $conv->customer_phone,
            "Please tap *View Menu* to select items, or type *menu* to see it again."
        );
    }

    // ── State: cart — after adding item ───────────────────
    private function handleCart(Restaurant $r, Conversation $conv, string $text, array $raw): void
    {
        // Handle button replies
        if (isset($raw['interactive']['button_reply'])) {
            $btnId = $raw['interactive']['button_reply']['id'];
            match ($btnId) {
                'btn_more'     => $this->sendMenu($r, $conv),
                'btn_cart'     => $this->showCart($r, $conv),
                'btn_checkout' => $this->startCheckout($r, $conv),
                default        => $this->sendMenu($r, $conv),
            };
            return;
        }

        $lower = strtolower(trim($text));
        if ($lower === 'cart')     { $this->showCart($r, $conv); return; }
        if ($lower === 'checkout') { $this->startCheckout($r, $conv); return; }

        $this->sendMenu($r, $conv);
    }

    // ── Show cart contents ────────────────────────────────
    private function showCart(Restaurant $r, Conversation $conv): void
    {
        if (empty($conv->cart)) {
            $this->wa->sendText($r, $conv->customer_phone, "Your cart is empty! Type *menu* to add items.");
            return;
        }

        $summary = $conv->cartSummary();
        $total   = $conv->cartTotal();

        $this->wa->sendButtons($r, $conv->customer_phone,
            body: "🛒 *Your Cart:*\n\n{$summary}\n\n" .
                  "─────────────\n" .
                  "*Subtotal: Rs." . number_format($total, 0) . "*",
            buttons: [
                ['id' => 'btn_more',     'title' => 'Add More'],
                ['id' => 'btn_checkout', 'title' => 'Checkout'],
            ],
            footer: 'Type *cancel* to clear cart',
        );
        $conv->update(['state' => 'cart']);
    }

    // ── Start checkout — ask for address ──────────────────
    private function startCheckout(Restaurant $r, Conversation $conv): void
    {
        if (empty($conv->cart)) {
            $this->wa->sendText($r, $conv->customer_phone, "Cart is empty. Type *menu* to add items.");
            return;
        }

        $minOrder = (float) $r->minimum_order;
        if ($minOrder > 0 && $conv->cartTotal() < $minOrder) {
            $this->wa->sendText($r, $conv->customer_phone,
                "Minimum order is Rs." . number_format($minOrder, 0) .
                ". Please add more items. Type *menu* to continue."
            );
            return;
        }

        $this->wa->sendText($r, $conv->customer_phone,
            "📍 Please share your *delivery address*:\n\n" .
            "Example: House 12, Street 4, Satellite Town, Bahawalpur"
        );
        $conv->update(['state' => 'address']);
    }

    // ── State: address ────────────────────────────────────
    private function handleAddress(Restaurant $r, Conversation $conv, string $text): void
    {
        if (strlen(trim($text)) < 10) {
            $this->wa->sendText($r, $conv->customer_phone,
                "Please enter your full address (at least 10 characters):"
            );
            return;
        }

        $conv->update(['customer_address' => trim($text), 'state' => 'payment']);

        $this->wa->sendButtons($r, $conv->customer_phone,
            body: "💳 *Choose payment method:*",
            buttons: [
                ['id' => 'pay_cod',       'title' => 'Cash on Delivery'],
                ['id' => 'pay_jazzcash',  'title' => 'JazzCash'],
                ['id' => 'pay_easypaisa', 'title' => 'EasyPaisa'],
            ],
        );
    }

    // ── State: payment ────────────────────────────────────
    private function handlePayment(Restaurant $r, Conversation $conv, string $text, array $raw): void
    {
        $method = null;

        if (isset($raw['interactive']['button_reply'])) {
            $btnId  = $raw['interactive']['button_reply']['id'];
            $method = match ($btnId) {
                'pay_cod'       => 'cod',
                'pay_jazzcash'  => 'jazzcash',
                'pay_easypaisa' => 'easypaisa',
                default         => null,
            };
        }

        // Also accept text input
        if (!$method) {
            $lower  = strtolower(trim($text));
            $method = match (true) {
                str_contains($lower, 'cash') || $lower === 'cod' => 'cod',
                str_contains($lower, 'jazz')                     => 'jazzcash',
                str_contains($lower, 'easy')                     => 'easypaisa',
                default                                           => null,
            };
        }

        if (!$method) {
            $this->wa->sendButtons($r, $conv->customer_phone,
                body: "Please choose a payment method:",
                buttons: [
                    ['id' => 'pay_cod',       'title' => 'Cash on Delivery'],
                    ['id' => 'pay_jazzcash',  'title' => 'JazzCash'],
                    ['id' => 'pay_easypaisa', 'title' => 'EasyPaisa'],
                ],
            );
            return;
        }

        $conv->update(['payment_method' => $method, 'state' => 'confirm']);
        $this->showOrderSummary($r, $conv, $method);
    }

    // ── Show final order summary before confirming ────────
    private function showOrderSummary(Restaurant $r, Conversation $conv, string $method): void
    {
        $subtotal   = $conv->cartTotal();
        $delivery   = (float) $r->delivery_charge;
        $total      = $subtotal + $delivery;
        $payLabel   = match($method) {
            'jazzcash'  => 'JazzCash',
            'easypaisa' => 'EasyPaisa',
            default     => 'Cash on Delivery',
        };
        $deliveryLine = $delivery > 0
            ? "\nDelivery: Rs." . number_format($delivery, 0)
            : "\nDelivery: Free";

        $summary = $conv->cartSummary();

        $this->wa->sendButtons($r, $conv->customer_phone,
            header: "📋 Order Summary",
            body:   "👤 Name: {$conv->customer_name}\n" .
                    "📍 Address: {$conv->customer_address}\n\n" .
                    "{$summary}\n\n" .
                    "─────────────\n" .
                    "Subtotal: Rs." . number_format($subtotal, 0) .
                    $deliveryLine . "\n" .
                    "*TOTAL: Rs." . number_format($total, 0) . "*\n" .
                    "Payment: {$payLabel}",
            buttons: [
                ['id' => 'btn_confirm', 'title' => 'Confirm Order ✅'],
                ['id' => 'btn_cancel',  'title' => 'Cancel ❌'],
            ],
            footer: 'Review your order carefully',
        );
    }

    // ── State: confirm ────────────────────────────────────
    private function handleConfirm(Restaurant $r, Conversation $conv, string $text, array $raw): void
    {
        $confirmed = false;
        $cancelled = false;

        if (isset($raw['interactive']['button_reply'])) {
            $btnId     = $raw['interactive']['button_reply']['id'];
            $confirmed = $btnId === 'btn_confirm';
            $cancelled = $btnId === 'btn_cancel';
        }

        // Also accept text
        $lower     = strtolower(trim($text));
        $confirmed = $confirmed || in_array($lower, ['confirm', 'yes', 'ok', 'haan', 'ji']);
        $cancelled = $cancelled || in_array($lower, ['cancel', 'no', 'nahi']);

        if ($confirmed) {
            $this->orderService->placeOrder($r, $conv);
        } elseif ($cancelled) {
            $conv->clearCart();
            $this->wa->sendText($r, $conv->customer_phone,
                "Order cancelled. Type *menu* to start a new order. 😊"
            );
        } else {
            $this->showOrderSummary($r, $conv, $conv->payment_method ?? 'cod');
        }
    }
}