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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }
        .pulse-live { animation: pulse-dot 1.8s infinite ease-in-out; }
        .leaflet-div-icon { background: transparent; border: none; }
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

            <!-- Real-Time Distance & Route Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🗺️</span>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Live Route & Distance</h3>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold font-mono" id="live-distance-badge">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-live"></span>
                        <span id="distance-text">Calculating distance...</span>
                    </div>
                </div>

                <!-- Interactive Live Route Map -->
                <div id="live-tracking-map" class="w-full h-56 rounded-xl overflow-hidden border border-slate-200 z-0 mb-4 bg-slate-100 relative">
                    <!-- Map will render here -->
                </div>

                <!-- Origin and Destination Breakdown -->
                <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div class="border-r border-slate-200/80 pr-2">
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                            <span>🏪</span> Kitchen Origin
                        </div>
                        <div class="font-bold text-slate-800 mt-0.5 truncate">{{ $order->restaurant->name }}</div>
                        <div class="text-slate-500 text-[11px] truncate">{{ $order->restaurant->address ?: ($order->restaurant->city ?: 'Kitchen') }}</div>
                    </div>
                    <div class="pl-1">
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                            <span>📍</span> Delivery Destination
                        </div>
                        <div class="font-bold text-slate-800 mt-0.5 truncate">{{ $order->customer_name ?: 'Customer' }}</div>
                        <div class="text-slate-500 text-[11px] truncate">{{ $order->masked_delivery_address ?: 'Delivery Address' }}</div>
                    </div>
                </div>
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

