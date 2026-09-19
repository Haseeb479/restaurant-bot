<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Confirm Delivery Location — {{ $restaurant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .pin-pulse {
            animation: pin-bounce 1s infinite alternate;
        }
        @keyframes pin-bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-slate-800/80 backdrop-blur border-b border-slate-700/60 px-4 py-3 sticky top-0 z-30 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-xl shadow-lg shadow-emerald-500/20">
                🛵
            </div>
            <div>
                <h1 class="font-bold text-sm text-white leading-tight">{{ $restaurant->name }}</h1>
                <p class="text-xs text-emerald-400 font-medium">📍 Pin Your Exact Delivery Location</p>
            </div>
        </div>
        @if($trackingCode)
            <span class="text-xs font-mono bg-slate-700/70 border border-slate-600 px-2.5 py-1 rounded-lg text-slate-300">
                #{{ $trackingCode }}
            </span>
        @endif
    </header>

    <!-- Map Viewport -->
    <div class="relative flex-1 min-h-[50vh]">
        <div id="map" class="w-full h-full min-h-[50vh] z-10"></div>

        <!-- Floating Action Controls (GPS & Map Mode) -->
        <div class="absolute top-4 right-4 z-20 flex flex-col gap-2 items-end">
            <button id="gps-btn" type="button" class="bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-semibold text-xs py-2.5 px-3.5 rounded-xl shadow-xl flex items-center gap-2 transition-all">
                <span class="text-base">🎯</span>
                <span>Use My GPS</span>
            </button>
            <div class="bg-slate-900/90 backdrop-blur border border-slate-700 p-1 rounded-xl shadow-lg flex gap-1">
                <button type="button" id="btn-mode-streets" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-500 text-white transition">
                    🗺️ Map
                </button>
                <button type="button" id="btn-mode-satellite" class="px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition">
                    🛰️ Satellite
                </button>
            </div>
        </div>


        <!-- Floating Helper Badge -->
        <div class="absolute top-4 left-4 z-20 bg-slate-900/90 backdrop-blur border border-slate-700 text-xs px-3 py-2 rounded-xl shadow-lg max-w-[240px]">
            <p class="text-slate-200 font-medium">👆 Drag map to place pin at your exact doorstep</p>
        </div>

        <!-- Center Crosshair / Fixed Pin Indicator -->
        <div class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center -translate-y-4">
            <div class="flex flex-col items-center">
                <div class="text-4xl filter drop-shadow-[0_8px_12px_rgba(0,0,0,0.6)] pin-pulse">📍</div>
                <div class="w-3 h-1.5 bg-black/40 rounded-full blur-[1px]"></div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Drawer -->
    <div class="bg-slate-800 border-t border-slate-700/80 p-4 z-30 shadow-2xl space-y-3">
        <!-- Live Detected Address -->
        <div class="bg-slate-900/70 border border-slate-700/60 rounded-xl p-3">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                <span class="font-medium">Selected Location</span>
                <span id="coords-text" class="font-mono text-[11px] text-slate-500">
                    {{ number_format($initialLat, 5) }}, {{ number_format($initialLng, 5) }}
                </span>
            </div>
            <p id="area-text" class="text-sm font-semibold text-white truncate">
                Detecting location name...
            </p>
        </div>

        <!-- Detailed Address Input (Optional adjustment) -->
        <div>
            <label for="address-input" class="block text-xs font-medium text-slate-300 mb-1">
                House / Flat / Street / Landmark (Barah-e-karam tafseel likhein):
            </label>
            <input type="text" id="address-input" value="" placeholder="e.g. House #14, Street 3, near Bilal Masjid (Optional)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
        </div>

        <!-- Confirm Button -->
        <button id="confirm-btn" type="button" class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2 transition-all">
            <span>Confirm Delivery Pin</span>
            <span class="text-lg">✅</span>
        </button>

        <p class="text-[11px] text-center text-slate-400">
            Rider will navigate directly to this GPS pin for fast, accurate delivery.
        </p>
    </div>

    <!-- Success Modal Overlay -->
    <div id="success-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl">
            <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center text-3xl mx-auto border border-emerald-500/40">
                ✓
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Location Pin Confirmed!</h3>
                <p id="success-msg" class="text-xs text-slate-300 mt-1">
                    Your exact delivery coordinates have been recorded.
                </p>
            </div>

            @if($isOrder)
                <a href="{{ url('/track/' . $trackingCode) }}" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl transition">
                    View Live Order Tracking 🛵
                </a>
            @else
                <button type="button" onclick="window.close();" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl transition">
                    Done — Return to WhatsApp 💬
                </button>
            @endif
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentLat = {{ (float) $initialLat }};
        let currentLng = {{ (float) $initialLng }};

        const map = L.map('map', { zoomControl: false, attributionControl: false })
            .setView([currentLat, currentLng], 16);

        const GOOGLE_ROADMAP = 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}';
        const GOOGLE_HYBRID  = 'https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}';

        let currentTileLayer = L.tileLayer(GOOGLE_ROADMAP, {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Map layer switcher
        const btnStreets   = document.getElementById('btn-mode-streets');
        const btnSatellite = document.getElementById('btn-mode-satellite');

        if (btnStreets && btnSatellite) {
            btnStreets.addEventListener('click', function() {
                map.removeLayer(currentTileLayer);
                currentTileLayer = L.tileLayer(GOOGLE_ROADMAP, { maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3'] }).addTo(map);
                btnStreets.className   = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-500 text-white transition';
                btnSatellite.className = 'px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition';
            });

            btnSatellite.addEventListener('click', function() {
                map.removeLayer(currentTileLayer);
                currentTileLayer = L.tileLayer(GOOGLE_HYBRID, { maxZoom: 20, subdomains: ['mt0', 'mt1', 'mt2', 'mt3'] }).addTo(map);
                btnSatellite.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-500 text-white transition';
                btnStreets.className   = 'px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition';
            });
        }


        const coordsText = document.getElementById('coords-text');
        const areaText   = document.getElementById('area-text');
        const confirmBtn = document.getElementById('confirm-btn');
        const gpsBtn     = document.getElementById('gps-btn');
        const addrInput  = document.getElementById('address-input');
        const successModal = document.getElementById('success-modal');

        let reverseTimer = null;
        let currentSelectedLocation = '';

        function isNoise(text) {
            if (!text) return true;
            const lower = text.toLowerCase().trim();
            return (
                lower.includes('tehsil') ||
                lower.includes('district') ||
                lower.includes('division') ||
                lower.includes('historical_division') ||
                lower.includes('تحصیل') ||
                lower.includes('ضلع') ||
                lower.includes('ڈویژن') ||
                lower.includes('punjab') ||
                lower.includes('پنجاب') ||
                lower.includes('sindh') ||
                lower.includes('pakistan') ||
                lower.includes('پاکستان') ||
                /^\d{4,6}$/.test(lower)
            );
        }

        function extractGranularLocality(nomData, phoData, lat, lng) {
            const addr = nomData?.address || {};
            const phoProps = phoData?.features?.[0]?.properties || {};

            // 1. POI / Landmark / Shop / Amenity / Building
            const poi = addr.amenity || addr.shop || addr.building || addr.office || addr.tourism || addr.historic || addr.leisure || addr.healthcare || nomData?.name || phoProps.name || '';
            
            // 2. Road / Street / Highway
            const road = addr.road || addr.street || addr.highway || phoProps.street || '';
            
            // 3. Locality / Basti / Neighborhood / Village / Hamlet
            const locality = addr.neighbourhood || addr.suburb || addr.hamlet || addr.isolated_dwelling || addr.village || addr.locality || phoProps.district || phoProps.locality || '';
            
            // 4. Town / City
            const city = addr.town || addr.city || phoProps.city || '';

            let components = [poi, road, locality, city];

            // If components are too sparse, check raw display_name parts
            if (components.filter(Boolean).length < 2 && nomData?.display_name) {
                const rawParts = nomData.display_name.split(',').map(p => p.trim());
                for (const p of rawParts) {
                    if (!isNoise(p) && !components.includes(p)) {
                        components.push(p);
                    }
                }
            }

            // Deduplicate and filter out administrative noise
            const seen = new Set();
            const result = [];
            for (const c of components) {
                const trimmed = (c || '').trim();
                if (trimmed && !isNoise(trimmed)) {
                    const key = trimmed.toLowerCase();
                    if (!seen.has(key)) {
                        seen.add(key);
                        result.push(trimmed);
                    }
                }
            }

            if (result.length === 0) {
                return 'Selected Pin Location (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';
            }

            return result.slice(0, 3).join(', ');
        }

        function reverseGeocode(lat, lng) {
            coordsText.textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
            areaText.innerHTML = '<span class="text-slate-400 font-normal animate-pulse">Detecting address...</span>';
            clearTimeout(reverseTimer);
            reverseTimer = setTimeout(async () => {
                try {
                    const nomP = fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=19&addressdetails=1&accept-language=en,ur', {
                        headers: { 'Accept': 'application/json' }
                    }).then(r => r.json()).catch(() => null);

                    const phoP = fetch('https://photon.komoot.io/reverse?lat=' + lat + '&lon=' + lng + '&lang=en')
                        .then(r => r.json()).catch(() => null);

                    const [nomData, phoData] = await Promise.all([nomP, phoP]);
                    const cleanLoc = extractGranularLocality(nomData, phoData, lat, lng);

                    currentSelectedLocation = cleanLoc;
                    areaText.textContent = cleanLoc;
                } catch (e) {
                    currentSelectedLocation = 'Selected Pin Location (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';
                    areaText.textContent = currentSelectedLocation;
                }
                // Keep addrInput empty so the user can optionally enter their house/flat details without clutter
            }, 350);
        }

        // Sync center on map move
        map.on('move', function() {
            const center = map.getCenter();
            currentLat = center.lat;
            currentLng = center.lng;
            coordsText.textContent = currentLat.toFixed(5) + ', ' + currentLng.toFixed(5);
        });

        map.on('moveend', function() {
            const center = map.getCenter();
            currentLat = center.lat;
            currentLng = center.lng;
            reverseGeocode(currentLat, currentLng);
        });

        // Click anywhere on map to pan pin to that location
        map.on('click', function(e) {
            map.panTo(e.latlng);
        });

        // Trigger initial reverse geocode
        reverseGeocode(currentLat, currentLng);

        // GPS Button handler
        gpsBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('GPS is not supported by your browser.');
                return;
            }
            gpsBtn.disabled = true;
            gpsBtn.innerHTML = '<span>⏳</span> Locating...';

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    currentLat = pos.coords.latitude;
                    currentLng = pos.coords.longitude;
                    map.flyTo([currentLat, currentLng], 17, { duration: 1.2 });
                    gpsBtn.disabled = false;
                    gpsBtn.innerHTML = '<span>🎯</span> Use My GPS';
                    reverseGeocode(currentLat, currentLng);
                },
                function(err) {
                    gpsBtn.disabled = false;
                    gpsBtn.innerHTML = '<span>🎯</span> Use My GPS';
                    alert('Unable to retrieve location. Please check location permissions or drag the pin manually.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });

        // Auto-prompt GPS if coordinates were default city coords
        @if(!$hasCoords)
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                currentLat = pos.coords.latitude;
                currentLng = pos.coords.longitude;
                map.setView([currentLat, currentLng], 17);
                reverseGeocode(currentLat, currentLng);
            }, function() {}, { enableHighAccuracy: true, timeout: 5000 });
        }
        @endif

        // Submit confirmed pin
        confirmBtn.addEventListener('click', function() {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span>Saving...</span> ⏳';

            const userExtra = addrInput.value.trim();
            const baseLoc = currentSelectedLocation || areaText.textContent.trim();
            const finalAddress = userExtra ? (userExtra + (baseLoc ? ', ' + baseLoc : '')) : baseLoc;

            const payload = {
                lat: currentLat,
                lng: currentLng,
                address: finalAddress,
                location_source: 'customer_pin'
            };

            const token = @json($token);
            fetch('/confirm-location/' + token, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('success-msg').textContent = data.message;
                    successModal.classList.remove('hidden');
                } else {
                    alert(data.message || 'Failed to save location.');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<span>Confirm Delivery Pin</span> ✅';
                }
            })
            .catch(err => {
                alert('Network error. Please check your connection and try again.');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<span>Confirm Delivery Pin</span> ✅';
            });
        });
    });
    </script>
</body>
</html>