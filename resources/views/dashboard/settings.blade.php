@extends('layouts.dashboard')
@section('title', 'Settings')
@section('header_title', 'Restaurant Settings')
@section('header_subtitle', 'Configure business information, delivery rules, and WhatsApp bot preferences')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .card-panel {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        margin-bottom: 20px;
    }

    .card-panel-header {
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-panel-header h3 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .card-panel-header p {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 13px;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        font-family: inherit;
        transition: all 0.15s ease;
    }
    .form-control:focus {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .form-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .status-banner {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .status-banner-left h3 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }
    .status-banner-left p {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-table td {
        padding: 10px 0;
        font-size: 13px;
        border-bottom: 1px solid #f8fafc;
    }
    .info-table .lbl { color: #64748b; font-weight: 500; width: 40%; }
    .info-table .val { color: #0f172a; font-weight: 700; text-align: right; }

    @media (max-width: 1024px) {
        .settings-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .status-banner {
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 14px;
            border-radius: 16px;
        }
        .settings-card {
            padding: 16px 14px;
            border-radius: 16px;
        }
    }
</style>

<!-- RESTAURANT STATUS BANNER -->
<form method="POST" action="{{ route('dashboard.update-settings', $restaurant->id) }}">
    @csrf
    <input type="hidden" name="toggle_open_only" value="{{ $restaurant->is_open ? 'closed' : 'open' }}">

    <div class="status-banner">
        <div class="status-banner-left">
            <h3>🏪 Restaurant Ordering Status</h3>
            <p>When set to Closed, the WhatsApp bot politely informs customers that your kitchen is currently closed.</p>
        </div>

        <div style="display: flex; align-items: center; gap: 14px;">
            <span class="badge-status {{ $restaurant->is_open ? 'delivered' : 'cancelled' }}" style="font-size: 12px; padding: 5px 12px;">
                ● {{ $restaurant->is_open ? 'OPEN FOR ORDERS' : 'CLOSED' }}
            </span>
            <label class="switch" style="width: 44px; height: 24px; cursor: pointer;" title="Toggle Open/Closed">
                <input type="checkbox" name="is_open" value="1" onchange="this.form.submit()" {{ $restaurant->is_open ? 'checked' : '' }}>
                <span class="slider" style="border-radius: 999px;"></span>
            </label>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('dashboard.update-settings', $restaurant->id) }}">
    @csrf
    <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? '1' : '0' }}">

    <div class="settings-grid">
        <!-- LEFT COLUMN: SETTINGS FORM -->
        <div>
            <!-- GENERAL SETTINGS -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>General Business Information</h3>
                        <p>Basic details shown to customers during WhatsApp order placement</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Restaurant Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $restaurant->name) }}" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">WhatsApp Bot Number *</label>
                        <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $restaurant->whatsapp_number) }}" required>
                        <div class="form-hint">Number used to connect your AI WhatsApp bot</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Owner Contact Number</label>
                        <input type="text" name="owner_phone" class="form-control" value="{{ old('owner_phone', $restaurant->owner_phone) }}">
                        <div class="form-hint">Receives new order notifications and alerts</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $restaurant->city) }}" placeholder="e.g. Lahore, Karachi, Bahawalpur">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Restaurant Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $restaurant->address) }}" placeholder="e.g. Main Boulevard, Phase 5">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Opening Hours</label>
                    <input type="text" name="hours" class="form-control" value="{{ old('hours', $restaurant->hours) }}" placeholder="e.g. 10 AM – 11 PM">
                    <div class="form-hint">Shown to customers when you're closed, and used by the bot to answer "what time do you open?"</div>
                </div>
            </div>

            <!-- DELIVERY RULES -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Delivery & Pricing Rules</h3>
                        <p>Automated price calculations applied by the bot</p>
                    </div>
                </div>

                {{-- Hidden Coordinates inputs --}}
                <input type="hidden" id="restaurant_lat" name="restaurant_lat" value="{{ old('restaurant_lat', $restaurant->restaurant_lat) }}">
                <input type="hidden" id="restaurant_lng" name="restaurant_lng" value="{{ old('restaurant_lng', $restaurant->restaurant_lng) }}">

                <!-- 1. FOODPANDA-STYLE DELIVERY RADIUS SLIDER -->
                <div class="form-group" style="margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="form-label" style="margin-bottom: 0; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                            <span>🛵 Maximum Delivery Radius</span>
                            <span style="font-size: 11px; background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 999px; font-weight: 700; border: 1px solid #a7f3d0;">Foodpanda-Style</span>
                        </label>
                        <span id="radius-val-badge" style="background: #10b981; color: white; padding: 3px 12px; border-radius: 999px; font-weight: 800; font-size: 13px; box-shadow: 0 2px 6px rgba(16,185,129,0.3);">
                            {{ old('delivery_radius_km', $restaurant->delivery_radius_km ?? 5.0) }} KM
                        </span>
                    </div>

                    <input 
                        type="range" 
                        id="delivery_radius_km" 
                        name="delivery_radius_km" 
                        min="1" 
                        max="25" 
                        step="0.5" 
                        value="{{ old('delivery_radius_km', $restaurant->delivery_radius_km ?? 5.0) }}" 
                        style="width: 100%; accent-color: #10b981; cursor: pointer;"
                        oninput="onRadiusSliderChange(this.value)"
                    >
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; margin-top: 4px;">
                        <span>1 KM (Immediate)</span>
                        <span>5 KM (Standard City)</span>
                        <span>10 KM (Wide Area)</span>
                        <span>25 KM (Max Coverage)</span>
                    </div>
                    <div class="form-hint" style="margin-top: 6px;">
                        The WhatsApp bot calculates the customer's exact GPS distance. Any address beyond <strong><span id="radius-hint-text">{{ old('delivery_radius_km', $restaurant->delivery_radius_km ?? 5.0) }}</span> KM</strong> is politely declined.
                    </div>
                </div>

                <!-- 2. INTERACTIVE COVERAGE GEOFENCE MAP -->
                <div class="form-group" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 12.5px; font-weight: 700; color: #1e293b;">📍 Kitchen Location & Delivery Geofence</span>
                        <button type="button" onclick="locateMyKitchen()" class="btn" style="padding: 4px 10px; font-size: 11px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-weight: 600;">
                            🎯 Auto-Detect My Location
                        </button>
                    </div>
                    <div id="coverage-map" style="width: 100%; height: 260px; border-radius: 12px; border: 1.5px solid #cbd5e1; overflow: hidden; background: #f8fafc; position: relative; z-index: 1;"></div>
                    <span style="font-size: 11px; color: #64748b; margin-top: 5px; display: block;">
                        💡 Click anywhere on the map or drag the 🏪 pin to set your exact kitchen position. The green circle shows your live delivery zone!
                    </span>
                </div>

                <!-- 3. OPTIONAL NEIGHBORHOOD WHITELIST -->
                <div class="form-group" style="border-top: 1px dashed #e2e8f0; pt-3; margin-top: 20px; padding-top: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="form-label" style="margin-bottom: 0;">🏷️ Specific Sector Whitelist <span style="font-size: 11px; color: #94a3b8; font-weight: normal;">(Optional)</span></label>
                    </div>
                    <p class="form-hint" style="margin-bottom: 8px;">
                        Optional override: If left empty, the bot automatically delivers anywhere within your <strong>KM radius</strong>. Only add names here if you want to strictly restrict delivery to specific sectors or phases.
                    </p>

                    {{-- Hidden real input submitted with form --}}
                    <input type="hidden" id="delivery_areas_value" name="delivery_areas" value="{{ old('delivery_areas', $restaurant->delivery_areas) }}">

                    {{-- Tag chip display --}}
                    <div id="area-tags-container" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:6px 8px;border:1.5px solid #cbd5e1;border-radius:10px;background:#f8fafc;cursor:text;" onclick="document.getElementById('area-tag-input').focus()">
                        {{-- JS will render chips here --}}
                    </div>

                    {{-- Typing input --}}
                    <div style="display:flex;gap:8px;margin-top:8px;">
                        <input
                            type="text"
                            id="area-tag-input"
                            placeholder="Type sector/town name (e.g. Model Town) then press Enter"
                            class="form-control"
                            style="flex:1;"
                            onkeydown="handleAreaKeydown(event)"
                        >
                        <button type="button" onclick="addAreaTag()" class="btn" style="background:#0f172a;color:#fff;white-space:nowrap;padding:0 16px;">+ Add</button>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Delivery Fee (PKR)</label>
                        <input type="number" name="delivery_charge" class="form-control" value="{{ old('delivery_charge', $restaurant->delivery_charge) }}" min="0" step="1">
                        <div class="form-hint">Auto-added to customer cart total (0 = Free Delivery)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Minimum Order Amount (PKR)</label>
                        <input type="number" name="minimum_order" class="form-control" value="{{ old('minimum_order', $restaurant->minimum_order) }}" min="0" step="1">
                        <div class="form-hint">Bot will enforce this minimum before accepting order</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Greeting Message</label>
                    <input type="text" name="greeting_message" class="form-control" value="{{ old('greeting_message', $restaurant->greeting_message) }}" placeholder="Welcome to Pizza Palace! How can I help you today?">
                    <div class="form-hint">First welcome message sent to new WhatsApp customers</div>
                </div>
            </div>

            <!-- GOOGLE SHEET WEBHOOK -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Google Sheets Webhook Sync</h3>
                        <p>Push live orders to your custom Google Sheet automatically</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Google Apps Script Webhook URL</label>
                    <input type="url" name="google_sheet_webhook" class="form-control" value="{{ old('google_sheet_webhook', $restaurant->google_sheet_webhook) }}" placeholder="https://script.google.com/macros/s/.../exec">
                    <div class="form-hint">Leave blank if not using Google Sheets sync</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 10px;">
                💾 Save All Settings
            </button>
        </div>

        <!-- RIGHT COLUMN: PLAN & STATS -->
        <div>
            <!-- SUBSCRIPTION INFO -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Subscription Package</h3>
                        <p>Your WhatsApp bot plan</p>
                    </div>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="lbl">Current Plan</td>
                        <td class="val">
                            <span class="badge-status confirmed" style="font-size: 11px;">
                                {{ ucfirst($restaurant->plan) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl">Plan Status</td>
                        <td class="val">
                            @if($restaurant->plan_expires_at)
                                {{ $restaurant->plan_expires_at->isFuture() ? 'Active' : 'Expired' }}
                            @else
                                Active (Trial)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl">Expires On</td>
                        <td class="val">
                            {{ $restaurant->plan_expires_at ? $restaurant->plan_expires_at->format('d M Y') : 'No Expiry' }}
                        </td>
                    </tr>
                </table>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b; line-height: 1.4;">
                    Need to extend your plan or add features? Contact your platform provider.
                </div>
            </div>

            <!-- ALL-TIME STATS -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Restaurant Performance</h3>
                        <p>All-time bot orders overview</p>
                    </div>
                </div>

                @php
                    $allOrders = $restaurant->orders;
                    $deliveredOrders = $allOrders->where('status', 'delivered');
                    $activeRevenue = $allOrders->where('status', '!=', 'cancelled')->sum('total');
                @endphp

                <table class="info-table">
                    <tr>
                        <td class="lbl">Total Orders</td>
                        <td class="val">{{ number_format($allOrders->count()) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Delivered Orders</td>
                        <td class="val" style="color: #16a34a;">{{ number_format($deliveredOrders->count()) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Total Revenue</td>
                        <td class="val" style="color: #4f46e5;">PKR {{ number_format($activeRevenue, 0) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Menu Food Items</td>
                        <td class="val">{{ $restaurant->menuItems()->count() }} items</td>
                    </tr>
                </table>
            </div>

            <!-- BOT CONNECTION SHORTCUT -->
            <div class="card-panel" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #bbf7d0;">
                <h4 style="font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 4px;">📱 WhatsApp Bot Status</h4>
                <p style="font-size: 12px; color: #15803d; line-height: 1.4; margin-bottom: 14px;">
                    Scan the QR code anytime to connect or re-link your WhatsApp number.
                </p>
                <a href="{{ route('dashboard.connect-whatsapp', $restaurant->id) }}" class="btn btn-success" style="width: 100%; justify-content: center;">
                    Open QR Connection Screen →
                </a>
            </div>
        </div>
    </div>
</form>

<script>
// ── Interactive Coverage Map & Radius Geofence ────────────────────────────────
let map, marker, circle;

function initCoverageMap() {
    const latInput = document.getElementById('restaurant_lat');
    const lngInput = document.getElementById('restaurant_lng');
    const radiusInput = document.getElementById('delivery_radius_km');
    const mapContainer = document.getElementById('coverage-map');
    if (!mapContainer) return;

    // Fallback known city centers
    const cityCoords = {
        'lodhran': [29.5405, 71.6336], 'multan': [30.1575, 71.5249],
        'bahawalpur': [29.3544, 71.6911], 'lahore': [31.5204, 74.3587],
        'faisalabad': [31.4504, 73.1350], 'rawalpindi': [33.5651, 73.0169],
        'islamabad': [33.6844, 73.0479], 'karachi': [24.8607, 67.0011],
        'peshawar': [34.0151, 71.5249], 'quetta': [30.1798, 66.9750],
        'gujranwala': [32.1877, 74.1945], 'sialkot': [32.4945, 74.5229],
        'sargodha': [32.0836, 72.6711], 'sahiwal': [30.6682, 73.1114],
        'hyderabad': [25.3960, 68.3578], 'sukkur': [27.7052, 68.8574],
    };

    const restCity = @json(strtolower(trim($restaurant->city ?? '')));
    let defaultLat = 29.5405;
    let defaultLng = 71.6336;
    for (const [c, coords] of Object.entries(cityCoords)) {
        if (restCity && (restCity.includes(c) || c.includes(restCity))) {
            defaultLat = coords[0];
            defaultLng = coords[1];
            break;
        }
    }

    let lat = parseFloat(latInput.value) || defaultLat;
    let lng = parseFloat(lngInput.value) || defaultLng;
    let radiusKm = parseFloat(radiusInput.value) || 5.0;

    map = L.map('coverage-map', { zoomControl: true, attributionControl: false }).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const kitchenIcon = L.divIcon({
        html: '<div style="background:#0f172a;color:white;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 10px rgba(0,0,0,0.4);border:2px solid white;cursor:grab;">🏪</div>',
        className: '', iconSize: [34, 34], iconAnchor: [17, 17]
    });

    marker = L.marker([lat, lng], { icon: kitchenIcon, draggable: true }).addTo(map);
    circle = L.circle([lat, lng], {
        radius: radiusKm * 1000,
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.18,
        weight: 2
    }).addTo(map);

    function updateCoords(newLat, newLng) {
        latInput.value = newLat.toFixed(7);
        lngInput.value = newLng.toFixed(7);
        marker.setLatLng([newLat, newLng]);
        circle.setLatLng([newLat, newLng]);
    }

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        updateCoords(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        updateCoords(e.latlng.lat, e.latlng.lng);
    });

    // If initial coords were empty, save default into inputs
    if (!latInput.value) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }
}

window.onRadiusSliderChange = function(val) {
    const km = parseFloat(val);
    document.getElementById('radius-val-badge').textContent = km + ' KM';
    document.getElementById('radius-hint-text').textContent = km;
    if (circle) {
        circle.setRadius(km * 1000);
        map.fitBounds(circle.getBounds(), { padding: [20, 20] });
    }
};

window.locateMyKitchen = function() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        document.getElementById('restaurant_lat').value = lat.toFixed(7);
        document.getElementById('restaurant_lng').value = lng.toFixed(7);
        if (map && marker && circle) {
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            map.setView([lat, lng], 14);
        }
    }, function() {
        alert('Unable to retrieve your current location. Please click on the map to set your kitchen pin.');
    });
};

document.addEventListener('DOMContentLoaded', initCoverageMap);

// ── Delivery Area Tag Chip Manager ────────────────────────────────────────────
(function () {
    const container  = document.getElementById('area-tags-container');
    const input      = document.getElementById('area-tag-input');
    const hidden     = document.getElementById('delivery_areas_value');
    if (!container || !input || !hidden) return;

    let areas = hidden.value
        ? hidden.value.split(',').map(a => a.trim()).filter(Boolean)
        : [];

    function syncHidden() {
        hidden.value = areas.join(', ');
    }

    function renderChips() {
        container.innerHTML = '';
        areas.forEach((area, i) => {
            const chip = document.createElement('span');
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:5px;background:#0f172a;color:#fff;padding:4px 10px;border-radius:999px;font-size:13px;font-weight:600;';
            chip.innerHTML = `${area} <button type="button" onclick="removeArea(${i})" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:15px;line-height:1;padding:0;" title="Remove">×</button>`;
            container.appendChild(chip);
        });
    }

    window.removeArea = function (i) {
        areas.splice(i, 1);
        syncHidden();
        renderChips();
    };

    window.addAreaTag = function () {
        const val = input.value.trim().replace(/,+$/, '');
        if (!val) return;
        // Allow comma-separated paste
        val.split(',').map(a => a.trim()).filter(Boolean).forEach(a => {
            if (!areas.map(x => x.toLowerCase()).includes(a.toLowerCase())) {
                areas.push(a);
            }
        });
        input.value = '';
        syncHidden();
        renderChips();
        input.focus();
    };

    window.handleAreaKeydown = function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addAreaTag();
        }
    };

    // Ensure hidden value is in sync when form submits
    const form = hidden.closest('form');
    if (form) form.addEventListener('submit', syncHidden);

    renderChips();
})();
</script>

@endsection