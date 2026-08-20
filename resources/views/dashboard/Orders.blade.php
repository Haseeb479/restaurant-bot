@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Welcome back, ' . ($restaurant->name ?? 'Owner') . '!')

@section('content')

<style>
    /* 4-COLUMN MAIN GRID */
    .owner-grid {
        display: grid;
        grid-template-columns: 1.1fr 1.6fr 1.1fr 1.2fr;
        gap: 16px;
        margin-bottom: 24px;
        align-items: stretch;
    }

    .panel-box {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .panel-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .panel-box-title h3 {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .panel-box-title p {
        font-size: 11px;
        color: #64748b;
        margin-top: 1px;
    }

    /* ORDER CARD LIST */
    .order-card-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
        overflow-y: auto;
        max-height: 520px;
    }

    .order-item-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        text-decoration: none;
        color: inherit;
        transition: all 0.15s ease;
        display: block;
    }
    .order-item-card:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .order-item-card.active {
        background: #ffffff;
        border-color: #4f46e5;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
    }

    .oic-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }
    .oic-code { font-size: 13px; font-weight: 800; color: #0f172a; }
    .oic-time { font-size: 11px; color: #94a3b8; }

    .oic-cust { font-size: 12px; color: #334155; font-weight: 600; margin-bottom: 2px; }
    .oic-addr { font-size: 11px; color: #64748b; margin-bottom: 6px; }

    .oic-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        color: #475569;
        border-top: 1px solid #edf2f7;
        padding-top: 6px;
    }
    .oic-total { font-weight: 800; color: #0f172a; font-size: 12px; }

    /* ORDER DETAIL PANEL */
    .detail-section { margin-bottom: 14px; }
    .detail-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
    .detail-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
    .detail-val { font-size: 12px; font-weight: 600; color: #0f172a; margin-top: 2px; }

    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .items-table td { padding: 6px 0; font-size: 12px; border-bottom: 1px dashed #f1f5f9; }
    .items-table .t-right { text-align: right; font-weight: 700; color: #0f172a; }

    /* STATUS FLOW BUTTONS */
    .status-stepper {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        padding: 6px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        margin-bottom: 14px;
        overflow-x: auto;
    }
    .step-btn {
        flex: 1;
        padding: 6px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        background: #ffffff;
        color: #64748b;
        white-space: nowrap;
        text-align: center;
        transition: 0.15s;
    }
    .step-btn.current {
        background: #4f46e5;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
    }

    /* RIDER ASSIGNMENT BOX */
    .dispatch-box {
        background: #fffbeb;
        border: 1px solid #fef3c7;
        border-radius: 12px;
        padding: 12px;
        margin-top: auto;
    }
    .dispatch-box .notice {
        font-size: 11px;
        font-weight: 700;
        color: #b45309;
        margin-bottom: 8px;
        display: block;
    }

    /* RIDERS LIST */
    .rider-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .rider-info { display: flex; align-items: center; gap: 10px; }
    .rider-avatar { width: 34px; height: 34px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .rider-name { font-size: 13px; font-weight: 700; color: #0f172a; }
    .rider-phone { font-size: 11px; color: #64748b; }

    /* MENU ITEMS LIST */
    .menu-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 0;
        border-bottom: 1px solid #f8fafc;
    }
    .menu-item-left { display: flex; align-items: center; gap: 10px; }
    .menu-item-img { width: 34px; height: 34px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 16px; object-fit: cover; }
    .menu-item-name { font-size: 12px; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .menu-item-price { font-size: 11px; color: #64748b; }

    /* CUSTOMER NOTIFICATIONS ALERT FOOTER */
    .notif-banner {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 16px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }
    .notif-left { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 280px; }
    .notif-icon { font-size: 24px; background: #fef3c7; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    .notif-text h4 { font-size: 13px; font-weight: 800; color: #92400e; margin-bottom: 2px; }
    .notif-text p  { font-size: 12px; color: #b45309; line-height: 1.4; }

    @media (max-width: 1380px) {
        .owner-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 800px) {
        .owner-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- MAIN 4-COLUMN DASHBOARD GRID -->
<div class="owner-grid">

    <!-- COLUMN 1: LIVE ORDERS LIST -->
    <div class="panel-box">
        <div class="panel-box-header">
            <div class="panel-box-title">
                <h3>Live Orders <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a; display: inline-block;"></span></h3>
                <p>● Real-time updates</p>
            </div>
            @if($pendingCount > 0)
                <span class="badge-pill" style="background: #ef4444; color: #fff; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 700;">
                    {{ $pendingCount }} New
                </span>
            @endif
        </div>

        <div class="order-card-list">
            @forelse($orders as $o)
            <a href="?order_id={{ $o->id }}" class="order-item-card {{ ($selectedOrder && $selectedOrder->id === $o->id) ? 'active' : '' }}">
                <div class="oic-top">
                    <span class="oic-code">#{{ $o->tracking_code }}</span>
                    <span class="oic-time">{{ $o->created_at->diffForHumans(null, true) }} ago</span>
                </div>
                <div class="oic-cust">👤 {{ $o->customer_name ?: 'Customer' }} ({{ $o->customer_phone }})</div>
                <div class="oic-addr">📍 {{ Str::limit($o->delivery_address ?: 'Standard Delivery', 28) }}</div>
                <div class="oic-bottom">
                    <span class="badge-status {{ $o->status }}">{{ ucfirst(str_replace('_', ' ', $o->status)) }}</span>
                    <span>{{ $o->items->count() }} Items</span>
                    <span class="oic-total">PKR {{ number_format($o->total, 0) }}</span>
                </div>
            </a>
            @empty
            <div style="padding: 2.5rem 1rem; text-align: center; color: #94a3b8; font-size: 13px;">
                No orders yet today.<br>Incoming bot orders will appear here in real-time.
            </div>
            @endforelse
        </div>

        <div style="margin-top: 14px; text-align: center;">
            <a href="/dashboard/{{ $restaurant->id }}/orders" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                View All Orders
            </a>
        </div>
    </div>

    <!-- COLUMN 2: ORDER DETAIL & DISPATCH FLOW -->
    <div class="panel-box">
        @if($selectedOrder)
            <div class="panel-box-header">
                <div class="panel-box-title">
                    <h3>Order #{{ $selectedOrder->tracking_code }}</h3>
                    <p>Placed {{ $selectedOrder->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <span class="badge-status {{ $selectedOrder->status }}">{{ ucfirst(str_replace('_', ' ', $selectedOrder->status)) }}</span>
            </div>

            <!-- Customer & Delivery Info -->
            <div class="detail-grid-2">
                <div>
                    <div class="detail-label">Customer</div>
                    <div class="detail-val">{{ $selectedOrder->customer_name ?: 'Guest Customer' }}</div>
                    <div style="font-size: 11px; color: #64748b;">{{ $selectedOrder->customer_phone }}</div>
                </div>
                <div>
                    <div class="detail-label">Delivery Address</div>
                    <div class="detail-val">{{ $selectedOrder->delivery_address ?: 'Self Pickup / Counter' }}</div>
                </div>
            </div>

            <!-- Items List (deduplicated display) -->
            <div class="detail-section">
                <div class="detail-label" style="margin-bottom: 6px;">Ordered Items</div>
                <table class="items-table">
                    @php
                        $groupedItems = $selectedOrder->items->groupBy(function($item) {
                            return strtolower(trim($item->name)) . '___' . strtolower(trim($item->size ?? ''));
                        })->map(function($group) {
                            $first = $group->first();
                            $qty = $group->sum('quantity') ?: 1;
                            $unit = $first->unit_price ?: ($first->subtotal / ($first->quantity ?: 1));
                            $subtotal = $group->sum('subtotal') ?: ($unit * $qty);
                            return (object)[
                                'name' => $first->name,
                                'size' => $first->size,
                                'quantity' => $qty,
                                'unit_price' => $unit,
                                'subtotal' => $subtotal,
                            ];
                        });
                    @endphp
                    @forelse($groupedItems as $item)
                    <tr>
                        <td><strong>{{ $item->quantity }}x</strong> {{ $item->name }} {{ $item->size ? "({$item->size})" : '' }}</td>
                        <td class="t-right">PKR {{ number_format($item->subtotal, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="color: #94a3b8; font-size: 11px;">Standard Food Order Package</td></tr>
                    @endforelse
                    @if($selectedOrder->delivery_charge > 0)
                    <tr>
                        <td style="color: #64748b;">Delivery Fee</td>
                        <td class="t-right" style="color: #64748b;">PKR {{ number_format($selectedOrder->delivery_charge, 0) }}</td>
                    </tr>
                    @endif
                    <tr style="border-top: 1.5px solid #e2e8f0;">
                        <td><strong style="font-size: 13px;">Total Bill</strong></td>
                        <td class="t-right" style="font-size: 14px; color: #4f46e5;">PKR {{ number_format($selectedOrder->total, 0) }}</td>
                    </tr>
                </table>
            </div>

            <!-- Stage Progression Buttons -->
            <div class="detail-section">
                <div class="detail-label" style="margin-bottom: 6px;">Update Order Status</div>
                <form id="statusFlowForm" method="POST" action="{{ route('dashboard.update-status', [$restaurant->id, $selectedOrder->id]) }}">
                    @csrf
                    <input type="hidden" name="status" id="selectedStatusInput" value="{{ $selectedOrder->status }}">
                    <input type="hidden" name="rider_name" id="formRiderName" value="{{ $selectedOrder->rider_name }}">
                    <input type="hidden" name="rider_phone" id="formRiderPhone" value="{{ $selectedOrder->rider_phone }}">
                    <input type="hidden" name="estimated_minutes" id="formRiderEta" value="{{ $selectedOrder->estimated_minutes ?: 30 }}">

                    <div class="status-stepper">
                        <button type="button" onclick="setOrderStatus('pending')" class="step-btn {{ $selectedOrder->status==='pending' ? 'current' : '' }}">Pending</button>
                        <span>→</span>
                        <button type="button" onclick="setOrderStatus('confirmed')" class="step-btn {{ $selectedOrder->status==='confirmed' ? 'current' : '' }}">Confirmed</button>
                        <span>→</span>
                        <button type="button" onclick="setOrderStatus('preparing')" class="step-btn {{ $selectedOrder->status==='preparing' ? 'current' : '' }}">Preparing</button>
                        <span>→</span>
                        <button type="button" onclick="setOrderStatus('out_for_delivery')" class="step-btn {{ $selectedOrder->status==='out_for_delivery' ? 'current' : '' }}">Dispatched</button>
                        <span>→</span>
                        <button type="button" onclick="setOrderStatus('delivered')" class="step-btn {{ $selectedOrder->status==='delivered' ? 'current' : '' }}">Delivered</button>
                    </div>

                    <!-- Dynamic Action Box Based on Current Status -->
                    @if($selectedOrder->status === 'delivered')
                        <!-- DELIVERED STATE -->
                        <div class="dispatch-box" style="background: #ecfdf5; border-color: #a7f3d0; text-align: center; padding: 16px;">
                            <div style="font-size: 14px; font-weight: 800; color: #047857; margin-bottom: 3px;">
                                🎉 Order Delivered & Completed
                            </div>
                            <p style="font-size: 12px; color: #065f46; margin: 0;">
                                Customer notified via WhatsApp. Total collected: PKR {{ number_format($selectedOrder->total, 0) }} ({{ ucwords(str_replace('_', ' ', $selectedOrder->payment_method ?: 'COD')) }})
                            </p>
                        </div>
                    @elseif($selectedOrder->status === 'out_for_delivery')
                        <!-- DISPATCHED STATE -->
                        <div class="dispatch-box" style="background: #f0fdf4; border-color: #bbf7d0;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                                <span style="font-size: 12px; font-weight: 700; color: #166534;">
                                    🛵 Assigned Rider: <strong>{{ $selectedOrder->rider_name ?: 'Rider' }}</strong> ({{ $selectedOrder->rider_phone ?: '—' }})
                                </span>
                                <span style="font-size: 11px; color: #15803d; font-weight: 600;">
                                    ⏱️ ETA: {{ $selectedOrder->estimated_minutes ? $selectedOrder->estimated_minutes . ' mins' : '20-30 mins' }}
                                </span>
                            </div>
                            <button type="button" onclick="setOrderStatus('delivered')" class="btn btn-success" style="width: 100%; justify-content: center; font-weight: 800; padding: 10px; font-size: 13px;">
                                ✓ Mark as Delivered & Complete Order
                            </button>
                        </div>
                    @elseif($selectedOrder->status === 'cancelled')
                        <!-- CANCELLED STATE -->
                        <div class="dispatch-box" style="background: #fef2f2; border-color: #fecaca; text-align: center; padding: 14px;">
                            <div style="font-size: 13px; font-weight: 800; color: #dc2626;">
                                ❌ Order Cancelled
                            </div>
                        </div>
                    @else
                        <!-- PENDING / CONFIRMED / PREPARING STATE -->
                        <div class="dispatch-box">
                            <span class="notice">🛵 Assign a rider before marking as Dispatched</span>
                            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 8px; margin-bottom: 8px;">
                                <select id="riderSelectBox" onchange="syncRiderDetails(this)" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; background: #fff;">
                                    <option value="">Select Rider ▾</option>
                                    @foreach($riders as $rdr)
                                        <option value="{{ $rdr->id }}" data-name="{{ $rdr->name }}" data-phone="{{ $rdr->phone }}" {{ $selectedOrder->rider_name === $rdr->name ? 'selected' : '' }}>
                                            {{ $rdr->name }} ({{ $rdr->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="text" id="etaInput" placeholder="30-40 mins" value="{{ $selectedOrder->estimated_minutes ? $selectedOrder->estimated_minutes . ' mins' : '30-40 mins' }}" onchange="document.getElementById('formRiderEta').value=parseInt(this.value)||30" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; background: #fff;">
                            </div>

                            <button type="button" onclick="dispatchOrderNow()" class="btn btn-success" style="width: 100%; justify-content: center; font-weight: 700; padding: 9px;">
                                🛵 Mark as Dispatched & Send WhatsApp
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        @else
            <div style="padding: 4rem 1rem; text-align: center; color: #94a3b8;">
                <div style="font-size: 36px; margin-bottom: 8px;">📋</div>
                <p>Select an order from the left list to view details and update its kitchen stage.</p>
            </div>
        @endif
    </div>

    <!-- COLUMN 3: RIDERS -->
    <div class="panel-box">
        <div class="panel-box-header">
            <div class="panel-box-title">
                <h3>Riders</h3>
                <p>Active delivery team</p>
            </div>
            <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;">
                + Add Rider
            </a>
        </div>

        <div style="display: flex; flex-direction: column; flex: 1;">
            @forelse($riders->take(5) as $rider)
            <div class="rider-row">
                <div class="rider-info">
                    <div class="rider-avatar">🛵</div>
                    <div>
                        <div class="rider-name">{{ $rider->name }}</div>
                        <div class="rider-phone">{{ $rider->phone }}</div>
                    </div>
                </div>
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('dashboard.riders', $restaurant->id) }}" style="text-decoration: none; font-size: 13px;" title="Edit">✏️</a>
                    <form method="POST" action="{{ route('dashboard.delete-rider', [$restaurant->id, $rider->id]) }}" style="display:inline;" onsubmit="return confirm('Delete rider?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 13px;" title="Delete">🗑️</button>
                    </form>
                </div>
            </div>
            @empty
            <div style="padding: 2rem 0; text-align: center; color: #94a3b8; font-size: 12px;">
                No riders added yet.<br>Click <strong>+ Add Rider</strong> to add delivery staff.
            </div>
            @endforelse
        </div>

        <div style="margin-top: 14px; text-align: center;">
            <a href="{{ route('dashboard.riders', $restaurant->id) }}" style="font-size: 12px; font-weight: 700; color: #4f46e5; text-decoration: none;">
                View All Riders →
            </a>
        </div>
    </div>

    <!-- COLUMN 4: MENU MANAGEMENT (INSTANT AVAILABILITY TOGGLE) -->
    <div class="panel-box">
        <div class="panel-box-header">
            <div class="panel-box-title">
                <h3>Menu Management</h3>
                <p>Instant Stock Availability</p>
            </div>
            <a href="{{ route('dashboard.menu', $restaurant->id) }}" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;">
                + Add Item
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
            @forelse($menuItems->take(6) as $item)
            <div class="menu-item-row">
                <div class="menu-item-left">
                    <div class="menu-item-img">🍕</div>
                    <div>
                        <div class="menu-item-name">{{ $item->name }}</div>
                        <div class="menu-item-price">PKR {{ number_format($item->price, 0) }}</div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 10px; font-weight: 700; color: {{ $item->is_available ? '#16a34a' : '#94a3b8' }};">
                        {{ $item->is_available ? 'In Stock' : 'Out' }}
                    </span>
                    <form method="POST" action="{{ route('dashboard.toggle-item', [$restaurant->id, $item->id]) }}" style="display: inline;">
                        @csrf
                        <label class="switch" title="Toggle Stock">
                            <input type="checkbox" onchange="this.form.submit()" {{ $item->is_available ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </form>
                </div>
            </div>
            @empty
            <div style="padding: 2rem 0; text-align: center; color: #94a3b8; font-size: 12px;">
                No menu items found.<br><a href="{{ route('dashboard.menu', $restaurant->id) }}" style="color: #4f46e5;">Upload CSV or Add Items</a>
            </div>
            @endforelse
        </div>

        <div style="margin-top: 14px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #16a34a; font-weight: 600; text-align: center;">
            ⚡ Availability is updated in real-time for your WhatsApp bot
        </div>
    </div>
</div>

<!-- BOTTOM ALERT BANNER: CUSTOMER NOTIFICATIONS -->
<div class="notif-banner">
    <div class="notif-left">
        <div class="notif-icon">🔔</div>
        <div class="notif-text">
            <h4>Customer Notifications</h4>
            <p>
                While order status is <strong>"Confirmed"</strong> or <strong>"Preparing"</strong>, the WhatsApp bot will only tell customer <strong>"Preparing in Kitchen"</strong>.<br>
                When you mark as <strong>"Dispatched"</strong>, customer automatically receives WhatsApp message with rider details, order ID, and ETA.
            </p>
        </div>
    </div>

    <div>
        <button onclick="alert('WhatsApp Bot notification system is active! Changing status triggers automated customer message.')" class="btn" style="background: #ffffff; border: 1px solid #fcd34d; color: #92400e; font-weight: 700; padding: 10px 16px;">
            💬 Test Message
        </button>
    </div>
</div>

<script>
    function setOrderStatus(status) {
        document.getElementById('selectedStatusInput').value = status;
        document.getElementById('statusFlowForm').submit();
    }

    function syncRiderDetails(selectElem) {
        const opt = selectElem.options[selectElem.selectedIndex];
        if (opt && opt.dataset.name) {
            document.getElementById('formRiderName').value = opt.dataset.name;
            document.getElementById('formRiderPhone').value = opt.dataset.phone;
        }
    }

    function dispatchOrderNow() {
        document.getElementById('selectedStatusInput').value = 'out_for_delivery';
        const selectBox = document.getElementById('riderSelectBox');
        syncRiderDetails(selectBox);
        document.getElementById('statusFlowForm').submit();
    }

    // ── Live Real-Time Feed Polling (every 6 seconds) ──
    let initialLatestId = {{ $orders->first()?->id ?? 0 }};
    let initialTodayCount = {{ $today->count() }};

    function pollLiveFeed() {
        fetch('/dashboard/{{ $restaurant->id }}/orders/live-feed')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.latest_order_id > initialLatestId || data.today_count !== initialTodayCount) {
                        // Gently refresh to show new incoming orders
                        window.location.reload();
                    }
                }
            })
            .catch(() => {});
    }

    setInterval(pollLiveFeed, 6000);
</script>

@endsection