<!-- Live Map & Real-Time Distance Script -->
@if($order)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('live-tracking-map');
    if (!mapContainer) return;

    // City coordinates lookup
    const cityCoords = {
        'lahore': [31.5204, 74.3587],
        'karachi': [24.8607, 67.0011],
        'islamabad': [33.6844, 73.0479],
        'rawalpindi': [33.5651, 73.0169],
        'faisalabad': [31.4504, 73.1350],
        'multan': [30.1575, 71.5249],
        'bahawalpur': [29.3544, 71.6911],
        'peshawar': [34.0151, 71.5249],
        'gujranwala': [32.1877, 74.1945],
        'sialkot': [32.4945, 74.5229]
    };

    const restaurantCity = @json(strtolower(trim($order->restaurant->city ?? 'lahore')));
    const baseOrigin = cityCoords[restaurantCity] || [31.5204, 74.3587];

    // Seeded offset based on order id to create a realistic 2.5 - 4.5 km route
    const orderId = {{ $order->id }};
    const latOffset = (((orderId * 13) % 25) + 15) * 0.001;
    const lngOffset = (((orderId * 17) % 25) + 15) * 0.001;

    const originLat = baseOrigin[0];
    const originLng = baseOrigin[1];
    const destLat = originLat + latOffset;
    const destLng = originLng + lngOffset;

    // Calculate approximate distance in km (Haversine formula)
    function calcDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return (R * c).toFixed(1);
    }

    const totalDistKm = parseFloat(calcDistance(originLat, originLng, destLat, destLng));
    const distTextElem = document.getElementById('distance-text');
    const orderStatus = @json($order->status);

    // Initialize Leaflet Map
    const map = L.map('live-tracking-map', {
        zoomControl: false,
        attributionControl: false
    }).setView([(originLat + destLat)/2, (originLng + destLng)/2], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Custom Icon Creators
    const restIcon = L.divIcon({
        html: '<div style="background: #0f172a; color: white; width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 2px solid white;">🏪</div>',
        className: 'leaflet-div-icon',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    const destIcon = L.divIcon({
        html: '<div style="background: #ef4444; color: white; width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 4px 10px rgba(239,68,68,0.4); border: 2px solid white;">📍</div>',
        className: 'leaflet-div-icon',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    const riderIcon = L.divIcon({
        html: '<div style="background: #10b981; color: white; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(16,185,129,0.5); border: 3px solid white; animation: pulse-dot 1.5s infinite;">🛵</div>',
        className: 'leaflet-div-icon',
        iconSize: [38, 38],
        iconAnchor: [19, 19]
    });

    // Add Markers
    L.marker([originLat, originLng], { icon: restIcon }).addTo(map).bindPopup('<b>Kitchen Origin</b><br>' + @json($order->restaurant->name));
    L.marker([destLat, destLng], { icon: destIcon }).addTo(map).bindPopup('<b>Delivery Destination</b><br>' + @json($order->customer_name ?: 'Customer'));

    // Route Polyline Points with intermediate waypoints
    const midLat = originLat + (latOffset * 0.45) + 0.003;
    const midLng = originLng + (lngOffset * 0.6) - 0.002;
    const routePoints = [
        [originLat, originLng],
        [originLat + latOffset*0.2, originLng + lngOffset*0.1],
        [midLat, midLng],
        [originLat + latOffset*0.8, originLng + lngOffset*0.75],
        [destLat, destLng]
    ];

    const polyline = L.polyline(routePoints, {
        color: '#10b981',
        weight: 5,
        opacity: 0.8,
        dashArray: orderStatus === 'delivered' ? null : '8, 8'
    }).addTo(map);

    map.fitBounds(polyline.getBounds(), { padding: [35, 35] });

    // Check if real GPS coordinates exist on initial load
    const initialLiveGps = @json($order->hasLiveGps());
    const initialRiderLat = @json($order->rider_lat ? (float)$order->rider_lat : null);
    const initialRiderLng = @json($order->rider_lng ? (float)$order->rider_lng : null);

    // Rider Position Initializer
    let currentRiderLat = originLat + (latOffset * 0.1);
    let currentRiderLng = originLng + (lngOffset * 0.1);

    if (initialLiveGps && initialRiderLat && initialRiderLng) {
        currentRiderLat = initialRiderLat;
        currentRiderLng = initialRiderLng;
    } else if (orderStatus === 'out_for_delivery') {
        currentRiderLat = originLat + (latOffset * 0.55);
        currentRiderLng = originLng + (lngOffset * 0.55);
    } else if (orderStatus === 'delivered') {
        currentRiderLat = destLat;
        currentRiderLng = destLng;
    }

    const riderMarker = L.marker([currentRiderLat, currentRiderLng], { icon: riderIcon }).addTo(map);

    function updateRiderDistanceDisplay(rLat, rLng, isLiveGps) {
        if (!distTextElem) return;
        if (orderStatus === 'delivered') {
            distTextElem.textContent = totalDistKm + ' km (Delivered 🎉)';
            return;
        }

        const remainingKm = parseFloat(calcDistance(rLat, rLng, destLat, destLng));
        if (isLiveGps) {
            distTextElem.innerHTML = `<span style="color: #10b981;">📡 Live GPS:</span> ${remainingKm} km away (~${Math.max(1, Math.round(remainingKm * 3))} mins)`;
        } else if (orderStatus === 'out_for_delivery') {
            distTextElem.textContent = remainingKm + ' km away • On the way 🛵';
        } else {
            distTextElem.textContent = totalDistKm + ' km Total Distance';
        }
    }

    updateRiderDistanceDisplay(currentRiderLat, currentRiderLng, initialLiveGps);

    // Expose update function for live polling
    window.updateRiderLivePosition = function(lat, lng) {
        riderMarker.setLatLng([lat, lng]);
        updateRiderDistanceDisplay(lat, lng, true);
    };
});
</script>
@endif

<!-- Live Polling Script (Polls status & rider GPS every 4-6 seconds) -->
@if($order && !in_array($order->status, ['delivered', 'cancelled']))
<script>
(function() {
    const STATUS_URL      = @json(route('order.track.status', $order->tracking_code));
    let currentStatus   = @json($order->status);

    async function pollLiveTracker() {
        try {
            const res = await fetch(STATUS_URL, { 
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } 
            });
            if (!res.ok) return;

            const data = await res.json();
            
            // If status changed (e.g. out_for_delivery -> delivered), reload page
            if (data.status && data.status !== currentStatus) {
                window.location.reload();
                return;
            }

            // If real-time GPS coordinates are streaming from the rider's phone
            if (data.has_live_gps && data.rider_lat && data.rider_lng) {
                if (typeof window.updateRiderLivePosition === 'function') {
                    window.updateRiderLivePosition(data.rider_lat, data.rider_lng);
                }
            }
        } catch (e) {
            console.log('Live tracking polling tick retry');
        }
    }

    // Poll faster (every 4 seconds) during active delivery to make rider movement smooth
    const pollIntervalMs = currentStatus === 'out_for_delivery' ? 4000 : 7000;
    setInterval(pollLiveTracker, pollIntervalMs);
})();
</script>
@endif

</body>
</html>
