<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Rider Delivery Portal | #{{ $order->tracking_code }}</title>
    
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes radar-pulse {
            0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70%  { transform: scale(1); box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .radar-active {
            animation: radar-pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen pb-12 antialiased">

<div class="max-w-md mx-auto px-4 py-6">

    <!-- Top Bar -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Rider Delivery Mode 🛵</span>
            <h1 class="text-lg font-extrabold text-white">{{ $order->restaurant->name ?? 'Restaurant' }}</h1>
        </div>
        <div class="text-right">
            <span class="text-xs text-slate-400 font-mono">Order #{{ $order->tracking_code }}</span>
            <div class="mt-0.5">
                @if($order->status === 'delivered')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                        ✓ Delivered
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                        ● Out for Delivery
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert / Success messages -->
    @if(session('success'))
        <div class="mb-5 p-4 rounded-xl bg-emerald-900/40 border border-emerald-600/50 text-emerald-200 text-sm font-semibold flex items-center gap-2">
            <span>🎉</span> {{ session('success') }}
        </div>
    @endif

    <!-- GPS Broadcasting Status Card -->
    @if($order->status !== 'delivered')
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-4 mb-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="gps-indicator" class="w-4 h-4 rounded-full bg-emerald-500 radar-active flex-shrink-0"></div>
                    <div>
                        <h3 id="gps-status-title" class="text-sm font-bold text-white">Live GPS Active</h3>
                        <p id="gps-status-sub" class="text-xs text-slate-400">Transmitting coordinates to customer...</p>
                    </div>
                </div>
                <span id="gps-pings-count" class="text-xs font-mono bg-slate-900/80 px-2.5 py-1 rounded-lg text-emerald-400 border border-slate-700">
                    📡 0 pings
                </span>
            </div>
            
            <!-- GPS Warning Banner (if location denied) -->
            <div id="gps-error-banner" class="hidden mt-3 p-3 bg-amber-950/60 border border-amber-600/60 rounded-xl text-amber-300 text-xs">
                ⚠️ <strong>Location permission required:</strong> Please allow location access in your mobile browser to stream your delivery location.
            </div>
        </div>
    @else
        <div class="bg-emerald-950/40 border border-emerald-800/60 rounded-2xl p-5 mb-5 text-center shadow-lg">
            <div class="text-3xl mb-2">🎉</div>
            <h3 class="text-base font-bold text-emerald-300">Delivery Completed</h3>
            <p class="text-xs text-slate-400 mt-1">This order has been completed and GPS tracking is turned off.</p>
        </div>
    @endif

    <!-- Customer & Destination Card -->
    <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-5 mb-5 shadow-lg space-y-4">
        <div>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-400">👤 Customer Details</span>
            <div class="text-base font-bold text-white mt-1">{{ $order->customer_name ?: 'Valued Customer' }}</div>
            <div class="text-xs text-slate-400 font-mono">{{ $order->formatted_customer_phone }}</div>
        </div>

        <!-- Quick Action Contact Buttons -->
        <div class="grid grid-cols-2 gap-2.5 pt-1">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}" class="flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition active:scale-95 shadow">
                <span>📞</span> Call Customer
            </a>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="flex items-center justify-center gap-2 py-2.5 px-3 bg-slate-700 hover:bg-slate-600 text-emerald-400 rounded-xl font-bold text-xs transition active:scale-95 border border-slate-600">
                <span>💬</span> WhatsApp
            </a>
        </div>

        <hr class="border-slate-700/70">

        <!-- Delivery Address & Google Maps Navigation -->
        <div>
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-400">📍 Delivering To</span>
            <p class="text-sm font-semibold text-slate-200 mt-1 leading-relaxed bg-slate-900/60 p-3 rounded-xl border border-slate-700/50">
                {{ $order->delivery_address ?: 'Address recorded in WhatsApp chat' }}
            </p>
        </div>

        <a 
            href="https://www.google.com/maps/search/?api=1&query={{ urlencode(($order->delivery_address ? $order->delivery_address . ', ' : '') . ($order->restaurant->city ?? '')) }}"
            target="_blank" 
            class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-sm transition active:scale-95 shadow-md"
        >
            <span>🗺️</span> Open Google Maps Navigation ↗
        </a>
    </div>

    <!-- Bill to Collect & Payment Card -->
    <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-5 mb-5 shadow-lg">
        <div class="flex items-center justify-between pb-3 border-b border-slate-700/70">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Amount to Collect</span>
                <div class="text-2xl font-extrabold text-emerald-400 mt-0.5">Rs. {{ number_format($order->total, 0) }}</div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-amber-950 text-amber-300 border border-amber-800 text-xs font-bold rounded-lg uppercase">
                    {{ str_replace('_', ' ', $order->payment_method ?: 'Cash On Delivery') }}
                </span>
            </div>
        </div>

        <!-- Ordered Items Summary -->
        <div class="mt-4 space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Items ({{ $order->items->sum('quantity') }} items)</span>
            <div class="divide-y divide-slate-700/40 text-xs">
                @foreach($order->items as $item)
                    <div class="py-2 flex items-center justify-between text-slate-300">
                        <span class="font-medium"><strong class="text-indigo-400">{{ $item->quantity }}x</strong> {{ $item->display_label }}</span>
                        <span class="font-semibold text-slate-200">Rs. {{ number_format($item->subtotal, 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Action Button: Mark as Delivered -->
    @if($order->status !== 'delivered')
        <form method="POST" action="{{ route('rider.deliver.complete', $order->rider_token) }}" onsubmit="return confirm('Are you sure you have delivered the food and collected Rs. {{ number_format($order->total, 0) }}?');">
            @csrf
            <button 
                type="submit" 
                class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-extrabold text-base transition transform active:scale-95 shadow-xl flex items-center justify-center gap-2"
            >
                <span>✅</span> Mark Order as Delivered
            </button>
        </form>
    @endif

    <div class="text-center text-xs text-slate-500 mt-6">
        Powered by AI WhatsApp Restaurant Platform
    </div>

</div>

<!-- Background GPS Geolocation Broadcaster Script -->
@if($order->status !== 'delivered')
<script>
(function() {
    const token = @json($order->rider_token);
    const updateUrl = @json(route('rider.deliver.location', $order->rider_token));
    const csrfToken = @json(csrf_token());

    const titleElem   = document.getElementById('gps-status-title');
    const subElem     = document.getElementById('gps-status-sub');
    const pingsElem   = document.getElementById('gps-pings-count');
    const errBanner   = document.getElementById('gps-error-banner');
    const indicator   = document.getElementById('gps-indicator');

    let pingCount = 0;
    let isTracking = true;

    async function sendLocation(lat, lng, accuracy) {
        try {
            const res = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng,
                    accuracy: accuracy || null
                })
            });

            if (res.ok) {
                const data = await res.json();
                if (data.success) {
                    pingCount++;
                    pingsElem.textContent = '📡 ' + pingCount + ' pings';
                    titleElem.textContent = 'Live GPS Active';
                    subElem.textContent = 'Lat: ' + lat.toFixed(4) + ', Lng: ' + lng.toFixed(4);
                    errBanner.classList.add('hidden');
                    indicator.classList.remove('bg-amber-500', 'bg-red-500');
                    indicator.classList.add('bg-emerald-500');
                } else if (data.status === 'delivered') {
                    // Order was marked delivered on another device
                    isTracking = false;
                    location.reload();
                }
            }
        } catch (e) {
            console.warn('GPS ping network retry:', e);
        }
    }

    if (!navigator.geolocation) {
        errBanner.textContent = '❌ Geolocation is not supported by your browser.';
        errBanner.classList.remove('hidden');
        return;
    }

    // High accuracy watchPosition
    const watchId = navigator.geolocation.watchPosition(
        function(position) {
            if (!isTracking) return;
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            sendLocation(lat, lng, accuracy);
        },
        function(error) {
            console.error('Geolocation error:', error);
            errBanner.classList.remove('hidden');
            indicator.classList.remove('bg-emerald-500');
            indicator.classList.add('bg-amber-500');
            titleElem.textContent = 'GPS Searching...';
            subElem.textContent = 'Please keep your phone screen on or allow location.';
        },
        {
            enableHighAccuracy: true,
            maximumAge: 3000,
            timeout: 10000
        }
    );

    // Also periodic ping every 6 seconds as fallback
    setInterval(() => {
        if (!isTracking) return;
        navigator.geolocation.getCurrentPosition(
            (pos) => sendLocation(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy),
            (err) => console.log('Periodic fallback location error', err),
            { enableHighAccuracy: true, timeout: 6000 }
        );
    }, 6000);
})();
</script>
@endif

</body>
</html>
