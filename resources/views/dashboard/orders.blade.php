@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Welcome back, ' . ($restaurant->name ?? 'Owner') . '!')

@section('content')

<style>
    /* Global Dashboard Styles */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 22px;
        color: #0f172a;
    }

    /* Top Stats Grid (5 Cards) */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    }

    .stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .stat-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .stat-icon-wrap.purple { background: #ede9fe; color: #7c3aed; }
    .stat-icon-wrap.green  { background: #dcfce7; color: #16a34a; }
    .stat-icon-wrap.blue   { background: #e0f2fe; color: #0284c7; }
    .stat-icon-wrap.orange { background: #ffedd5; color: #ea580c; }
    .stat-icon-wrap.teal   { background: #ccfbf1; color: #0d9488; }

    .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 2px;
    }
    .stat-val {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        line-height: 1.1;
    }

    .stat-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        color: #94a3b8;
        padding-top: 10px;
        border-top: 1px solid #f8fafc;
    }
    .stat-growth {
        color: #16a34a;
        font-weight: 700;
        background: #f0fdf4;
        padding: 2px 6px;
        border-radius: 6px;
    }
    .stat-link {
        color: #6366f1;
        font-weight: 700;
        text-decoration: none;
    }
    .stat-link:hover { text-decoration: underline; }

    /* Middle 3-Column Section */
    .middle-grid {
        display: grid;
        grid-template-columns: 1.1fr 1.8fr 1.1fr;
        gap: 18px;
        align-items: stretch;
    }

    .panel-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .panel-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .badge-count {
        background: #ede9fe;
        color: #7c3aed;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 9999px;
    }

    /* Live Orders List */
    .live-orders-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        overflow-y: auto;
        max-height: 480px;
        padding-right: 4px;
    }
    .live-order-item {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 14px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.15s ease;
    }
    .live-order-item:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        transform: translateX(2px);
    }
    .live-order-item.active {
        border-color: #818cf8;
        background: #f5f3ff;
        box-shadow: 0 0 0 1px #818cf8;
    }
    .wa-avatar-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #dcfce7;
        color: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .order-meta-info {
        flex-grow: 1;
        min-width: 0;
    }
    .order-meta-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2px;
    }
    .order-code-text {
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
    }
    .order-time-text {
        font-size: 11px;
        color: #94a3b8;
    }
    .order-customer-text {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .order-item-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 6px;
    }
    .status-pill {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: capitalize;
    }
    .status-pill.pending   { background: #fef3c7; color: #b45309; }
    .status-pill.confirmed { background: #dcfce7; color: #15803d; }
    .status-pill.preparing { background: #ede9fe; color: #6d28d9; }
    .status-pill.out_for_delivery { background: #e0f2fe; color: #0369a1; }
    .status-pill.delivered { background: #f1f5f9; color: #475569; }
    .status-pill.cancelled { background: #fee2e2; color: #b91c1c; }

    .order-price-bold {
        font-size: 12px;
        font-weight: 800;
        color: #4f46e5;
    }

    /* Center Column: Order Details & Live Tracking */
    .order-detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .order-detail-title h3 {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }
    .order-detail-title p {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .customer-info-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }
    .info-col-label {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-col-val {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .info-col-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 1px;
    }

    /* Live Route Map Graphic Mock */
    .route-map-preview {
        background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 50%, #f5f3ff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        height: 120px;
        position: relative;
        overflow: hidden;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 30px;
    }
    .map-distance-badge {
        position: absolute;
        top: 10px;
        right: 12px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #cbd5e1;
        padding: 3px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
        color: #0f172a;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }
    .map-pin {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        z-index: 2;
    }
    .map-pin.store { background: #4f46e5; color: #fff; }
    .map-pin.dest  { background: #7c3aed; color: #fff; }
    .route-line-svg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    /* Order Items Table */
    .order-items-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 14px;
    }
    .order-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        padding: 4px 0;
    }
    .order-item-qty-name {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #334155;
        font-weight: 600;
    }
    .order-item-qty-badge {
        font-weight: 800;
        color: #4f46e5;
    }
    .order-item-price {
        font-weight: 700;
        color: #0f172a;
    }
    .order-bill-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 8px 0;
    }
    .order-total-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        padding-top: 4px;
    }

    /* Assigned Rider Block */
    .assigned-rider-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 14px 0;
    }
    .rider-avatar-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rider-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .rider-name-status h4 {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .rider-status-dot {
        font-size: 9px;
        background: #dcfce7;
        color: #16a34a;
        padding: 1px 6px;
        border-radius: 9999px;
        font-weight: 700;
    }
    .rider-phone-sub {
        font-size: 11px;
        color: #64748b;
    }
    .btn-call-rider {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 14px;
    }

    /* Action Buttons Row */
    .action-btn-row {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .btn-action-primary {
        flex: 1;
        padding: 10px;
        background: #4f46e5;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-action-primary:hover { background: #4338ca; }
    .btn-action-secondary {
        padding: 10px 16px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-action-secondary:hover { background: #e2e8f0; }

    /* Right Column: Active Riders List */
    .rider-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
        overflow-y: auto;
        max-height: 360px;
    }
    .rider-item-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        transition: all 0.15s;
    }
    .rider-item-card:hover {
        background: #f1f5f9;
    }
    .rider-meta-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rider-pic {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }
    .rider-tag {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .rider-tag.delivery { background: #dcfce7; color: #16a34a; }
    .rider-tag.pickup   { background: #e0f2fe; color: #0284c7; }
    .rider-tag.free     { background: #f1f5f9; color: #64748b; }
    .rider-tag.offline  { background: #fee2e2; color: #991b1b; }

    .rider-actions-bottom {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: auto;
    }
    .btn-sub-action {
        padding: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-sub-action:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    /* Bottom 4 Analytics Cards */
    .bottom-analytics-grid {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1fr 1.2fr;
        gap: 18px;
    }

    /* Donut Chart */
    .donut-chart-container {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 10px;
    }
    .donut-circle-wrap {
        width: 100px;
        height: 100px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .donut-center-text {
        text-align: center;
    }
    .donut-center-text h4 {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }
    .donut-center-text p {
        font-size: 9px;
        color: #94a3b8;
    }
    .legend-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 11px;
        flex-grow: 1;
    }
    .legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .legend-bullet {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    /* Top Selling Items List */
    .top-items-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 8px;
    }
    .top-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }
    .top-item-rank-name {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: #1e293b;
    }
    .top-item-rank {
        font-size: 11px;
        color: #94a3b8;
        width: 14px;
    }
    .top-item-count {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }

    /* Activity Feed */
    .activity-feed-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 8px;
    }
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 11px;
    }
    .activity-dot {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .activity-text {
        flex-grow: 1;
        color: #334155;
        line-height: 1.4;
    }
    .activity-time {
        font-size: 10px;
        color: #94a3b8;
        white-space: nowrap;
    }

    /* Bottom Notification Notice */
    .notice-bar {
        background: #fefce8;
        border: 1px solid #fef08a;
        border-radius: 16px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .notice-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .notice-icon {
        font-size: 24px;
    }
    .notice-text h4 {
        font-size: 13px;
        font-weight: 700;
        color: #854d0e;
    }
    .notice-text p {
        font-size: 11px;
        color: #a16207;
        margin-top: 2px;
    }
    .btn-test-wa {
        padding: 8px 16px;
        background: #ffffff;
        border: 1px solid #fde047;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        color: #854d0e;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        white-space: nowrap;
    }

    @media (max-width: 1200px) {
        .stats-row { grid-template-columns: repeat(3, 1fr); }
        .middle-grid { grid-template-columns: 1fr; gap: 16px; }
        .bottom-analytics-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .dashboard-container { gap: 16px; }
        .stats-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .stat-card { padding: 14px; border-radius: 14px; }
        .stat-val { font-size: 20px; }
        .stat-icon-wrap { width: 34px; height: 34px; font-size: 16px; border-radius: 10px; }
        .stat-footer { font-size: 10.5px; padding-top: 8px; }
        .bottom-analytics-grid { grid-template-columns: 1fr; gap: 14px; }
        .notice-bar { flex-direction: column; align-items: flex-start; gap: 10px; padding: 12px 14px; }
        .notice-bar a { width: 100%; justify-content: center; }
        .panel-card { padding: 16px 14px; border-radius: 16px; }
        .live-order-item { padding: 12px; border-radius: 14px; }
        .order-actions-bar { flex-direction: column; width: 100%; }
        .order-actions-bar .btn-action-primary,
        .order-actions-bar .btn-action-secondary { width: 100%; justify-content: center; padding: 10px; }
        .customer-info-box { padding: 12px; gap: 10px; flex-direction: column; }
        .assigned-rider-box { padding: 12px; }
        .route-map-preview { height: 110px; }
        #dispatchModal > div {
            width: calc(100% - 24px) !important;
            max-width: 480px !important;
            padding: 20px 16px !important;
            border-radius: 18px !important;
        }
    }
    @media (max-width: 480px) {
        .stats-row { grid-template-columns: 1fr; }
        .panel-title { font-size: 14px; }
        .order-detail-title { flex-direction: column; align-items: flex-start; gap: 6px; }
    }
</style>

<div class="dashboard-container">

    <!-- 1. TOP 5 KPI SUMMARY CARDS -->
    <div class="stats-row">
        <!-- Card 1 -->
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Live Orders</div>
                    <div class="stat-val" id="kpi-live-orders">{{ $liveOrdersCount }}</div>
                </div>
                <div class="stat-icon-wrap purple">🛍️</div>
            </div>
            <div class="stat-footer">
                <span>Active right now</span>
                <a href="{{ route('dashboard.orders', $restaurant->id) }}" class="stat-link">View all →</a>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Today's Revenue</div>
                    <div class="stat-val" id="kpi-revenue">PKR {{ number_format($todayRevenue) }}</div>
                </div>
                <div class="stat-icon-wrap green">📈</div>
            </div>
            <div class="stat-footer">
                <span>vs yesterday <span class="stat-growth">+18.6%</span></span>
                <svg width="40" height="16" viewBox="0 0 40 16" fill="none"><path d="M1 14L10 8L20 12L30 3L39 7" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Active Riders</div>
                    <div class="stat-val">{{ $activeRidersCount }}</div>
                </div>
                <div class="stat-icon-wrap blue">🚴</div>
            </div>
            <div class="stat-footer">
                <span>On delivery</span>
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="stat-link">View riders →</a>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-val">{{ $totalOrdersToday }}</div>
                </div>
                <div class="stat-icon-wrap orange">📦</div>
            </div>
            <div class="stat-footer">
                <span>Today</span>
                <a href="{{ route('dashboard.reports', $restaurant->id) }}" class="stat-link">View report →</a>
            </div>
        </div>

        <!-- Card 5 -->
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Menu Items</div>
                    <div class="stat-val">{{ $menuItemsCount }}</div>
                </div>
                <div class="stat-icon-wrap teal">🍽️</div>
            </div>
            <div class="stat-footer">
                <span>In stock</span>
                <a href="{{ route('dashboard.menu', $restaurant->id) }}" class="stat-link">Manage →</a>
            </div>
        </div>
    </div>

    <!-- 2. MAIN 3-COLUMN SECTION -->
    <div class="middle-grid">

        <!-- Column 1: Live Incoming Orders -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <span>Live Orders</span>
                    <span class="badge-count" id="live-orders-badge">{{ $liveOrdersCount }}</span>
                </div>
                <span style="font-size: 11px; color: #94a3b8;">Real-time incoming</span>
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
                        <div style="font-size: 32px; margin-bottom: 8px;">🍽️</div>
                        <p style="font-weight: 700;">No live orders right now</p>
                        <p style="font-size: 11px; margin-top: 4px;">Orders placed on WhatsApp appear here instantly.</p>
                    </div>
                @endforelse
            </div>

            <div style="margin-top: 14px; text-align: center; border-top: 1px solid #f8fafc; padding-top: 10px;">
                <a href="{{ route('dashboard.history', $restaurant->id) }}" style="font-size: 11px; font-weight: 700; color: #6366f1; text-decoration: none;">View all orders history →</a>
            </div>
        </div>

        <!-- Column 2: Order Details & Live Kitchen Tracking -->
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

                <!-- Route Map Mock Graphic -->
                <div class="route-map-preview">
                    <span class="map-distance-badge">📍 2.3 km away</span>
                    <div class="map-pin store">🏪</div>
                    <svg class="route-line-svg" viewBox="0 0 300 120" preserveAspectRatio="none">
                        <path d="M 40 60 Q 150 10 260 60" stroke="#818cf8" stroke-width="3" stroke-dasharray="6,6" fill="none"/>
                    </svg>
                    <div class="map-pin dest">📍</div>
                </div>

                <!-- Order Items -->
                <div class="order-items-list">
                    @foreach($selectedOrder->items as $item)
                        <div class="order-item-row">
                            <div class="order-item-qty-name">
                                <span class="order-item-qty-badge">{{ $item->quantity }}x</span>
                                <span>{{ $item->name ?: $item->item_name }}</span>
                            </div>
                            <span class="order-item-price">PKR {{ number_format($item->subtotal) }}</span>
                        </div>
                    @endforeach
                    <div class="order-item-row" style="color: #64748b;">
                        <span>Delivery Fee</span>
                        <span>PKR {{ number_format($restaurant->delivery_fee ?? 150) }}</span>
                    </div>
                    <div class="order-bill-divider"></div>
                    <div class="order-total-row">
                        <span>Total Bill</span>
                        <span style="color: #4f46e5; font-size: 16px;">PKR {{ number_format($selectedOrder->total) }}</span>
                    </div>
                </div>

                <!-- Assigned Rider -->
                <div class="assigned-rider-box">
                    <div class="rider-avatar-info">
                        <div class="rider-avatar">🚴</div>
                        <div class="rider-name-status">
                            <h4>
                                <span>{{ $selectedOrder->rider->name ?? ($selectedOrder->rider_name ?: 'No Rider Assigned') }}</span>
                                @if($selectedOrder->rider || $selectedOrder->rider_name)
                                    <span class="rider-status-dot">Online</span>
                                @endif
                            </h4>
                            <div class="rider-phone-sub">{{ $selectedOrder->rider->phone ?? ($selectedOrder->rider_phone ?: 'Assign rider before dispatch') }}</div>
                        </div>
                    </div>
                    @if(($selectedOrder->rider && $selectedOrder->rider->phone) || $selectedOrder->rider_phone)
                        <a href="tel:{{ $selectedOrder->rider->phone ?? $selectedOrder->rider_phone }}" class="btn-call-rider" title="Call Rider">📞</a>
                    @endif
                </div>

                <!-- Action Buttons: Real-Time Status Transitions -->
                <div class="action-btn-row" id="action-btn-row">
                    @if($selectedOrder->status === 'pending')
                        <button type="button" class="btn-action-primary"
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
                            onclick="openDispatchModal('{{ $selectedOrder->id }}', '{{ $selectedOrder->tracking_code }}', '{{ addslashes($selectedOrder->customer_name) }}', '{{ addslashes($selectedOrder->delivery_address ?: $selectedOrder->masked_delivery_address) }}')">
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

        <!-- Column 3: Active Riders & Quick Actions -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">
                    <span>Active Riders</span>
                    <span class="badge-count">{{ $activeRidersCount }}</span>
                </div>
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" style="font-size: 11px; font-weight: 700; color: #6366f1; text-decoration: none;">View all</a>
            </div>

            <div class="rider-list">
                @forelse($riders as $rider)
                    <div class="rider-item-card">
                        <div class="rider-meta-left">
                            <div class="rider-pic">🚴</div>
                            <div>
                                <div style="font-size: 12px; font-weight: 700; color: #0f172a;">{{ $rider->name }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $rider->phone }}</div>
                            </div>
                        </div>
                        <span class="rider-tag {{ $rider->is_active ? 'delivery' : 'offline' }}">
                            {{ $rider->is_active ? 'Available' : 'Offline' }}
                        </span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 30px 10px; color: #94a3b8;">
                        <p style="font-size: 11px;">No delivery riders added yet.</p>
                    </div>
                @endforelse
            </div>

            <div class="rider-actions-bottom">
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="btn-sub-action">
                    <span>➕</span>
                    <span>Add Rider</span>
                </a>
                <a href="{{ route('dashboard.customers', $restaurant->id) }}" class="btn-sub-action" style="color: #16a34a; border-color: #bbf7d0;">
                    <span>💬</span>
                    <span>Broadcast</span>
                </a>
            </div>
        </div>

    </div>

    <!-- 3. BOTTOM 4 ANALYTICAL CARDS -->
    <div class="bottom-analytics-grid">

        <!-- Card 1: Order Status Overview Donut -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">Order Status</div>
                <span style="font-size: 11px; color: #94a3b8;">Today ▾</span>
            </div>
            <div class="donut-chart-container">
                <div class="donut-circle-wrap">
                    <svg width="100" height="100" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#f1f5f9" stroke-width="3.8"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#16a34a" stroke-width="3.8" stroke-dasharray="{{ $statusPercentages['delivered'] ?? 50 }}, 100"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#7c3aed" stroke-width="3.8" stroke-dasharray="{{ $statusPercentages['preparing'] ?? 20 }}, 100" stroke-dashoffset="-{{ $statusPercentages['delivered'] ?? 50 }}"/>
                    </svg>
                    <div class="donut-center-text" style="position: absolute;">
                        <h4>{{ $totalOrdersToday }}</h4>
                        <p>Orders</p>
                    </div>
                </div>
                <div class="legend-list">
                    <div class="legend-item">
                        <span><span class="legend-bullet" style="background: #16a34a;"></span>Delivered</span>
                        <strong>{{ $statusCounts['delivered'] }} ({{ $statusPercentages['delivered'] }}%)</strong>
                    </div>
                    <div class="legend-item">
                        <span><span class="legend-bullet" style="background: #7c3aed;"></span>Preparing</span>
                        <strong>{{ $statusCounts['preparing'] }} ({{ $statusPercentages['preparing'] }}%)</strong>
                    </div>
                    <div class="legend-item">
                        <span><span class="legend-bullet" style="background: #0284c7;"></span>Confirmed</span>
                        <strong>{{ $statusCounts['confirmed'] }} ({{ $statusPercentages['confirmed'] }}%)</strong>
                    </div>
                    <div class="legend-item">
                        <span><span class="legend-bullet" style="background: #f59e0b;"></span>Pending</span>
                        <strong>{{ $statusCounts['pending'] }} ({{ $statusPercentages['pending'] }}%)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Orders Trend (Smooth Weekly Chart) -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">Orders Trend</div>
                <span style="font-size: 11px; color: #94a3b8;">This Week ▾</span>
            </div>
            <div style="height: 120px; position: relative; margin-top: 10px;">
                <svg viewBox="0 0 280 100" style="width: 100%; height: 100%; overflow: visible;">
                    <defs>
                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#818cf8" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="#818cf8" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M 0,80 Q 40,40 80,65 T 160,30 T 240,45 T 280,20 L 280,100 L 0,100 Z" fill="url(#chartGrad)"/>
                    <path d="M 0,80 Q 40,40 80,65 T 160,30 T 240,45 T 280,20" fill="none" stroke="#6366f1" stroke-width="2.5"/>
                    <circle cx="160" cy="30" r="4" fill="#6366f1" stroke="#fff" stroke-width="2"/>
                </svg>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; margin-top: 6px;">
                @foreach($weeklyTrend as $wt)
                    <span>{{ $wt['day'] }}</span>
                @endforeach
            </div>
        </div>

        <!-- Card 3: Top Selling Items -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">Top Selling Items</div>
                <span style="font-size: 11px; color: #94a3b8;">Today ▾</span>
            </div>
            <div class="top-items-list">
                @forelse($topSellingItems as $idx => $ti)
                    <div class="top-item-row">
                        <div class="top-item-rank-name">
                            <span class="top-item-rank">{{ $idx + 1 }}</span>
                            <span>🍽️ {{ $ti->name ?? ($ti->item_name ?? 'Special Dish') }}</span>
                        </div>
                        <span class="top-item-count">{{ $ti->total_qty }} orders</span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 11px;">
                        No item sales recorded today yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Card 4: Recent Activity Feed -->
        <div class="panel-card">
            <div class="panel-header">
                <div class="panel-title">Recent Activity</div>
                <span style="font-size: 11px; color: #94a3b8;">Live Feed</span>
            </div>
            <div class="activity-feed-list">
                @forelse($recentActivity as $act)
                    <div class="activity-item">
                        <div class="activity-dot">⚡</div>
                        <div class="activity-text">
                            <strong>#{{ $act->tracking_code }}</strong> is now <span class="status-pill {{ $act->status }}">{{ $act->status_label }}</span>
                        </div>
                        <div class="activity-time">{{ $act->created_at->diffForHumans(null, true, true) }}</div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 11px;">
                        No recent activity yet.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- 4. BOTTOM NOTIFICATIONS ALERT BAR -->
    <div class="notice-bar">
        <div class="notice-content">
            <div class="notice-icon">🔔</div>
            <div class="notice-text">
                <h4>Automated Customer Notifications</h4>
                <p>When you update status to <strong>"Preparing"</strong> or <strong>"Dispatched"</strong>, the WhatsApp bot automatically alerts the customer with rider details, live tracking link, and ETA.</p>
            </div>
        </div>
        <a href="{{ route('dashboard.connect-whatsapp', $restaurant->id) }}" class="btn-test-wa">
            <span>🤖</span>
            <span>Bot Connection Settings</span>
        </a>
    </div>

</div>

<!-- 5. DISPATCH TO RIDER MODAL -->
<div id="dispatchModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 20px; width: 500px; max-width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0; overflow: hidden; animation: modalFadeIn 0.2s ease;">
        <div style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    🛵
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; line-height: 1.2;">Dispatch to Rider</h3>
                    <p style="font-size: 12px; color: #e0f2fe; margin-top: 2px;">Order <strong id="dispatchOrderCode"></strong> • <span id="dispatchCustomerName"></span></p>
                </div>
            </div>
            <button type="button" onclick="closeDispatchModal()" style="background: none; border: none; color: #ffffff; font-size: 22px; cursor: pointer; line-height: 1; padding: 4px;">✕</button>
        </div>

        <form id="dispatchForm" method="POST" action="" onsubmit="return ajaxSubmitDispatch(event);" style="padding: 22px 24px;">
            @csrf
            <input type="hidden" name="status" value="out_for_delivery">

            <!-- Rider Selection -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Select Delivery Rider *</label>
                
                @if($riders->isNotEmpty())
                    <select id="riderSelect" class="form-control" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; width: 100%; font-size: 13px; margin-bottom: 10px;" onchange="handleRiderSelect(this)">
                        <option value="">-- Choose from Registered Fleet --</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->name }}" data-phone="{{ $rider->phone }}">
                                {{ $rider->name }} ({{ $rider->phone }}) {{ $rider->is_active ? '• Active' : '• Inactive' }}
                            </option>
                        @endforeach
                        <option value="__custom__">➕ Enter Other / Third-Party Rider</option>
                    </select>
                @endif

                <div id="customRiderFields" style="{{ $riders->isNotEmpty() ? 'display: none;' : '' }}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #64748b; margin-bottom: 4px;">Rider Name *</label>
                            <input type="text" id="inputRiderName" name="rider_name" class="form-control" placeholder="e.g. Ali Khan" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #64748b; margin-bottom: 4px;">Rider Phone Number</label>
                            <input type="text" id="inputRiderPhone" name="rider_phone" class="form-control" placeholder="e.g. 03001234567" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ETA & Notes -->
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Estimated Mins</label>
                    <input type="number" name="estimated_minutes" class="form-control" value="25" min="5" max="180" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; width: 100%; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Rider Notes (Optional)</label>
                    <input type="text" name="rider_notes" class="form-control" placeholder="e.g. Call before ringing bell" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; width: 100%; font-size: 13px;">
                </div>
            </div>

            <!-- Delivery Address Snapshot -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 12px; color: #64748b;">
                <span style="font-weight: 700; color: #0f172a;">📍 Delivering to:</span>
                <span id="dispatchAddress"></span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeDispatchModal()" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 10px 22px; border-radius: 10px; border: none; background: #0284c7; color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);">Confirm & Dispatch 🛵</button>
            </div>
        </form>
    </div>
</div>

<script>
    const RESTAURANT_ID     = '{{ $restaurant->id }}';
    let SELECTED_ORDER_ID   = {{ $selectedOrder?->id ?? 'null' }};
    const LIVE_FEED_URL     = '/dashboard/' + RESTAURANT_ID + '/orders/live-feed';
    const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Store of all live orders
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

    // Status transition metadata
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

    // ── Open / Close Dispatch Modal ──
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

    // ── AJAX: Dispatch Form Submission ──
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

            // Update order object in memory
            if (currentOrdersMap[SELECTED_ORDER_ID]) {
                currentOrdersMap[SELECTED_ORDER_ID].status = 'out_for_delivery';
                currentOrdersMap[SELECTED_ORDER_ID].status_label = data.status_label || '🛵 Out for Delivery';
                currentOrdersMap[SELECTED_ORDER_ID].rider_name = dataObj.rider_name || '';
                currentOrdersMap[SELECTED_ORDER_ID].rider_phone = dataObj.rider_phone || '';
                renderOrderDetail(currentOrdersMap[SELECTED_ORDER_ID]);
            }

            // Update row status in left list
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

    // ── AJAX: Update order status without page reload ──
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

            // Flash success toast
            showToast('✅ ' + (data.message || 'Status updated!'), 'success');

            // Update order object in local map
            if (currentOrdersMap[SELECTED_ORDER_ID]) {
                currentOrdersMap[SELECTED_ORDER_ID].status = data.status;
                currentOrdersMap[SELECTED_ORDER_ID].status_label = data.status_label;
                renderOrderDetail(currentOrdersMap[SELECTED_ORDER_ID]);
            }

            // Update corresponding left list item pill
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

    // ── Select an order and render middle panel ──
    function selectOrder(orderId) {
        SELECTED_ORDER_ID = orderId;

        // Highlight active order in list
        document.querySelectorAll('.live-order-item').forEach(el => {
            if (parseInt(el.dataset.orderId) === orderId) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        // Update URL state without page reload
        if (window.history && window.history.pushState) {
            const newUrl = `/dashboard/${RESTAURANT_ID}/orders?order_id=${orderId}`;
            window.history.pushState({ orderId }, '', newUrl);
        }

        const order = currentOrdersMap[orderId];
        if (order) {
            renderOrderDetail(order);
        }
    }

    // ── Render Middle Order Details Panel ──
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

        // Rider HTML
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
                                <span class="rider-status-dot">Online</span>
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

        // Items HTML
        const itemsHtml = (o.items || []).map(it => `
            <div class="order-item-row">
                <div class="order-item-qty-name">
                    <span class="order-item-qty-badge">${it.quantity}x</span>
                    <span>${escHtml(it.name)}</span>
                </div>
                <span class="order-item-price">PKR ${Number(it.subtotal).toLocaleString()}</span>
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

            <!-- Route Map Preview Graphic -->
            <div class="route-map-preview">
                <span class="map-distance-badge">📍 2.3 km away</span>
                <div class="map-pin store">🏪</div>
                <svg class="route-line-svg" viewBox="0 0 300 120" preserveAspectRatio="none">
                    <path d="M 40 60 Q 150 10 260 60" stroke="#818cf8" stroke-width="3" stroke-dasharray="6,6" fill="none"/>
                </svg>
                <div class="map-pin dest">📍</div>
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
                    <span style="color: #4f46e5; font-size: 16px;">PKR ${Number(o.total).toLocaleString()}</span>
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
                <a href="/dashboard/${RESTAURANT_ID}/orders/${o.id}/print" target="_blank" class="btn-action-secondary" title="Print Parcel Bill / Receipt">
                    🖨️ Print Bill
                </a>
            </div>
        `;
    }

    // ── Toast notification ──
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

    // ── Render order row HTML in Left List ──
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

    // ── In-place live feed polling (every 5 seconds) ──
    async function pollLiveFeed() {
        try {
            const res  = await fetch(LIVE_FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            // Update KPI cards
            const kpiLive = document.getElementById('kpi-live-orders');
            const kpiBadge = document.getElementById('live-orders-badge');
            const kpiRev  = document.getElementById('kpi-revenue');

            if (kpiLive)  kpiLive.textContent  = data.active_count ?? 0;
            if (kpiBadge) kpiBadge.textContent  = data.active_count ?? 0;
            if (kpiRev)   kpiRev.textContent    = 'PKR ' + Number(data.revenue ?? 0).toLocaleString();

            const orders = data.orders ?? [];
            const list   = document.getElementById('live-orders-list');

            // Update our local map of active orders
            orders.forEach(o => {
                currentOrdersMap[o.id] = o;
            });

            if (list) {
                if (orders.length === 0) {
                    list.innerHTML = `<div style="text-align:center;padding:40px 10px;color:#94a3b8;" id="empty-orders-state">
                        <div style="font-size:32px;margin-bottom:8px;">🍽️</div>
                        <p style="font-weight:700;">No live orders right now</p>
                        <p style="font-size:11px;margin-top:4px;">Orders placed on WhatsApp appear here instantly.</p>
                    </div>`;

                    // If selected order is gone, show empty detail state
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
                        // Update existing list rows in place
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

                    // Auto-select first order if none selected
                    if (SELECTED_ORDER_ID === null && orders.length > 0) {
                        selectOrder(orders[0].id);
                    }
                }
            }

        } catch (_) { /* offline — retry next tick */ }
    }

    // Poll immediately then every 5 seconds
    pollLiveFeed();
    setInterval(pollLiveFeed, 5000);
</script>

@endsection