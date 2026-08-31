@extends('layouts.dashboard')

@section('title', 'Live Orders • ' . ($restaurant->name ?? 'Dashboard'))

@section('content')
<style>
    .live-command-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Top Command Header */
    .live-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: #ffffff;
        border-radius: 18px;
        padding: 18px 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
    }
    [data-theme="dark"] .live-top-bar {
        background: #1e293b;
        border-color: #334155;
    }

    .live-pulse-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ecfdf5;
        color: #059669;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 50px;
        border: 1px solid #a7f3d0;
    }
    [data-theme="dark"] .live-pulse-badge {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulseGreen 1.8s infinite;
    }
    @keyframes pulseGreen {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Live Stat Strips */
    .live-stats-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }
    .live-stat-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    [data-theme="dark"] .live-stat-box {
        background: #1e293b;
        border-color: #334155;
    }
    .live-stat-info .stat-num {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    [data-theme="dark"] .live-stat-info .stat-num {
        color: #f8fafc;
    }
    .live-stat-info .stat-title {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-top: 4px;
    }
    .live-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* 3-Column Main Grid */
    .live-main-grid {
        display: grid;
        grid-template-columns: 360px 1fr 300px;
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 1200px) {
        .live-main-grid {
            grid-template-columns: 340px 1fr;
        }
        .live-fleet-column {
            grid-column: span 2;
        }
    }
    @media (max-width: 860px) {
        .live-main-grid {
            grid-template-columns: 1fr;
        }
        .live-fleet-column {
            grid-column: span 1;
        }
    }

    .panel-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    [data-theme="dark"] .panel-card {
        background: #1e293b;
        border-color: #334155;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    [data-theme="dark"] .panel-header {
        border-bottom-color: #334155;
    }
    .panel-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    [data-theme="dark"] .panel-title {
        color: #f8fafc;
    }

    /* Live Orders List */
    .live-orders-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 680px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .live-order-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    [data-theme="dark"] .live-order-item {
        background: #0f172a;
        border-color: #334155;
    }
    .live-order-item:hover {
        border-color: #6366f1;
        background: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.12);
    }
    [data-theme="dark"] .live-order-item:hover {
        background: #1e293b;
        border-color: #818cf8;
    }
    .live-order-item.active {
        border-color: #6366f1;
        background: #eff6ff;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.15);
    }
    [data-theme="dark"] .live-order-item.active {
        background: rgba(99, 102, 241, 0.18);
        border-color: #818cf8;
    }

    .wa-avatar-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #dcfce7;
        color: #15803d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex-shrink: 0;
    }
    .order-meta-info {
        flex: 1;
        min-width: 0;
    }
    .order-meta-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }
    .order-code-text {
        font-size: 13px;
        font-weight: 800;
        color: #0f172a;
    }
    [data-theme="dark"] .order-code-text { color: #f8fafc; }
    .order-time-text {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
    }
    .order-customer-text {
        font-size: 12px;
        color: #475569;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    [data-theme="dark"] .order-customer-text { color: #cbd5e1; }
    .order-item-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .order-price-bold {
        font-size: 12.5px;
        font-weight: 800;
        color: #0f172a;
    }
    [data-theme="dark"] .order-price-bold { color: #f8fafc; }

    /* Status Pills */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
    }
    .status-pill.pending { background: #fef3c7; color: #b45309; }
    .status-pill.confirmed { background: #dbeafe; color: #1e40af; }
    .status-pill.preparing { background: #f3e8ff; color: #7e22ce; }
    .status-pill.out_for_delivery { background: #e0f2fe; color: #0369a1; }
    .status-pill.delivered { background: #dcfce7; color: #15803d; }
    .status-pill.cancelled { background: #fee2e2; color: #b91c1c; }

    /* Order Details Workbench */
    .order-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    [data-theme="dark"] .order-detail-header { border-bottom-color: #334155; }
    .order-detail-title h3 {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    [data-theme="dark"] .order-detail-title h3 { color: #f8fafc; }
    .order-detail-title p {
        font-size: 12px;
        color: #64748b;
        margin: 2px 0 0 0;
    }

    .customer-info-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    [data-theme="dark"] .customer-info-box {
        background: #0f172a;
        border-color: #334155;
    }
    .info-col-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-col-val {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    [data-theme="dark"] .info-col-val { color: #f8fafc; }
    .info-col-sub {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Order Items Table */
    .order-items-list {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    [data-theme="dark"] .order-items-list {
        background: #0f172a;
        border-color: #334155;
    }
    .order-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        font-size: 13px;
    }
    .order-item-qty-badge {
        background: #e2e8f0;
        color: #334155;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 6px;
        font-size: 11px;
        margin-right: 8px;
    }
    [data-theme="dark"] .order-item-qty-badge {
        background: #334155;
        color: #f1f5f9;
    }
    .order-bill-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 8px 0;
    }
    [data-theme="dark"] .order-bill-divider { background: #334155; }
    .order-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 6px;
        font-size: 15px;
        font-weight: 800;
    }

    /* Route Map Graphic */
    .route-map-preview {
        position: relative;
        height: 100px;
        background: linear-gradient(135deg, #eef2ff 0%, #f0fdf4 100%);
        border: 1px solid #e0e7ff;
        border-radius: 14px;
        margin-bottom: 16px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
    }
    [data-theme="dark"] .route-map-preview {
        background: linear-gradient(135deg, #1e1b4b 0%, #064e3b 100%);
        border-color: #312e81;
    }
    .map-distance-badge {
        position: absolute;
        top: 8px;
        left: 12px;
        background: #ffffff;
        color: #4f46e5;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    [data-theme="dark"] .map-distance-badge {
        background: #0f172a;
        color: #a5b4fc;
    }

    /* Assigned Rider Box */
    .assigned-rider-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 18px;
        margin-bottom: 18px;
    }
    [data-theme="dark"] .assigned-rider-box {
        background: #0f172a;
        border-color: #334155;
    }
    .rider-avatar-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .rider-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #e0e7ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .rider-name-status h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .rider-phone-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }
    .btn-call-rider {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #dcfce7;
        color: #15803d;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 16px;
    }

    /* Action Buttons Row */
    .action-btn-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .btn-action-primary {
        flex: 1;
        padding: 12px 18px;
        border-radius: 12px;
        border: none;
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    }
    .btn-action-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
    }
    .btn-action-secondary {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
    }
    [data-theme="dark"] .btn-action-secondary {
        background: #0f172a;
        border-color: #475569;
        color: #cbd5e1;
    }
    .btn-action-secondary:hover {
        background: #f1f5f9;
    }

    /* Fleet Column */
    .rider-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 480px;
        overflow-y: auto;
    }
    .rider-item-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    [data-theme="dark"] .rider-item-card {
        background: #0f172a;
        border-color: #334155;
    }
    .rider-tag {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 5px;
    }
    .rider-tag.delivery { background: #dcfce7; color: #166534; }
    .rider-tag.offline { background: #f1f5f9; color: #64748b; }
</style>

<div class="live-command-container">

    <!-- 1. LIVE COMMAND HEADER -->
    <div class="live-top-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">🛍️ Live Orders Command Center</h2>
                <div class="live-pulse-badge">
                    <div class="pulse-dot"></div>
                    <span>Real-Time Kitchen Feed Active</span>
                </div>
            </div>
            <p style="font-size: 12.5px; color: #64748b; margin: 4px 0 0 0;">
                Incoming WhatsApp orders appear here instantly. Update status, manage kitchen preparation, and dispatch delivery riders without refreshing.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('dashboard.orders', $restaurant->id) }}" class="btn-action-secondary" title="View Executive Dashboard Overview">
                📊 Dashboard Overview
            </a>
            <a href="{{ route('dashboard.history', $restaurant->id) }}" class="btn-action-secondary" title="View Past Completed Orders">
                📋 Orders History →
            </a>
        </div>
    </div>

    <!-- 2. LIVE METRICS STRIP -->
    <div class="live-stats-strip">
        <div class="live-stat-box">
            <div class="live-stat-info">
                <div class="stat-num" id="kpi-live-orders" style="color: #6366f1;">{{ $liveOrdersCount }}</div>
                <div class="stat-title">Active Live Orders</div>
            </div>
            <div class="live-stat-icon" style="background: #e0e7ff; color: #4338ca;">🛍️</div>
        </div>

        <div class="live-stat-box">
            <div class="live-stat-info">
                <div class="stat-num" id="kpi-pending-orders" style="color: #d97706;">{{ $pendingCount }}</div>
                <div class="stat-title">Awaiting Acceptance</div>
            </div>
            <div class="live-stat-icon" style="background: #fef3c7; color: #b45309;">⏳</div>
        </div>

        <div class="live-stat-box">
            <div class="live-stat-info">
                <div class="stat-num" id="kpi-preparing-orders" style="color: #7c3aed;">{{ $preparingCount }}</div>
                <div class="stat-title">Cooking in Kitchen</div>
            </div>
            <div class="live-stat-icon" style="background: #f3e8ff; color: #7e22ce;">🍳</div>
        </div>

        <div class="live-stat-box">
            <div class="live-stat-info">
                <div class="stat-num" id="kpi-dispatched-orders" style="color: #0284c7;">{{ $dispatchedCount }}</div>
                <div class="stat-title">On Road with Rider</div>
            </div>
            <div class="live-stat-icon" style="background: #e0f2fe; color: #0369a1;">🛵</div>
        </div>

        <div class="live-stat-box">
            <div class="live-stat-info">
                <div class="stat-num" id="kpi-revenue" style="color: #059669;">PKR {{ number_format($todayRevenue) }}</div>
                <div class="stat-title">Today's Live Sales</div>
            </div>
            <div class="live-stat-icon" style="background: #dcfce7; color: #15803d;">💰</div>
        </div>
    </div>

    <!-- 3. MAIN WORKBENCH 3-COLUMN GRID -->
    <div class="live-main-grid">

        <!-- Column 1: Live Incoming Orders Stream -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <span>Incoming Orders</span>
                    <span class="status-pill pending" id="live-orders-badge">{{ $liveOrdersCount }}</span>
                </div>
                <span style="font-size: 11px; color: #94a3b8;">5s Auto-Sync</span>
            </div>

            <div class="live-orders-list" id="live-orders-list">
                @forelse($orders as $o)
                    <a href="javascript:void(0)" 
                       onclick="selectOrder({{ $o->id }}); return false;"
                       class="live-order-item {{ ($selectedOrder && $selectedOrder->id === $o->id) ? 'active' : '' }}"
                       data-order-id="{{ $o->id }}">
                        <div class="wa-avatar-box">💬</div>
                        <div class="order-meta-info">
                            <div class="order-meta-top">
                                <span class="order-code-text">#{{ $o->tracking_code }}</span>
                                <span class="order-time-text">{{ $o->created_at->diffForHumans(null, true, true) }}</span>
                            </div>
                            <div class="order-customer-text">
                                👤 {{ $o->customer_name ?: 'Guest' }} ({{ substr($o->customer_phone ?? 'N/A', -6) }})
                            </div>
                            <div class="order-item-footer">
                                <span class="status-pill {{ $o->status }}">{{ $o->status_label }}</span>
                                <span class="order-price-bold">PKR {{ number_format($o->total) }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="text-align: center; padding: 40px 10px; color: #94a3b8;" id="empty-orders-state">
                        <div style="font-size: 36px; margin-bottom: 8px;">🍽️</div>
                        <p style="font-weight: 700; font-size: 14px;">No active live orders right now</p>
                        <p style="font-size: 11.5px; margin-top: 4px;">Orders placed on WhatsApp appear here instantly in real-time.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: Order Detail Workbench & Kitchen Ticket -->
        <div class="panel-card" id="order-detail-panel">
            @if($selectedOrder)
                <div class="order-detail-header">
                    <div class="order-detail-title">
                        <h3 id="detail-tracking-code">Order #{{ $selectedOrder->tracking_code }}</h3>
                        <p id="detail-placed-time">Placed at {{ $selectedOrder->created_at->format('h:i A') }} • {{ $selectedOrder->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="status-pill {{ $selectedOrder->status }}" id="selected-order-status-pill" style="font-size: 11px; padding: 4px 12px;">{{ $selectedOrder->status_label }}</span>
                </div>

                <!-- Customer info & Address -->
                <div class="customer-info-box">
                    <div>
                        <div class="info-col-label">👤 Customer</div>
                        <div class="info-col-val">
                            <span id="detail-customer-name">{{ $selectedOrder->customer_name ?: 'Guest Customer' }}</span>
                            <a id="detail-wa-link" href="https://wa.me/{{ preg_replace('/\D/', '', $selectedOrder->customer_phone) }}" target="_blank" style="color: #16a34a; text-decoration: none;" title="Open WhatsApp Chat">💬</a>
                        </div>
                        <div class="info-col-sub" id="detail-customer-phone">{{ $selectedOrder->formatted_customer_phone }}</div>
                    </div>
                    <div>
                        <div class="info-col-label">📍 Delivery Address</div>
                        <div class="info-col-val" id="detail-delivery-address">{{ $selectedOrder->delivery_address ?: 'Dine-in / Pickup' }}</div>
                        <div class="info-col-sub">{{ $restaurant->city ?: 'Local Delivery' }}</div>
                    </div>
                </div>

                <!-- Route Map Graphic -->
                <div class="route-map-preview">
                    <span class="map-distance-badge">📍 2.3 km away</span>
                    <div style="font-size: 24px;">🏪</div>
                    <svg style="flex:1; height: 40px; margin: 0 16px;" viewBox="0 0 300 40" preserveAspectRatio="none">
                        <path d="M 10 20 Q 150 -10 290 20" stroke="#818cf8" stroke-width="3" stroke-dasharray="6,6" fill="none"/>
                    </svg>
                    <div style="font-size: 24px;">📍</div>
                </div>

                <!-- Order Items List -->
                <div class="order-items-list" id="detail-items-list">
                    @foreach($selectedOrder->items as $item)
                        <div class="order-item-row">
                            <div>
                                <span class="order-item-qty-badge">{{ $item->quantity }}x</span>
                                <span>{{ $item->name ?: $item->item_name }}</span>
                            </div>
                            <span style="font-weight: 700;">PKR {{ number_format($item->subtotal) }}</span>
                        </div>
                    @endforeach
                    <div class="order-item-row" style="color: #64748b;">
                        <span>Delivery Fee</span>
                        <span>PKR {{ number_format($restaurant->delivery_charge ?? 0) }}</span>
                    </div>
                    <div class="order-bill-divider"></div>
                    <div class="order-total-row">
                        <span>Total Bill</span>
                        <span style="color: #4f46e5; font-size: 17px;">PKR {{ number_format($selectedOrder->total) }}</span>
                    </div>
                </div>

                <!-- Assigned Rider Box -->
                <div class="assigned-rider-box">
                    <div class="rider-avatar-info">
                        <div class="rider-avatar">🚴</div>
                        <div class="rider-name-status">
                            <h4>
                                <span>{{ $selectedOrder->rider_name ?: 'No Rider Assigned' }}</span>
                                @if($selectedOrder->rider_name)
                                    <span class="status-pill delivered" style="font-size: 9.5px; padding: 2px 6px;">Assigned</span>
                                @endif
                            </h4>
                            <div class="rider-phone-sub">{{ $selectedOrder->rider_phone ?: 'Assign rider before dispatch' }}</div>
                        </div>
                    </div>
                    @if($selectedOrder->rider_phone)
                        <a href="tel:{{ $selectedOrder->rider_phone }}" class="btn-call-rider" title="Call Rider">📞</a>
                    @endif
                </div>

                <!-- Action Buttons: Real-Time Status Transitions -->
                <div class="action-btn-row" id="action-btn-row">
                    @if($selectedOrder->status === 'pending')
                        <button type="button" class="btn-action-primary" style="background: #2563eb;"
                            onclick="ajaxUpdateStatus('{{ route('dashboard.update-status', [$restaurant->id, $selectedOrder->id]) }}', 'confirmed', this)">
                            ✓ Mark as Confirmed
                        </button>
                    @elseif($selectedOrder->status === 'confirmed')
                        <button type="button" class="btn-action-primary" style="background: #7c3aed;"
                            onclick="ajaxUpdateStatus('{{ route('dashboard.update-status', [$restaurant->id, $selectedOrder->id]) }}', 'preparing', this)">
                            🍳 Mark as Preparing
                        </button>
                    @elseif($selectedOrder->status === 'preparing')
                        <button type="button" class="btn-action-primary" style="background: #0284c7;"
                            onclick="openDispatchModal('{{ $selectedOrder->id }}', '{{ $selectedOrder->tracking_code }}', '{{ addslashes($selectedOrder->customer_name) }}', '{{ addslashes($selectedOrder->delivery_address ?: '') }}')">
                            🚴 Dispatch to Rider
                        </button>
                    @elseif($selectedOrder->status === 'out_for_delivery')
                        <button type="button" class="btn-action-primary" style="background: #16a34a;"
                            onclick="ajaxUpdateStatus('{{ route('dashboard.update-status', [$restaurant->id, $selectedOrder->id]) }}', 'delivered', this)">
                            ✅ Mark as Delivered
                        </button>
                    @else
                        <div style="flex: 1; text-align: center; font-weight: 700; color: #64748b; padding: 10px; background: #f8fafc; border-radius: 10px;">
                            Order is {{ ucfirst($selectedOrder->status) }}
                        </div>
                    @endif

                    <a href="{{ route('order.track.live', $selectedOrder->tracking_code) }}" target="_blank" class="btn-action-secondary" title="View Customer Live Tracking Page">
                        🌐 Live Track
                    </a>

                    <a href="{{ route('dashboard.print-bill', [$restaurant->id, $selectedOrder->id]) }}" target="_blank" class="btn-action-secondary" title="Print Parcel Bill / Receipt">
                        🖨️ Print Bill
                    </a>
                </div>
            @else
                <div style="text-align: center; padding: 80px 20px; color: #94a3b8;">
                    <div style="font-size: 40px; margin-bottom: 12px;">🛍️</div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #334155;">Select an order</h3>
                    <p style="font-size: 12px; margin-top: 4px;">Click any order on the left to view its items, delivery route and customer chat.</p>
                </div>
            @endif
        </div>

        <!-- Column 3: Active Fleet & Riders -->
        <div class="panel-card live-fleet-column">
            <div class="panel-header">
                <div class="panel-title">
                    <span>Delivery Fleet</span>
                    <span class="status-pill delivered">{{ $activeRidersCount }}</span>
                </div>
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" style="font-size: 11px; font-weight: 700; color: #6366f1; text-decoration: none;">Manage</a>
            </div>

            <div class="rider-list">
                @forelse($riders as $rider)
                    <div class="rider-item-card">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="font-size: 20px;">🚴</div>
                            <div>
                                <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;">{{ $rider->name }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $rider->phone }}</div>
                            </div>
                        </div>
                        <span class="rider-tag {{ $rider->is_active ? 'delivery' : 'offline' }}">
                            {{ $rider->is_active ? 'Available' : 'Offline' }}
                        </span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 24px 10px; color: #94a3b8;">
                        <p style="font-size: 12px;">No delivery riders registered yet.</p>
                    </div>
                @endforelse
            </div>

            <div style="margin-top: 16px; display: flex; gap: 8px;">
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="btn-action-secondary" style="flex:1; font-size: 12px;">
                    ➕ Add Rider
                </a>
                <a href="{{ route('dashboard.customers', $restaurant->id) }}" class="btn-action-secondary" style="flex:1; font-size: 12px; color: #16a34a; border-color: #bbf7d0;">
                    💬 Broadcast
                </a>
            </div>
        </div>

    </div>
</div>

<!-- DISPATCH MODAL -->
<div id="dispatchModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 440px; max-width: calc(100% - 32px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #0284c7, #0369a1); padding: 18px 22px; color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 22px;">🛵</span>
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Dispatch to Rider</h3>
                    <p style="font-size: 11.5px; color: #e0f2fe; margin: 2px 0 0 0;">Order <strong id="dispatchOrderCode"></strong> • <span id="dispatchCustomerName"></span></p>
                </div>
            </div>
            <button type="button" onclick="closeDispatchModal()" style="background: none; border: none; color: #ffffff; font-size: 20px; cursor: pointer;">✕</button>
        </div>

        <form id="dispatchForm" method="POST" action="" onsubmit="return ajaxSubmitDispatch(event);" style="padding: 20px 22px;">
            @csrf
            <input type="hidden" name="status" value="out_for_delivery">

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Select Delivery Rider *</label>
                @if($riders->isNotEmpty())
                    <select id="riderSelect" class="form-control" style="padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1; width: 100%; font-size: 13px; margin-bottom: 10px;" onchange="handleRiderSelect(this)">
                        <option value="">-- Choose from Registered Fleet --</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->name }}" data-phone="{{ $rider->phone }}">{{ $rider->name }} ({{ $rider->phone }})</option>
                        @endforeach
                        <option value="__custom__">➕ Enter Other / Third-Party Rider</option>
                    </select>
                @endif

                <div id="customRiderFields" style="{{ $riders->isNotEmpty() ? 'display: none;' : '' }}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #64748b; margin-bottom: 4px;">Rider Name *</label>
                            <input type="text" id="inputRiderName" name="rider_name" placeholder="e.g. Ali Khan" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #64748b; margin-bottom: 4px;">Rider Phone</label>
                            <input type="text" id="inputRiderPhone" name="rider_phone" placeholder="e.g. 03001234567" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">Estimated Mins</label>
                    <input type="number" name="estimated_minutes" value="25" min="5" max="180" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">Rider Notes (Optional)</label>
                    <input type="text" name="rider_notes" placeholder="e.g. Call customer" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;">
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #64748b;">
                <span style="font-weight: 700; color: #0f172a;">📍 Deliver to:</span>
                <span id="dispatchAddress"></span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeDispatchModal()" style="padding: 9px 16px; border-radius: 9px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 12.5px; font-weight: 700; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 9px 20px; border-radius: 9px; border: none; background: #0284c7; color: #ffffff; font-size: 12.5px; font-weight: 700; cursor: pointer;">Confirm & Dispatch 🛵</button>
            </div>
        </form>
    </div>
</div>

<script>
    const RESTAURANT_ID     = '{{ $restaurant->id }}';
    let SELECTED_ORDER_ID   = {{ $selectedOrder?->id ?? 'null' }};
    const LIVE_FEED_URL     = '/dashboard/' + RESTAURANT_ID + '/orders/live-feed';
    const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let currentOrdersMap = {};

    @if($selectedOrder)
    currentOrdersMap[{{ $selectedOrder->id }}] = {
        id: {{ $selectedOrder->id }},
        tracking_code: '{{ $selectedOrder->tracking_code }}',
        status: '{{ $selectedOrder->status }}',
        status_label: '{{ $selectedOrder->status_label }}',
        total: {{ (float) $selectedOrder->total }},
        customer_name: '{{ addslashes($selectedOrder->customer_name ?: 'Guest Customer') }}',
        customer_phone: '{{ substr($selectedOrder->customer_phone ?? 'N/A', -6) }}',
        full_customer_phone: '{{ $selectedOrder->customer_phone ?: '' }}',
        created_at_humans: '{{ $selectedOrder->created_at->diffForHumans(null, true, true) }}',
        created_at_time: '{{ $selectedOrder->created_at->format('h:i A') }}',
        created_at_ago: '{{ $selectedOrder->created_at->diffForHumans() }}',
        rider_name: '{{ addslashes($selectedOrder->rider_name ?? '') }}',
        rider_phone: '{{ addslashes($selectedOrder->rider_phone ?? '') }}',
        delivery_address: '{{ addslashes($selectedOrder->delivery_address ?: '') }}',
        estimated_minutes: {{ $selectedOrder->estimated_minutes ?? 25 }},
        payment_method: '{{ $selectedOrder->payment_method ?: 'cash_on_delivery' }}',
        delivery_fee: {{ (float) ($restaurant->delivery_charge ?? 0) }},
        items: [
            @foreach($selectedOrder->items as $it)
            {
                name: '{{ addslashes($it->name ?: $it->item_name) }}',
                quantity: {{ (int) $it->quantity }},
                subtotal: {{ (float) $it->subtotal }}
            },
            @endforeach
        ]
    };
    @endif

    const STATUS_FLOW = {
        pending: {
            label: 'Pending',
            next: 'confirmed',
            btnText: '✓ Mark as Confirmed',
            btnColor: '#2563eb',
            btnAction: (url, order) => `ajaxUpdateStatus('${url}', 'confirmed', this)`
        },
        confirmed: {
            label: 'Confirmed',
            next: 'preparing',
            btnText: '🍳 Mark as Preparing',
            btnColor: '#7c3aed',
            btnAction: (url, order) => `ajaxUpdateStatus('${url}', 'preparing', this)`
        },
        preparing: {
            label: 'Preparing',
            next: 'out_for_delivery',
            btnText: '🚴 Dispatch to Rider',
            btnColor: '#0284c7',
            btnAction: (url, order) => `openDispatchModal('${order.id}', '${order.tracking_code}', '${escJs(order.customer_name)}', '${escJs(order.delivery_address)}')`
        },
        out_for_delivery: {
            label: 'Out for Delivery',
            next: 'delivered',
            btnText: '✅ Mark as Delivered',
            btnColor: '#16a34a',
            btnAction: (url, order) => `ajaxUpdateStatus('${url}', 'delivered', this)`
        },
        delivered: {
            label: 'Delivered',
            next: null,
            btnText: null,
            btnColor: '#16a34a',
            btnAction: null
        },
        cancelled: {
            label: 'Cancelled',
            next: null,
            btnText: null,
            btnColor: '#ef4444',
            btnAction: null
        }
    };

    function escJs(s) {
        return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openDispatchModal(orderId, orderCode, customerName, address) {
        document.getElementById('dispatchOrderCode').textContent = '#' + orderCode;
        document.getElementById('dispatchCustomerName').textContent = customerName;
        document.getElementById('dispatchAddress').textContent = address || 'Address provided in WhatsApp chat';
        document.getElementById('dispatchForm').action = '/dashboard/' + RESTAURANT_ID + '/orders/' + orderId + '/status';
        
        const riderSelect = document.getElementById('riderSelect');
        const customFields = document.getElementById('customRiderFields');
        const nameInput = document.getElementById('inputRiderName');
        const phoneInput = document.getElementById('inputRiderPhone');

        if (riderSelect && riderSelect.options.length > 2) {
            riderSelect.selectedIndex = 1;
            const opt = riderSelect.options[1];
            nameInput.value = opt.value;
            phoneInput.value = opt.getAttribute('data-phone') || '';
            customFields.style.display = 'none';
        } else {
            if (customFields) customFields.style.display = 'block';
        }

        document.getElementById('dispatchModal').style.display = 'flex';
    }

    function handleRiderSelect(select) {
        const customFields = document.getElementById('customRiderFields');
        const nameInput = document.getElementById('inputRiderName');
        const phoneInput = document.getElementById('inputRiderPhone');

        if (select.value === '__custom__' || !select.value) {
            customFields.style.display = 'block';
            nameInput.value = '';
            phoneInput.value = '';
            nameInput.focus();
        } else {
            customFields.style.display = 'none';
            nameInput.value = select.value;
            const opt = select.options[select.selectedIndex];
            phoneInput.value = opt.getAttribute('data-phone') || '';
        }
    }

    function closeDispatchModal() {
        document.getElementById('dispatchModal').style.display = 'none';
    }

    async function ajaxSubmitDispatch(event) {
        if (event) event.preventDefault();
        const form = document.getElementById('dispatchForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.style.opacity = '0.6'; }

        const formData = new FormData(form);
        const dataObj = {};
        formData.forEach((value, key) => { dataObj[key] = value; });

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify(dataObj),
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Dispatch failed');

            closeDispatchModal();
            showToast('🛵 ' + (data.message || 'Order dispatched to rider!'), 'success');

            if (currentOrdersMap[SELECTED_ORDER_ID]) {
                currentOrdersMap[SELECTED_ORDER_ID].status = 'out_for_delivery';
                currentOrdersMap[SELECTED_ORDER_ID].status_label = data.status_label || '🛵 Out for Delivery';
                currentOrdersMap[SELECTED_ORDER_ID].rider_name = dataObj.rider_name || '';
                currentOrdersMap[SELECTED_ORDER_ID].rider_phone = dataObj.rider_phone || '';
                renderOrderDetail(currentOrdersMap[SELECTED_ORDER_ID]);
            }

            const listItem = document.querySelector(`[data-order-id="${SELECTED_ORDER_ID}"] .status-pill`);
            if (listItem) {
                listItem.textContent = data.status_label || 'Out for Delivery';
                listItem.className   = 'status-pill out_for_delivery';
            }

        } catch (err) {
            showToast('❌ Error: ' + err.message, 'error');
        } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = '1'; }
        }
        return false;
    }

    async function ajaxUpdateStatus(url, status, btn) {
        if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ status }),
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed');

            showToast('✅ ' + (data.message || 'Status updated!'), 'success');

            if (currentOrdersMap[SELECTED_ORDER_ID]) {
                currentOrdersMap[SELECTED_ORDER_ID].status = data.status;
                currentOrdersMap[SELECTED_ORDER_ID].status_label = data.status_label;
                renderOrderDetail(currentOrdersMap[SELECTED_ORDER_ID]);
            }

            const listItem = document.querySelector(`[data-order-id="${SELECTED_ORDER_ID}"] .status-pill`);
            if (listItem) {
                listItem.textContent = data.status_label;
                listItem.className   = 'status-pill ' + data.status;
            }

        } catch (e) {
            showToast('❌ Error: ' + e.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
        }
    }

    function selectOrder(orderId) {
        SELECTED_ORDER_ID = orderId;

        document.querySelectorAll('.live-order-item').forEach(el => {
            if (parseInt(el.dataset.orderId) === orderId) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        if (window.history && window.history.pushState) {
            const newUrl = `/dashboard/${RESTAURANT_ID}/live-orders?order_id=${orderId}`;
            window.history.pushState({ orderId }, '', newUrl);
        }

        const order = currentOrdersMap[orderId];
        if (order) {
            renderOrderDetail(order);
        }
    }

    function renderOrderDetail(o) {
        const panel = document.getElementById('order-detail-panel');
        if (!panel || !o) return;

        const updateUrl = `/dashboard/${RESTAURANT_ID}/orders/${o.id}/status`;
        const flow = STATUS_FLOW[o.status] || { label: o.status_label, next: null, btnText: null };

        let actionBtnHtml = '';
        if (flow.next && flow.btnText) {
            const actionCall = flow.btnAction(updateUrl, o);
            actionBtnHtml = `
                <button type="button" class="btn-action-primary" style="background: ${flow.btnColor};"
                    onclick="${actionCall}">
                    ${escHtml(flow.btnText)}
                </button>
            `;
        } else {
            actionBtnHtml = `
                <div style="flex: 1; text-align: center; font-weight: 700; color: #64748b; padding: 10px; background: #f8fafc; border-radius: 10px;">
                    Order is ${escHtml(o.status_label || o.status)}
                </div>
            `;
        }

        let riderHtml = '';
        if (o.rider_name || o.rider_phone) {
            const phoneLink = o.rider_phone ? `<a href="tel:${escHtml(o.rider_phone)}" class="btn-call-rider" title="Call Rider">📞</a>` : '';
            riderHtml = `
                <div class="assigned-rider-box">
                    <div class="rider-avatar-info">
                        <div class="rider-avatar">🚴</div>
                        <div class="rider-name-status">
                            <h4>
                                <span>${escHtml(o.rider_name || 'Assigned Rider')}</span>
                                <span class="status-pill delivered" style="font-size: 9.5px; padding: 2px 6px;">Assigned</span>
                            </h4>
                            <div class="rider-phone-sub">${escHtml(o.rider_phone || '')}</div>
                        </div>
                    </div>
                    ${phoneLink}
                </div>
            `;
        } else {
            riderHtml = `
                <div class="assigned-rider-box">
                    <div class="rider-avatar-info">
                        <div class="rider-avatar">🚴</div>
                        <div class="rider-name-status">
                            <h4><span>No Rider Assigned</span></h4>
                            <div class="rider-phone-sub">Assign rider before dispatch</div>
                        </div>
                    </div>
                </div>
            `;
        }

        const itemsHtml = (o.items || []).map(it => `
            <div class="order-item-row">
                <div>
                    <span class="order-item-qty-badge">${it.quantity}x</span>
                    <span>${escHtml(it.name)}</span>
                </div>
                <span style="font-weight: 700;">PKR ${Number(it.subtotal).toLocaleString()}</span>
            </div>
        `).join('');

        const cleanPhoneDigits = (o.full_customer_phone || '').replace(/\D/g, '');

        panel.innerHTML = `
            <div class="order-detail-header">
                <div class="order-detail-title">
                    <h3 id="detail-tracking-code">Order #${escHtml(o.tracking_code)}</h3>
                    <p id="detail-placed-time">Placed at ${escHtml(o.created_at_time || '')} • ${escHtml(o.created_at_ago || '')}</p>
                </div>
                <span class="status-pill ${escHtml(o.status)}" id="selected-order-status-pill" style="font-size: 11px; padding: 4px 12px;">
                    ${escHtml(o.status_label)}
                </span>
            </div>

            <!-- Customer info & Address -->
            <div class="customer-info-box">
                <div>
                    <div class="info-col-label">👤 Customer</div>
                    <div class="info-col-val">
                        <span id="detail-customer-name">${escHtml(o.customer_name || 'Guest Customer')}</span>
                        <a id="detail-wa-link" href="https://wa.me/${cleanPhoneDigits}" target="_blank" style="color: #16a34a; text-decoration: none;" title="Open WhatsApp Chat">💬</a>
                    </div>
                    <div class="info-col-sub" id="detail-customer-phone">${escHtml(o.full_customer_phone || o.customer_phone || '')}</div>
                </div>
                <div>
                    <div class="info-col-label">📍 Delivery Address</div>
                    <div class="info-col-val" id="detail-delivery-address">${escHtml(o.delivery_address || 'Dine-in / Pickup')}</div>
                    <div class="info-col-sub">Local Delivery</div>
                </div>
            </div>

            <!-- Route Map Graphic -->
            <div class="route-map-preview">
                <span class="map-distance-badge">📍 2.3 km away</span>
                <div style="font-size: 24px;">🏪</div>
                <svg style="flex:1; height: 40px; margin: 0 16px;" viewBox="0 0 300 40" preserveAspectRatio="none">
                    <path d="M 10 20 Q 150 -10 290 20" stroke="#818cf8" stroke-width="3" stroke-dasharray="6,6" fill="none"/>
                </svg>
                <div style="font-size: 24px;">📍</div>
            </div>

            <!-- Order Items -->
            <div class="order-items-list" id="detail-items-list">
                ${itemsHtml}
                <div class="order-item-row" style="color: #64748b;">
                    <span>Delivery Fee</span>
                    <span>PKR ${Number(o.delivery_fee || 0).toLocaleString()}</span>
                </div>
                <div class="order-bill-divider"></div>
                <div class="order-total-row">
                    <span>Total Bill</span>
                    <span style="color: #4f46e5; font-size: 17px;">PKR ${Number(o.total).toLocaleString()}</span>
                </div>
            </div>

            <!-- Assigned Rider -->
            ${riderHtml}

            <!-- Action Buttons -->
            <div class="action-btn-row" id="action-btn-row">
                ${actionBtnHtml}
                <a href="/track/${escHtml(o.tracking_code)}" target="_blank" class="btn-action-secondary" title="View Customer Live Tracking Page">
                    🌐 Live Track
                </a>
                <a href="/dashboard/${RESTAURANT_ID}/orders/${o.id}/print-bill" target="_blank" class="btn-action-secondary" title="Print Parcel Bill / Receipt">
                    🖨️ Print Bill
                </a>
            </div>
        `;
    }

    function showToast(msg, type = 'success') {
        let t = document.getElementById('live-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'live-toast';
            t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.15);transition:opacity 0.3s;max-width:320px;';
            document.body.appendChild(t);
        }
        t.style.background = type === 'success' ? '#0f172a' : '#dc2626';
        t.style.color       = '#fff';
        t.textContent       = msg;
        t.style.opacity     = '1';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.opacity = '0'; }, 3500);
    }

    function renderOrderRow(o) {
        const statusClass = o.status || 'pending';
        const isActive    = (o.id === SELECTED_ORDER_ID);
        return `<a href="javascript:void(0)" onclick="selectOrder(${o.id}); return false;"
                   class="live-order-item ${isActive ? 'active' : ''}"
                   data-order-id="${o.id}">
            <div class="wa-avatar-box">💬</div>
            <div class="order-meta-info">
                <div class="order-meta-top">
                    <span class="order-code-text">#${escHtml(o.tracking_code)}</span>
                    <span class="order-time-text">${escHtml(o.created_at_humans)}</span>
                </div>
                <div class="order-customer-text">👤 ${escHtml(o.customer_name)} (${escHtml(o.customer_phone)})</div>
                <div class="order-item-footer">
                    <span class="status-pill ${statusClass}">${escHtml(o.status_label)}</span>
                    <span class="order-price-bold">PKR ${Number(o.total).toLocaleString()}</span>
                </div>
            </div>
        </a>`;
    }

    async function pollLiveFeed() {
        try {
            const res  = await fetch(LIVE_FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            const kpiLive = document.getElementById('kpi-live-orders');
            const kpiBadge = document.getElementById('live-orders-badge');
            const kpiRev  = document.getElementById('kpi-revenue');
            const kpiPend = document.getElementById('kpi-pending-orders');
            const kpiPrep = document.getElementById('kpi-preparing-orders');
            const kpiDisp = document.getElementById('kpi-dispatched-orders');

            if (kpiLive)  kpiLive.textContent  = data.active_count ?? 0;
            if (kpiBadge) kpiBadge.textContent  = data.active_count ?? 0;
            if (kpiRev)   kpiRev.textContent    = 'PKR ' + Number(data.revenue ?? 0).toLocaleString();
            if (kpiPend)  kpiPend.textContent   = data.pending_count ?? 0;

            const orders = data.orders ?? [];
            const list   = document.getElementById('live-orders-list');

            let prepCount = 0;
            let dispCount = 0;
            orders.forEach(o => {
                currentOrdersMap[o.id] = o;
                if (o.status === 'preparing' || o.status === 'confirmed') prepCount++;
                if (o.status === 'out_for_delivery') dispCount++;
            });
            if (kpiPrep) kpiPrep.textContent = prepCount;
            if (kpiDisp) kpiDisp.textContent = dispCount;

            if (list) {
                if (orders.length === 0) {
                    list.innerHTML = `<div style="text-align:center;padding:40px 10px;color:#94a3b8;" id="empty-orders-state">
                        <div style="font-size:36px;margin-bottom:8px;">🍽️</div>
                        <p style="font-weight:700;font-size:14px;">No active live orders right now</p>
                        <p style="font-size:11.5px;margin-top:4px;">Orders placed on WhatsApp appear here instantly in real-time.</p>
                    </div>`;

                    if (SELECTED_ORDER_ID !== null && !orders.some(o => o.id === SELECTED_ORDER_ID)) {
                        const panel = document.getElementById('order-detail-panel');
                        if (panel) {
                            panel.innerHTML = `<div style="text-align:center;padding:80px 20px;color:#94a3b8;">
                                <div style="font-size:40px;margin-bottom:12px;">🛍️</div>
                                <h3 style="font-size:16px;font-weight:700;color:#334155;">Select an order</h3>
                                <p style="font-size:12px;margin-top:4px;">Click any order on the left to view its items, delivery route and customer chat.</p>
                            </div>`;
                        }
                    }
                } else {
                    const existingIds = [...list.querySelectorAll('[data-order-id]')].map(el => parseInt(el.dataset.orderId));
                    const newIds      = orders.map(o => o.id);
                    const hasNew      = newIds.some(id => !existingIds.includes(id));
                    const hasGone     = existingIds.some(id => !newIds.includes(id));

                    if (hasNew || hasGone || list.querySelector('#empty-orders-state')) {
                        list.innerHTML = orders.map(renderOrderRow).join('');
                        if (hasNew && existingIds.length > 0) {
                            showToast('🔔 New order arrived!', 'success');
                            const bell = document.getElementById('notif-bell');
                            if (bell) { bell.style.animation = 'bellShake 0.6s'; setTimeout(() => bell.style.animation = '', 700); }
                        }
                    } else {
                        orders.forEach(o => {
                            const row  = list.querySelector(`[data-order-id="${o.id}"]`);
                            if (!row) return;
                            const pill = row.querySelector('.status-pill');
                            if (pill && pill.textContent !== o.status_label) {
                                pill.textContent = o.status_label;
                                pill.className   = 'status-pill ' + o.status;
                            }
                            const time = row.querySelector('.order-time-text');
                            if (time) time.textContent = o.created_at_humans;
                        });
                    }

                    if (SELECTED_ORDER_ID === null && orders.length > 0) {
                        selectOrder(orders[0].id);
                    }
                }
            }

        } catch (_) { /* offline */ }
    }

    pollLiveFeed();
    setInterval(pollLiveFeed, 5000);
</script>

@endsection
