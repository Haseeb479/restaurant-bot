<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- The tracking code is in the URL. Keep it out of search indexes and out
         of the Referer header sent to the CDNs, fonts and wa.me links below. --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>Live Order Tracking | {{ $order ? $order->tracking_code : 'Track Your Order' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }
        .pulse-live { animation: pulse-dot 1.8s infinite ease-in-out; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">

<div class="max-w-xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-live"></span> Live Order Tracker
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            {{ $order ? $order->restaurant->name : 'Order Status' }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">Real-time status of your food delivery</p>
    </div>

    <!-- Search / Change Code Form -->
    <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200/80 mb-6">
        <form action="{{ route('order.track.live') }}" method="GET" class="flex gap-2">
            <input 
                type="text" 
                name="code" 
                value="{{ $order ? $order->tracking_code : request('code') }}" 
                placeholder="Enter Tracking Code (e.g. JC-2026-00001)" 
                required
                class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white transition"
            >
            <button 
                type="submit" 
                class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition active:scale-95"
            >
                Track
            </button>
        </form>
    </div>

    @if(!$order)
        <!-- Not Found State -->
        <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 shadow-sm">
            <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                🔍
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">No Order Found</h3>
            <p class="text-sm text-slate-500 mb-4">
                Please enter a valid tracking code or send your tracking code directly in WhatsApp.
            </p>
        </div>
    @else
        <!-- Order Found & Active Tracker -->
        <div id="tracker-container" class="space-y-6">

            <!-- Status Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                    <div>
                        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tracking Code</span>
                        <div class="text-lg font-bold text-slate-900 tracking-wide font-mono">{{ $order->tracking_code }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Order Time</span>
                        <div class="text-sm font-medium text-slate-700">{{ $order->created_at->format('h:i A') }}</div>
                    </div>
                </div>

                <!-- Status Progress Stepper -->
                @php
                    $steps = [
                        'pending'          => ['label' => 'Received',  'icon' => '📝'],
                        'confirmed'        => ['label' => 'Confirmed', 'icon' => '✅'],
                        'preparing'        => ['label' => 'Preparing', 'icon' => '👨‍🍳'],
                        'out_for_delivery' => ['label' => 'On the Way','icon' => '🛵'],
                        'delivered'        => ['label' => 'Delivered', 'icon' => '🎉'],
                    ];
                    $stepKeys = array_keys($steps);
                    $currentIndex = array_search($order->status, $stepKeys);
                    if ($currentIndex === false && $order->status === 'cancelled') {
                        $currentIndex = -1;
                    }
                @endphp

                @if($order->status === 'cancelled')
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-center">
                        <span class="text-rose-700 font-bold">❌ Order Cancelled</span>
                        <p class="text-xs text-rose-600 mt-1">This order has been cancelled by the restaurant.</p>
                    </div>
                @else
                    <div class="relative flex justify-between items-center mb-6">
                        <!-- Progress Line -->
                        <div class="absolute top-1/2 left-0 w-full h-1 bg-slate-100 -translate-y-1/2 z-0"></div>
                        <div 
                            id="progress-fill" 
                            class="absolute top-1/2 left-0 h-1 bg-emerald-500 -translate-y-1/2 z-0 transition-all duration-700"
                            style="width: {{ max(0, min(100, ($currentIndex / 4) * 100)) }}%"
                        ></div>

                        @foreach($steps as $key => $step)
                            @php
                                $stepIdx = array_search($key, $stepKeys);
                                $isCompleted = $stepIdx <= $currentIndex;
                                $isCurrent = $stepIdx === $currentIndex;
                            @endphp
                            <div class="relative z-10 flex flex-col items-center group">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 {{ $isCurrent ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 scale-110 shadow-sm' : ($isCompleted ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400 border border-slate-200') }}">
                                    {{ $step['icon'] }}
                                </div>
                                <span class="text-[11px] font-semibold mt-2 {{ $isCurrent ? 'text-emerald-700 font-bold' : ($isCompleted ? 'text-slate-700' : 'text-slate-400') }}">
                                    {{ $step['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Dynamic Status Highlight -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                        <h4 class="text-base font-bold text-slate-800" id="status-title">{{ $order->status_label }}</h4>
                        <p class="text-xs text-slate-500 mt-1" id="status-desc">{{ $order->status_message }}</p>
                    </div>
                @endif
            </div>

            <!-- Rider Assignment Card (Shown when Rider Assigned) -->
            @if($order->rider_display_name || $order->status === 'out_for_delivery')
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-2xl">
                                🛵
                            </div>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-emerald-200 font-bold">Your Delivery Partner</span>
                                {{-- First name only, and the number appears solely while the
                                     order is actually in transit. See Order::showsRiderContact(). --}}
                                <h3 class="text-lg font-extrabold text-white leading-tight">
                                    {{ $order->rider_display_name ?: 'Delivery Rider Assigned' }}
                                </h3>
                                @if($order->showsRiderContact())
                                    <p class="text-xs text-emerald-100 mt-0.5">{{ $order->rider_phone }}</p>
                                @endif
                            </div>
                        </div>

                        @if($order->showsRiderContact())
                            <div class="flex gap-2">
                                <a
                                    href="tel:{{ preg_replace('/[^0-9+]/', '', $order->rider_phone) }}"
                                    class="p-2.5 bg-white text-emerald-700 rounded-xl hover:bg-emerald-50 transition active:scale-95 shadow-sm"
                                    title="Call Rider"
                                >
                                    📞
                                </a>
                                <a
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->rider_phone) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="p-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-400 transition active:scale-95 shadow-sm"
                                    title="WhatsApp Rider"
                                >
                                    💬
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Order Details & Receipt -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3">
                    Order Summary
                </h3>

                <!-- Delivery Address (partly hidden — this page needs no login) -->
                @if($order->masked_delivery_address !== '')
                    <div class="flex items-start gap-3 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl">
                        <span class="text-base">📍</span>
                        <div>
                            <strong class="text-slate-800">Delivering to:</strong>
                            <p class="mt-0.5 leading-relaxed">{{ $order->masked_delivery_address }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Partly hidden for your privacy — the restaurant and rider have the full address.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Items Ordered -->
                {{-- From the order_items rows, not `notes`: `notes` is a copy of the
                     bot's last two chat messages, which restate the customer's full
                     address and would undo the masking above. --}}
                @if($order->items->isNotEmpty())
                    <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl">
                        <strong class="text-slate-800">Items Ordered:</strong>
                        <ul class="mt-1.5 space-y-1">
                            @foreach($order->items as $item)
                                <li class="flex justify-between gap-3">
                                    <span class="text-slate-700 font-medium">{{ $item->display_label }}</span>
                                    <span class="font-semibold text-slate-800 whitespace-nowrap">Rs. {{ number_format($item->subtotal, 0) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl">
                        Items were confirmed over WhatsApp — check your chat for the full list.
                    </div>
                @endif

                <!-- Payment Breakdown -->
                <div class="border-t border-slate-100 pt-3 space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span>Payment Method:</span>
                        <span class="font-semibold text-slate-800 uppercase">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                    </div>
                    @if($order->delivery_charge > 0)
                        <div class="flex justify-between">
                            <span>Delivery Fee:</span>
                            <span class="font-semibold text-slate-800">Rs. {{ number_format($order->delivery_charge, 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold text-slate-900 border-t border-slate-100 pt-2 mt-2">
                        <span>Total:</span>
                        <span class="text-emerald-600">Rs. {{ number_format($order->total, 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Restaurant Contact Footer -->
            <div class="text-center text-xs text-slate-400 pt-2 pb-6">
                Need help with your order?
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->restaurant->whatsapp_number) }}" target="_blank" rel="noopener noreferrer" class="text-slate-700 font-semibold underline hover:text-slate-900">
                    Chat on WhatsApp
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Live Polling Script (Checks status every 8 seconds) -->
@if($order && !in_array($order->status, ['delivered', 'cancelled']))
<script>
    // Status-only endpoint: no address, no rider, nothing that should not be
    // re-fetched every 8 seconds. Both values are JSON-encoded server-side so a
    // tracking code can never break out of the string literal.
    const STATUS_URL      = @json(route('order.track.status', $order->tracking_code));
    const CURRENT_STATUS  = @json($order->status);

    const pollInterval = setInterval(async () => {
        try {
            const res = await fetch(STATUS_URL, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;

            const data = await res.json();
            if (data.status && data.status !== CURRENT_STATUS) {
                // Reload so the stepper, rider card and receipt all re-render
                // from the server's redacted view.
                clearInterval(pollInterval);
                window.location.reload();
            }
        } catch (e) {
            // Offline or a dropped connection — the next tick will retry.
        }
    }, 8000);
</script>
@endif

</body>
</html>
