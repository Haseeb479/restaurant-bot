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
                'last_order_id' => null,
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
        ];
    }

    /**
     * Persist session state to cache and database
     */
    public function saveSession(): void
    {
        Cache::put($this->sessionKey, $this->session, now()->addHours(2));

        $meta = [
            'delivery_lat' => $this->session['delivery_lat'],
            'delivery_lng' => $this->session['delivery_lng'],
            'poi_name' => $this->session['poi_name'],
            'pending_variant_item' => $this->session['pending_variant_item'],
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

    public function resetSession(): void
    {
        $this->session = [
            'state' => self::STATE_WELCOME,
            'cart' => [],
            'customer_name' => null,
            'customer_address' => null,
            'delivery_lat' => null,
            'delivery_lng' => null,
            'poi_name' => null,
            'pending_variant_item' => null,
            'last_order_id' => null,
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
            $this->resetSession();
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        // Global intent: Order Status
        if ($intent === 'ASK_ORDER_STATUS') {
            return $this->handleOrderStatus($nlu);
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

        // If customer name is empty, try using raw_text if it looks like a name
        if (empty($this->session['customer_name'])) {
            if ($raw !== '' && strlen($raw) <= 35 && !preg_match('/\b(menu|cancel|pizza|burger|yes|no|skip)\b/i', $raw)) {
                $this->session['customer_name'] = $raw;
                $this->saveSession();
            } else {
                return "Aapka shukriya! Barahe karam apna *Naam* (Full Name) batayein:";
            }
        }

        // If customer address is empty, check if provided or if raw_text is the address
        if (empty($this->session['customer_address'])) {
            if ($raw !== '' && $raw !== $this->session['customer_name'] && strlen($raw) <= 80 && !preg_match('/\b(menu|cancel|pizza|burger|yes|no|skip)\b/i', $raw)) {
                $this->session['customer_address'] = $raw;
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
            $this->session['customer_address'] = $nlu['address'];
            $this->saveSession();
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
            $this->resetSession();
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
        if ($intent === 'SHOW_MENU' || $intent === 'ADD_ITEM') {
            $this->resetSession();
            return $this->handleWelcomeState($intent, $nlu);
        }

        if ($intent === 'ASK_ORDER_STATUS') {
            return $this->handleOrderStatus($nlu);
        }

        return "Aapka order process ho raha hai! Agar naya order karna chahte hain to *Menu* likhein.";
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
    // FLOW LOGIC & HELPERS
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

    protected function captureCustomerInfo(array $nlu): void
    {
        if (!empty($nlu['name']) && empty($this->session['customer_name'])) {
            $this->session['customer_name'] = trim($nlu['name']);
        }

        if (!empty($nlu['address'])) {
            $this->session['customer_address'] = trim($nlu['address']);
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

        $this->session['last_order_id'] = $order->id;
        $this->session['cart'] = [];
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
        return (bool)preg_match('/\b(yes|ha|haan|confirm|theek|ok|g|jee|sahi|order kar do|done)\b/i', $text);
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