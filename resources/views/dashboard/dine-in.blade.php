@extends('layouts.dashboard')

@section('title', 'Dine-In Tables & Kitchen Session • ' . ($restaurant->name ?? 'Dashboard'))

@section('header_title')
    Dine-In Tables & Orders
@endsection

@section('header_subtitle')
    Live table orders received from customer app & table sessions today.
@endsection

@section('content')
<style>
    .dine-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Top Command Header */
    .dine-top-bar {
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
    [data-theme="dark"] .dine-top-bar {
        background: #1e293b;
        border-color: #334155;
    }

    .dine-pulse-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 50px;
        border: 1px solid #ffedd5;
    }
    [data-theme="dark"] .dine-pulse-badge {
        background: rgba(234, 88, 12, 0.15);
        color: #fb923c;
        border-color: rgba(234, 88, 12, 0.3);
    }
    .pulse-dot-orange {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ea580c;
        box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7);
        animation: pulseOrange 1.8s infinite;
    }
    @keyframes pulseOrange {
        0% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(234, 88, 12, 0); }
        100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
    }

    /* Metric Cards Strip */
    .dine-stats-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
    }
    .dine-stat-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    [data-theme="dark"] .dine-stat-box {
        background: #1e293b;
        border-color: #334155;
    }
    .dine-stat-info .stat-num {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    [data-theme="dark"] .dine-stat-info .stat-num {
        color: #f8fafc;
    }
    .dine-stat-info .stat-title {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-top: 4px;
    }
    .dine-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* Tables Grid Section */
    .tables-section {
        background: #ffffff;
        border-radius: 18px;
        padding: 20px 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.03);
    }
    [data-theme="dark"] .tables-section {
        background: #1e293b;
        border-color: #334155;
    }
    .section-title-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .section-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    [data-theme="dark"] .section-title {
        color: #f8fafc;
    }

    .table-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
    }
    .table-tile {
        border-radius: 14px;
        padding: 14px 12px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    [data-theme="dark"] .table-tile {
        background: #0f172a;
        border-color: #334155;
    }
    .table-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    }
    .table-tile.occupied {
        border-color: #f97316;
        background: #fff7ed;
    }
    [data-theme="dark"] .table-tile.occupied {
        background: rgba(234, 88, 12, 0.12);
        border-color: #ea580c;
    }
    .table-tile.has-pending {
        border-color: #ef4444;
        animation: pulseBorder 1.5s infinite;
    }
    @keyframes pulseBorder {
        0%, 100% { border-color: #ef4444; }
        50% { border-color: #f87171; }
    }
    .table-tile-number {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }
    [data-theme="dark"] .table-tile-number {
        color: #f8fafc;
    }
    .table-tile-status {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .table-tile-status.free {
        background: #ecfdf5;
        color: #059669;
    }
    .table-tile-status.occupied {
        background: #ea580c;
        color: #ffffff;
    }
    .table-tile-status.pending {
        background: #ef4444;
        color: #ffffff;
    }
    .table-tile-status.served {
        background: #8b5cf6;
        color: #ffffff;
    }
    .table-tile-amount {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    /* Filter Pills */
    .filter-pills-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
    }
    .filter-pill {
        padding: 8px 16px;
        border-radius: 9999px;
        font-size: 12.5px;
        font-weight: 700;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        text-decoration: none;
    }
    [data-theme="dark"] .filter-pill {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }
    .filter-pill:hover {
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .filter-pill.active {
        background: #ea580c;
        border-color: #ea580c;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(234, 88, 12, 0.3);
    }

    /* Orders Grid */
    .orders-tickets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }
    .order-ticket-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        position: relative;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    [data-theme="dark"] .order-ticket-card {
        background: #1e293b;
        border-color: #334155;
    }
    .order-ticket-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    .ticket-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .ticket-table-badge {
        background: #0f172a;
        color: #ffffff;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.05em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    [data-theme="dark"] .ticket-table-badge {
        background: #334155;
    }
    .ticket-order-meta {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }

    .ticket-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .ticket-status-pill.pending   { background: #fef3c7; color: #b45309; }
    .ticket-status-pill.confirmed { background: #e0e7ff; color: #4338ca; }
    .ticket-status-pill.preparing { background: #ffedd5; color: #c2410c; }
    .ticket-status-pill.served    { background: #ede9fe; color: #6d28d9; }
    .ticket-status-pill.delivered { background: #dcfce7; color: #15803d; }
    .ticket-status-pill.cancelled { background: #fee2e2; color: #b91c1c; }

    /* Items List Box */
    .ticket-items-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 14px;
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    [data-theme="dark"] .ticket-items-box {
        background: #0f172a;
        border-color: rgba(255, 255, 255, 0.04);
    }
    .ticket-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
    }
    .item-qty-name {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1e293b;
    }
    [data-theme="dark"] .item-qty-name {
        color: #f1f5f9;
    }
    .item-qty-tag {
        background: #e2e8f0;
        color: #0f172a;
        font-size: 11px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 6px;
    }
    [data-theme="dark"] .item-qty-tag {
        background: #334155;
        color: #f8fafc;
    }
    .item-price-sub {
        font-weight: 700;
        color: #475569;
    }
    [data-theme="dark"] .item-price-sub {
        color: #94a3b8;
    }

    .ticket-notes {
        font-size: 12px;
        background: #fffbeb;
        color: #92400e;
        padding: 8px 12px;
        border-radius: 8px;
        border-left: 3px solid #f59e0b;
        font-style: italic;
    }
    [data-theme="dark"] .ticket-notes {
        background: rgba(245, 158, 11, 0.15);
        color: #fcd34d;
    }

    .ticket-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }
    [data-theme="dark"] .ticket-footer {
        border-color: #334155;
    }
    .ticket-total-title {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }
    .ticket-total-num {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
    }
    [data-theme="dark"] .ticket-total-num {
        color: #f8fafc;
    }

    /* Actions Bar */
    .ticket-actions-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .t-btn {
        flex: 1 1 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .t-btn-cook {
        background: #ea580c;
        color: #ffffff;
    }
    .t-btn-cook:hover {
        background: #c2410c;
    }
    .t-btn-served {
        background: #8b5cf6;
        color: #ffffff;
    }
    .t-btn-served:hover {
        background: #7c3aed;
    }
    .t-btn-pay {
        background: #10b981;
        color: #ffffff;
    }
    .t-btn-pay:hover {
        background: #059669;
    }
    .t-btn-print {
        background: #f1f5f9;
        color: #334155;
        border-color: #e2e8f0;
    }
    [data-theme="dark"] .t-btn-print {
        background: #334155;
        color: #f8fafc;
        border-color: #475569;
    }
    .t-btn-cancel {
        background: transparent;
        color: #ef4444;
        border-color: #fecaca;
        flex: 0 0 auto;
    }
    .t-btn-cancel:hover {
        background: #fee2e2;
    }

    .empty-state-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 48px 24px;
        text-align: center;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }
    [data-theme="dark"] .empty-state-card {
        background: #1e293b;
        border-color: #334155;
    }
    .empty-state-icon {
        font-size: 44px;
    }
    .empty-state-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }
    [data-theme="dark"] .empty-state-title {
        color: #f8fafc;
    }
    .empty-state-sub {
        font-size: 13px;
        color: #64748b;
        max-width: 380px;
    }
</style>

<div class="dine-container">

    <!-- 1. Top Command Header -->
    <div class="dine-top-bar">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--text-heading); margin: 0;">
                    🍽️ Dine-In Session & Kitchen Orders
                </h2>
                <span class="dine-pulse-badge">
                    <span class="pulse-dot-orange"></span>
                    <span>Live Table Feed</span>
                </span>
            </div>
            <p style="font-size: 12.5px; color: var(--text-muted); margin: 4px 0 0 0;">
                Orders sent directly from customer table app & QR sessions appear here instantly.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" class="btn" style="background: var(--bg-card); border-color: var(--border-card); color: var(--text-body);" onclick="playChimeSound()" title="Test Order Bell Sound">
                🔔 Test Bell
            </button>
            <button type="button" class="btn btn-primary" onclick="pollDineInFeed(true)">
                ↻ Refresh Live
            </button>
        </div>
    </div>

    @php
        $totalConfiguredTables = max(1, (int) ($restaurant->total_tables ?: 12));
        $standardTables = array_map(fn($n) => (string)$n, range(1, $totalConfiguredTables));
        $customActiveTables = array_keys($tableSessions);
        $allTableNames = array_values(array_unique(array_merge($standardTables, $customActiveTables)));
        natsort($allTableNames);
    @endphp

    <!-- Visual Table Status Map & Table Management Section -->
    <div class="tables-section">
        <div class="section-title-wrap">
            <div class="section-title">
                <span>🪑 Table Floor Map ({{ count($allTableNames) }} Tables)</span>
                <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); padding: 2px 8px; background: var(--border-subtle); border-radius: 6px;">
                    Tap any table to filter tickets
                </span>
            </div>

            <!-- Table Capacity Increase / Decrease Controls -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="display: inline-flex; align-items: center; background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 10px; padding: 3px 6px; gap: 6px;">
                    <span style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-left: 4px;">
                        Tables: <strong id="currentTablesCount" style="color: var(--text-heading);">{{ $totalConfiguredTables }}</strong>
                    </span>
                    <button type="button" onclick="adjustTableCapacity('decrement')" class="btn" style="padding: 2px 8px; font-size: 12px; font-weight: 800; min-width: 26px; height: 26px; border-radius: 6px; background: #f1f5f9; color: #475569;" title="Remove Last Table">
                        −
                    </button>
                    <button type="button" onclick="adjustTableCapacity('increment')" class="btn btn-primary" style="padding: 2px 10px; font-size: 12px; font-weight: 800; height: 26px; border-radius: 6px;" title="Add Table to Floor">
                        + Add Table
                    </button>
                </div>

                <div style="display: flex; gap: 12px; font-size: 11.5px; font-weight: 600; color: var(--text-muted);">
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> Free Table</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #ea580c;"></span> Active Order</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span> New Order</span>
                </div>
            </div>
        </div>

        <div class="table-cards-grid" id="tableCardsGrid">
            @foreach($allTableNames as $tNum)
                @php
                    $isOccupied = isset($tableSessions[$tNum]);
                    $session = $isOccupied ? $tableSessions[$tNum] : null;
                    $status = $session['status'] ?? 'free';
                    $hasPending = $status === 'pending';
                @endphp
                <a href="{{ route('dashboard.dine-in', [$restaurant->id, 'table' => $selectedTable === $tNum ? null : $tNum]) }}" 
                   class="table-tile {{ $isOccupied ? 'occupied' : '' }} {{ $hasPending ? 'has-pending' : '' }} {{ $selectedTable === $tNum ? 'filter-active' : '' }}">
                    <div class="table-tile-number">
                        Table {{ $tNum }}
                    </div>
                    @if($isOccupied)
                        <span class="table-tile-status {{ $status }}">
                            {{ $status === 'pending' ? '⚡ New' : ($status === 'preparing' ? '🔥 Cook' : ($status === 'served' ? '✓ Served' : 'Active')) }}
                        </span>
                        <div class="table-tile-amount">
                            Rs. {{ number_format($session['total'], 0) }}
                        </div>
                    @else
                        <span class="table-tile-status free">
                            Available
                        </span>
                        <div class="table-tile-amount" style="opacity: 0.5;">
                            Free
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <!-- 4. Filter Pills Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div class="filter-pills-bar">
            <a href="{{ route('dashboard.dine-in', $restaurant->id) }}" 
               class="filter-pill {{ !request('status') && !$selectedTable ? 'active' : '' }}">
                All Active ({{ $activeDineIn->count() }})
            </a>
            <a href="{{ route('dashboard.dine-in', [$restaurant->id, 'status' => 'pending']) }}" 
               class="filter-pill {{ request('status') === 'pending' ? 'active' : '' }}">
                ⚡ Pending Orders ({{ $activeDineIn->where('status', 'pending')->count() }})
            </a>
            <a href="{{ route('dashboard.dine-in', [$restaurant->id, 'status' => 'preparing']) }}" 
               class="filter-pill {{ request('status') === 'preparing' ? 'active' : '' }}">
                🔥 In Kitchen / Preparing ({{ $activeDineIn->where('status', 'preparing')->count() }})
            </a>
            <a href="{{ route('dashboard.dine-in', [$restaurant->id, 'status' => 'served']) }}" 
               class="filter-pill {{ request('status') === 'served' ? 'active' : '' }}">
                🍽️ Served to Table ({{ $activeDineIn->where('status', 'served')->count() }})
            </a>
            <a href="{{ route('dashboard.dine-in', [$restaurant->id, 'status' => 'completed']) }}" 
               class="filter-pill {{ request('status') === 'completed' ? 'active' : '' }}">
                ✓ Paid & Cleared Today ({{ $completedDineIn->count() }})
            </a>
        </div>

        @if($selectedTable)
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--brand-primary);">
                    Filtering Table {{ $selectedTable }}
                </span>
                <a href="{{ route('dashboard.dine-in', $restaurant->id) }}" class="btn" style="padding: 4px 10px; font-size: 11px; background: #fee2e2; color: #b91c1c;">
                    ✕ Clear Filter
                </a>
            </div>
        @endif
    </div>

    <!-- 5. Active Dine-In Tickets Grid -->
    @php
        $filteredOrders = $dineInOrders;
        if (request('status') === 'completed') {
            $filteredOrders = $completedDineIn;
        } elseif (request('status')) {
            $filteredOrders = $dineInOrders->where('status', request('status'));
        } elseif (!$selectedTable) {
            $filteredOrders = $activeDineIn;
        }

        if ($selectedTable) {
            $filteredOrders = $filteredOrders->filter(function($ord) use ($selectedTable) {
                return $ord->getResolvedTableNumber() == $selectedTable;
            });
        }
    @endphp

    @if($filteredOrders->isEmpty())
        <div class="empty-state-card">
            <div class="empty-state-icon">🍽️</div>
            <div class="empty-state-title">No Dine-In Orders in this View</div>
            <div class="empty-state-sub">
                @if($selectedTable)
                    Table {{ $selectedTable }} currently has no active orders.
                @else
                    When customers choose their table and order food from the Dine-In mobile app or table QR, tickets will appear here with a live chime.
                @endif
            </div>
        </div>
    @else
        <div class="orders-tickets-grid" id="dineInTicketsContainer">
            @foreach($filteredOrders as $order)
                @php
                    $tNum = $order->getResolvedTableNumber() ?: 'Counter';
                @endphp
                <div class="order-ticket-card" id="ticketCard_{{ $order->id }}">
                    <!-- Header -->
                    <div class="ticket-header">
                        <div>
                            <span class="ticket-table-badge">
                                🪑 TABLE {{ strtoupper($tNum) }}
                            </span>
                            <div class="ticket-order-meta">
                                <span>Order #{{ $order->daily_order_number ?: $order->id }}</span>
                                <span>•</span>
                                <span>{{ $order->created_at ? $order->created_at->format('g:i A') : '' }}</span>
                                <span>•</span>
                                <span style="font-weight: 700; color: var(--text-heading);">{{ $order->customer_name ?: 'Guest' }}</span>
                            </div>
                        </div>

                        <span class="ticket-status-pill {{ $order->status }}" id="statusBadge_{{ $order->id }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>

                    <!-- Items Checklist -->
                    <div class="ticket-items-box">
                        @foreach($order->items as $item)
                            <div class="ticket-item-row">
                                <div class="item-qty-name">
                                    <span class="item-qty-tag">{{ $item->quantity }}x</span>
                                    <span>{{ $item->name }}</span>
                                    @if($item->size)
                                        <span style="font-size: 11px; color: var(--text-muted);">({{ $item->size }})</span>
                                    @endif
                                </div>
                                <span class="item-price-sub">Rs. {{ number_format($item->subtotal, 0) }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($order->notes) && !str_starts_with($order->notes, 'DINE-IN ORDER • Table'))
                        <div class="ticket-notes">
                            💬 {{ $order->notes }}
                        </div>
                    @endif

                    <!-- Total & Payment -->
                    <div class="ticket-footer">
                        <div>
                            <div class="ticket-total-title">Total Bill (Dine-In)</div>
                            <div class="ticket-total-num">Rs. {{ number_format($order->total, 0) }}</div>
                        </div>
                        <div style="font-size: 11.5px; font-weight: 700; color: {{ $order->is_paid ? '#059669' : '#ea580c' }};">
                            {{ $order->is_paid ? '✓ Paid' : 'Cash at Counter' }}
                        </div>
                    </div>

                    <!-- Fast Action Buttons -->
                    <div class="ticket-actions-bar">
                        @if($order->status === 'pending')
                            <button type="button" class="t-btn t-btn-cook" onclick="changeDineStatus({{ $order->id }}, 'preparing')">
                                🔥 Start Cooking
                            </button>
                        @elseif($order->status === 'confirmed' || $order->status === 'preparing')
                            <button type="button" class="t-btn t-btn-served" onclick="changeDineStatus({{ $order->id }}, 'served')">
                                🍽️ Mark Served
                            </button>
                        @endif

                        @if($order->status !== 'delivered' && $order->status !== 'cancelled')
                            <button type="button" class="t-btn t-btn-pay" onclick="changeDineStatus({{ $order->id }}, 'delivered')">
                                💳 Paid & Clear Table
                            </button>
                        @endif

                        <a href="{{ route('dashboard.print-bill', [$restaurant->id, $order->id]) }}" target="_blank" class="t-btn t-btn-print" title="Print Kitchen / Guest Receipt">
                            🖨️ Bill
                        </a>

                        @if($order->status !== 'delivered' && $order->status !== 'cancelled')
                            <button type="button" class="t-btn t-btn-cancel" onclick="changeDineStatus({{ $order->id }}, 'cancelled')" title="Cancel Order">
                                ✕
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<!-- Realtime Sound Generator (Web Audio API Synthesizer - Works without external audio files) -->
<script>
    let knownOrderIds = @json($dineInOrders->pluck('id'));
    let dinePollTimer = null;

    function playChimeSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;
            
            // Bell chime note 1 (Higher ping)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, now); // A5
            gain1.gain.setValueAtTime(0.4, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.6);

            // Bell chime note 2 (Harmonic ring)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'triangle';
            osc2.frequency.setValueAtTime(1174.66, now + 0.12); // D6
            gain2.gain.setValueAtTime(0.45, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.9);
        } catch (e) {
            console.log('Audio error:', e);
        }
    }

    // Change order status with instant optimistic UI
    function changeDineStatus(orderId, newStatus) {
        const csrf = "{{ csrf_token() }}";
        const url = "{{ url('dashboard/' . $restaurant->id . '/dine-in/orders') }}/" + orderId + "/status";

        // Optimistic UI state
        const badge = document.getElementById('statusBadge_' + orderId);
        if (badge) {
            badge.className = 'ticket-status-pill ' + newStatus;
            badge.innerText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: newStatus }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // If delivered or cancelled, smoothly fade card if on active view
                if (newStatus === 'delivered' || newStatus === 'cancelled') {
                    const card = document.getElementById('ticketCard_' + orderId);
                    if (card && !window.location.search.includes('status=completed')) {
                        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            pollDineInFeed(false);
                        }, 400);
                    }
                } else {
                    pollDineInFeed(false);
                }
            } else {
                alert(data.message || 'Error updating status');
            }
        })
        .catch(err => {
            console.error('Failed to update status:', err);
        });
    }

    // Adjust Table Capacity (Add / Remove Table)
    function adjustTableCapacity(action) {
        const updateUrl = "{{ route('dashboard.dine-in.update-tables', $restaurant->id) }}";
        const csrf = "{{ csrf_token() }}";

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ action: action }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const elTot = document.getElementById('currentTablesCount');
                if (elTot) elTot.innerText = data.total_tables;
                // Reload to refresh the table grid smoothly
                window.location.reload();
            } else {
                alert(data.message || 'Could not update table capacity');
            }
        })
        .catch(err => {
            console.error('Error updating tables count:', err);
        });
    }

    // Live Polling Feed
    function pollDineInFeed(manual = false) {
        const feedUrl = "{{ route('dashboard.dine-in.feed', $restaurant->id) }}";

        fetch(feedUrl, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update metrics
                const elOcc = document.getElementById('metricOccupiedTables');
                if (elOcc) elOcc.innerText = data.occupied_tables_count;

                const elPend = document.getElementById('metricPendingTickets');
                if (elPend) elPend.innerText = data.pending_count;

                const elRev = document.getElementById('metricDineRevenue');
                if (elRev) elRev.innerText = 'Rs. ' + Number(data.total_revenue_today).toLocaleString();

                const elTotTables = document.getElementById('currentTablesCount');
                if (elTotTables && data.total_tables) elTotTables.innerText = data.total_tables;

                // Update sidebar badge if exists
                const navBadge = document.getElementById('dineInNavBadge');
                if (navBadge) {
                    if (data.pending_count > 0 || data.occupied_tables_count > 0) {
                        navBadge.style.display = 'inline-block';
                    } else {
                        navBadge.style.display = 'none';
                    }
                }

                // Check for new incoming orders
                const currentIds = (data.orders || []).map(o => o.id);
                const hasNewOrder = currentIds.some(id => !knownOrderIds.includes(id));

                if (hasNewOrder) {
                    playChimeSound();
                    knownOrderIds = currentIds;
                    // Reload page to smoothly render new cards and tables
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    knownOrderIds = currentIds;
                }

                if (manual) {
                    const toast = document.createElement('div');
                    toast.innerText = '✓ Dine-in session refreshed';
                    toast.style.position = 'fixed';
                    toast.style.bottom = '24px';
                    toast.style.right = '24px';
                    toast.style.background = '#10b981';
                    toast.style.color = '#fff';
                    toast.style.padding = '10px 18px';
                    toast.style.borderRadius = '10px';
                    toast.style.fontSize = '12px';
                    toast.style.fontWeight = '700';
                    toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.15)';
                    toast.style.zIndex = '9999';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2500);
                }
            }
        })
        .catch(err => console.log('Feed poll error:', err));
    }

    // Auto-poll every 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        dinePollTimer = setInterval(() => {
            pollDineInFeed(false);
        }, 5000);
    });
</script>
@endsection
