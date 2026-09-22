<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderingStateEngine
{
    // Fixed Deterministic State Constants
    public const STATE_WELCOME = 'WELCOME';
    public const STATE_MENU_SELECTION = 'MENU_SELECTION';
    public const STATE_WAITING_FOR_VARIANT = 'WAITING_FOR_VARIANT';
    public const STATE_COLLECT_CUSTOMER_INFO = 'COLLECT_CUSTOMER_INFO';
    public const STATE_WAITING_FOR_LOCATION = 'WAITING_FOR_LOCATION';
    public const STATE_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';
    public const STATE_ORDER_CREATED = 'ORDER_CREATED';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_MODIFY_EXISTING_ORDER = 'MODIFY_EXISTING_ORDER';
    public const STATE_WAITING_FOR_MODIFICATION_CONFIRMATION = 'WAITING_FOR_MODIFICATION_CONFIRMATION';
    public const STATE_CLARIFY_NEW_OR_MODIFY = 'CLARIFY_NEW_OR_MODIFY';

    protected Restaurant $restaurant;
    protected string $phone;
    protected string $cleanPhone;
    protected string $sessionKey;
    protected array $session;

    public function __construct(Restaurant $restaurant, string $phone)
    {
        $this->restaurant = $restaurant;
        $this->phone = $phone;
        $this->cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $this->sessionKey = "order_session_{$this->restaurant->id}_{$this->cleanPhone}";
        $this->session = $this->loadSession();
    }

    /**
     * Load session from Cache or DB Conversation
     */
    protected function loadSession(): array
    {
        $cached = Cache::get($this->sessionKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $conversation = Conversation::where('restaurant_id', $this->restaurant->id)
            ->where('customer_phone', $this->cleanPhone)
            ->first();

        if ($conversation) {
            $cart = is_array($conversation->cart) ? $conversation->cart : (json_decode($conversation->cart ?? '[]', true) ?: []);
            $meta = is_array($conversation->metadata) ? $conversation->metadata : (json_decode($conversation->metadata ?? '[]', true) ?: []);
            return [
                'state' => $conversation->state ?: self::STATE_WELCOME,
                'cart' => $cart,
                'customer_name' => $conversation->customer_name ?? null,
                'customer_address' => $conversation->customer_address ?? null,
                'delivery_lat' => $meta['delivery_lat'] ?? null,
                'delivery_lng' => $meta['delivery_lng'] ?? null,
                'poi_name' => $meta['poi_name'] ?? null,
                'pending_variant_item' => $meta['pending_variant_item'] ?? null,
                'last_order_id' => $meta['last_order_id'] ?? null,
                'modifying_order_id' => $meta['modifying_order_id'] ?? null,
                'pending_mod_items' => $meta['pending_mod_items'] ?? [],
            ];
        }

        return [
            'state' => self::STATE_WELCOME,
            'cart' => [],
            'customer_name' => null,
            'customer_address' => null,
            'delivery_lat' => null,
            'delivery_lng' => null,
            'poi_name' => null,
            'pending_variant_item' => null,
            'last_order_id' => null,
            'modifying_order_id' => null,
            'pending_mod_items' => [],
        ];
    }

    /**
     * Persist session state to cache and database
     */
    public function saveSession(): void
    {
        Cache::put($this->sessionKey, $this->session, now()->addHours(2));

        $meta = [
            'delivery_lat' => $this->session['delivery_lat'] ?? null,
            'delivery_lng' => $this->session['delivery_lng'] ?? null,
            'poi_name' => $this->session['poi_name'] ?? null,
            'pending_variant_item' => $this->session['pending_variant_item'] ?? null,
            'last_order_id' => $this->session['last_order_id'] ?? null,
            'modifying_order_id' => $this->session['modifying_order_id'] ?? null,
            'pending_mod_items' => $this->session['pending_mod_items'] ?? [],
        ];

        Conversation::updateOrCreate(
            [
                'restaurant_id' => $this->restaurant->id,
                'customer_phone' => $this->cleanPhone,
            ],
            [
                'state' => $this->session['state'],
                'cart' => $this->session['cart'],
                'customer_name' => $this->session['customer_name'],
                'customer_address' => $this->session['customer_address'],
                'metadata' => $meta,
                'last_message_at' => now(),
            ]
        );
    }

    public function getSession(): array
    {
        return $this->session;
    }

    public function getState(): string
    {
        return $this->session['state'] ?? self::STATE_WELCOME;
    }

    public function transitionTo(string $newState): void
    {
        Log::info("State Engine [{$this->cleanPhone}]: Transitioning from {$this->session['state']} to {$newState}");
        $this->session['state'] = $newState;
        $this->saveSession();
    }

    public function resetSession(bool $keepCustomerProfile = true): void
    {
        $preservedName = $keepCustomerProfile ? ($this->session['customer_name'] ?? null) : null;
        $preservedAddr = $keepCustomerProfile ? ($this->session['customer_address'] ?? null) : null;
        $preservedLat  = $keepCustomerProfile ? ($this->session['delivery_lat'] ?? null) : null;
        $preservedLng  = $keepCustomerProfile ? ($this->session['delivery_lng'] ?? null) : null;
        $preservedPoi  = $keepCustomerProfile ? ($this->session['poi_name'] ?? null) : null;

        $this->session = [
            'state' => self::STATE_WELCOME,
            'cart' => [],
            'customer_name' => $preservedName,
            'customer_address' => $preservedAddr,
            'delivery_lat' => $preservedLat,
            'delivery_lng' => $preservedLng,
            'poi_name' => $preservedPoi,
            'pending_variant_item' => null,
            'last_order_id' => null,
            'modifying_order_id' => null,
            'pending_mod_items' => [],
        ];
        $this->saveSession();
    }

    /**
     * Entry point for processing customer intent & entities
     */
    public function process(array $nlu): string
    {
        $intent = strtoupper($nlu['intent'] ?? 'UNKNOWN');
        $currentState = $this->getState();

        Log::info("State Engine [{$this->cleanPhone}]: Current State={$currentState}, Intent={$intent}, Payload=" . json_encode($nlu));

        // Global intent: Reset / Cancel
        if ($intent === 'CANCEL_ORDER' || $intent === 'RESET') {
            if ($currentState === self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION) {
                return $this->handleWaitingForModificationConfirmationState($intent, $nlu);
            }
            $this->resetSession(true);
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        // Global intent: Explicit New Order
        if ($intent === 'START_NEW_ORDER') {
            $this->resetSession(true);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct apna order batayein.";
        }

        // Global intent: Order Status
        if ($intent === 'ASK_ORDER_STATUS') {
            return $this->handleOrderStatus($nlu);
        }

        // Global trigger: Explicit Modification of Existing Order
        if ($intent === 'MODIFY_EXISTING_ORDER') {
            return $this->handleModifyExistingOrder($nlu);
        }

        // State Machine Dispatch
        switch ($currentState) {
            case self::STATE_WELCOME:
                return $this->handleWelcomeState($intent, $nlu);

            case self::STATE_MENU_SELECTION:
                return $this->handleMenuSelectionState($intent, $nlu);

            case self::STATE_WAITING_FOR_VARIANT:
                return $this->handleWaitingForVariantState($intent, $nlu);

            case self::STATE_COLLECT_CUSTOMER_INFO:
                return $this->handleCollectCustomerInfoState($intent, $nlu);

            case self::STATE_WAITING_FOR_LOCATION:
                return $this->handleWaitingForLocationState($intent, $nlu);

            case self::STATE_WAITING_FOR_CONFIRMATION:
                return $this->handleWaitingForConfirmationState($intent, $nlu);

            case self::STATE_MODIFY_EXISTING_ORDER:
                return $this->handleModifyExistingOrder($nlu);

            case self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION:
                return $this->handleWaitingForModificationConfirmationState($intent, $nlu);

            case self::STATE_CLARIFY_NEW_OR_MODIFY:
                return $this->handleClarifyNewOrModifyState($intent, $nlu);

            case self::STATE_ORDER_CREATED:
            case self::STATE_COMPLETED:
                return $this->handleCompletedState($intent, $nlu);

            default:
                $this->transitionTo(self::STATE_WELCOME);
                return $this->handleWelcomeState($intent, $nlu);
        }
    }

    // =========================================================================
    // STATE HANDLERS
    // =========================================================================

    protected function handleWelcomeState(string $intent, array $nlu): string
    {
        if ($intent === 'SHOW_MENU') {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->renderMenuText();
        }

        if ($intent === 'ADD_ITEM' && !empty($nlu['items'])) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleAddItems($nlu);
        }

        $restaurantName = $this->restaurant->name ?? 'Foodio';
        $greeting = "Assalam-o-Alaikum! Welcome to *{$restaurantName}* 🍕🍔\n\n";
        $greeting .= "Aap hamara menu dekhne ke liye *Menu* likhein ya direct apna order batayein.\n\n";
        $greeting .= "Example: _'1 Large Shahi Pizza aur 1 Coke'_";
        
        $this->transitionTo(self::STATE_MENU_SELECTION);
        return $greeting;
    }

    protected function handleMenuSelectionState(string $intent, array $nlu): string
    {
        if ($intent === 'SHOW_MENU') {
            return $this->renderMenuText();
        }

        if ($intent === 'ADD_ITEM') {
            return $this->handleAddItems($nlu);
        }

        if ($intent === 'REMOVE_ITEM') {
            return $this->handleRemoveItem($nlu);
        }

        if ($intent === 'CHANGE_QUANTITY') {
            return $this->handleChangeQuantity($nlu);
        }

        if ($intent === 'SELECT_VARIANT') {
            return $this->handleSelectVariantDirect($nlu);
        }

        if ($intent === 'VIEW_CART') {
            return $this->renderCartReview();
        }

        // If cart has items and customer provides info or requests checkout
        if (!empty($this->session['cart']) && (
            $intent === 'CHECKOUT' || 
            $intent === 'CONFIRM_ORDER' ||
            $intent === 'COLLECT_CUSTOMER_INFO' ||
            $intent === 'PROVIDE_NAME' ||
            $intent === 'PROVIDE_ADDRESS' ||
            $this->hasCustomerInfoProvided($nlu)
        )) {
            $this->captureCustomerInfo($nlu);
            return $this->proceedToNextStepAfterCart();
        }

        if (!empty($nlu['items'])) {
            return $this->handleAddItems($nlu);
        }

        return "Main samajh nahi saka. Aap menu dekhne ke liye *Menu* likh sakte hain ya direct item name aur quantity batayein.\n\nExample: _'2 Large Chicken Pizza'_";
    }

    protected function handleWaitingForVariantState(string $intent, array $nlu): string
    {
        $pending = $this->session['pending_variant_item'];
        if (!$pending) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Koi pending item nahi mila. Aap dobara apna order bata sakte hain.";
        }

        $menuItem = MenuItem::with('variants')->find($pending['id']);
        if (!$menuItem) {
            $this->session['pending_variant_item'] = null;
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Item nahi mila. Dobara menu se select karein.";
        }

        $selectedVariantName = $nlu['variant'] ?? null;
        if (!$selectedVariantName && !empty($nlu['raw_text'])) {
            $selectedVariantName = $this->extractVariantFromText($nlu['raw_text'], $menuItem->variants);
        }

        if ($selectedVariantName) {
            $matchedVariant = $this->matchVariant($menuItem, $selectedVariantName);
            if ($matchedVariant) {
                $qty = (int)($pending['quantity'] ?? 1);
                $this->addItemToCart($menuItem, $matchedVariant, $qty);
                $this->session['pending_variant_item'] = null;
                $this->saveSession();

                // Check if we are in modification context
                if (!empty($this->session['modifying_order_id'])) {
                    $order = Order::find($this->session['modifying_order_id']);
                    if ($order) {
                        $this->transitionTo(self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION);
                        return "✅ *{$matchedVariant->name} {$menuItem->name}* (x{$qty}) shaamil kar diya gaya!\n\n" . $this->renderModificationReview($order);
                    }
                }

                $reply = "✅ *{$matchedVariant->name} {$menuItem->name}* (x{$qty}) cart mein add ho gaya!\n";
                $reply .= "Price: Rs. " . number_format($matchedVariant->price * $qty) . "\n\n";

                if ($this->hasCustomerInfoProvided($nlu)) {
                    $this->captureCustomerInfo($nlu);
                }

                if ($this->hasCompleteInfo()) {
                    return $this->proceedToNextStepAfterCart();
                }

                $reply .= $this->renderCartSummary();
                $reply .= "\n\nKuch aur add karna chahenge, ya proceed karein? (Type *Checkout* ya apna address batayein)";
                
                $this->transitionTo(self::STATE_MENU_SELECTION);
                return $reply;
            }
        }

        $variantList = $menuItem->variants->map(fn($v) => "• *{$v->name}*: Rs. " . number_format($v->price))->join("\n");
        return "Barahe karam darust size select karein:\n\n{$variantList}\n\nExample: _'{$menuItem->variants->first()->name}'_";
    }

    protected function handleCollectCustomerInfoState(string $intent, array $nlu): string
    {
        $this->captureCustomerInfo($nlu);

        $raw = trim($nlu['raw_text'] ?? '');

        // If customer name is empty, sanitize raw_text
        if (empty($this->session['customer_name'])) {
            $sanitized = $this->sanitizeCustomerField($raw, $this->session['customer_name'] ?? null);
            if ($sanitized) {
                $this->session['customer_name'] = $sanitized;
                $this->saveSession();
            } else {
                return "Aapka shukriya! Barahe karam apna *Naam* (Full Name) batayein:";
            }
        }

        // If customer address is empty, sanitize raw_text
        if (empty($this->session['customer_address'])) {
            $sanitizedAddr = $this->sanitizeCustomerField($raw, $this->session['customer_address'] ?? null);
            if ($sanitizedAddr && $sanitizedAddr !== $this->session['customer_name']) {
                $this->session['customer_address'] = $sanitizedAddr;
                $this->saveSession();
            } else {
                return "Shukriya {$this->session['customer_name']}! Barahe karam apna *Delivery Address* batayein (House/Street/Area):";
            }
        }

        // Both name and address are available!
        return $this->proceedToNextStepAfterCart();
    }

    protected function handleWaitingForLocationState(string $intent, array $nlu): string
    {
        if (!empty($nlu['address'])) {
            $cleanAddr = $this->sanitizeCustomerField($nlu['address'], $this->session['customer_address'] ?? null);
            if ($cleanAddr) {
                $this->session['customer_address'] = $cleanAddr;
                $this->saveSession();
            }
        }

        $raw = strtolower($nlu['raw_text'] ?? '');
        if (str_contains($raw, 'skip') || str_contains($raw, 'nahi') || str_contains($raw, 'rehne do') || str_contains($raw, 'no')) {
            $this->transitionTo(self::STATE_WAITING_FOR_CONFIRMATION);
            return $this->renderFinalOrderReview();
        }

        return "📍 Barahe karam WhatsApp se apni *Current Location pin share karein* (Attachment 📎 -> Location).\n\nAgar aap pin share nahi kar sakte to *Skip* likhein.";
    }

    protected function handleWaitingForConfirmationState(string $intent, array $nlu): string
    {
        if ($intent === 'CONFIRM_ORDER' || $this->isAffirmative($nlu['raw_text'] ?? '')) {
            return $this->executeOrderCreation();
        }

        if ($intent === 'CANCEL_ORDER' || $this->isNegative($nlu['raw_text'] ?? '')) {
            $this->resetSession(true);
            return "Aapka order cancel kar diya gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        if ($intent === 'ADD_ITEM') {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleAddItems($nlu);
        }

        if ($intent === 'REMOVE_ITEM') {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleRemoveItem($nlu);
        }

        return "Barahe karam order confirm karne ke liye *Yes / Confirm* likhein, ya cancel karne ke liye *Cancel* likhein.\n\n" . $this->renderFinalOrderReview();
    }

    protected function handleCompletedState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        // If customer explicitly asks for new order
        if ($intent === 'START_NEW_ORDER' || preg_match('/\b(new|another|naya|alag)\s*order\b/i', $raw)) {
            $this->resetSession(true);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct item batayein.";
        }

        if ($intent === 'SHOW_MENU') {
            return $this->renderMenuText();
        }

        if ($intent === 'ASK_ORDER_STATUS') {
            return $this->handleOrderStatus($nlu);
        }

        // Check if customer refers to existing order modification
        if ($intent === 'MODIFY_EXISTING_ORDER' || $this->isModifyExistingOrderPhrase($raw)) {
            return $this->handleModifyExistingOrder($nlu);
        }

        // If customer sends food items without explicitly saying "new order" or "same order"
        if (!empty($nlu['items']) || $intent === 'ADD_ITEM') {
            $activeOrder = $this->getActiveOrder();
            if ($activeOrder && $this->isOrderModifiable($activeOrder)) {
                // Ambiguous: ask whether to modify active order or start new order
                $this->session['pending_mod_items'] = $nlu['items'] ?? [];
                $this->transitionTo(self::STATE_CLARIFY_NEW_OR_MODIFY);

                $itemsNames = implode(', ', array_map(fn($i) => ($i['quantity'] ?? 1) . 'x ' . ($i['name'] ?? ''), $this->session['pending_mod_items']));
                return "Aapka *Order #{$activeOrder->id}* abhi pending hai.\n\nKya aap *{$itemsNames}* usi order mein add karna chahte hain ya naya alag order banana chahte hain?\n\n1️⃣ Reply *Same Order* (Order #{$activeOrder->id} mein add hoga)\n2️⃣ Reply *New Order* (Alag naya order banega)";
            }

            // If no active order, proceed as new order
            $this->resetSession(true);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleAddItems($nlu);
        }

        return "Aapka order process ho raha hai! Naya order karne ke liye *New Order* ya *Menu* likhein.";
    }

    // =========================================================================
    // MODIFICATION WORKFLOW & CLARIFICATION
    // =========================================================================

    public function handleModifyExistingOrder(array $nlu): string
    {
        $activeOrder = $this->getActiveOrder();
        if (!$activeOrder) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Aapka koi active order nahi mila jise modify kiya ja sake. Naya order karne ke liye *Menu* likhein.";
        }

        // Status Guard: rule 5
        if (!$this->isOrderModifiable($activeOrder)) {
            return $this->getOrderStatusGuardMessage($activeOrder);
        }

        $this->session['modifying_order_id'] = $activeOrder->id;

        // Rule 3: Automatically reuse existing customer profile data from Order
        $this->session['customer_name'] = $activeOrder->customer_name;
        $this->session['customer_address'] = $activeOrder->delivery_address;
        $this->session['delivery_lat'] = $activeOrder->delivery_lat;
        $this->session['delivery_lng'] = $activeOrder->delivery_lng;
        $this->session['poi_name'] = $activeOrder->delivery_place_name;

        // Load existing order items into cart if cart doesn't have them
        if (empty($this->session['cart'])) {
            $this->session['cart'] = $this->extractCartFromOrder($activeOrder);
        }

        // Parse items to add
        $items = $nlu['items'] ?? [];
        if (empty($items) && !empty($this->session['pending_mod_items'])) {
            $items = $this->session['pending_mod_items'];
            $this->session['pending_mod_items'] = [];
        }

        if (empty($items)) {
            $this->transitionTo(self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION);
            return "Aap Order #{$activeOrder->id} mein kya add karna chahte hain? Barahe karam item ka naam aur quantity batayein.\n\nExample: _'2 Chicken Wrap'_";
        }

        // Resolve each item authoritatively from DB (Rule 4)
        foreach ($items as $itemData) {
            $itemName = trim($itemData['name'] ?? '');
            $qty = max(1, (int)($itemData['quantity'] ?? 1));
            $requestedVariant = $itemData['size'] ?? $itemData['variant'] ?? null;

            if (empty($itemName)) {
                continue;
            }

            $menuItem = $this->resolveMenuItemFromDb($itemName);
            if (!$menuItem) {
                return "Maazrat! *{$itemName}* hamare menu mein dastiyab nahi hai. Type *Menu* to see available items.";
            }

            $menuItem->loadMissing('variants');
            if ($menuItem->variants->isNotEmpty()) {
                $matchedVariant = $requestedVariant ? $this->matchVariant($menuItem, $requestedVariant) : null;
                if (!$matchedVariant) {
                    $this->session['pending_variant_item'] = [
                        'id' => $menuItem->id,
                        'name' => $menuItem->name,
                        'quantity' => $qty,
                    ];
                    $this->transitionTo(self::STATE_WAITING_FOR_VARIANT);
                    $variantList = $menuItem->variants->map(fn($v) => "• *{$v->name}*: Rs. " . number_format($v->price))->join("\n");
                    return "Aap ne *{$menuItem->name}* chuna hai. Barahe karam size select karein:\n\n{$variantList}\n\nExample: _'Large'_";
                }

                $this->addItemToCart($menuItem, $matchedVariant, $qty);
            } else {
                $this->addItemToCart($menuItem, null, $qty);
            }
        }

        $this->saveSession();
        $this->transitionTo(self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION);

        return $this->renderModificationReview($activeOrder);
    }

    public function handleWaitingForModificationConfirmationState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        // Customer Confirms Modification
        if ($intent === 'CONFIRM_ORDER' || $this->isAffirmative($raw)) {
            return $this->executeOrderModification();
        }

        // Customer Cancels Modification (Discard staged changes, keep DB order intact)
        if ($intent === 'CANCEL_ORDER' || $this->isNegative($raw)) {
            $orderId = $this->session['modifying_order_id'] ?? $this->session['last_order_id'];
            $this->session['cart'] = [];
            $this->session['modifying_order_id'] = null;
            $this->session['pending_mod_items'] = [];
            $this->transitionTo(self::STATE_ORDER_CREATED);
            return "Theek hai! Order #{$orderId} mein koi tabdeeli nahi ki gayi. Aapka original order as it is rahega.";
        }

        // Customer wants to add additional items in same modification
        if ($intent === 'ADD_ITEM' || !empty($nlu['items'])) {
            return $this->handleModifyExistingOrder($nlu);
        }

        // Unrecognized reply -> re-prompt with review
        $order = Order::find($this->session['modifying_order_id'] ?? 0) ?? $this->getActiveOrder();
        if ($order) {
            return "Barahe karam Order #{$order->id} ki tabdeeli confirm karne ke liye *Confirm* likhein, ya cancel karne ke liye *Cancel* likhein.\n\n" . $this->renderModificationReview($order);
        }

        $this->transitionTo(self::STATE_MENU_SELECTION);
        return "Pehle menu se item select karein.";
    }

    public function handleClarifyNewOrModifyState(string $intent, array $nlu): string
    {
        $raw = strtolower(trim($nlu['raw_text'] ?? ''));
        $activeOrder = $this->getActiveOrder();

        if (preg_match('/\b(same|isi|is me|1|first|modify|ha|yes|theek)\b/i', $raw)) {
            // Customer chose to add to existing order
            return $this->handleModifyExistingOrder(['intent' => 'MODIFY_EXISTING_ORDER', 'items' => $this->session['pending_mod_items'] ?? []]);
        }

        if (preg_match('/\b(new|2|second|naya|alag|another)\b/i', $raw)) {
            // Customer chose to create a new order
            $pendingItems = $this->session['pending_mod_items'] ?? [];
            $this->resetSession(true);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleAddItems(['intent' => 'ADD_ITEM', 'items' => $pendingItems]);
        }

        $orderId = $activeOrder ? $activeOrder->id : '';
        return "Barahe karam clear batayein:\n\n1️⃣ Reply *Same Order* (Order #{$orderId} mein add hoga)\n2️⃣ Reply *New Order* (Alag naya order banega)";
    }

    public function executeOrderModification(): string
    {
        $orderId = $this->session['modifying_order_id'] ?? $this->session['last_order_id'];
        $order = Order::find($orderId);

        if (!$order || !$this->isOrderModifiable($order)) {
            $this->session['cart'] = [];
            $this->session['modifying_order_id'] = null;
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Maazrat! Yeh order ab modify nahi ho sakta.";
        }

        $cart = $this->session['cart'] ?? [];
        if (empty($cart)) {
            return "Cart khali hai.";
        }

        // Authoritative DB pricing recalculation
        $subtotal = 0;
        $orderItemsData = [];

        foreach ($cart as $cItem) {
            $menuItem = MenuItem::find($cItem['item_id']);
            if (!$menuItem) {
                continue;
            }

            $unitPrice = (float)$menuItem->price;
            $variantName = null;

            if (!empty($cItem['variant_id'])) {
                $variant = MenuItemVariant::find($cItem['variant_id']);
                if ($variant) {
                    $unitPrice = (float)$variant->price;
                    $variantName = $variant->name;
                }
            }

            $lineTotal = $unitPrice * $cItem['quantity'];
            $subtotal += $lineTotal;

            $orderItemsData[] = [
                'item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'size' => $variantName,
                'unit_price' => $unitPrice,
                'quantity' => $cItem['quantity'],
                'subtotal' => $lineTotal,
            ];
        }

        $deliveryCharge = (float)$order->delivery_charge;
        $total = $subtotal + $deliveryCharge;

        // Atomic DB Update
        DB::transaction(function () use ($order, $subtotal, $total, $orderItemsData) {
            $order->subtotal = $subtotal;
            $order->total = $total;
            $order->save();

            // Replace order items with updated synchronized cart
            OrderItem::where('order_id', $order->id)->delete();
            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['item_id'],
                    'name' => $item['name'],
                    'size' => $item['size'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }
        });

        // Notify Restaurant Owner of modification
        $this->notifyOwnerOfUpdate($order);

        // Finalize state and clear cart (Rule 6: Cart/Order Separation)
        $this->session['cart'] = [];
        $this->session['modifying_order_id'] = null;
        $this->session['pending_mod_items'] = [];
        $this->transitionTo(self::STATE_ORDER_CREATED);

        $appUrl = config('app.url', 'http://localhost');
        $trackingUrl = "{$appUrl}/track/{$order->tracking_code}";

        $receipt = "🎉 *ORDER #{$order->id} UPDATE HO GAYA HAI!*\n\n";
        $receipt .= "Aapke order mein tabdeeli darj kar li gayi hai:\n\n";
        foreach ($orderItemsData as $i) {
            $lbl = $i['name'] . ($i['size'] ? " ({$i['size']})" : '');
            $receipt .= "• {$i['quantity']}x {$lbl} — Rs. " . number_format($i['subtotal']) . "\n";
        }
        $receipt .= "━━━━━━━━━━━━━\n";
        $receipt .= "Subtotal: Rs. " . number_format($subtotal) . "\n";
        $receipt .= "Delivery Fee: Rs. " . number_format($deliveryCharge) . "\n";
        $receipt .= "💰 *NEW TOTAL:* Rs. " . number_format($total) . " (COD)\n\n";
        $receipt .= "🔴 *Live Tracking:* {$trackingUrl}\n\n";
        $receipt .= "Kitchen ko update bhej di gayi hai. Shukriya! ❤️";

        return $receipt;
    }

    protected function renderModificationReview(Order $order): string
    {
        $cart = $this->session['cart'] ?? [];
        $subtotal = 0;
        $itemsText = "";

        foreach ($cart as $item) {
            $name = $item['name'] . ($item['variant_name'] ? " ({$item['variant_name']})" : '');
            $itemsText .= "• {$item['quantity']}x {$name} — Rs. " . number_format($item['subtotal']) . "\n";
            $subtotal += $item['subtotal'];
        }

        $deliveryCharge = (float)$order->delivery_charge;
        $total = $subtotal + $deliveryCharge;

        $name = $order->customer_name;
        $address = $order->delivery_address;
        $poi = $order->delivery_place_name ? "\n📍 *Landmark:* {$order->delivery_place_name}" : "";

        $out = "📝 *ORDER #{$order->id} UPDATE REVIEW:*\n\n";
        $out .= "{$itemsText}\n";
        $out .= "Subtotal: Rs. " . number_format($subtotal) . "\n";
        $out .= "Delivery Fee: Rs. " . number_format($deliveryCharge) . "\n";
        $out .= "━━━━━━━━━━━━━\n";
        $out .= "💰 *NEW TOTAL:* Rs. " . number_format($total) . " (COD)\n\n";
        $out .= "👤 *Customer:* {$name}\n";
        $out .= "🏠 *Address:* {$address}{$poi}\n\n";
        $out .= "Kya aap Order #{$order->id} mein yeh changes confirm karte hain? Reply *Confirm* ya *Cancel*.";

        return $out;
    }

    protected function extractCartFromOrder(Order $order): array
    {
        $cart = [];
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $key = $item->menu_item_id . '_' . ($item->size ?: '0');
            $variantId = null;
            if (!empty($item->size)) {
                $variantId = MenuItemVariant::where('menu_item_id', $item->menu_item_id)
                    ->where('name', $item->size)
                    ->value('id');
            }

            $cart[$key] = [
                'item_id' => $item->menu_item_id,
                'variant_id' => $variantId,
                'name' => $item->name,
                'variant_name' => $item->size,
                'unit_price' => (float)$item->unit_price,
                'quantity' => (int)$item->quantity,
                'subtotal' => (float)$item->subtotal,
            ];
        }
        return $cart;
    }

    public function getActiveOrder(): ?Order
    {
        if (!empty($this->session['modifying_order_id'])) {
            $ord = Order::with('items')->where('restaurant_id', $this->restaurant->id)
                ->where('id', $this->session['modifying_order_id'])
                ->first();
            if ($ord) return $ord;
        }

        if (!empty($this->session['last_order_id'])) {
            $ord = Order::with('items')->where('restaurant_id', $this->restaurant->id)
                ->where('id', $this->session['last_order_id'])
                ->first();
            if ($ord) return $ord;
        }

        return Order::with('items')->where('restaurant_id', $this->restaurant->id)
            ->where('customer_phone', $this->cleanPhone)
            ->latest()
            ->first();
    }

    public function isOrderModifiable(?Order $order): bool
    {
        if (!$order) {
            return false;
        }

        // Rule 5: Modifiable only in pending status
        return $order->status === 'pending';
    }

    protected function getOrderStatusGuardMessage(Order $order): string
    {
        return match ($order->status) {
            'preparing' => "Maazrat! Order #{$order->id} kitchen mein tayyar ho raha hai (Preparing), is liye is mein tabdeeli mumkin nahi hai. Agar aap mazeed kuch mangwana chahte hain to naya order place kar sakte hain (Reply *New Order* ya *Menu*).",
            'out_for_delivery' => "Maazrat! Order #{$order->id} deliver karne ke liye nikal chuka hai (Out for Delivery), is liye is mein tabdeeli mumkin nahi hai. Naya order karne ke liye *Menu* likhein.",
            'delivered' => "Aapka Order #{$order->id} deliver ho chuka hai. Naya order place karne ke liye *Menu* likhein.",
            'cancelled' => "Order #{$order->id} cancel ho chuka hai. Naya order karne ke liye *Menu* likhein.",
            default => "Order #{$order->id} ab modify nahi ho sakta. Naya order place karne ke liye *Menu* likhein.",
        };
    }

    protected function notifyOwnerOfUpdate(Order $order): void
    {
        try {
            $ownerPhone = $this->restaurant->owner_phone ?: $this->restaurant->manager_phone;
            if (!$ownerPhone) {
                return;
            }

            $itemsStr = OrderItem::where('order_id', $order->id)
                ->get()
                ->map(fn($i) => "{$i->quantity}x {$i->name}" . ($i->size ? " ({$i->size})" : ""))
                ->implode(', ');

            $msg = "🚨 *ORDER #{$order->id} UPDATED BY CUSTOMER!*\n\n";
            $msg .= "📦 *#{$order->tracking_code}*\n";
            $msg .= "📱 *Customer:* {$order->customer_name} ({$order->customer_phone})\n";
            $msg .= "🍽️ *Updated Items:* {$itemsStr}\n";
            $msg .= "💰 *New Total:* Rs. " . number_format($order->total) . " (COD)\n";
            $msg .= "📍 *Address:* {$order->delivery_address}\n";
            $msg .= "\n✅ Check dashboard for live updates.";

            BotEvolutionClient::sendMessage($this->restaurant, $ownerPhone, $msg);
        } catch (\Throwable $e) {
            Log::warning("Owner notification of update failed: " . $e->getMessage());
        }
    }

    // =========================================================================
    // NATIVE GPS LOCATION HANDLER (Called directly from webhook)
    // =========================================================================

    public function handleLocationPin(float $lat, float $lng): string
    {
        Log::info("State Engine [{$this->cleanPhone}]: Native GPS pin received: {$lat}, {$lng}");

        $this->session['delivery_lat'] = $lat;
        $this->session['delivery_lng'] = $lng;

        // Radius check
        $restLat = $this->restaurant->restaurant_lat;
        $restLng = $this->restaurant->restaurant_lng;
        $maxRadius = $this->restaurant->maxDeliveryRadiusKm();

        if ($restLat && $restLng) {
            $dist = $this->calculateDistanceKm((float)$restLat, (float)$restLng, $lat, $lng);
            if ($dist > $maxRadius) {
                return "Maazrat! Yeh location hamare delivery radius ({$maxRadius} km) se bahar hai (Faasla: " . round($dist, 1) . " km). Barahe karam delivery area ke andar ki location share karein.";
            }
        }

        // Landmark / POI Detection
        try {
            $locService = app(\App\Services\LocationResolutionService::class);
            $resolution = $locService->resolve($lat, $lng);
            $placeName = $resolution['delivery_place_name'] ?? null;
            if ($placeName) {
                $this->session['poi_name'] = $placeName;
                if (empty($this->session['customer_address'])) {
                    $this->session['customer_address'] = $resolution['delivery_address'] ?? $placeName;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("LocationResolutionService error in State Engine: " . $e->getMessage());
        }

        $this->saveSession();

        if (empty($this->session['cart'])) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            $locTxt = $this->session['poi_name'] ? " ({$this->session['poi_name']})" : "";
            return "📍 Location receive ho gayi hai{$locTxt}!\n\nAb bataiye aap kya order karna chahenge? (Type *Menu* to see all items)";
        }

        if (empty($this->session['customer_name']) || empty($this->session['customer_address'])) {
            $this->transitionTo(self::STATE_COLLECT_CUSTOMER_INFO);
            if (empty($this->session['customer_name'])) {
                return "📍 Location confirm ho gayi!\n\nBarahe karam apna *Naam* (Full Name) batayein:";
            }
            return "📍 Location confirm ho gayi!\n\nBarahe karam apna *Delivery Address* batayein (House/Street/Area):";
        }

        $this->transitionTo(self::STATE_WAITING_FOR_CONFIRMATION);
        return "📍 Location tasdeeq ho gayi hai!\n\n" . $this->renderFinalOrderReview();
    }

    // =========================================================================
    // ITEM & CART OPERATIONS (Strict DB Price Enforcement)
    // =========================================================================

    protected function handleAddItems(array $nlu): string
    {
        $items = $nlu['items'] ?? [];
        if (empty($items)) {
            return "Aap kya order karna chahte hain? Barahe karam item ka naam batayein.";
        }

        $this->captureCustomerInfo($nlu);

        $addedSummary = [];

        foreach ($items as $itemData) {
            $itemName = trim($itemData['name'] ?? '');
            $qty = max(1, (int)($itemData['quantity'] ?? 1));
            $requestedVariant = $itemData['size'] ?? $itemData['variant'] ?? null;

            if (empty($itemName)) {
                continue;
            }

            $menuItem = $this->resolveMenuItemFromDb($itemName);
            if (!$menuItem) {
                return "Maazrat! *{$itemName}* hamare menu mein dastiyab nahi hai. Type *Menu* to see items.";
            }

            $menuItem->loadMissing('variants');
            if ($menuItem->variants->isNotEmpty()) {
                $matchedVariant = $requestedVariant ? $this->matchVariant($menuItem, $requestedVariant) : null;

                if (!$matchedVariant) {
                    $this->session['pending_variant_item'] = [
                        'id' => $menuItem->id,
                        'name' => $menuItem->name,
                        'quantity' => $qty,
                    ];
                    $this->transitionTo(self::STATE_WAITING_FOR_VARIANT);

                    $variantList = $menuItem->variants->map(fn($v) => "• *{$v->name}*: Rs. " . number_format($v->price))->join("\n");
                    return "Aap ne *{$menuItem->name}* chuna hai. Barahe karam size select karein:\n\n{$variantList}\n\nExample: _'Large'_";
                }

                $this->addItemToCart($menuItem, $matchedVariant, $qty);
                $addedSummary[] = "{$qty}x {$matchedVariant->name} {$menuItem->name} (Rs. " . number_format($matchedVariant->price * $qty) . ")";
            } else {
                $this->addItemToCart($menuItem, null, $qty);
                $addedSummary[] = "{$qty}x {$menuItem->name} (Rs. " . number_format($menuItem->price * $qty) . ")";
            }
        }

        $this->saveSession();

        if ($this->hasCompleteInfo()) {
            return $this->proceedToNextStepAfterCart();
        }

        return "✅ Added to Cart:\n" . implode("\n", $addedSummary) . "\n\n" . $this->renderCartSummary() . "\n\nKuch aur chahiye ya proceed karein? (Type *Checkout* ya address batayein)";
    }

    protected function addItemToCart(MenuItem $item, ?MenuItemVariant $variant, int $qty): void
    {
        $cart = $this->session['cart'] ?? [];
        $key = $item->id . '_' . ($variant ? $variant->id : '0');

        $unitPrice = $variant ? (float)$variant->price : (float)$item->price;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $qty;
            $cart[$key]['subtotal'] = $cart[$key]['quantity'] * $unitPrice;
        } else {
            $cart[$key] = [
                'item_id' => $item->id,
                'variant_id' => $variant ? $variant->id : null,
                'name' => $item->name,
                'variant_name' => $variant ? $variant->name : null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'subtotal' => $unitPrice * $qty,
            ];
        }

        $this->session['cart'] = $cart;
    }

    protected function handleRemoveItem(array $nlu): string
    {
        $itemName = trim($nlu['item_name'] ?? ($nlu['items'][0]['name'] ?? ''));
        if (empty($itemName)) {
            return "Aap kon sa item remove karna chahte hain?";
        }

        $cart = $this->session['cart'] ?? [];
        $removed = false;

        foreach ($cart as $key => $cartItem) {
            if (stripos($cartItem['name'], $itemName) !== false) {
                unset($cart[$key]);
                $removed = true;
                break;
            }
        }

        if ($removed) {
            $this->session['cart'] = $cart;
            $this->saveSession();
            return "Item cart se remove kar diya gaya hai.\n\n" . $this->renderCartSummary();
        }

        return "Yeh item aapke cart mein nahi mila.";
    }

    protected function handleChangeQuantity(array $nlu): string
    {
        $items = $nlu['items'] ?? [];
        if (empty($items)) {
            return "Aap kis item ki quantity badalna chahte hain?";
        }

        $cart = $this->session['cart'] ?? [];
        foreach ($items as $itemData) {
            $name = trim($itemData['name'] ?? '');
            $newQty = (int)($itemData['quantity'] ?? 1);

            foreach ($cart as $key => &$cartItem) {
                if (stripos($cartItem['name'], $name) !== false) {
                    if ($newQty <= 0) {
                        unset($cart[$key]);
                    } else {
                        $cartItem['quantity'] = $newQty;
                        $cartItem['subtotal'] = $cartItem['quantity'] * $cartItem['unit_price'];
                    }
                    break;
                }
            }
        }

        $this->session['cart'] = $cart;
        $this->saveSession();
        return "Cart update ho gaya hai!\n\n" . $this->renderCartSummary();
    }

    protected function handleSelectVariantDirect(array $nlu): string
    {
        if ($this->getState() === self::STATE_WAITING_FOR_VARIANT) {
            return $this->handleWaitingForVariantState('SELECT_VARIANT', $nlu);
        }

        return "Pehle menu se item select karein.";
    }

    // =========================================================================
    // FLOW LOGIC & DATA SANITIZATION
    // =========================================================================

    public function proceedToNextStepAfterCart(): string
    {
        if (empty($this->session['customer_name'])) {
            $this->transitionTo(self::STATE_COLLECT_CUSTOMER_INFO);
            return $this->renderCartSummary() . "\n\nOrder aage barhane ke liye barahe karam apna *Naam* (Full Name) batayein:";
        }

        if (empty($this->session['customer_address'])) {
            $this->transitionTo(self::STATE_COLLECT_CUSTOMER_INFO);
            return "Shukriya {$this->session['customer_name']}! Barahe karam apna *Delivery Address* batayein (House/Street/Area):";
        }

        if (empty($this->session['delivery_lat'])) {
            $this->transitionTo(self::STATE_WAITING_FOR_LOCATION);
            return "📍 Delivery tez aur exact karne ke liye WhatsApp se apni *Location pin share karein*. (Ya *Skip* likhein)";
        }

        $this->transitionTo(self::STATE_WAITING_FOR_CONFIRMATION);
        return $this->renderFinalOrderReview();
    }

    public function sanitizeCustomerField(?string $value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        $val = trim($value);
        if ($val === '') {
            return $fallback;
        }

        // Rule 3: Never store conversational instructions as field values
        // If customer said "same", "wohi", "same address", "pichla address", "sab kuch wohi use kro", reuse fallback!
        if (preg_match('/^(?:same|wohi|wahi|same\s*name|same\s*address|same\s*location|same\s*pata|pehle\s*wala|pehle\s*wali|previous|sab\s*kuch\s*wohi|usi\s*order|same\s*usi)$/iu', $val) ||
            preg_match('/\b(?:same|wohi|wahi|pichla|pehle|use\s*kr|use\s*kar|sub\s*kuch|sab\s*kuch)\b/iu', $val)) {
            return $fallback;
        }

        // Check if value contains conversational instruction verbs or order item names
        if (preg_match('/\b(?:add\s*kar|kr\s*do|kardo|kar\s*do|daal\s*do|bhej\s*do|mangwana|order\s*me|same\s*order|is\s*me|wrap|pizza|burger|coke|deal|rupaye|rs\.?)\b/iu', $val)) {
            return $fallback;
        }

        // Length checks (avoid entire sentences being saved as a name)
        if (strlen($val) > 100) {
            return $fallback;
        }

        return $val;
    }

    protected function captureCustomerInfo(array $nlu): void
    {
        if (!empty($nlu['name']) && empty($this->session['customer_name'])) {
            $cleanName = $this->sanitizeCustomerField($nlu['name'], $this->session['customer_name'] ?? null);
            if ($cleanName) {
                $this->session['customer_name'] = $cleanName;
            }
        }

        if (!empty($nlu['address'])) {
            $cleanAddr = $this->sanitizeCustomerField($nlu['address'], $this->session['customer_address'] ?? null);
            if ($cleanAddr) {
                $this->session['customer_address'] = $cleanAddr;
            }
        }

        $this->saveSession();
    }

    protected function hasCustomerInfoProvided(array $nlu): bool
    {
        return !empty($nlu['name']) || !empty($nlu['address']);
    }

    protected function hasCompleteInfo(): bool
    {
        return !empty($this->session['cart']) &&
               !empty($this->session['customer_name']) &&
               !empty($this->session['customer_address']);
    }

    protected function isModifyExistingOrderPhrase(string $text): bool
    {
        return (bool)preg_match('/\b(?:is\s*me|isme|is\s*order\s*me|same\s*order\s*me|order\s*me\s*(?:aur\s*)?add|add\s*(?:this\s*)?(?:to\s*)?(?:my\s*)?order|pichle\s*order|usi\s*order)\b|(?:\b(?:is\s*me|isme)\b.*?\b(?:kr\s*do|kardo|kar\s*do|add|bhej\s*do|daal\s*do)\b)|(?:^add\s+\d+\s+)/iu', $text);
    }

    // =========================================================================
    // ORDER EXECUTION (Atomic Transaction, Authoritative DB Pricing)
    // =========================================================================

    public function executeOrderCreation(): string
    {
        $cart = $this->session['cart'] ?? [];
        if (empty($cart)) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Aapka cart khaali hai. Order create nahi ho sakta.";
        }

        $subtotal = 0;
        $orderItemsData = [];

        foreach ($cart as $cItem) {
            $menuItem = MenuItem::find($cItem['item_id']);
            if (!$menuItem) {
                continue;
            }

            $unitPrice = (float)$menuItem->price;
            $variantName = null;

            if (!empty($cItem['variant_id'])) {
                $variant = MenuItemVariant::find($cItem['variant_id']);
                if ($variant) {
                    $unitPrice = (float)$variant->price;
                    $variantName = $variant->name;
                }
            }

            $lineTotal = $unitPrice * $cItem['quantity'];
            $subtotal += $lineTotal;

            $orderItemsData[] = [
                'item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'variant_id' => $cItem['variant_id'] ?? null,
                'variant_name' => $variantName,
                'unit_price' => $unitPrice,
                'quantity' => $cItem['quantity'],
                'subtotal' => $lineTotal,
            ];
        }

        $deliveryCharge = (float)($this->restaurant->delivery_charge ?? 0);
        $total = $subtotal + $deliveryCharge;

        $order = DB::transaction(function () use ($subtotal, $deliveryCharge, $total, $orderItemsData) {
            $trackingCode = Order::generateTrackingCode($this->restaurant);

            $newOrder = Order::create([
                'restaurant_id' => $this->restaurant->id,
                'customer_name' => $this->session['customer_name'] ?? 'WhatsApp Customer',
                'customer_phone' => $this->cleanPhone,
                'delivery_address' => $this->session['customer_address'] ?? 'WhatsApp Order',
                'delivery_lat' => $this->session['delivery_lat'] ?? null,
                'delivery_lng' => $this->session['delivery_lng'] ?? null,
                'delivery_place_name' => $this->session['poi_name'] ?? null,
                'location_source' => ($this->session['delivery_lat'] ? 'whatsapp_pin' : null),
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'total' => $total,
                'payment_method' => 'cash_on_delivery',
                'status' => 'pending',
                'is_paid' => false,
                'tracking_code' => $trackingCode,
            ]);

            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id' => $newOrder->id,
                    'menu_item_id' => $item['item_id'],
                    'name' => $item['name'],
                    'size' => $item['variant_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $newOrder;
        });

        $this->notifyOwner($order);

        // Rule 6: Cart/Order Separation - finalize cart immediately
        $this->session['last_order_id'] = $order->id;
        $this->session['cart'] = [];
        $this->session['modifying_order_id'] = null;
        $this->session['pending_mod_items'] = [];
        $this->transitionTo(self::STATE_ORDER_CREATED);

        $appUrl = config('app.url', 'http://localhost');
        $trackingUrl = "{$appUrl}/track/{$order->tracking_code}";

        $receipt = "🎉 *MUBARAK HO! AAPKA ORDER CONFIRM HO GAYA HAI!*\n\n";
        $receipt .= "🆔 *Order #{$order->id}* (Code: `{$order->tracking_code}`)\n";
        $receipt .= "👤 *Customer:* {$order->customer_name}\n";
        $receipt .= "📍 *Delivery to:* " . ($order->delivery_place_name ? "{$order->delivery_place_name} ({$order->delivery_address})" : $order->delivery_address) . "\n";
        $receipt .= "💵 *Payment:* Cash on Delivery\n";
        $receipt .= "💰 *Total Bill:* Rs. " . number_format($order->total) . "\n\n";
        $receipt .= "⏱️ Estimated Delivery: 30–45 mins\n";
        $receipt .= "🔴 *Live Tracking:* {$trackingUrl}\n\n";
        $receipt .= "Foodio istemal karne ka shukriya! ❤️";

        return $receipt;
    }

    protected function notifyOwner(Order $order): void
    {
        try {
            $ownerPhone = $this->restaurant->owner_phone ?: $this->restaurant->manager_phone;
            if (!$ownerPhone) {
                return;
            }

            $itemsStr = OrderItem::where('order_id', $order->id)
                ->get()
                ->map(fn($i) => "{$i->quantity}x {$i->name}" . ($i->size ? " ({$i->size})" : ""))
                ->implode(', ');

            $msg = "🔔 *NEW ORDER #{$order->id} — {$this->restaurant->name}!*\n\n";
            $msg .= "📦 *#{$order->tracking_code}*\n";
            $msg .= "📱 *Customer:* {$order->customer_name} ({$order->customer_phone})\n";
            if ($itemsStr) {
                $msg .= "🍽️ *Items:* {$itemsStr}\n";
            }
            $msg .= "💰 *Total:* Rs. " . number_format($order->total) . " (COD)\n";
            $msg .= "📍 *Address:* {$order->delivery_address}\n";
            if ($order->delivery_place_name) {
                $msg .= "📌 *Landmark:* {$order->delivery_place_name}\n";
            }
            $msg .= "\n✅ Login to dashboard to confirm order.";

            BotEvolutionClient::sendMessage($this->restaurant, $ownerPhone, $msg);
        } catch (\Throwable $e) {
            Log::warning("Owner notification failed: " . $e->getMessage());
        }
    }

    public function handleOrderStatus(array $nlu): string
    {
        $code = $nlu['tracking_code'] ?? null;
        $query = Order::where('restaurant_id', $this->restaurant->id);

        if ($code) {
            $query->where('tracking_code', $code);
        } else {
            $query->where('customer_phone', $this->cleanPhone)->latest();
        }

        $order = $query->first();
        if (!$order) {
            return "Aapka koi active order nahi mila.";
        }

        $statusEmoji = match ($order->status) {
            'pending' => '⏳ Pending (Restaurant confirming)',
            'preparing' => '👨‍🍳 Preparing in kitchen',
            'out_for_delivery' => '🛵 Out for delivery',
            'delivered' => '✅ Delivered',
            'cancelled' => '❌ Cancelled',
            default => $order->status,
        };

        $appUrl = config('app.url', 'http://localhost');
        $trackingUrl = "{$appUrl}/track/{$order->tracking_code}";

        return "📦 *Order #{$order->id} Status:*\n\nStatus: {$statusEmoji}\nTotal: Rs. " . number_format($order->total) . "\nLive Link: {$trackingUrl}";
    }

    // =========================================================================
    // RESOLUTION & RENDERING HELPERS
    // =========================================================================

    public function resolveMenuItemFromDb(string $query): ?MenuItem
    {
        $queryClean = strtolower(trim($query));

        // Exact match
        $item = MenuItem::where('restaurant_id', $this->restaurant->id)
            ->whereRaw('LOWER(name) = ?', [$queryClean])
            ->first();

        if ($item) {
            return $item;
        }

        // Substring match
        $item = MenuItem::where('restaurant_id', $this->restaurant->id)
            ->whereRaw('LOWER(name) LIKE ?', ["%{$queryClean}%"])
            ->first();

        if ($item) {
            return $item;
        }

        // Keyword token match
        $words = array_filter(explode(' ', $queryClean), fn($w) => strlen($w) >= 3);
        $allItems = MenuItem::where('restaurant_id', $this->restaurant->id)
            ->where('is_available', true)
            ->get();

        $best = null;
        $highestScore = 0;

        foreach ($allItems as $mItem) {
            $mName = strtolower($mItem->name);
            $score = 0;
            foreach ($words as $w) {
                if (str_contains($mName, $w)) {
                    $score++;
                }
            }
            if ($score > $highestScore) {
                $highestScore = $score;
                $best = $mItem;
            }
        }

        return $best;
    }

    protected function matchVariant(MenuItem $item, string $variantName): ?MenuItemVariant
    {
        $vName = strtolower(trim($variantName));
        foreach ($item->variants as $variant) {
            $curr = strtolower($variant->name);
            if ($curr === $vName || str_contains($curr, $vName) || str_contains($vName, $curr)) {
                return $variant;
            }
        }
        return null;
    }

    protected function extractVariantFromText(string $text, $variants): ?string
    {
        $lower = strtolower($text);
        foreach ($variants as $v) {
            if (stripos($lower, strtolower($v->name)) !== false) {
                return $v->name;
            }
        }

        if (preg_match('/\b(s|small)\b/i', $text)) return 'Small';
        if (preg_match('/\b(m|medium|med)\b/i', $text)) return 'Medium';
        if (preg_match('/\b(l|large)\b/i', $text)) return 'Large';
        if (preg_match('/\b(xl|extra large)\b/i', $text)) return 'XL';

        return null;
    }

    protected function isAffirmative(string $text): bool
    {
        return (bool)preg_match('/\b(yes|ha|haan|confirm|theek|ok|g|jee|sahi|order kar do|done|update kar do)\b/i', $text);
    }

    protected function isNegative(string $text): bool
    {
        return (bool)preg_match('/\b(no|nahi|cancel|rehne do|mat karo|stop)\b/i', $text);
    }

    public function renderMenuText(): string
    {
        $items = MenuItem::where('restaurant_id', $this->restaurant->id)
            ->where('is_available', true)
            ->with('variants')
            ->get();

        if ($items->isEmpty()) {
            return "Menu abhi upload nahi hua.";
        }

        $restaurantName = $this->restaurant->name ?? 'Foodio';
        $out = "📜 *{$restaurantName} — Menu:*\n\n";

        foreach ($items as $item) {
            if ($item->variants->isNotEmpty()) {
                $out .= "🍕 *{$item->name}*\n";
                foreach ($item->variants as $v) {
                    $out .= "   ▫️ {$v->name}: Rs. " . number_format($v->price) . "\n";
                }
            } else {
                $out .= "🍔 *{$item->name}* — Rs. " . number_format($item->price) . "\n";
            }
        }

        $out .= "\n_Apna order likh kar bhejein (e.g. '1 Large Shahi Pizza aur 2 Coke')_";
        return $out;
    }

    public function renderCartSummary(): string
    {
        $cart = $this->session['cart'] ?? [];
        if (empty($cart)) {
            return "🛒 Cart: Khali";
        }

        $out = "🛒 *Aapka Cart:*\n";
        $total = 0;
        foreach ($cart as $item) {
            $name = $item['name'] . ($item['variant_name'] ? " ({$item['variant_name']})" : '');
            $out .= "• {$item['quantity']}x {$name} = Rs. " . number_format($item['subtotal']) . "\n";
            $total += $item['subtotal'];
        }

        $out .= "━━━━━━━━━━━━━\n";
        $out .= "*Subtotal:* Rs. " . number_format($total);
        return $out;
    }

    public function renderFinalOrderReview(): string
    {
        $cart = $this->session['cart'] ?? [];
        $subtotal = 0;
        $itemsText = "";

        foreach ($cart as $item) {
            $name = $item['name'] . ($item['variant_name'] ? " ({$item['variant_name']})" : '');
            $itemsText .= "• {$item['quantity']}x {$name} — Rs. " . number_format($item['subtotal']) . "\n";
            $subtotal += $item['subtotal'];
        }

        $deliveryCharge = (float)($this->restaurant->delivery_charge ?? 0);
        $total = $subtotal + $deliveryCharge;

        $name = $this->session['customer_name'] ?? 'N/A';
        $address = $this->session['customer_address'] ?? 'N/A';
        $poi = $this->session['poi_name'] ? "\n📍 *Landmark:* {$this->session['poi_name']}" : "";
        $coords = ($this->session['delivery_lat'] && $this->session['delivery_lng']) ? "\n📌 *GPS:* {$this->session['delivery_lat']}, {$this->session['delivery_lng']}" : "";

        $out = "📋 *ORDER SUMMARY REVIEW:*\n\n";
        $out .= "{$itemsText}\n";
        $out .= "Subtotal: Rs. " . number_format($subtotal) . "\n";
        $out .= "Delivery Fee: Rs. " . number_format($deliveryCharge) . "\n";
        $out .= "━━━━━━━━━━━━━\n";
        $out .= "💰 *TOTAL:* Rs. " . number_format($total) . " (COD)\n\n";
        $out .= "👤 *Name:* {$name}\n";
        $out .= "🏠 *Address:* {$address}{$poi}{$coords}\n\n";
        $out .= "Kya yeh order confirm hai? Reply *Confirm* ya *Cancel*.";

        return $out;
    }

    protected function renderCartReview(): string
    {
        return $this->renderCartSummary() . "\n\nProceed karne ke liye *Checkout* likhein.";
    }

    protected function calculateDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}