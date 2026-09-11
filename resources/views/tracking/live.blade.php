<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    {{-- The tracking code is in the URL. Keep it out of search indexes and referrers --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>Live Order Tracking | {{ $order ? $order->tracking_code : 'Track Your Order' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.35; transform: scale(1.35); }
        }
        .pulse-live { animation: pulse-dot 1.8s infinite ease-in-out; }
        .custom-map-shadow {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

@if(!$order)
    <!-- No Order Found State -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-3xl p-8 text-center border border-slate-200/80 shadow-xl space-y-6">
            <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-inner">
                🔍
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">No Order Found</h2>
                <p class="text-sm text-slate-500 mt-1.5">
                    Please enter a valid tracking code to view your delivery status.
                </p>
            </div>
            <form action="{{ route('order.track.live') }}" method="GET" class="flex gap-2">
                <input 
                    type="text" 
                    name="code" 
                    value="{{ request('code') }}" 
                    placeholder="e.g. TRK-FEZ-1010" 
                    required
                    class="flex-1 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                >
                <button 
                    type="submit" 
                    class="px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/25 transition active:scale-95"
                >
                    Track
                </button>
            </form>
        </div>
    </div>
@else
    <!-- Foodpanda-Style Live Delivery Tracking Experience -->
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
        $isDelivered = ($order->status === 'delivered');
        $isCancelled = ($order->status === 'cancelled');
        $googleMapsKey = config('services.google.maps_api_key') ?: env('GOOGLE_MAPS_API_KEY', '');
    @endphp

    <div class="lg:flex lg:h-screen lg:overflow-hidden">

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- LEFT PANEL (Desktop Sidebar / Mobile Order Sheet)                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="lg:w-[460px] xl:w-[500px] lg:h-screen lg:overflow-y-auto bg-white border-r border-slate-200/80 shadow-2xl z-30 flex flex-col order-2 lg:order-1">
            
            <!-- Sticky Header -->
            <div class="sticky top-0 bg-white/95 backdrop-blur-md border-b border-slate-100 p-4 sm:p-5 z-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-xl shadow-lg shadow-emerald-500/20">
                        🛵
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-extrabold text-slate-900 text-base leading-tight">{{ $order->restaurant->name }}</h1>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <p class="text-xs font-mono font-bold text-slate-400">#{{ $order->tracking_code }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Order Time</span>
                    <p class="text-xs font-semibold text-slate-700">{{ $order->created_at->format('h:i A') }}</p>
                </div>
            </div>

            <!-- Content Body -->
            <div class="p-4 sm:p-6 space-y-6 flex-1">

                <!-- Status Banner / Headline -->
                @if($isCancelled)
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-center">
                        <span class="text-rose-700 font-extrabold text-sm">❌ Order Cancelled</span>
                        <p class="text-xs text-rose-600 mt-1">This order was cancelled by the restaurant.</p>
                    </div>
                @else
                    <div class="bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent border border-emerald-500/20 rounded-2xl p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700" id="status-pill">
                                    {{ $order->status_label }}
                                </span>
                                <h3 class="text-lg font-extrabold text-slate-900 mt-0.5" id="status-title">
                                    {{ $order->status === 'out_for_delivery' ? 'Rider is on the way!' : $order->status_label }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed" id="status-desc">
                                    {{ $order->status_message }}
                                </p>
                            </div>
                            <div class="text-2xl pt-1">
                                {{ $steps[$order->status]['icon'] ?? '📦' }}
                            </div>
                        </div>
                    </div>

                    <!-- Modern Stepper Progress -->
                    <div class="relative py-2">
                        <div class="flex justify-between items-center relative">
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
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 {{ $isCurrent ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 scale-110 shadow-sm' : ($isCompleted ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400 border border-slate-200') }}">
                                        {{ $step['icon'] }}
                                    </div>
                                    <span class="text-[10px] font-semibold mt-1.5 {{ $isCurrent ? 'text-emerald-700 font-bold' : ($isCompleted ? 'text-slate-700' : 'text-slate-400') }}">
                                        {{ $step['label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Rider Card (if assigned or on delivery) -->
                @if($order->rider_display_name || $order->status === 'out_for_delivery')
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-4 shadow-lg border border-slate-700/60 relative overflow-hidden">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 rounded-2xl flex items-center justify-center text-2xl shadow-inner">
                                    🛵
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider font-extrabold text-emerald-400">Delivery Partner</span>
                                    <h4 class="text-base font-extrabold text-white leading-tight">
                                        {{ $order->rider_display_name ?: 'Rider Assigned' }}
                                    </h4>
                                    @if($order->showsRiderContact())
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $order->rider_phone }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($order->showsRiderContact())
                                <div class="flex gap-2">
                                    <a
                                        href="tel:{{ preg_replace('/[^0-9+]/', '', $order->rider_phone) }}"
                                        class="w-9 h-9 bg-white/10 hover:bg-white/20 text-white rounded-xl flex items-center justify-center text-sm transition active:scale-95"
                                        title="Call Rider"
                                    >
                                        📞
                                    </a>
                                    <a
                                        href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->rider_phone) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="w-9 h-9 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl flex items-center justify-center text-sm transition active:scale-95 shadow-md shadow-emerald-500/20"
                                        title="WhatsApp Rider"
                                    >
                                        💬
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Origin and Destination Locations -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-3 text-xs">
                    <div class="flex items-start gap-3">
                        <span class="text-base mt-0.5">🏪</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">From Kitchen</div>
                            <div class="font-bold text-slate-900 truncate">{{ $order->restaurant->name }}</div>
                            <div class="text-slate-500 text-[11px] truncate">{{ $order->restaurant->address ?: ($order->restaurant->city ?: 'Kitchen') }}</div>
                        </div>
                    </div>
                    <div class="border-t border-slate-200/60 pt-3 flex items-start gap-3">
                        <span class="text-base mt-0.5">📍</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Delivery Destination</span>
                                <a href="{{ route('location.confirm', $order->tracking_code) }}" class="text-emerald-600 hover:text-emerald-700 font-bold text-[10px] underline">
                                    {{ ($order->delivery_lat && $order->delivery_lng) ? 'Adjust Pin' : 'Set Pin' }}
                                </a>
                            </div>
                            <div class="font-bold text-slate-900 truncate">{{ $order->customer_name ?: 'Customer' }}</div>
                            <div class="text-slate-500 text-[11px] truncate">{{ $order->masked_delivery_address ?: 'Address provided in chat' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Missing Pin Alert -->
                @if(! $order->delivery_lat || ! $order->delivery_lng)
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-3.5 py-2.5 rounded-2xl flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📍</span>
                            <span class="font-medium">Exact doorstep pin not set yet</span>
                        </div>
                        <a href="{{ route('location.confirm', $order->tracking_code) }}" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition active:scale-95 shadow-sm">
                            Set Pin on Map
                        </a>
                    </div>
                @endif

                <!-- Order Items Breakdown -->
                <div class="border border-slate-200/80 rounded-2xl p-4 bg-white space-y-3 text-xs">
                    <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider border-b border-slate-100 pb-2">
                        Order Details
                    </h4>

                    @if($order->items->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($order->items as $item)
                                <li class="flex justify-between items-center gap-2">
                                    <span class="text-slate-700 font-medium truncate">{{ $item->display_label }}</span>
                                    <span class="font-bold text-slate-900 whitespace-nowrap">Rs. {{ number_format($item->subtotal, 0) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-slate-400 italic">Items confirmed via WhatsApp chat.</p>
                    @endif

                    <!-- Totals -->
                    <div class="border-t border-slate-100 pt-3 space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Payment:</span>
                            <span class="font-semibold text-slate-800 uppercase">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                        </div>
                        @if($order->delivery_charge > 0)
                            <div class="flex justify-between">
                                <span>Delivery Charge:</span>
                                <span class="font-semibold text-slate-800">Rs. {{ number_format($order->delivery_charge, 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm font-extrabold text-slate-900 border-t border-slate-100 pt-2 mt-1">
                            <span>Total Amount:</span>
                            <span class="text-emerald-600">Rs. {{ number_format($order->total, 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Help Link -->
                <div class="text-center text-xs text-slate-400 py-3">
                    Need assistance?
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->restaurant->whatsapp_number) }}" target="_blank" rel="noopener noreferrer" class="text-slate-700 font-bold underline hover:text-slate-900">
                        Chat with Restaurant on WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- RIGHT PANEL (Full-Screen Responsive Google Map Viewport)           -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="relative w-full h-[65vh] sm:h-[70vh] lg:h-screen lg:flex-1 bg-slate-900 overflow-hidden order-1 lg:order-2">
            
            <!-- Actual Google Maps Container -->
            <div id="live-tracking-map" class="w-full h-full"></div>

            <!-- Floating Top Distance / ETA Badge -->
            <div class="absolute top-4 left-4 z-20 pointer-events-auto">
                <div class="inline-flex items-center gap-2 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-2xl shadow-lg border border-slate-200/90 text-xs font-extrabold text-slate-800" id="live-distance-badge">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-live"></span>
                    <span id="distance-text">Calculating route...</span>
                </div>
            </div>

            <!-- Floating Map Type Toggle (Map / Satellite) -->
            <div class="absolute top-4 right-4 z-20 pointer-events-auto">
                <div class="inline-flex bg-white/95 backdrop-blur-md rounded-2xl p-1 shadow-lg border border-slate-200/90 text-xs font-bold" role="group">
                    <button 
                        type="button" 
                        id="btn-mode-roadmap" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 bg-slate-900 text-white shadow-sm"
                        title="Switch to Clean Road Map"
                    >
                        <span>🗺️</span>
                        <span>Map</span>
                    </button>
                    <button 
                        type="button" 
                        id="btn-mode-satellite" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80"
                        title="Switch to Real Google Satellite Imagery"
                    >
                        <span>🛰️</span>
                        <span>Satellite</span>
                    </button>
                </div>
            </div>

            <!-- Floating Custom Zoom & Center Controls -->
            <div class="absolute bottom-6 right-4 z-20 flex flex-col gap-2 pointer-events-auto">
                <button 
                    type="button" 
                    id="btn-map-recenter" 
                    class="w-10 h-10 bg-white/95 hover:bg-white active:scale-95 text-slate-800 rounded-2xl shadow-lg border border-slate-200/90 flex items-center justify-center text-base font-bold transition"
                    title="Fit Route to Screen"
                >
                    🎯
                </button>
                <div class="bg-white/95 rounded-2xl shadow-lg border border-slate-200/90 flex flex-col overflow-hidden">
                    <button 
                        type="button" 
                        id="btn-zoom-in" 
                        class="w-10 h-10 hover:bg-slate-100 active:scale-95 text-slate-800 flex items-center justify-center text-lg font-bold transition border-b border-slate-200/60"
                        title="Zoom In"
                    >
                        +
                    </button>
                    <button 
                        type="button" 
                        id="btn-zoom-out" 
                        class="w-10 h-10 hover:bg-slate-100 active:scale-95 text-slate-800 flex items-center justify-center text-lg font-bold transition"
                        title="Zoom Out"
                    >
                        −
                    </button>
                </div>
            </div>

        </div>
    </div>
@endif

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- LEAFLET MAP ENGINE & LIVE TRACKING ENGINE                         -->
<!-- ══════════════════════════════════════════════════════════════════ -->
@if($order)
<script>
(function() {
    // 1. Coordinates from Database (Confirmed Delivery GPS)
    const dbRestLat       = @json($order->restaurant->restaurant_lat ? (float) $order->restaurant->restaurant_lat : null);
    const dbRestLng       = @json($order->restaurant->restaurant_lng ? (float) $order->restaurant->restaurant_lng : null);
    const dbDeliveryLat   = @json($order->delivery_lat ? (float) $order->delivery_lat : null);
    const dbDeliveryLng   = @json($order->delivery_lng ? (float) $order->delivery_lng : null);
    const initialLiveGps  = @json($order->hasLiveGps());
    const initialRiderLat = @json($order->rider_lat ? (float) $order->rider_lat : null);
    const initialRiderLng = @json($order->rider_lng ? (float) $order->rider_lng : null);
    const orderStatus     = @json($order->status);
    const restaurantName  = @json($order->restaurant->name);
    const customerName    = @json($order->customer_name ?: 'Customer');
    const riderName       = @json($order->rider_display_name ?: 'Rider');

    // Pakistan city coordinates fallback table
    const cityCoords = {
        'lodhran': [29.5405, 71.6336], 'multan': [30.1575, 71.5249],
        'bahawalpur': [29.3544, 71.6911], 'lahore': [31.5204, 74.3587],
        'faisalabad': [31.4504, 73.1350], 'rawalpindi': [33.5651, 73.0169],
        'islamabad': [33.6844, 73.0479], 'karachi': [24.8607, 67.0011],
        'peshawar': [34.0151, 71.5249], 'quetta': [30.1798, 66.9750],
        'gujranwala': [32.1877, 74.1945], 'sialkot': [32.4945, 74.5229],
        'sargodha': [32.0836, 72.6711], 'dera ghazi khan': [30.0561, 70.6403],
        'sahiwal': [30.6682, 73.1114], 'okara': [30.8081, 73.4458],
        'khanewal': [30.3017, 71.9321], 'vehari': [30.0452, 72.3489],
        'rahim yar khan': [28.4212, 70.2989], 'hyderabad': [25.3960, 68.3578],
        'sukkur': [27.7052, 68.8574]
    };

    function resolveCityCoords(str) {
        if (!str) return null;
        const lower = String(str).toLowerCase().trim();
        for (const [c, coords] of Object.entries(cityCoords)) {
            if (lower.includes(c)) return coords;
        }
        return null;
    }

    const restCity = @json(strtolower(trim($order->restaurant->city ?? '')));
    const fallbackOrigin = resolveCityCoords(restCity) || [29.5405, 71.6336];
    const originLat = dbRestLat || fallbackOrigin[0];
    const originLng = dbRestLng || fallbackOrigin[1];

    let destLat = dbDeliveryLat || null;
    let destLng = dbDeliveryLng || null;
    const hasRealDest = !!(destLat && destLng);

    // Initial Rider position
    let currentRiderLat = originLat;
    let currentRiderLng = originLng;
    if (initialLiveGps && initialRiderLat && initialRiderLng) {
        currentRiderLat = initialRiderLat;
        currentRiderLng = initialRiderLng;
    } else if (orderStatus === 'out_for_delivery' && destLat && destLng) {
        currentRiderLat = (originLat * 0.45) + (destLat * 0.55);
        currentRiderLng = (originLng * 0.45) + (destLng * 0.55);
    } else if (orderStatus === 'delivered' && destLat && destLng) {
        currentRiderLat = destLat;
        currentRiderLng = destLng;
    }

    // Distance calculation (Haversine)
    function calcDistanceKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2)**2;
        return (R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))).toFixed(1);
    }

    // ── Leaflet Custom HTML Marker Icons ──────────────────────────────────────
    function makeLeafletIcon(html, size) {
        return L.divIcon({ html: html, className: '', iconSize: size, iconAnchor: [size[0]/2, size[1]] });
    }

    const kitchenIconHtml = `<div style="
        width:42px;height:52px;display:flex;align-items:flex-end;justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 42 52" width="42" height="52">
            <defs>
                <filter id="kS" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="4" stdDeviation="3" flood-color="#000" flood-opacity="0.35"/>
                </filter>
            </defs>
            <path d="M21 2 C11 2 2 11 2 21 C2 33 21 50 21 50 C21 50 40 33 40 21 C40 11 31 2 21 2 Z" fill="#0f172a" filter="url(#kS)" stroke="#fff" stroke-width="2.5"/>
            <circle cx="21" cy="20" r="10" fill="#10b981"/>
            <path d="M15 22 L15 19 L27 19 L27 22 L25 22 L25 24 L17 24 L17 22 Z M17 16 L25 16 L26 18 L16 18 Z" fill="#fff"/>
        </svg>
    </div>`;

    const customerIconHtml = `<div style="
        width:44px;height:54px;display:flex;align-items:flex-end;justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 54" width="44" height="54">
            <defs>
                <filter id="cS" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="4" stdDeviation="3" flood-color="#000" flood-opacity="0.35"/>
                </filter>
            </defs>
            <path d="M22 2 C11 2 2 11 2 22 C2 34 22 52 22 52 C22 52 42 34 42 22 C42 11 33 2 22 2 Z" fill="#e11d48" filter="url(#cS)" stroke="#fff" stroke-width="2.5"/>
            <circle cx="22" cy="21" r="11" fill="#fff"/>
            <path d="M16 23 L22 17 L28 23 L27 23 L27 26 L23 26 L23 23 L21 23 L21 26 L17 26 L17 23 Z" fill="#e11d48"/>
        </svg>
    </div>`;

    const riderIconHtml = `<div style="
        width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="52" height="52">
            <defs>
                <filter id="rS" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="#000" flood-opacity="0.4"/>
                </filter>
            </defs>
            <circle cx="26" cy="26" r="23" fill="#10b981" fill-opacity="0.25" stroke="#10b981" stroke-width="1.2"/>
            <circle cx="26" cy="26" r="18" fill="#10b981" filter="url(#rS)" stroke="#fff" stroke-width="3"/>
            <text x="26" y="32" font-size="18" text-anchor="middle" fill="#fff" font-family="'Segoe UI Emoji','Apple Color Emoji',sans-serif">🛵</text>
        </svg>
    </div>`;

    // ── Global state ────────────────────────────────────────────────────────
    let leafletMap = null;
    let kitchenMarker = null;
    let customerMarker = null;
    let riderMarker = null;
    let routePolyline = null;
    let currentTileLayer = null;
    let isSatellite = false;

    const distTextElem = document.getElementById('distance-text');

    function updateDistanceBadge(rLat, rLng, isLive) {
        if (!distTextElem) return;
        if (orderStatus === 'delivered') {
            distTextElem.textContent = 'Delivered 🎉';
            return;
        }
        if (destLat && destLng) {
            const rem = parseFloat(calcDistanceKm(rLat, rLng, destLat, destLng));
            if (isLive) {
                distTextElem.innerHTML = `<span class="text-emerald-600">Live GPS:</span> ${rem} km away (~${Math.max(1, Math.round(rem * 3))} mins)`;
            } else if (orderStatus === 'out_for_delivery') {
                distTextElem.textContent = `${rem} km away • On the way 🛵`;
            } else {
                const total = parseFloat(calcDistanceKm(originLat, originLng, destLat, destLng));
                distTextElem.textContent = `${total} km total distance`;
            }
        } else {
            distTextElem.textContent = orderStatus === 'out_for_delivery' ? 'On the way 🛵' : 'Awaiting pin confirmation 📍';
        }
    }

    function fitDeliveryBounds() {
        if (!leafletMap) return;
        const pts = [[originLat, originLng]];
        if (hasRealDest && destLat && destLng) pts.push([destLat, destLng]);
        if (currentRiderLat && currentRiderLng) pts.push([currentRiderLat, currentRiderLng]);
        if (pts.length === 1) {
            leafletMap.setView(pts[0], 15);
        } else {
            leafletMap.fitBounds(pts, { padding: [60, 60], maxZoom: 16 });
        }
    }

    function drawRoute(dLat, dLng) {
        destLat = dLat;
        destLng = dLng;

        if (customerMarker) {
            customerMarker.setLatLng([dLat, dLng]);
        } else {
            customerMarker = L.marker([dLat, dLng], {
                icon: makeLeafletIcon(customerIconHtml, [44, 54]),
                title: 'Delivery Destination: ' + customerName,
                zIndexOffset: 150
            }).addTo(leafletMap)
              .bindPopup(`<b>🏠 ${customerName}</b><br><span style="color:#64748b;">Delivery Destination</span>`);
        }

        // Smooth curved route using a quadratic Bezier approximation
        const midLat = (originLat + dLat) / 2 + 0.0012;
        const midLng = (originLng + dLng) / 2 - 0.0012;
        const routePath = [[originLat, originLng], [midLat, midLng], [dLat, dLng]];

        if (routePolyline) {
            routePolyline.setLatLngs(routePath);
        } else {
            routePolyline = L.polyline(routePath, {
                color: '#10b981',
                weight: 5,
                opacity: 0.9,
                dashArray: null,
                lineJoin: 'round'
            }).addTo(leafletMap);
        }

        fitDeliveryBounds();
        updateDistanceBadge(currentRiderLat, currentRiderLng, initialLiveGps);
    }

    // Smooth movement interpolation for rider marker (lerp over 1500ms)
    function smoothMoveRider(targetLat, targetLng) {
        if (!riderMarker) return;
        const startLat = currentRiderLat;
        const startLng = currentRiderLng;
        const startTime = performance.now();
        const duration = 1500;

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;

            const lat = startLat + (targetLat - startLat) * ease;
            const lng = startLng + (targetLng - startLng) * ease;

            currentRiderLat = lat;
            currentRiderLng = lng;
            riderMarker.setLatLng([lat, lng]);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                currentRiderLat = targetLat;
                currentRiderLng = targetLng;
                riderMarker.setLatLng([targetLat, targetLng]);
                updateDistanceBadge(targetLat, targetLng, true);
            }
        }
        requestAnimationFrame(step);
    }

    // ── Initialize Leaflet Map ─────────────────────────────────────────────────
    function initLeafletDeliveryMap() {
        const mapContainer = document.getElementById('live-tracking-map');
        if (!mapContainer || !window.L) return;

        const initialCenter = hasRealDest
            ? [(originLat + destLat) / 2, (originLng + destLng) / 2]
            : [originLat, originLng];

        leafletMap = L.map('live-tracking-map', {
            center: initialCenter,
            zoom: hasRealDest ? 14 : 15,
            zoomControl: false,
            attributionControl: false
        });

        // Default: OpenStreetMap roadmap (clean, no watermark)
        currentTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(leafletMap);

        // 1. Kitchen Marker
        kitchenMarker = L.marker([originLat, originLng], {
            icon: makeLeafletIcon(kitchenIconHtml, [42, 52]),
            title: 'Kitchen: ' + restaurantName,
            zIndexOffset: 100
        }).addTo(leafletMap)
          .bindPopup(`<b>🏪 ${restaurantName}</b><br><span style="color:#64748b;">Kitchen Origin</span>`);

        // 2. Rider Marker
        riderMarker = L.marker([currentRiderLat, currentRiderLng], {
            icon: makeLeafletIcon(riderIconHtml, [52, 52]),
            title: 'Delivery Partner: ' + riderName,
            zIndexOffset: 200
        }).addTo(leafletMap)
          .bindPopup(`<b>🛵 ${riderName}</b><br><span style="color:#10b981;font-weight:bold;">Delivery Partner</span>`);

        // 3. Destination & Route
        if (hasRealDest) {
            drawRoute(destLat, destLng);
        } else {
            fitDeliveryBounds();
            if (distTextElem) {
                distTextElem.textContent = 'Awaiting pin confirmation 📍';
            }
        }

        updateDistanceBadge(currentRiderLat, currentRiderLng, initialLiveGps);

        // ── Map Mode Toggle (Roadmap / Satellite) ──────────────────────────
        const btnRoadmap   = document.getElementById('btn-mode-roadmap');
        const btnSatellite = document.getElementById('btn-mode-satellite');
        const TILE_ROADMAP = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        const TILE_SAT     = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';

        if (btnRoadmap && btnSatellite) {
            btnRoadmap.addEventListener('click', function() {
                if (isSatellite) {
                    leafletMap.removeLayer(currentTileLayer);
                    currentTileLayer = L.tileLayer(TILE_ROADMAP, { maxZoom: 19 }).addTo(leafletMap);
                    isSatellite = false;
                }
                btnRoadmap.className  = 'flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 bg-slate-900 text-white shadow-sm';
                btnSatellite.className = 'flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80';
            });

            btnSatellite.addEventListener('click', function() {
                if (!isSatellite) {
                    leafletMap.removeLayer(currentTileLayer);
                    currentTileLayer = L.tileLayer(TILE_SAT, { maxZoom: 17 }).addTo(leafletMap);
                    isSatellite = true;
                }
                btnSatellite.className = 'flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 bg-slate-900 text-white shadow-sm';
                btnRoadmap.className   = 'flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80';
            });
        }

        // Custom Zoom Controls
        const btnZoomIn   = document.getElementById('btn-zoom-in');
        const btnZoomOut  = document.getElementById('btn-zoom-out');
        const btnRecenter = document.getElementById('btn-map-recenter');

        if (btnZoomIn)  btnZoomIn.addEventListener('click', () => leafletMap.setZoom(leafletMap.getZoom() + 1));
        if (btnZoomOut) btnZoomOut.addEventListener('click', () => leafletMap.setZoom(leafletMap.getZoom() - 1));
        if (btnRecenter) btnRecenter.addEventListener('click', fitDeliveryBounds);

        // Expose live polling updater
        window.updateRiderLivePosition = function(lat, lng) {
            smoothMoveRider(lat, lng);
            updateDistanceBadge(lat, lng, true);
        };
    }

    // Initialize once DOM is ready (Leaflet is loaded synchronously in <head>)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLeafletDeliveryMap);
    } else {
        initLeafletDeliveryMap();
    }
})();
</script>

<!-- Live Polling Script (Polls status & rider GPS every 4-7 seconds) -->
@if(!in_array($order->status, ['delivered', 'cancelled']))
<script>
(function() {
    const STATUS_URL    = @json(route('order.track.status', $order->tracking_code));
    let currentStatus   = @json($order->status);

    async function pollLiveTracker() {
        try {
            const res = await fetch(STATUS_URL, { 
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } 
            });
            if (!res.ok) return;

            const data = await res.json();
            
            // If status changed (e.g. out_for_delivery -> delivered), reload to refresh full stepper
            if (data.status && data.status !== currentStatus) {
                window.location.reload();
                return;
            }

            // If real-time GPS coordinates are streaming from rider's device
            if (data.has_live_gps && data.rider_lat && data.rider_lng) {
                if (typeof window.updateRiderLivePosition === 'function') {
                    window.updateRiderLivePosition(Number(data.rider_lat), Number(data.rider_lng));
                }
            }
        } catch (e) {
            // Silent retry on network blip
        }
    }

    // Active delivery: 4 seconds polling for smooth movement; otherwise 7 seconds
    const pollIntervalMs = currentStatus === 'out_for_delivery' ? 4000 : 7000;
    setInterval(pollLiveTracker, pollIntervalMs);
})();
</script>
@endif

@endif

</body>
</html>