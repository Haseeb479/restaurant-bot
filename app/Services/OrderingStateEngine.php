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
    public const STATE_WAITING_FOR_ORDER_CONFIRMATION = 'WAITING_FOR_ORDER_CONFIRMATION';
    public const STATE_WAITING_FOR_CONFIRMATION = self::STATE_WAITING_FOR_ORDER_CONFIRMATION;
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
            
            $hasActiveDraft = !empty($cart) && in_array($conversation->state, [
                self::STATE_COLLECT_CUSTOMER_INFO,
                self::STATE_WAITING_FOR_LOCATION,
                self::STATE_WAITING_FOR_CONFIRMATION,
                self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION,
            ], true);

            return [
                'state' => $conversation->state ?: self::STATE_WELCOME,
                'cart' => $cart,
                'customer_name' => $hasActiveDraft ? $this->sanitizeCustomerName($conversation->customer_name ?? null) : null,
                'customer_address' => $hasActiveDraft ? $this->sanitizeCustomerAddress($conversation->customer_address ?? null) : null,
                'delivery_lat' => $hasActiveDraft ? ($meta['delivery_lat'] ?? null) : null,
                'delivery_lng' => $hasActiveDraft ? ($meta['delivery_lng'] ?? null) : null,
                'poi_name' => $hasActiveDraft ? ($meta['poi_name'] ?? null) : null,
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
     * Check if customer has an active unconfirmed draft or order in progress
     */
    public function hasActiveDraft(): bool
    {
        $state = $this->getState();
        if (in_array($state, [
            self::STATE_COLLECT_CUSTOMER_INFO,
            self::STATE_WAITING_FOR_LOCATION,
            self::STATE_WAITING_FOR_CONFIRMATION,
            self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION,
            self::STATE_CLARIFY_NEW_OR_MODIFY,
        ], true)) {
            return true;
        }

        return !empty($this->session['cart']) || !empty($this->session['pending_mod_items']);
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

    public function resetSession(bool $keepCustomerProfile = false): void
    {
        $preservedName = $keepCustomerProfile ? $this->sanitizeCustomerName($this->session['customer_name'] ?? null) : null;
        $preservedAddr = $keepCustomerProfile ? $this->sanitizeCustomerAddress($this->session['customer_address'] ?? null) : null;
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
            'location_skipped' => false,
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
        if ($intent === 'CANCEL_ORDER' || $intent === 'RESET' || preg_match('/^(?:cancel|order\s+cancel|cancel\s+order|radd|khatam|stop|rehne\s+do)$/i', trim($nlu['raw_text'] ?? ''))) {
            if ($currentState === self::STATE_WAITING_FOR_MODIFICATION_CONFIRMATION) {
                return $this->handleWaitingForModificationConfirmationState($intent, $nlu);
            }

            $activeOrder = $this->getActiveOrder();
            if ($activeOrder && $activeOrder->status === 'pending') {
                $activeOrder->update(['status' => 'cancelled']);
            }

            $this->resetSession(false);
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        // Global trigger: Checkout / Proceed
        if ($intent === 'CHECKOUT' || preg_match('/^(?:checkout|check\s*out|proceed|aage\s*barho|bill)$/i', trim($nlu['raw_text'] ?? ''))) {
            if (!empty($this->session['cart'])) {
                $this->captureCustomerInfo($nlu);
                return $this->proceedToNextStepAfterCart();
            }
            return "Aapka cart khali hai. Pehle menu se item select karein (Reply *Menu*).";
        }

        // Global intent: Explicit New Order
        if ($intent === 'START_NEW_ORDER') {
            $this->resetSession(false);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct apna order batayein.";
        }

        // Global intent: Order Status
        if ($intent === 'ASK_ORDER_STATUS') {
            return $this->handleOrderStatus($nlu);
        }

        // Global trigger: Explicit Modification of Existing Order
        if ($intent === 'MODIFY_EXISTING_ORDER' || $this->isModifyExistingOrderPhrase($nlu['raw_text'] ?? '')) {
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

            case self::STATE_WAITING_FOR_ORDER_CONFIRMATION:
            case 'WAITING_FOR_CONFIRMATION':
                return $this->handleWaitingForOrderConfirmationState($intent, $nlu);

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
        $raw = trim($nlu['raw_text'] ?? '');

        // 1. Checkout / Proceed with cart
        if ($intent === 'CHECKOUT' || preg_match('/^(?:checkout|check\s*out|proceed|aage\s*barho|bill)$/i', $raw)) {
            if (!empty($this->session['cart'])) {
                $this->captureCustomerInfo($nlu);
                return $this->proceedToNextStepAfterCart();
            }
            return "Aapka cart khali hai. Pehle menu se item select karein (Reply *Menu*).";
        }

        if ($intent === 'SHOW_MENU') {
            return $this->renderMenuText();
        }

        if ($intent === 'VIEW_CART') {
            return $this->renderCartReview();
        }

        // If cart has items and customer provides info or confirms
        if (!empty($this->session['cart'])) {
            if ($intent === 'CONFIRM_ORDER' || $this->isAffirmative($raw)) {
                $this->captureCustomerInfo($nlu);
                if ($this->hasCompleteInfo()) {
                    return $this->executeOrderCreation();
                }
                return $this->proceedToNextStepAfterCart();
            }

            if (
                $intent === 'COLLECT_CUSTOMER_INFO' ||
                $intent === 'PROVIDE_NAME' ||
                $intent === 'PROVIDE_ADDRESS' ||
                $this->hasCustomerInfoProvided($nlu)
            ) {
                $this->captureCustomerInfo($nlu);
                return $this->proceedToNextStepAfterCart();
            }

            // Also check if raw text is a valid customer name
            if (empty($this->session['customer_name'])) {
                $sanitizedName = $this->sanitizeCustomerName($raw);
                if ($sanitizedName) {
                    $this->session['customer_name'] = $sanitizedName;
                    $this->saveSession();
                    return $this->proceedToNextStepAfterCart();
                }
            }
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

                $cartPrompt = empty($this->session['customer_name'])
                    ? "Kuch aur chahiye ya proceed karein?  Apna name bataein ?"
                    : (empty($this->session['customer_address'])
                        ? "Kuch aur chahiye ya proceed karein?  Apna address batayein ?"
                        : "Kuch aur chahiye ya proceed karein? (Type *Checkout*)");

                $reply .= $this->renderCartSummary();
                $reply .= "\n\n" . $cartPrompt;
                
                $this->transitionTo(self::STATE_MENU_SELECTION);
                return $reply;
            }
        }

        $variantList = $menuItem->variants->map(fn($v) => "• *{$v->name}*: Rs. " . number_format($v->price))->join("\n");
        return "Barahe karam darust size select karein:\n\n{$variantList}\n\nExample: _'{$menuItem->variants->first()->name}'_";
    }

    protected function handleCollectCustomerInfoState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        if ($intent === 'SHOW_MENU' || preg_match('/\b(?:menu|rate\s*list|card)\b/i', $raw)) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->renderMenuText();
        }

        if ($intent === 'CANCEL_ORDER' || $this->isNegative($raw)) {
            $this->resetSession(false);
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        if ($intent === 'START_NEW_ORDER' || preg_match('/\b(?:new|another|naya)\s*order\b/i', $raw)) {
            $this->resetSession(false);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct item batayein.";
        }

        $this->captureCustomerInfo($nlu);

        $justSetCustomerName = false;
        // If customer name is empty, sanitize raw_text
        if (empty($this->session['customer_name'])) {
            $sanitized = $this->sanitizeCustomerName($raw, $this->session['customer_name'] ?? null);
            if ($sanitized) {
                $this->session['customer_name'] = $sanitized;
                $this->saveSession();
                $justSetCustomerName = true;
            } else {
                return "Aapka shukriya! Barahe karam apna *Naam* (Full Name) batayein:";
            }
        }

        // If customer address is empty, sanitize raw_text
        if (empty($this->session['customer_address'])) {
            if ($justSetCustomerName) {
                return "Shukriya *{$this->session['customer_name']}*! Barahe karam apna *Delivery Address* batayein (House/Street/Area ya Landmark):";
            }

            $sanitizedAddr = $this->sanitizeCustomerAddress($raw, $this->session['customer_address'] ?? null);
            if ($sanitizedAddr && strtolower($sanitizedAddr) !== strtolower($this->session['customer_name'] ?? '')) {
                $valCheck = $this->validateAddressDistance($sanitizedAddr);
                if (!$valCheck['valid']) {
                    return $valCheck['error_message'];
                }

                $this->session['customer_address'] = $sanitizedAddr;
                $this->session['poi_name'] = null;
                $this->session['delivery_lat'] = null;
                $this->session['delivery_lng'] = null;
                $this->saveSession();
            } else {
                return "Shukriya *{$this->session['customer_name']}*! Barahe karam apna *Delivery Address* batayein (House/Street/Area ya Landmark):";
            }
        }

        // Both name and address are available!
        return $this->proceedToNextStepAfterCart();
    }

    protected function handleWaitingForLocationState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        if ($intent === 'SHOW_MENU' || preg_match('/\b(?:menu|rate\s*list|card)\b/i', $raw)) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->renderMenuText();
        }

        if ($intent === 'CANCEL_ORDER' || $this->isNegative($raw)) {
            $this->resetSession(false);
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        if ($intent === 'START_NEW_ORDER' || preg_match('/\b(?:new|another|naya)\s*order\b/i', $raw)) {
            $this->resetSession(false);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct item batayein.";
        }

        $rawLower = strtolower($raw);
        if (preg_match('/^(?:skip|nahi|rehne\s*do|no|skip\s*karo)$/i', $rawLower) || str_contains($rawLower, 'skip') || str_contains($rawLower, 'rehne do')) {
            $this->session['location_skipped'] = true;
            $this->saveSession();
            $this->transitionTo(self::STATE_WAITING_FOR_ORDER_CONFIRMATION);
            return $this->renderFinalOrderReview();
        }

        // Check if customer typed a manual address or landmark (e.g. "jamshaid Medical store")
        $manualAddress = $this->sanitizeCustomerAddress(!empty($nlu['address']) ? $nlu['address'] : $raw);
        if ($manualAddress && strtolower($manualAddress) !== strtolower($this->session['customer_name'] ?? '')) {
            $valCheck = $this->validateAddressDistance($manualAddress);
            if (!$valCheck['valid']) {
                return $valCheck['error_message'];
            }

            $this->session['customer_address'] = $manualAddress;
            $this->session['poi_name'] = null;
            $this->session['delivery_lat'] = null;
            $this->session['delivery_lng'] = null;
            $this->saveSession();
            $this->transitionTo(self::STATE_WAITING_FOR_ORDER_CONFIRMATION);
            return "📍 Delivery Address note kar liya gaya hai: *{$manualAddress}*.\n\n" . $this->renderFinalOrderReview();
        }

        return "📍 Barahe karam WhatsApp se apni *Current Location pin share karein* (Attachment 📎 -> Location).\n\nAgar aap pin share nahi kar sakte to *Skip* likhein ya apna *Address/Landmark* likhein.";
    }

    protected function handleWaitingForOrderConfirmationState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        // If customer says Confirm/Yes/Haan/Han/Ji/Okay/Done/etc., interpret as CONFIRM_ORDER
        if ($intent === 'CONFIRM_ORDER' || $this->isAffirmative($raw)) {
            return $this->executeOrderCreation();
        }

        if ($intent === 'CANCEL_ORDER' || $this->isNegative($raw)) {
            $this->resetSession(false);
            return "Aapka order cancel kar diya gaya hai aur cart clear ho gaya hai. Dobara order karne ke liye koi bhi message karein.";
        }

        if ($intent === 'SHOW_MENU' || preg_match('/\b(?:menu|rate\s*list|card)\b/i', $raw)) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->renderMenuText();
        }

        if ($intent === 'START_NEW_ORDER' || preg_match('/\b(?:new|another|naya)\s*order\b/i', $raw)) {
            $this->resetSession(false);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Theek hai! Naya order shuru karte hain. Menu dekhne ke liye *Menu* likhein ya direct item batayein.";
        }

        // Do NOT send confirmation text back through menu-item matching!
        // Only if legitimate ADD_ITEM intent or customer explicitly named an existing menu item in DB:
        if ($intent === 'ADD_ITEM' || !empty($nlu['items'])) {
            $items = $nlu['items'] ?? [];
            $hasRealMenuItem = false;
            foreach ($items as $itemData) {
                $itemName = trim($itemData['name'] ?? '');
                if (!empty($itemName) && $this->resolveMenuItemFromDb($itemName) !== null) {
                    $hasRealMenuItem = true;
                    break;
                }
            }

            if ($hasRealMenuItem) {
                $this->transitionTo(self::STATE_MENU_SELECTION);
                return $this->handleAddItems($nlu);
            }

            // Not a real menu item — check if customer meant to confirm
            if ($this->isAffirmative($raw)) {
                return $this->executeOrderCreation();
            }

            // Keep user in WAITING_FOR_ORDER_CONFIRMATION and prompt clearly
            return "Barahe karam order confirm karne ke liye *Confirm* likhein, ya cancel karne ke liye *Cancel* likhein.\n\n" . $this->renderFinalOrderReview();
        }

        if ($intent === 'REMOVE_ITEM') {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleRemoveItem($nlu);
        }

        // Helpful response for greetings/conversational questions to avoid infinite summary walls
        if (preg_match('/^(?:hi|hello|hey|salam|aoa|assalam(?:o|u)?\s*alaikum|kya\s*haal|sunen|suno|bhai)\b/i', $raw)) {
            $cartSummary = $this->renderCartSummary();
            return "Assalam-o-Alaikum! Aapka order checkout confirmation par hai:\n\n{$cartSummary}\n\n• Order confirm karne ke liye *Confirm* likhein\n• Cancel karne ke liye *Cancel* likhein\n• Menu dekhne ke liye *Menu* likhein";
        }

        return "Barahe karam order confirm karne ke liye *Yes / Confirm* likhein, ya cancel karne ke liye *Cancel* likhein.\n\n" . $this->renderFinalOrderReview();
    }

    protected function handleWaitingForConfirmationState(string $intent, array $nlu): string
    {
        return $this->handleWaitingForOrderConfirmationState($intent, $nlu);
    }

    protected function handleCompletedState(string $intent, array $nlu): string
    {
        $raw = trim($nlu['raw_text'] ?? '');

        $activeOrder = $this->getActiveOrder();

        // If previous order was cancelled, delivered, or none exists, treat as fresh session
        if (!$activeOrder || in_array($activeOrder->status, ['cancelled', 'delivered'], true)) {
            $this->resetSession(false);
            $this->transitionTo(self::STATE_WELCOME);
            return $this->handleWelcomeState($intent, $nlu);
        }

        // If customer explicitly asks for new order
        if ($intent === 'START_NEW_ORDER' || preg_match('/\b(new|another|naya|alag)\s*order\b/i', $raw)) {
            $this->resetSession(false);
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
            if ($this->isOrderModifiable($activeOrder)) {
                // Ambiguous: ask whether to modify active order or start new order
                $this->session['pending_mod_items'] = $nlu['items'] ?? [];
                $this->transitionTo(self::STATE_CLARIFY_NEW_OR_MODIFY);

                $itemsNames = implode(', ', array_map(fn($i) => ($i['quantity'] ?? 1) . 'x ' . ($i['name'] ?? ''), $this->session['pending_mod_items']));
                return "Aapka *Order #{$activeOrder->id}* abhi pending hai.\n\nKya aap *{$itemsNames}* usi order mein add karna chahte hain ya naya alag order banana chahte hain?\n\n1️⃣ Reply *Same Order* (Order #{$activeOrder->id} mein add hoga)\n2️⃣ Reply *New Order* (Alag naya order banega)";
            }

            // If not modifiable, start new order
            $this->resetSession(true);
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return $this->handleAddItems($nlu);
        }

        // If customer sends greeting like "hi" or "salam"
        if (preg_match('/^(?:hi|hello|hey|salam|aoa|assalam(?:o|u)?\s*alaikum)\b/i', $raw)) {
            $statusEmoji = match ($activeOrder->status) {
                'pending' => '⏳ Pending (Kitchen confirming)',
                'preparing' => '👨‍🍳 Preparing in kitchen',
                'out_for_delivery' => '🛵 Out for delivery',
                default => $activeOrder->status,
            };
            return "Assalam-o-Alaikum! Aapka *Order #{$activeOrder->id}* abhi *{$statusEmoji}* hai.\n\n• Naya order karne ke liye *New Order* ya *Menu* likhein\n• Order modify karne ke liye *Modify Order* likhein";
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
                    $detectedSize = $this->extractVariantFromText($itemName, $menuItem->variants)
                        ?? $this->extractVariantFromText($nlu['raw_text'] ?? '', $menuItem->variants);
                    if ($detectedSize) {
                        $matchedVariant = $this->matchVariant($menuItem, $detectedSize);
                    }
                }

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

        $restLat = $this->restaurant->restaurant_lat;
        $restLng = $this->restaurant->restaurant_lng;
        $maxRadius = (float)$this->restaurant->maxDeliveryRadiusKm();

        // 1. Authoritative Backend Distance Calculation
        if ($restLat && $restLng) {
            $dist = $this->calculateDistanceKm((float)$restLat, (float)$restLng, $lat, $lng);
            if ($dist > $maxRadius) {
                $distRounded = round($dist, 1);
                $this->session['location_valid'] = false;
                $this->session['delivery_lat'] = null;
                $this->session['delivery_lng'] = null;
                $this->session['delivery_distance_km'] = null;
                $this->session['location_source'] = null;
                $this->saveSession();

                return "Maazrat! Yeh location hamare delivery radius ({$maxRadius} km) se bahar hai (Faasla: {$distRounded} km door hai). Hum sirf {$maxRadius} km ke andar delivery karte hain. Barahe karam delivery area ke andar ki location pin share karein.";
            }
        }

        // Inside radius → location_valid = true
        $distCalculated = ($restLat && $restLng) ? round($this->calculateDistanceKm((float)$restLat, (float)$restLng, $lat, $lng), 2) : null;
        $this->session['location_valid'] = true;
        $this->session['delivery_lat'] = $lat;
        $this->session['delivery_lng'] = $lng;
        $this->session['delivery_distance_km'] = $distCalculated;
        $this->session['location_source'] = 'whatsapp_pin';
        $this->session['location_skipped'] = false;

        // 2. Supplemental POI / Landmark Detection (Never replaces GPS coords)
        $placeName = null;
        $resolvedAddress = null;
        try {
            $locService = app(\App\Services\LocationResolutionService::class);
            $resolution = $locService->resolve($lat, $lng);
            $placeName = $resolution['delivery_place_name'] ?? null;
            $resolvedAddress = $resolution['delivery_address'] ?? $placeName;
        } catch (\Throwable $e) {
            Log::warning("LocationResolutionService error in State Engine: " . $e->getMessage());
        }

        $this->session['poi_name'] = $placeName ?: null;

        // Display address: preserve manual typed address if customer provided one, otherwise use reverse geocode
        if (empty($this->session['customer_address']) ||
            str_starts_with($this->session['customer_address'], 'GPS Pin') ||
            str_starts_with($this->session['customer_address'], 'WhatsApp')) {
            $this->session['customer_address'] = $resolvedAddress ?: ($placeName ?: "GPS Pin ({$lat}, {$lng})");
        }

        $this->saveSession();

        if (empty($this->session['cart'])) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            $locTxt = $this->session['poi_name'] ? " ({$this->session['poi_name']})" : "";
            return "📍 Location receive ho gayi hai{$locTxt}!\n\nAb bataiye aap kya order karna chahenge? (Type *Menu* to see all items)";
        }

        // Check if customer name is missing:
        if (empty($this->session['customer_name'])) {
            $this->transitionTo(self::STATE_COLLECT_CUSTOMER_INFO);
            $locTxt = $this->session['poi_name'] ? " (*{$this->session['poi_name']}*)" : "";
            return "📍 Location confirm ho gayi hai{$locTxt}!\n\nOrder aage barhane ke liye barahe karam apna *Naam* (Full Name) batayein:";
        }

        $this->transitionTo(self::STATE_WAITING_FOR_ORDER_CONFIRMATION);
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

        // Action-word guard: if non-food command was mistakenly parsed as an item name
        $filteredItems = [];
        $hasCheckout = false;
        foreach ($items as $itemData) {
            $lower = strtolower(trim($itemData['name'] ?? ''));
            if (in_array($lower, ['checkout', 'check out', 'proceed', 'bill', 'order', 'menu'], true) ||
                preg_match('/^(?:checkout|check\s*out|proceed|bill)$/i', $lower)) {
                $hasCheckout = true;
                continue;
            }
            $filteredItems[] = $itemData;
        }

        if (empty($filteredItems)) {
            if ($hasCheckout && !empty($this->session['cart'])) {
                $this->captureCustomerInfo($nlu);
                return $this->proceedToNextStepAfterCart();
            }
            return "Aap kya order karna chahte hain? Barahe karam item ka naam batayein.";
        }

        $items = $filteredItems;

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
                    $detectedSize = $this->extractVariantFromText($itemName, $menuItem->variants)
                        ?? $this->extractVariantFromText($nlu['raw_text'] ?? '', $menuItem->variants);
                    if ($detectedSize) {
                        $matchedVariant = $this->matchVariant($menuItem, $detectedSize);
                    }
                }

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

        $cartPrompt = empty($this->session['customer_name'])
            ? "Kuch aur chahiye ya proceed karein?  Apna name bataein ?"
            : (empty($this->session['customer_address'])
                ? "Kuch aur chahiye ya proceed karein?  Apna address batayein ?"
                : "Kuch aur chahiye ya proceed karein? (Type *Checkout*)");

        return "✅ Added to Cart:\n" . implode("\n", $addedSummary) . "\n\n" . $this->renderCartSummary() . "\n\n" . $cartPrompt;
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

        // Validate address radius if an address is set
        $valCheck = $this->validateAddressDistance($this->session['customer_address']);
        if (!$valCheck['valid']) {
            $this->session['customer_address'] = null;
            $this->session['delivery_lat'] = null;
            $this->session['delivery_lng'] = null;
            $this->session['poi_name'] = null;
            $this->saveSession();
            $this->transitionTo(self::STATE_COLLECT_CUSTOMER_INFO);
            return $valCheck['error_message'];
        }

        $this->transitionTo(self::STATE_WAITING_FOR_ORDER_CONFIRMATION);
        return $this->renderFinalOrderReview();
    }

    public function sanitizeCustomerName(?string $value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback ? $this->sanitizeCustomerName($fallback) : null;
        }

        $val = trim($value);
        if ($val === '') {
            return $fallback ? $this->sanitizeCustomerName($fallback) : null;
        }

        // Reject if contains digits, URLs, or punctuation indicating sentence/instructions
        if (preg_match('/[0-9?:;!+=_<>@#$%^&*()]/', $val)) {
            return $fallback ? $this->sanitizeCustomerName($fallback) : null;
        }

        // Length checks: names are 2-35 chars and max 4 words
        $words = array_filter(explode(' ', $val));
        if (count($words) < 1 || count($words) > 4 || strlen($val) < 2 || strlen($val) > 35) {
            return $fallback ? $this->sanitizeCustomerName($fallback) : null;
        }

        // Reject conversational phrases, verbs, food items, or ordering instructions
        $stopWordsRegex = '/\b(?:add|kr|kro|kardo|kardi|kr\s*do|kr\s*di|kar\s*do|karo|karna|kar|de\s*do|bata\s*do|bhej\s*do|daal\s*do|mangwa|mangwana|chahiye|order|summary|summery|menu|address|pata|location|ghar|street|house|road|block|sector|same|wohi|wahi|pichla|pichle|pehle|use|kuch|sab|sub|usi|bhai|janab|suno|yes|no|ok|theek|confirm|cancel|radd|deal|wrap|pizza|burger|coke|bottle|drink|fries|shawarma|roll|biryani|price|rate|rupaye|rs|hi|hello|hey|salam)\b/iu';

        if (preg_match($stopWordsRegex, $val)) {
            return $fallback ? $this->sanitizeCustomerName($fallback) : null;
        }

        return ucwords(strtolower($val));
    }

    public function sanitizeCustomerAddress(?string $value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback ? $this->sanitizeCustomerAddress($fallback) : null;
        }

        $val = trim($value);
        if ($val === '') {
            return $fallback ? $this->sanitizeCustomerAddress($fallback) : null;
        }

        // Check if customer explicitly requested previous address
        if (preg_match('/^(?:same|wohi|wahi|same\s*address|same\s*location|same\s*pata|pehle\s*wala|pehle\s*wali|previous|sab\s*kuch\s*wohi|usi\s*order|same\s*usi)$/iu', $val) ||
            preg_match('/\b(?:same|wohi|wahi|pichla|pehle|use\s*kr|use\s*kar|sub\s*kuch|sab\s*kuch)\b/iu', $val)) {
            $pastOrder = Order::where('restaurant_id', $this->restaurant->id)
                ->where('customer_phone', $this->cleanPhone)
                ->whereNotNull('delivery_address')
                ->latest()
                ->first();
            if ($pastOrder && !empty($pastOrder->delivery_address)) {
                return $pastOrder->delivery_address;
            }
            return $fallback ? $this->sanitizeCustomerAddress($fallback) : null;
        }

        // Check for conversational instruction verbs, queries, or order item names
        if (preg_match('/\b(?:add\s*kar|kr\s*do|kardo|kar\s*do|daal\s*do|bhej\s*do|bhejo|bhejna|bhej|mangwana|mangwao|order\s*me|same\s*order|is\s*me|wrap|pizza|burger|coke|deal|rupaye|rs\.?|summery|summary|bata\s*do|de\s*do|use\s*kro|use\s*karo|cancel|confirm|menu|kahan|status|track|chahiye|suno|bhai|skip|rehne\s*do|nahi|no)\b/iu', $val)) {
            return $fallback ? $this->sanitizeCustomerAddress($fallback) : null;
        }

        if (strlen($val) > 120 || strlen($val) < 3) {
            return $fallback ? $this->sanitizeCustomerAddress($fallback) : null;
        }

        return $val;
    }

    public function sanitizeCustomerField(?string $value, ?string $fallback = null): ?string
    {
        return $this->sanitizeCustomerAddress($value, $fallback);
    }

    protected function captureCustomerInfo(array $nlu): void
    {
        if (!empty($nlu['name']) && empty($this->session['customer_name'])) {
            $cleanName = $this->sanitizeCustomerName($nlu['name'], $this->session['customer_name'] ?? null);
            if ($cleanName) {
                $this->session['customer_name'] = $cleanName;
            }
        }

        if (!empty($nlu['address'])) {
            $cleanAddr = $this->sanitizeCustomerAddress($nlu['address'], $this->session['customer_address'] ?? null);
            if ($cleanAddr) {
                $valCheck = $this->validateAddressDistance($cleanAddr);
                if ($valCheck['valid']) {
                    $this->session['customer_address'] = $cleanAddr;
                    $this->session['poi_name'] = null;
                    $this->session['delivery_lat'] = null;
                    $this->session['delivery_lng'] = null;
                }
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
        $currentState = $this->getState();
        if ($currentState !== self::STATE_WAITING_FOR_ORDER_CONFIRMATION && $currentState !== 'WAITING_FOR_CONFIRMATION') {
            Log::warning("Order creation blocked: State is {$currentState}, expected WAITING_FOR_ORDER_CONFIRMATION.");
            return "Order create karne ke liye pehle summary review confirm karein.";
        }

        $cart = $this->session['cart'] ?? [];
        if (empty($cart)) {
            $this->transitionTo(self::STATE_MENU_SELECTION);
            return "Aapka cart khaali hai. Order create nahi ho sakta.";
        }

        try {
            $orderService = app(\App\Services\OrderService::class);
            $order = $orderService->createOrder($this->restaurant, $this->session, $this->cleanPhone);
        } catch (\DomainException $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            Log::error("executeOrderCreation failed: " . $e->getMessage(), ['exception' => $e]);
            return "Maazrat! Order process karne mein masla paish aaya hai. Barahe karam thori dair baad dobara koshish karein.";
        }

        $this->notifyOwner($order);

        // Rule 6: Cart/Order Separation - finalize cart immediately
        $this->session['last_order_id'] = $order->id;
        $this->session['cart'] = [];
        $this->session['customer_name'] = null;
        $this->session['customer_address'] = null;
        $this->session['delivery_lat'] = null;
        $this->session['delivery_lng'] = null;
        $this->session['delivery_distance_km'] = null;
        $this->session['poi_name'] = null;
        $this->session['modifying_order_id'] = null;
        $this->session['pending_mod_items'] = [];
        $this->session['location_skipped'] = false;
        $this->session['location_valid'] = null;
        $this->transitionTo(self::STATE_ORDER_CREATED);

        $receipt = "🎉 *AAPKA ORDER PLACE HO GAYA HAI!*\n\n";
        $receipt .= "🆔 *Order #{$order->id}*\n";
        $receipt .= "👤 *Customer:* {$order->customer_name}\n";
        $receipt .= "📍 *Delivery to:* " . ($order->delivery_place_name ? "{$order->delivery_place_name} ({$order->delivery_address})" : $order->delivery_address) . "\n";
        $receipt .= "💵 *Payment:* Cash on Delivery\n";
        $receipt .= "🍔 *Food Subtotal:* Rs. " . number_format($order->subtotal) . "\n";
        $receipt .= "🛵 *Delivery Charges:* Restaurant review karke confirm karega.\n\n";
        $receipt .= "⏱️ Restaurant jald hi order accept karke final bill WhatsApp par bhejega.\n\n";
        $receipt .= "Shukriya! ❤️";

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

        return "📦 *Order #{$order->id} Status:*\n\nStatus: {$statusEmoji}\nTotal: Rs. " . number_format($order->total);
    }

    // =========================================================================
    // RESOLUTION & RENDERING HELPERS
    // =========================================================================

    public function resolveMenuItemFromDb(string $query): ?MenuItem
    {
        $queryClean = strtolower(trim($query));

        // Authority Rule: Never allow confirmation words or workflow actions to be searched as menu items
        if (empty($queryClean) || in_array($queryClean, [
            'confirm', 'confim', 'cnfrm', 'cnfm', 'conferm', 'confrm', 'comfirm', 'confrim',
            'yes', 'yep', 'yup', 'haan', 'ha', 'han', 'jee', 'ji', 'theek', 'thek', 'ok', 'okay',
            'done', 'cancel', 'radd', 'skip', 'menu', 'status', 'track', 'kardo', 'kr do', 'bhej do', 'bhejo'
        ], true)) {
            return null;
        }

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
        if ($vName === 'chota' || $vName === 'choti' || $vName === 's') $vName = 'small';
        if ($vName === 'darmiyana' || $vName === 'darmiyani' || $vName === 'med' || $vName === 'm') $vName = 'medium';
        if ($vName === 'bara' || $vName === 'bari' || $vName === 'bada' || $vName === 'l') $vName = 'large';
        if ($vName === 'extra large') $vName = 'xl';

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

        if (preg_match('/\b(s|small|chota|choti)\b/i', $text)) return 'Small';
        if (preg_match('/\b(m|medium|med|darmiyana|darmiyani)\b/i', $text)) return 'Medium';
        if (preg_match('/\b(l|large|bara|bari|bada)\b/i', $text)) return 'Large';
        if (preg_match('/\b(xl|extra large)\b/i', $text)) return 'XL';

        return null;
    }

    protected function isAffirmative(string $text): bool
    {
        $clean = strtolower(trim($text));
        if (preg_match('/\b(yes|ha|haan|han|confirm|confim|cnfrm|cnfm|conferm|confrm|comfirm|confrim|theek|thek|ok|okay|g|jee|ji|sahi|order\s+kar\s+do|done|update\s+kar\s+do|bhej\s*do|bhejo|kardo|kr\s*do|yup|yep|bilkul|kar\s*dain|kar\s*den)\b/i', $clean)) {
            return true;
        }

        $words = preg_split('/\s+/', $clean);
        foreach ($words as $w) {
            if (strlen($w) >= 4 && (levenshtein($w, 'confirm') <= 2 || levenshtein($w, 'confirmed') <= 2)) {
                return true;
            }
        }

        return false;
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

        $name = $this->session['customer_name'] ?? 'N/A';
        $address = $this->session['customer_address'] ?? 'N/A';
        if (strtolower(trim((string)$address)) === strtolower(trim((string)$name)) || empty($address)) {
            $address = $this->session['poi_name'] ?? 'N/A';
        }
        $poi = ($this->session['poi_name'] && strtolower(trim((string)$this->session['poi_name'])) !== strtolower(trim((string)$address)))
            ? "\n📍 *Landmark:* {$this->session['poi_name']}"
            : "";
        $coords = ($this->session['delivery_lat'] && $this->session['delivery_lng']) ? "\n📌 *GPS:* {$this->session['delivery_lat']}, {$this->session['delivery_lng']}" : "";

        $out = "📋 *ORDER SUMMARY:*\n\n";
        $out .= "{$itemsText}\n";
        $out .= "━━━━━━━━━━━━━\n";
        $out .= "🍔 *Food Total:* Rs. " . number_format($subtotal) . "\n";
        $out .= "🛵 *Delivery:* (Restaurant confirms upon order acceptance)\n\n";
        $out .= "👤 *Name:* {$name}\n";
        $out .= "🏠 *Address:* {$address}{$poi}{$coords}\n\n";
        $out .= "Order confirm karein? Reply *Confirm* ya *Cancel*.";

        return $out;
    }

    protected function renderCartReview(): string
    {
        return $this->renderCartSummary() . "\n\nProceed karne ke liye *Checkout* likhein.";
    }

    /**
     * Validates whether an address string is within the restaurant delivery radius.
     */
    public function validateAddressDistance(string $address): array
    {
        $cleanAddr = trim($address);
        if (empty($cleanAddr)) {
            return [
                'valid' => false,
                'lat' => null,
                'lng' => null,
                'resolved_address' => null,
                'error_message' => "Barahe karam durust delivery address batayein.",
            ];
        }

        $restLat = $this->restaurant->restaurant_lat;
        $restLng = $this->restaurant->restaurant_lng;
        $maxRadius = $this->restaurant->maxDeliveryRadiusKm();
        $restCity = strtolower(trim($this->restaurant->city ?? ''));

        // 1. Explicit Major Cities rejection (when restaurant is in another city)
        $majorCities = ['karachi', 'lahore', 'islamabad', 'rawalpindi', 'peshawar', 'quetta', 'faisalabad', 'sialkot', 'gujranwala'];
        foreach ($majorCities as $mc) {
            if (preg_match('/\b' . preg_quote($mc, '/') . '\b/i', $cleanAddr) && !str_contains($restCity, $mc)) {
                return [
                    'valid' => false,
                    'lat' => null,
                    'lng' => null,
                    'resolved_address' => $cleanAddr,
                    'error_message' => "Maazrat! Hum sirf local delivery karte hain ({$this->restaurant->city}). {$cleanAddr} hamare delivery radius ({$maxRadius} km) se bahar hai.",
                ];
            }
        }

        // 2. City-specific distant landmarks check
        // If restaurant is in/near Bahawalpur:
        if (str_contains($restCity, 'bahawalpur') || ($restLat && $restLat < 29.50 && $restLat > 29.30)) {
            if ($maxRadius < 20.0 && preg_match('/\b(?:dha|d\.h\.a|defence)\b/i', $cleanAddr)) {
                return [
                    'valid' => false,
                    'lat' => null,
                    'lng' => null,
                    'resolved_address' => 'DHA Bahawalpur',
                    'error_message' => "Maazrat! DHA Bahawalpur hamare delivery radius ({$maxRadius} km) se bahar hai (~22 km door hai). Hum sirf Bahawalpur city ke andar delivery karte hain. Barahe karam delivery area ke andar ka address share karein.",
                ];
            }
            if (preg_match('/\b(?:ahmedpur|yazman|uch\s*sharif|lal\s*suhanra)\b/i', $cleanAddr)) {
                return [
                    'valid' => false,
                    'lat' => null,
                    'lng' => null,
                    'resolved_address' => $cleanAddr,
                    'error_message' => "Maazrat! Yeh ilaqa hamare delivery radius ({$maxRadius} km) se bahar hai. Barahe karam Bahawalpur city ke andar ka address share karein.",
                ];
            }
        }

        // If restaurant is in/near Lodhran:
        if (str_contains($restCity, 'lodhran') || ($restLat && $restLat >= 29.50 && $restLat <= 29.65)) {
            if (preg_match('/\b(?:multan|dunyapur|dunya\s*pur|kahror\s*pakka|kahror|jalalpur|jalal\s*pur)\b/i', $cleanAddr)) {
                return [
                    'valid' => false,
                    'lat' => null,
                    'lng' => null,
                    'resolved_address' => $cleanAddr,
                    'error_message' => "Maazrat! Yeh ilaqa hamare delivery radius ({$maxRadius} km) se bahar hai. Hum sirf Lodhran city aur qareebi ilaqon mein delivery karte hain.",
                ];
            }
        }

        if (!$restLat || !$restLng) {
            return [
                'valid' => true,
                'lat' => null,
                'lng' => null,
                'resolved_address' => $cleanAddr,
                'error_message' => null,
            ];
        }

        // 3. Authority Rule: Never infer delivery eligibility from the customer's typed address.
        // WhatsApp shared GPS lat/lng is the authoritative delivery location.
        // Typed address is accepted as customer address text without geocoding inference.
        return [
            'valid'            => true,
            'lat'              => null,
            'lng'              => null,
            'resolved_address' => $cleanAddr,
            'error_message'    => null,
        ];
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