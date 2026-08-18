@extends('layouts.dashboard')
@section('content')

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin: 1.5rem 0;
}
.stat-card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 14px;
    padding: 16px;
    transition: all 0.2s ease;
}
.stat-label { font-size: 12px; color: #888; }
.stat-value { font-size: 22px; font-weight: 700; margin: 6px 0; color: #0f172a; }
.stat-sub   { font-size: 11px; color: #aaa; }

.alert {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 1.5rem;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card {
    background: #fff;
    border: 1px solid #e8e8e4;
    border-radius: 16px;
    overflow: hidden;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
    border-bottom: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
}
.card-header h2 { font-size: 16px; font-weight: 700; color: #0f172a; }

.btn {
    background: #0e0e10;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    border: none;
    font-weight: 600;
}
.btn:hover { background: #2a2a2e; }

.btn-sm {
    padding: 4px 10px;
    font-size: 11px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: none;
    cursor: pointer;
    font-weight: 600;
}
.btn-confirm   { background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc; }
.btn-prepare   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.btn-dispatch  { background: #0e0e10; color: #ffffff; border: 1px solid #0e0e10; }
.btn-delivered { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

.table { width: 100%; border-collapse: collapse; }
.table th {
    text-align: left;
    font-size: 11px;
    color: #888;
    padding: 12px;
    background: #fafafa;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.table td {
    padding: 12px;
    border-top: 1px solid #f0f0f0;
    vertical-align: top;
    font-size: 13px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}
.status-pending          { background:#f3f4f6; color:#374151; }
.status-confirmed        { background:#ecfeff; color:#0e7490; }
.status-preparing        { background:#fff7ed; color:#c2410c; }
.status-out_for_delivery { background:#f0fdf4; color:#15803d; }
.status-delivered        { background:#dcfce7; color:#15803d; }
.status-cancelled        { background:#fee2e2; color:#991b1b; }

.tracking-code {
    font-family: monospace;
    font-size: 12px;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 6px;
    color: #374151;
    font-weight: 700;
    text-decoration: none;
}
.tracking-code:hover { background: #e5e7eb; color: #111827; }

.pulse-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulse-ring 1.8s infinite cubic-bezier(0.66, 0, 0, 1);
}
@keyframes pulse-ring {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@media(max-width: 900px){
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Live Orders</h1>
        <p style="font-size: 13px; color: #64748b;">
            {{ now()->format('l, d F Y') }} — <span class="pulse-dot"></span> <strong>Live Real-Time Sync Active</strong>
        </p>
    </div>
    <div style="display: flex; gap: 8px;">
        <button class="btn" onclick="location.reload()" style="display: flex; align-items: center; gap: 6px;">
            ↻ Refresh Feed
        </button>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Today's Orders</div>
        <div class="stat-value" id="stat-today-count">{{ $today->count() }}</div>
        <div class="stat-sub">since midnight</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue</div>
        <div class="stat-value" id="stat-today-revenue">Rs. {{ number_format($today->where('status', '!=', 'cancelled')->sum('total'), 0) }}</div>
        <div class="stat-sub">confirmed & active</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Orders</div>
        <div class="stat-value" id="stat-active-count">
            {{ $today->whereIn('status', ['pending','confirmed','preparing','out_for_delivery'])->count() }}
        </div>
        <div class="stat-sub">in progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Delivered</div>
        <div class="stat-value" id="stat-delivered-count">{{ $today->where('status','delivered')->count() }}</div>
        <div class="stat-sub">completed</div>
    </div>
</div>

{{-- NEW ORDER ALERT --}}
@php $pendingOrders = $today->where('status','pending') @endphp
<div id="pending-alert" class="alert" style="{{ $pendingOrders->count() > 0 ? '' : 'display:none;' }}">
    <div>
        🔔 <strong><span id="pending-count-text">{{ $pendingOrders->count() }}</span> new order(s) waiting for confirmation!</strong>
    </div>
    <span style="font-size: 12px; color: #ea580c; font-weight: 600;">Action required</span>
</div>

{{-- ORDERS TABLE --}}
<div class="card">
    <div class="card-header">
        <h2>Active Order Pipeline</h2>
        <span style="font-size: 12px; color: #64748b;">
            Stage Flow: <strong>Pending → Confirmed → Preparing → Dispatched → Delivered</strong>
        </span>
    </div>

    @if($orders->count() === 0)
        <div style="text-align:center; padding:40px; color:#888; font-size:14px;">
            No orders yet. Orders placed via WhatsApp will appear here in real-time.
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="table" id="orders-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tracking</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Rider Info</th>
                    <th>Current Status</th>
                    <th>Time</th>
                    <th style="min-width: 190px;">Quick Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr id="order-row-{{ $order->id }}">
                    <td><strong>#{{ $order->id }}</strong></td>

                    <td>
                        <a href="{{ url('/track/' . $order->tracking_code) }}" target="_blank" class="tracking-code" title="View Live Customer Tracking Page">
                            {{ $order->tracking_code }} ↗
                        </a>
                    </td>

                    <td>
                        @if($order->customer_name)
                            <strong>{{ $order->customer_name }}</strong><br>
                        @endif
                        <span style="font-size:12px; color:#2563eb; font-weight:600;">{{ $order->customer_phone }}</span><br>
                        <span style="font-size:12px; color:#475569;">📍 {{ Str::limit($order->delivery_address ?: 'Collected via chat', 35) }}</span>
                    </td>

                    <td>
                        @if($order->items->count() > 0)
                            @foreach($order->items as $item)
                                <div style="margin-bottom:2px;">
                                    <strong>{{ $item->name }}</strong>
                                    @if($item->size) <span style="font-size:11px; color:#64748b;">({{ $item->size }})</span> @endif
                                    <span style="color:#0284c7; font-weight:700;">× {{ $item->quantity }}</span>
                                </div>
                            @endforeach
                        @else
                            {{-- Fallback: Extract item lines from notes for past orders --}}
                            @php
                                $itemLines = [];
                                if ($order->notes) {
                                    $lines = explode("\n", $order->notes);
                                    foreach ($lines as $l) {
                                        $cleanL = trim(str_replace(['*','_'], '', $l));
                                        if (preg_match('/^([0-9]+)\s*[xX×]\s*(.+?)(?:\s*—|\s*[-–:]\s*|\s+Rs\.|\s+@|\s+each|$)/i', $cleanL, $m)) {
                                            $itemLines[] = $m[1] . 'x ' . trim($m[2]);
                                        }
                                    }
                                }
                            @endphp
                            @if(count($itemLines) > 0)
                                @foreach($itemLines as $il)
                                    <div style="margin-bottom:2px; font-weight:600; color:#334155;">{{ $il }}</div>
                                @endforeach
                            @else
                                <span style="color:#64748b; font-size:12px;">Standard Order</span>
                            @endif
                        @endif
                    </td>

                    <td>
                        <strong style="color: #0f172a; font-size: 14px;">Rs. {{ number_format($order->total, 0) }}</strong><br>
                        <span style="font-size: 11px; color: #64748b; text-transform: uppercase;">{{ str_replace('_', ' ', $order->payment_method ?: 'COD') }}</span>
                    </td>

                    <td>
                        @if($order->rider_name || $order->rider_phone)
                            <div style="font-size:12px; background:#f0fdf4; border:1px solid #bbf7d0; padding:4px 8px; border-radius:6px; color:#166534;">
                                <strong>🛵 {{ $order->rider_name }}</strong><br>
                                <span style="font-size:11px;">{{ $order->rider_phone }}</span>
                                @if($order->estimated_minutes)
                                    <div style="font-size:10px; color:#15803d; margin-top:2px;">⏱️ ~{{ $order->estimated_minutes }} mins</div>
                                @endif
                            </div>
                        @else
                            <span style="font-size:12px; color:#94a3b8;">Not assigned</span>
                        @endif
                    </td>

                    <td>
                        <span class="status-badge status-{{ $order->status }}">
                            {{ $order->status_label }}
                        </span>
                    </td>

                    <td style="font-size:12px; color:#64748b;">
                        {{ $order->created_at->diffForHumans() }}
                    </td>

                    <td>
                        @if(!in_array($order->status, ['delivered','cancelled']))
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            {{-- Quick 1-Click Action Button based on current stage --}}
                            @if($order->status === 'pending')
                                <button type="button" onclick="quickAdvanceStatus('{{ $order->id }}', 'confirmed')" class="btn-sm btn-confirm">
                                    ✅ Confirm Order
                                </button>
                            @elseif($order->status === 'confirmed')
                                <button type="button" onclick="quickAdvanceStatus('{{ $order->id }}', 'preparing')" class="btn-sm btn-prepare">
                                    👨‍🍳 Start Preparing
                                </button>
                            @elseif($order->status === 'preparing')
                                <button type="button" onclick="openRiderModal('{{ $order->id }}', '{{ $order->tracking_code }}', '{{ $order->rider_name }}', '{{ $order->rider_phone }}', '{{ $order->estimated_minutes }}')" class="btn-sm btn-dispatch">
                                    🛵 Dispatch & Assign Rider
                                </button>
                            @elseif($order->status === 'out_for_delivery')
                                <button type="button" onclick="quickAdvanceStatus('{{ $order->id }}', 'delivered')" class="btn-sm btn-delivered">
                                    🎉 Mark Delivered
                                </button>
                            @endif

                            {{-- Manual Dropdown Fallback --}}
                            <form id="form-order-{{ $order->id }}" method="POST" action="{{ route('dashboard.update-status', [$restaurant->id, $order->id]) }}">
                                @csrf
                                <input type="hidden" name="rider_name" id="rider-name-{{ $order->id }}" value="{{ $order->rider_name }}">
                                <input type="hidden" name="rider_phone" id="rider-phone-{{ $order->id }}" value="{{ $order->rider_phone }}">
                                <input type="hidden" name="estimated_minutes" id="rider-eta-{{ $order->id }}" value="{{ $order->estimated_minutes }}">
                                
                                <select name="status" onchange="handleDropdownChange(this, '{{ $order->id }}', '{{ $order->tracking_code }}')"
                                        style="padding:4px 8px; border-radius:6px; border:1px solid #e2e8f0; font-size:11px; color:#475569; cursor:pointer; width:100%; background:#f8fafc;">
                                    <option value="pending"          {{ $order->status==='pending'          ? 'selected':'' }}>1. Pending</option>
                                    <option value="confirmed"        {{ $order->status==='confirmed'        ? 'selected':'' }}>2. Confirmed</option>
                                    <option value="preparing"        {{ $order->status==='preparing'        ? 'selected':'' }}>3. Preparing</option>
                                    <option value="out_for_delivery" {{ $order->status==='out_for_delivery' ? 'selected':'' }}>4. 🛵 Dispatched</option>
                                    <option value="delivered"        {{ $order->status==='delivered'        ? 'selected':'' }}>5. Delivered</option>
                                    <option value="cancelled">Cancel Order</option>
                                </select>
                            </form>
                        </div>
                        @else
                            <span style="color:#166534; font-size:12px; font-weight:700;">✓ Completed</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:1rem;">
        {{ $orders->links() }}
    </div>
    @endif
</div>

<!-- Modal for Assigning Rider & Dispatching -->
<div id="rider-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    <div style="background:#fff; border-radius:16px; padding:24px; max-width:440px; width:92%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="font-size:17px; font-weight:800; color:#0f172a; margin:0;">🛵 Dispatch Order & Assign Rider</h3>
            <button onclick="closeRiderModal()" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>

        <p style="font-size:12px; color:#64748b; margin-bottom:16px; line-height:1.4;">
            When you click <strong>Dispatch Order</strong>, the customer automatically receives a WhatsApp alert with the rider's contact details, ETA, and live tracking link.
        </p>
        
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Rider Name:</label>
            <input type="text" id="modal-rider-name" placeholder="e.g. Ali Raza" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
        </div>

        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Rider Phone Number:</label>
            <input type="text" id="modal-rider-phone" placeholder="e.g. 03001234567" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Estimated Delivery Time:</label>
            <div style="display:flex; gap:8px; margin-bottom:6px;">
                <button type="button" onclick="setEta(15)" style="padding:4px 8px; font-size:11px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">15 mins</button>
                <button type="button" onclick="setEta(25)" style="padding:4px 8px; font-size:11px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">25 mins</button>
                <button type="button" onclick="setEta(35)" style="padding:4px 8px; font-size:11px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">35 mins</button>
                <button type="button" onclick="setEta(45)" style="padding:4px 8px; font-size:11px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">45 mins</button>
            </div>
            <input type="number" id="modal-rider-eta" placeholder="Estimated minutes (e.g. 25)" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button onclick="closeRiderModal()" style="padding:9px 16px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; color:#475569;">Cancel</button>
            <button onclick="confirmRiderDispatch()" style="padding:9px 18px; background:#0e0e10; color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;">Dispatch & Send WhatsApp 🛵</button>
        </div>
    </div>
</div>

<script>
    let activeOrderId = null;
    let lastLatestOrderId = {{ $orders->first()?->id ?? 0 }};

    function quickAdvanceStatus(orderId, nextStatus) {
        const form = document.getElementById('form-order-' + orderId);
        if (!form) return;
        const select = form.querySelector('select[name="status"]');
        if (select) select.value = nextStatus;
        form.submit();
    }

    function openRiderModal(orderId, trackingCode, name, phone, eta) {
        activeOrderId = orderId;
        document.getElementById('modal-rider-name').value = name || '';
        document.getElementById('modal-rider-phone').value = phone || '';
        document.getElementById('modal-rider-eta').value = eta || '25';
        document.getElementById('rider-modal').style.display = 'flex';
    }

    function handleDropdownChange(selectElem, orderId, trackingCode) {
        if (selectElem.value === 'out_for_delivery') {
            const currentRiderName = document.getElementById('rider-name-' + orderId).value;
            const currentRiderPhone = document.getElementById('rider-phone-' + orderId).value;
            const currentRiderEta = document.getElementById('rider-eta-' + orderId).value;
            openRiderModal(orderId, trackingCode, currentRiderName, currentRiderPhone, currentRiderEta);
        } else {
            document.getElementById('form-order-' + orderId).submit();
        }
    }

    function setEta(mins) {
        document.getElementById('modal-rider-eta').value = mins;
    }

    function closeRiderModal() {
        document.getElementById('rider-modal').style.display = 'none';
    }

    function confirmRiderDispatch() {
        if (!activeOrderId) return;
        const name = document.getElementById('modal-rider-name').value.trim();
        const phone = document.getElementById('modal-rider-phone').value.trim();
        const eta = document.getElementById('modal-rider-eta').value.trim();
        
        document.getElementById('rider-name-' + activeOrderId).value = name;
        document.getElementById('rider-phone-' + activeOrderId).value = phone;
        document.getElementById('rider-eta-' + activeOrderId).value = eta;
        
        const form = document.getElementById('form-order-' + activeOrderId);
        const select = form.querySelector('select[name="status"]');
        if (select) select.value = 'out_for_delivery';
        form.submit();
    }

    // ── Live Real-Time Dashboard Poller (every 6 seconds) ──
    function pollLiveOrders() {
        fetch('{{ route("dashboard.orders.live-feed", $restaurant->id) }}')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                // Update Stat counters in real-time
                document.getElementById('stat-today-count').textContent = data.today_count;
                document.getElementById('stat-today-revenue').textContent = 'Rs. ' + Number(data.revenue).toLocaleString();
                document.getElementById('stat-active-count').textContent = data.active_count;
                document.getElementById('stat-delivered-count').textContent = data.delivered_count;

                // Update Pending alert badge
                const alertEl = document.getElementById('pending-alert');
                const countTextEl = document.getElementById('pending-count-text');
                if (data.pending_count > 0) {
                    alertEl.style.display = 'flex';
                    countTextEl.textContent = data.pending_count;
                } else {
                    alertEl.style.display = 'none';
                }

                // If a new order arrived, play chime & refresh table
                if (data.latest_order_id > lastLatestOrderId) {
                    lastLatestOrderId = data.latest_order_id;
                    playChime();
                    setTimeout(() => location.reload(), 1200);
                }
            })
            .catch(err => console.debug('Live feed sync...', err));
    }

    function playChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.6);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.6);
        } catch (e) {}
    }

    setInterval(pollLiveOrders, 6000);
</script>

@endsection