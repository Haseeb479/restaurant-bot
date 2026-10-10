@extends('layouts.dashboard')

@section('title', 'Order Management • ' . ($restaurant->name ?? 'TastyIgniter'))

@section('content')
<style>
    /* ═══════════════════════════════════════════════════════════════
       TASTYIGNITER DESIGN SYSTEM: ORDER MANAGEMENT & ACTIVE ORDERS
       ═══════════════════════════════════════════════════════════════ */
    .order-mgmt-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 1440px;
        margin: 0 auto;
    }

    /* ── 1. Top Page Header Row ── */
    .order-mgmt-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .order-mgmt-title {
        font-size: 32px;
        line-height: 1.2;
        font-weight: 700;
        color: var(--color-dark, #070A08);
        letter-spacing: -0.02em;
        margin: 0;
    }
    [data-theme="dark"] .order-mgmt-title {
        color: #ffffff;
    }
    .order-mgmt-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .btn-tasty-export {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid var(--border-subtle, #ebeef2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-dark, #070A08);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    [data-theme="dark"] .btn-tasty-export {
        background: #151a17;
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }
    .btn-tasty-export:hover {
        background: #f8fafc;
        transform: translateY(-1px);
        border-color: #cbd5e1;
    }

    .btn-tasty-sound {
        height: 44px;
        padding: 0 14px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid var(--border-subtle, #ebeef2);
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 700;
        color: var(--color-dark, #070A08);
        cursor: pointer;
        transition: all 0.15s ease;
    }
    [data-theme="dark"] .btn-tasty-sound {
        background: #151a17;
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .btn-tasty-add-order {
        height: 44px;
        padding: 0 24px;
        border-radius: 9999px;
        background: var(--color-coral, #FD6941);
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(253, 105, 65, 0.35);
        transition: all 0.18s ease;
        white-space: nowrap;
    }
    .btn-tasty-add-order:hover {
        background: #e5532b;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(253, 105, 65, 0.45);
        color: #ffffff;
    }

    /* ── 2. Top 4 Metric Cards Strip ── */
    .metric-cards-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    @media (max-width: 1100px) {
        .metric-cards-strip {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 640px) {
        .metric-cards-strip {
            grid-template-columns: 1fr;
        }
    }

    .tasty-metric-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 24px 26px;
        border: 1px solid var(--border-subtle, #ebeef2);
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
    }
    [data-theme="dark"] .tasty-metric-card {
        background: #111513;
        border-color: rgba(255, 255, 255, 0.08);
    }

    .metric-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .metric-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .metric-icon-circle {
        background: #1a201c;
        color: #ffffff;
    }

    .metric-big-num {
        font-size: 32px;
        line-height: 1.1;
        font-weight: 700;
        color: var(--color-dark, #070A08);
        letter-spacing: -0.02em;
        margin-top: 14px;
    }
    [data-theme="dark"] .metric-big-num {
        color: #ffffff;
    }

    .metric-label {
        font-size: 13.5px;
        font-weight: 500;
        color: var(--color-gray, #888E89);
        margin-top: 4px;
    }

    /* Card 4: Today Order Complete Milestone Bar */
    .today-complete-card {
        position: relative;
    }
    .today-complete-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .today-complete-title {
        color: #ffffff;
    }
    .today-complete-ratio {
        font-size: 28px;
        font-weight: 800;
        color: var(--color-dark, #070A08);
        letter-spacing: -0.02em;
    }
    [data-theme="dark"] .today-complete-ratio {
        color: #ffffff;
    }
    .milestone-ticks-row {
        display: flex;
        justify-content: space-between;
        margin-top: 18px;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 700;
        color: var(--color-gray, #888E89);
    }
    .milestone-bar-wrap {
        position: relative;
        height: 10px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
    }
    [data-theme="dark"] .milestone-bar-wrap {
        background: #1a201c;
    }
    .milestone-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #FDE68A 0%, #F2AC11 100%);
        border-radius: 9999px;
        transition: width 0.4s ease;
    }

    /* ── 3. Active Order Container Card ── */
    .active-orders-section {
        background: #ffffff;
        border-radius: 28px;
        padding: 28px;
        border: 1px solid var(--border-subtle, #ebeef2);
        box-shadow: 0 2px 14px rgba(0, 0, 0, 0.02);
    }
    [data-theme="dark"] .active-orders-section {
        background: #111513;
        border-color: rgba(255, 255, 255, 0.08);
    }

    .active-orders-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .active-orders-title {
        font-size: 22px;
        line-height: 1.2;
        font-weight: 700;
        color: var(--color-dark, #070A08);
        margin: 0;
    }
    [data-theme="dark"] .active-orders-title {
        color: #ffffff;
    }

    .active-orders-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .search-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .search-input-field {
        height: 40px;
        padding: 0 16px 0 38px;
        border-radius: 9999px;
        border: 1px solid var(--border-subtle, #ebeef2);
        background: #f8fafc;
        font-size: 13px;
        color: var(--color-dark, #070A08);
        outline: none;
        width: 220px;
        transition: all 0.15s ease;
    }
    [data-theme="dark"] .search-input-field {
        background: #1a201c;
        border-color: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }
    .search-input-field:focus {
        border-color: var(--color-coral, #FD6941);
        background: #ffffff;
        width: 260px;
    }
    .search-input-icon {
        position: absolute;
        left: 12px;
        color: var(--color-gray, #888E89);
        pointer-events: none;
    }

    .filter-stage-pill {
        height: 40px;
        padding: 0 16px;
        border-radius: 9999px;
        background: #f8fafc;
        border: 1px solid var(--border-subtle, #ebeef2);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    [data-theme="dark"] .filter-stage-pill {
        background: #1a201c;
        border-color: rgba(255, 255, 255, 0.08);
        color: #9ca3af;
    }
    .filter-stage-pill.active {
        background: var(--color-dark, #070A08);
        color: #ffffff;
        border-color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .filter-stage-pill.active {
        background: #ffffff;
        color: #070A08;
    }

    /* ── 4. The 3-Column Order Cards Grid ── */
    .order-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
    @media (max-width: 1200px) {
        .order-cards-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 768px) {
        .order-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ── 5. Individual Order Card (Mockup Exact Duplicate) ── */
    .tasty-order-card {
        background: #ffffff;
        border: 1.5px solid #F0F2F5;
        border-radius: 24px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
    }
    [data-theme="dark"] .tasty-order-card {
        background: #151a17;
        border-color: rgba(255, 255, 255, 0.08);
    }
    .tasty-order-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 28px rgba(7, 10, 8, 0.06);
        border-color: #cbd5e1;
    }

    /* Card Header: Order 002 + Status Pill */
    .card-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .card-order-code {
        font-size: 18px;
        font-weight: 700;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .card-order-code {
        color: #ffffff;
    }

    .status-pill-tasty {
        padding: 5px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.01em;
        text-transform: capitalize;
    }
    .status-pill-tasty.preparing {
        background: #FFF3EB;
        color: var(--color-peach, #F08D45);
    }
    .status-pill-tasty.ready, .status-pill-tasty.confirmed {
        background: #EBFBF0;
        color: var(--color-green, #36D161);
    }
    .status-pill-tasty.pending {
        background: #FFF8E7;
        color: var(--color-yellow, #F2AC11);
    }
    .status-pill-tasty.out_for_delivery {
        background: #F5F3FF;
        color: #7C3AED;
    }
    .status-pill-tasty.delivered {
        background: #EBFBF0;
        color: #36D161;
    }

    /* Card Middle Row: Meta Grid (2x2) + Circular Work-Time Gauge */
    .card-middle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 16px;
        border-bottom: 1px dashed #E5E7EB;
        margin-bottom: 16px;
    }
    [data-theme="dark"] .card-middle-row {
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }

    .card-meta-grid {
        display: grid;
        grid-template-columns: auto auto;
        gap: 8px 14px;
        font-size: 13px;
    }
    .card-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #4b5563;
        font-weight: 500;
        white-space: nowrap;
    }
    [data-theme="dark"] .card-meta-item {
        color: #9ca3af;
    }
    .card-meta-item strong {
        font-weight: 700;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .card-meta-item strong {
        color: #ffffff;
    }

    /* Circular Timer Gauge */
    .circular-timer-wrap {
        position: relative;
        width: 68px;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .circular-timer-text {
        position: absolute;
        text-align: center;
        line-height: 1.1;
    }
    .circular-timer-val {
        font-size: 13px;
        font-weight: 800;
        color: var(--color-dark, #070A08);
        display: block;
    }
    [data-theme="dark"] .circular-timer-val {
        color: #ffffff;
    }
    .circular-timer-lbl {
        font-size: 9px;
        font-weight: 500;
        color: var(--color-gray, #888E89);
        display: block;
    }

    /* Items Breakdown Table */
    .card-items-table {
        width: 100%;
        margin-bottom: 16px;
    }
    .card-items-header {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        font-weight: 700;
        color: var(--color-gray, #888E89);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .card-item-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 6px 0;
        font-size: 13.5px;
        border-bottom: 1px solid #F3F4F6;
    }
    [data-theme="dark"] .card-item-row {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }
    .card-item-row:last-child {
        border-bottom: none;
    }
    .card-item-name {
        font-weight: 600;
        color: var(--color-dark, #070A08);
        display: flex;
        flex-direction: column;
    }
    [data-theme="dark"] .card-item-name {
        color: #f3f4f6;
    }
    .card-item-qty {
        font-size: 11.5px;
        font-weight: 500;
        color: var(--color-gray, #888E89);
        margin-top: 1px;
    }
    .card-item-price {
        font-weight: 700;
        color: var(--color-dark, #070A08);
        white-space: nowrap;
    }
    [data-theme="dark"] .card-item-price {
        color: #ffffff;
    }

    /* Total Amount Row */
    .card-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0 16px;
        border-top: 1px solid #E5E7EB;
        margin-top: auto;
    }
    [data-theme="dark"] .card-total-row {
        border-top-color: rgba(255, 255, 255, 0.08);
    }
    .card-total-label {
        font-size: 15px;
        font-weight: 700;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .card-total-label {
        color: #ffffff;
    }
    .card-total-amount {
        font-size: 18px;
        font-weight: 800;
        color: var(--color-dark, #070A08);
    }
    [data-theme="dark"] .card-total-amount {
        color: #ffffff;
    }

    /* Action Buttons Row */
    .card-actions-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .btn-tasty-primary {
        padding: 11px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        text-align: center;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-tasty-primary:hover {
        opacity: 0.92;
        transform: translateY(-1px);
    }
    .btn-tasty-primary.btn-accept {
        background: var(--color-coral, #FD6941);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(253, 105, 65, 0.25);
    }
    .btn-tasty-primary.btn-ready {
        background: var(--color-yellow, #F2AC11);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(242, 172, 17, 0.25);
    }
    .btn-tasty-primary.btn-complete {
        background: var(--color-dark, #070A08);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(7, 10, 8, 0.25);
    }
    [data-theme="dark"] .btn-tasty-primary.btn-complete {
        background: #ffffff;
        color: #070A08;
    }

    .btn-tasty-secondary {
        padding: 11px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        background: #f8fafc;
        border: 1px solid #E2E8F0;
        color: var(--color-dark, #070A08);
        cursor: pointer;
        text-align: center;
        transition: all 0.15s ease;
        white-space: nowrap;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    [data-theme="dark"] .btn-tasty-secondary {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
    .btn-tasty-secondary:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* ── 6. Drawer Overlay for Full Order Workbench ── */
    .tasty-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(7, 10, 8, 0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: flex-end;
    }
    .tasty-drawer-backdrop.show {
        display: flex;
    }
    .tasty-drawer-panel {
        width: 480px;
        max-width: 100vw;
        height: 100vh;
        background: #ffffff;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        padding: 28px;
        box-sizing: border-box;
    }
    [data-theme="dark"] .tasty-drawer-panel {
        background: #111513;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
    }
    .drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 20px;
        border-bottom: 1px solid #E5E7EB;
        margin-bottom: 20px;
    }
    .drawer-close-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f1f5f9;
        border: none;
        font-size: 16px;
        color: #64748b;
        cursor: pointer;
    }
    [data-theme="dark"] .drawer-close-btn {
        background: #1a201c;
        color: #ffffff;
    }

    /* Empty state */
    .empty-orders-view {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: var(--color-gray, #888E89);
    }
</style>

<div class="order-mgmt-container">

    <!-- 1. TOP PAGE HEADER ROW -->
    <div class="order-mgmt-header">
        <h1 class="order-mgmt-title">Order Management</h1>

        <div class="order-mgmt-actions">
            <!-- Kitchen Audio Chime Toggle -->
            <button id="btn-sound-toggle" onclick="toggleKitchenChime()" class="btn-tasty-sound" title="Toggle Kitchen Audio Chime">
                <span id="sound-icon">🔔</span>
                <span id="sound-label">Chime: ON</span>
            </button>

            <!-- Download Archive CSV -->
            <a href="{{ route('dashboard.download-daily-archive', [$restaurant->id, 'date' => now()->toDateString()]) }}" 
               class="btn-tasty-export" 
               title="Export Today's CSV Archive">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </a>

            <!-- + Add new Order Button -->
            <a href="{{ route('dashboard.menu', $restaurant->id) }}?pos=1" class="btn-tasty-add-order" title="Open POS / Add New Order">
                <span style="font-size: 18px; line-height: 1; font-weight: 800;">+</span>
                <span>Add new Order</span>
            </a>
        </div>
    </div>

    <!-- 2. TOP 4 METRIC CARDS STRIP -->
    <div class="metric-cards-strip">
        <!-- Card 1: Total Pending Orders -->
        <div class="tasty-metric-card" onclick="filterLiveStage('pending')" style="cursor: pointer;">
            <div class="metric-top-row">
                <div class="metric-icon-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="metric-big-num" id="pipe-pending">{{ $statusCounts['pending'] ?? $pendingCount }}</div>
                <div class="metric-label">Total Pending Orders</div>
            </div>
        </div>

        <!-- Card 2: Preparing Orders -->
        <div class="tasty-metric-card" onclick="filterLiveStage('preparing')" style="cursor: pointer;">
            <div class="metric-top-row">
                <div class="metric-icon-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 13.8V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v9.8"/>
                        <path d="M4 10h16"/>
                        <path d="M12 22a7 7 0 0 0 7-7H5a7 7 0 0 0 7 7z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="metric-big-num" id="pipe-preparing">{{ $statusCounts['preparing'] ?? $preparingCount }}</div>
                <div class="metric-label">Preparing Orders</div>
            </div>
        </div>

        <!-- Card 3: Ready to serve -->
        <div class="tasty-metric-card" onclick="filterLiveStage('ready')" style="cursor: pointer;">
            <div class="metric-top-row">
                <div class="metric-icon-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"/>
                        <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"/>
                        <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"/>
                        <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="metric-big-num" id="pipe-ready">{{ $statusCounts['ready'] ?? $statusCounts['confirmed'] ?? 0 }}</div>
                <div class="metric-label">Ready to serve</div>
            </div>
        </div>

        <!-- Card 4: Today Order Complete -->
        @php
            $todayTotalOrders = $todayOrders->count();
            $todayDelivered   = $statusCounts['delivered'] ?? 0;
            $fillPercent      = $todayTotalOrders > 0 ? min(100, round(($todayDelivered / $todayTotalOrders) * 100)) : ($todayDelivered > 0 ? 100 : 0);
        @endphp
        <div class="tasty-metric-card today-complete-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div class="today-complete-title">Today Order Complete</div>
                <div class="today-complete-ratio">
                    <span id="metric-delivered-count">{{ $todayDelivered }}</span><span style="color:var(--color-gray, #888E89); font-weight:400; margin: 0 1px;">/</span><span id="metric-total-count">{{ $todayTotalOrders > 0 ? $todayTotalOrders : max(1, $todayDelivered) }}</span>
                </div>
            </div>

            <div class="milestone-ticks-row">
                <span>33%</span>
                <span>60%</span>
                <span>100%</span>
            </div>

            <div class="milestone-bar-wrap">
                <div class="milestone-bar-fill" id="milestone-progress-fill" style="width: {{ $fillPercent }}%;"></div>
            </div>
        </div>
    </div>

    <!-- 3. ACTIVE ORDER SECTION -->
    <div class="active-orders-section">
        <div class="active-orders-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 class="active-orders-title">Active Order</h2>
                <span id="activeStageLabel" style="font-size: 13px; font-weight: 700; color: var(--color-coral, #FD6941);"></span>
            </div>

            <div class="active-orders-controls">
                <div class="search-input-wrap">
                    <svg class="search-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="liveSearchInput" placeholder="Search orders..." oninput="handleLiveSearch(this.value)" class="search-input-field">
                </div>

                <button type="button" class="filter-stage-pill" onclick="filterLiveStage('all')" id="btn-filter-all" title="Show All">
                    All
                </button>
                <button type="button" class="filter-stage-pill" onclick="filterLiveStage('pending')" id="btn-filter-pending" title="Show Pending">
                    Pending
                </button>
                <button type="button" class="filter-stage-pill" onclick="filterLiveStage('preparing')" id="btn-filter-preparing" title="Show Preparing">
                    Preparing
                </button>
                <button type="button" class="filter-stage-pill" onclick="filterLiveStage('ready')" id="btn-filter-ready" title="Show Ready">
                    Ready
                </button>
            </div>
        </div>

        <!-- 3-Column Order Cards Grid -->
        <div class="order-cards-grid" id="live-orders-grid">
            @forelse($orders as $o)
                @php
                    $isPending   = ($o->status === 'pending');
                    $isPreparing = ($o->status === 'preparing');
                    $isReady     = ($o->status === 'confirmed' || $o->status === 'ready');
                    $isDelivery  = ($o->status === 'out_for_delivery');

                    $statusClass = $isPreparing ? 'preparing' : ($isReady ? 'ready' : ($isDelivery ? 'out_for_delivery' : 'pending'));
                    $statusLabel = $o->status_label ?: ucfirst($o->status);
                    $ringColor   = $isPreparing ? '#F2AC11' : ($isReady ? '#36D161' : ($isDelivery ? '#7C3AED' : '#FD6941'));
                    $workTime    = $o->created_at ? $o->created_at->format('H:i') : '12:34';
                    $orderCode   = 'Order ' . str_pad($o->daily_order_number ?: $o->id, 3, '0', STR_PAD_LEFT);
                @endphp
                <div class="tasty-order-card" id="order-card-{{ $o->id }}" data-order-id="{{ $o->id }}">
                    <div>
                        <!-- Top Row: Code + Pill -->
                        <div class="card-header-row">
                            <span class="card-order-code">{{ $orderCode }}</span>
                            <span class="status-pill-tasty {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <!-- Middle Row: 2x2 Meta + Circular Gauge -->
                        <div class="card-middle-row">
                            <div class="card-meta-grid">
                                <div class="card-meta-item">
                                    <span>🏷️</span>
                                    <span>{{ $o->delivery_address ? 'Delivery' : 'Dine in' }}</span>
                                </div>
                                <div class="card-meta-item">
                                    <span>👥</span>
                                    <span>Online Order</span>
                                </div>
                                <div class="card-meta-item">
                                    <span>🕒</span>
                                    <span>Time <strong>{{ $o->created_at ? $o->created_at->format('g:i') : '2:34' }}</strong></span>
                                </div>
                                <div class="card-meta-item">
                                    <span>👤</span>
                                    <span>{{ $o->customer_name ?: 'John doe' }}</span>
                                </div>
                            </div>

                            <!-- Circular Timer Gauge -->
                            <div class="circular-timer-wrap">
                                <svg width="68" height="68" viewBox="0 0 68 68">
                                    <circle cx="34" cy="34" r="28" stroke="#F1F5F9" stroke-width="4.5" fill="none"/>
                                    <circle cx="34" cy="34" r="28" stroke="{{ $ringColor }}" stroke-width="4.5" stroke-dasharray="176" stroke-dashoffset="40" fill="none" stroke-linecap="round" transform="rotate(-90 34 34)"/>
                                </svg>
                                <div class="circular-timer-text">
                                    <span class="circular-timer-val">{{ $workTime }}</span>
                                    <span class="circular-timer-lbl">Work time</span>
                                </div>
                            </div>
                        </div>

                        <!-- Items Breakdown Table -->
                        <div class="card-items-table">
                            <div class="card-items-header">
                                <span>Order Item</span>
                                <span>Amount</span>
                            </div>
                            @foreach($o->items->take(3) as $it)
                                <div class="card-item-row">
                                    <div class="card-item-name">
                                        <span>{{ $it->name ?: $it->item_name }}</span>
                                        <span class="card-item-qty">Qty {{ $it->quantity }}</span>
                                    </div>
                                    <span class="card-item-price">PKR {{ number_format($it->subtotal) }}</span>
                                </div>
                            @endforeach
                            @if($o->items->count() > 3)
                                <div style="font-size: 11.5px; color: var(--color-gray, #888E89); padding-top: 4px;">
                                    +{{ $o->items->count() - 3 }} more item(s)
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <!-- Total Amount -->
                        <div class="card-total-row">
                            <span class="card-total-label">Total Amount</span>
                            <span class="card-total-amount">PKR {{ number_format($o->total) }}</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-actions-row">
                            @if($isPending)
                                <button type="button" class="btn-tasty-primary btn-accept" onclick="openConfirmOrderModal('{{ $o->id }}')">
                                    Accept
                                </button>
                            @elseif($isPreparing)
                                <button type="button" class="btn-tasty-primary btn-ready" onclick="ajaxUpdateStatus('{{ route('dashboard.update-status', [$restaurant->id, $o->id]) }}', 'confirmed', this)">
                                    Mark Ready
                                </button>
                            @elseif($isReady)
                                <button type="button" class="btn-tasty-primary btn-complete" onclick="openDispatchModal('{{ $o->id }}', '{{ $o->tracking_code }}', '{{ addslashes($o->customer_name) }}', '{{ addslashes($o->delivery_address ?: '') }}')">
                                    Mark Complete
                                </button>
                            @elseif($isDelivery)
                                <button type="button" class="btn-tasty-primary" style="background:#36D161; color:#fff;" onclick="ajaxUpdateStatus('{{ route('dashboard.update-status', [$restaurant->id, $o->id]) }}', 'delivered', this)">
                                    Mark Delivered
                                </button>
                            @else
                                <button type="button" class="btn-tasty-primary" style="background:#070A08; color:#fff;">
                                    Completed
                                </button>
                            @endif

                            <button type="button" class="btn-tasty-secondary" onclick="openOrderDetailDrawer('{{ $o->id }}')">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-orders-view" id="empty-orders-state">
                    <div style="font-size: 40px; margin-bottom: 8px;">🍽️</div>
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--color-dark, #070A08);">No Active Orders Right Now</h3>
                    <p style="font-size: 13.5px; margin-top: 4px;">Orders placed by customers will arrive here automatically in real-time.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- 4. ORDER DETAILS WORKBENCH DRAWER -->
<div id="orderDrawerModal" class="tasty-drawer-backdrop" onclick="closeOrderDetailDrawer(event)">
    <div class="tasty-drawer-panel" onclick="event.stopPropagation()">
        <div class="drawer-header">
            <div>
                <h3 id="drawerOrderTitle" style="font-size: 20px; font-weight: 800; color: var(--color-dark, #070A08); margin: 0;">Order #0</h3>
                <p id="drawerOrderSub" style="font-size: 13px; color: var(--color-gray, #888E89); margin: 3px 0 0 0;">Placed at --</p>
            </div>
            <button type="button" class="drawer-close-btn" onclick="closeOrderDetailDrawer()">✕</button>
        </div>

        <!-- Customer & Contact Box -->
        <div style="background: #f8fafc; border: 1px solid var(--border-subtle, #ebeef2); border-radius: 16px; padding: 16px; margin-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b;">CUSTOMER</span>
                <a id="drawerWhatsAppLink" href="#" target="_blank" style="font-size: 13px; color: #16a34a; font-weight: 700; text-decoration: none;">
                    💬 Open WhatsApp Chat
                </a>
            </div>
            <div id="drawerCustomerName" style="font-size: 16px; font-weight: 800; color: var(--color-dark, #070A08);">Customer Name</div>
            <div id="drawerCustomerPhone" style="font-size: 13px; color: #64748b; margin-top: 2px;">03001234567</div>

            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b;">DELIVERY ADDRESS</span>
                <div id="drawerAddress" style="font-size: 13px; font-weight: 600; color: #070A08; margin-top: 2px;">Lahore, Pakistan</div>
            </div>
        </div>

        <!-- Order Items Breakdown -->
        <div style="margin-bottom: 20px;">
            <div style="font-size: 13px; font-weight: 700; color: var(--color-gray, #888E89); margin-bottom: 10px; text-transform: uppercase;">
                ORDER ITEMS
            </div>
            <div id="drawerItemsList" style="display: flex; flex-direction: column; gap: 8px;"></div>

            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 16px; font-weight: 700; color: var(--color-dark, #070A08);">Total Bill:</span>
                <span id="drawerTotalBill" style="font-size: 20px; font-weight: 800; color: var(--color-coral, #FD6941);">PKR 0</span>
            </div>
        </div>

        <!-- Rider Info -->
        <div style="background: #f8fafc; border: 1px solid var(--border-subtle, #ebeef2); border-radius: 16px; padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px;">ASSIGNED RIDER</div>
            <div id="drawerRiderName" style="font-size: 14px; font-weight: 800; color: var(--color-dark, #070A08);">No Rider Assigned</div>
            <div id="drawerRiderPhone" style="font-size: 12.5px; color: #64748b; margin-top: 2px;">Assign rider before dispatch</div>
        </div>

        <!-- Action Buttons in Drawer -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
            <a id="drawerPrintLink" href="#" target="_blank" class="btn-tasty-secondary" style="width: 100%; box-sizing: border-box;">
                🖨️ Print Parcel Bill / Receipt
            </a>
            <div id="drawerDynamicActions" style="display: flex; gap: 10px;"></div>
        </div>
    </div>
</div>

<!-- 5. CONFIRM ORDER MODAL -->
<div id="confirmOrderModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(7, 10, 8, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 460px; max-width: calc(100% - 32px); border-radius: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); overflow: hidden; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--color-dark, #070A08); display: flex; align-items: center; gap: 8px; margin: 0;">
                <span>✅ Accept Order</span>
                <span id="confirmModalCode" style="font-size: 14px; color: var(--color-coral, #FD6941); font-weight: 700;">#0</span>
            </h3>
            <button type="button" onclick="closeConfirmOrderModal()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
        </div>

        <div style="background: #f8fafc; border: 1px solid var(--border-subtle, #ebeef2); border-radius: 14px; padding: 14px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Customer:</span>
                <span id="confirmModalCustomer" style="font-size: 13px; font-weight: 700; color: var(--color-dark, #070A08);">Customer Name</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 6px;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Delivery Address:</span>
                <span id="confirmModalAddress" style="font-size: 12px; font-weight: 600; color: #334155; text-align: right; word-break: break-word; max-width: 260px;">Address</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 6px; margin-top: 6px;">
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Food Subtotal:</span>
                <span id="confirmModalSubtotal" style="font-size: 13px; font-weight: 800; color: var(--color-dark, #070A08);">Rs. 0</span>
            </div>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: var(--color-dark, #070A08); margin-bottom: 8px;">
                Select Delivery Charges:
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                <button type="button" class="filter-stage-pill active" id="btnDelivFree" onclick="selectDeliveryCharge(0)" style="justify-content: center;">
                    🟢 Free (Rs. 0)
                </button>
                <button type="button" class="filter-stage-pill" id="btnDelivStandard" onclick="selectDeliveryCharge(250)" style="justify-content: center;">
                    🟡 Rs. 250
                </button>
                <button type="button" class="filter-stage-pill" id="btnDelivCustom" onclick="enableCustomDeliveryCharge()" style="justify-content: center;">
                    ✏️ Custom
                </button>
            </div>
            <div id="customDeliveryChargeRow" style="display: none; margin-top: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: #64748b;">Rs.</span>
                    <input type="number" id="inputCustomDeliveryCharge" min="0" max="50000" placeholder="e.g. 100"
                        oninput="onCustomDeliveryInput(this.value)"
                        style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #070A08; font-size: 13px; font-weight: 700;">
                </div>
            </div>
        </div>

        <div style="background: #fff8e7; border: 1px solid #fde68a; border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 13px; font-weight: 700; color: #b45309;">Total Bill to Collect:</span>
            <span id="confirmModalTotalPreview" style="font-size: 18px; font-weight: 800; color: var(--color-coral, #FD6941);">Rs. 0</span>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeConfirmOrderModal()" style="padding: 10px 18px; border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; font-weight: 700; cursor: pointer; color: #475569;">Cancel</button>
            <button type="button" id="btnSubmitConfirmOrder" onclick="submitConfirmOrder()" style="padding: 10px 22px; border-radius: 12px; border: none; background: var(--color-coral, #FD6941); color: #ffffff; font-weight: 700; cursor: pointer;">
                ✓ Accept & Send WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- 6. DISPATCH MODAL -->
<div id="dispatchModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(7, 10, 8, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 440px; max-width: calc(100% - 32px); border-radius: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); overflow: hidden;">
        <div style="background: var(--color-dark, #070A08); padding: 18px 22px; color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 22px;">🛵</span>
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; margin: 0;">Dispatch to Delivery Rider</h3>
                    <p style="font-size: 11.5px; color: #9ca3af; margin: 2px 0 0 0;">Order <strong id="dispatchOrderCode"></strong> • <span id="dispatchCustomerName"></span></p>
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
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">Rider Notes</label>
                    <input type="text" name="rider_notes" placeholder="e.g. Call customer" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px;">
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #64748b;">
                <span style="font-weight: 700; color: #070A08;">📍 Deliver to:</span>
                <span id="dispatchAddress"></span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeDispatchModal()" style="padding: 9px 16px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 12.5px; font-weight: 700; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 9px 20px; border-radius: 10px; border: none; background: var(--color-coral, #FD6941); color: #ffffff; font-size: 12.5px; font-weight: 700; cursor: pointer;">Confirm & Dispatch 🛵</button>
            </div>
        </form>
    </div>
</div>

<script>
    const RESTAURANT_ID     = '{{ $restaurant->id }}';
    const LIVE_FEED_URL     = '/dashboard/' + RESTAURANT_ID + '/orders/live-feed';
    const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let currentOrdersMap = {};
    let currentLiveStageFilter = 'all';
    let searchQuery = '';

    const ACTIVE_LIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'out_for_delivery'];

    @foreach($orders as $ord)
    currentOrdersMap[{{ $ord->id }}] = {
        id: {{ $ord->id }},
        daily_order_number: {{ (int) ($ord->daily_order_number ?: $ord->id) }},
        tracking_code: '{{ $ord->tracking_code }}',
        status: '{{ $ord->status }}',
        status_label: '{{ $ord->status_label }}',
        total: {{ (float) $ord->total }},
        customer_name: '{{ addslashes($ord->customer_name ?: 'John doe') }}',
        customer_phone: '{{ $ord->customer_phone ?: '' }}',
        created_at_time: '{{ $ord->created_at ? $ord->created_at->format('H:i') : '12:34' }}',
        created_at_humans: '{{ $ord->created_at ? $ord->created_at->diffForHumans() : '' }}',
        rider_name: '{{ addslashes($ord->rider_name ?? '') }}',
        rider_phone: '{{ addslashes($ord->rider_phone ?? '') }}',
        delivery_address: '{{ addslashes($ord->delivery_address ?: '') }}',
        estimated_minutes: {{ $ord->estimated_minutes ?? 25 }},
        delivery_fee: {{ (float) ($restaurant->delivery_charge ?? 0) }},
        items: [
            @foreach($ord->items as $it)
            {
                name: '{{ addslashes($it->name ?: $it->item_name) }}',
                quantity: {{ (int) $it->quantity }},
                subtotal: {{ (float) $it->subtotal }}
            },
            @endforeach
        ]
    };
    @endforeach

    function escJs(s) {
        return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Render Individual Order Card (Mockup Exact Match) ── */
    function renderOrderCard(o) {
        const isPending   = (o.status === 'pending');
        const isPreparing = (o.status === 'preparing');
        const isReady     = (o.status === 'confirmed' || o.status === 'ready');
        const isDelivery  = (o.status === 'out_for_delivery');

        const statusClass = isPreparing ? 'preparing' : (isReady ? 'ready' : (isDelivery ? 'out_for_delivery' : 'pending'));
        const statusLabel = o.status_label || (isPreparing ? 'Preparing' : (isReady ? 'Ready' : (isDelivery ? 'Out for delivery' : 'Pending')));
        const ringColor   = isPreparing ? '#F2AC11' : (isReady ? '#36D161' : (isDelivery ? '#7C3AED' : '#FD6941'));
        const orderNum    = 'Order ' + String(o.daily_order_number || o.id).padStart(3, '0');
        const workTime    = o.created_at_time || '12:34';

        let primaryBtnHtml = '';
        if (isPending) {
            primaryBtnHtml = `<button type="button" class="btn-tasty-primary btn-accept" onclick="openConfirmOrderModal('${o.id}')">Accept</button>`;
        } else if (isPreparing) {
            primaryBtnHtml = `<button type="button" class="btn-tasty-primary btn-ready" onclick="ajaxUpdateStatus('/dashboard/${RESTAURANT_ID}/orders/${o.id}/status', 'confirmed', this)">Mark Ready</button>`;
        } else if (isReady) {
            primaryBtnHtml = `<button type="button" class="btn-tasty-primary btn-complete" onclick="openDispatchModal('${o.id}', '${o.tracking_code}', '${escJs(o.customer_name)}', '${escJs(o.delivery_address)}')">Mark Complete</button>`;
        } else if (isDelivery) {
            primaryBtnHtml = `<button type="button" class="btn-tasty-primary" style="background:#36D161; color:#fff;" onclick="ajaxUpdateStatus('/dashboard/${RESTAURANT_ID}/orders/${o.id}/status', 'delivered', this)">Mark Delivered</button>`;
        } else {
            primaryBtnHtml = `<button type="button" class="btn-tasty-primary" style="background:#070A08; color:#fff;">Completed</button>`;
        }

        const items = o.items || [];
        const itemsHtml = items.slice(0, 3).map(it => `
            <div class="card-item-row">
                <div class="card-item-name">
                    <span>${escHtml(it.name)}</span>
                    <span class="card-item-qty">Qty ${it.quantity}</span>
                </div>
                <span class="card-item-price">PKR ${Number(it.subtotal).toLocaleString()}</span>
            </div>
        `).join('') + (items.length > 3 ? `<div style="font-size:11.5px;color:var(--color-gray,#888E89);padding-top:4px;">+${items.length - 3} more item(s)</div>` : '');

        return `
        <div class="tasty-order-card" id="order-card-${o.id}" data-order-id="${o.id}">
            <div>
                <div class="card-header-row">
                    <span class="card-order-code">${orderNum}</span>
                    <span class="status-pill-tasty ${statusClass}">${statusLabel}</span>
                </div>

                <div class="card-middle-row">
                    <div class="card-meta-grid">
                        <div class="card-meta-item">
                            <span>🏷️</span>
                            <span>${escHtml(o.delivery_address ? 'Delivery' : 'Dine in')}</span>
                        </div>
                        <div class="card-meta-item">
                            <span>👥</span>
                            <span>Online Order</span>
                        </div>
                        <div class="card-meta-item">
                            <span>🕒</span>
                            <span>Time <strong>${escHtml(workTime)}</strong></span>
                        </div>
                        <div class="card-meta-item">
                            <span>👤</span>
                            <span>${escHtml(o.customer_name || 'John doe')}</span>
                        </div>
                    </div>

                    <div class="circular-timer-wrap">
                        <svg width="68" height="68" viewBox="0 0 68 68">
                            <circle cx="34" cy="34" r="28" stroke="#F1F5F9" stroke-width="4.5" fill="none"/>
                            <circle cx="34" cy="34" r="28" stroke="${ringColor}" stroke-width="4.5" stroke-dasharray="176" stroke-dashoffset="40" fill="none" stroke-linecap="round" transform="rotate(-90 34 34)"/>
                        </svg>
                        <div class="circular-timer-text">
                            <span class="circular-timer-val">${escHtml(workTime)}</span>
                            <span class="circular-timer-lbl">Work time</span>
                        </div>
                    </div>
                </div>

                <div class="card-items-table">
                    <div class="card-items-header">
                        <span>Order Item</span>
                        <span>Amount</span>
                    </div>
                    ${itemsHtml}
                </div>
            </div>

            <div>
                <div class="card-total-row">
                    <span class="card-total-label">Total Amount</span>
                    <span class="card-total-amount">PKR ${Number(o.total).toLocaleString()}</span>
                </div>

                <div class="card-actions-row">
                    ${primaryBtnHtml}
                    <button type="button" class="btn-tasty-secondary" onclick="openOrderDetailDrawer('${o.id}')">
                        View Details
                    </button>
                </div>
            </div>
        </div>`;
    }

    function applyLiveOrdersFilter() {
        const grid = document.getElementById('live-orders-grid');
        if (!grid) return;

        let orders = Object.values(currentOrdersMap).sort((a,b) => b.id - a.id);

        // Stage filter
        if (currentLiveStageFilter === 'pending') {
            orders = orders.filter(o => o.status === 'pending');
        } else if (currentLiveStageFilter === 'preparing') {
            orders = orders.filter(o => o.status === 'preparing');
        } else if (currentLiveStageFilter === 'ready') {
            orders = orders.filter(o => o.status === 'confirmed' || o.status === 'ready');
        }

        // Search query
        if (searchQuery.trim() !== '') {
            const q = searchQuery.toLowerCase().trim();
            orders = orders.filter(o => 
                String(o.daily_order_number || o.id).includes(q) ||
                (o.customer_name || '').toLowerCase().includes(q) ||
                (o.customer_phone || '').includes(q)
            );
        }

        // Highlight active filter pills
        document.querySelectorAll('.filter-stage-pill').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById('btn-filter-' + currentLiveStageFilter);
        if (activeBtn) activeBtn.classList.add('active');

        const lbl = document.getElementById('activeStageLabel');
        if (lbl) {
            lbl.textContent = currentLiveStageFilter !== 'all' ? `• Filtering: ${currentLiveStageFilter.toUpperCase()}` : '';
        }

        if (orders.length === 0) {
            grid.innerHTML = `
            <div class="empty-orders-view" id="empty-orders-state">
                <div style="font-size: 38px; margin-bottom: 8px;">🔍</div>
                <h3 style="font-size: 17px; font-weight: 700; color: var(--color-dark, #070A08);">No Orders Found</h3>
                <p style="font-size: 13px; margin-top: 4px;">No active orders matching "${currentLiveStageFilter}" filter right now.</p>
            </div>`;
        } else {
            grid.innerHTML = orders.map(o => renderOrderCard(o)).join('');
        }
    }

    function filterLiveStage(stage) {
        currentLiveStageFilter = (currentLiveStageFilter === stage && stage !== 'all') ? 'all' : stage;
        applyLiveOrdersFilter();
    }

    function handleLiveSearch(val) {
        searchQuery = val || '';
        applyLiveOrdersFilter();
    }

    /* ── Details Drawer Controls ── */
    function openOrderDetailDrawer(orderId) {
        const o = currentOrdersMap[orderId];
        if (!o) return;

        document.getElementById('drawerOrderTitle').textContent = 'Order #' + (o.daily_order_number || o.id);
        document.getElementById('drawerOrderSub').textContent   = 'Placed at ' + (o.created_at_time || '--') + ' • ' + (o.created_at_humans || '');
        document.getElementById('drawerCustomerName').textContent  = o.customer_name || 'Guest';
        document.getElementById('drawerCustomerPhone').textContent = o.customer_phone || '';
        document.getElementById('drawerAddress').textContent       = o.delivery_address || 'Dine-in / Pickup';
        document.getElementById('drawerTotalBill').textContent     = 'PKR ' + Number(o.total).toLocaleString();
        document.getElementById('drawerPrintLink').href           = '/dashboard/' + RESTAURANT_ID + '/orders/' + o.id + '/print-bill';

        const waLink = document.getElementById('drawerWhatsAppLink');
        if (waLink) {
            const cleanPhone = (o.customer_phone || '').replace(/\D/g, '');
            waLink.href = cleanPhone ? 'https://wa.me/' + cleanPhone : '#';
        }

        const riderNameEl  = document.getElementById('drawerRiderName');
        const riderPhoneEl = document.getElementById('drawerRiderPhone');
        if (riderNameEl)  riderNameEl.textContent  = o.rider_name || 'No Rider Assigned';
        if (riderPhoneEl) riderPhoneEl.textContent = o.rider_phone ? '📞 ' + o.rider_phone : 'Assign rider before dispatch';

        // Render Items
        const itemsBox = document.getElementById('drawerItemsList');
        if (itemsBox) {
            itemsBox.innerHTML = (o.items || []).map(it => `
                <div style="display:flex; justify-content:space-between; font-size:13.5px; padding: 4px 0; border-bottom: 1px dashed #f1f5f9;">
                    <span><strong>${it.quantity}x</strong> ${escHtml(it.name)}</span>
                    <span style="font-weight:700;">PKR ${Number(it.subtotal).toLocaleString()}</span>
                </div>
            `).join('');
        }

        // Render Dynamic Action Buttons in Drawer
        const actionsBox = document.getElementById('drawerDynamicActions');
        if (actionsBox) {
            if (o.status === 'pending') {
                actionsBox.innerHTML = `<button type="button" class="btn-tasty-primary btn-accept" style="flex:1;" onclick="closeOrderDetailDrawer(); openConfirmOrderModal('${o.id}');">Accept Order</button>`;
            } else if (o.status === 'preparing') {
                actionsBox.innerHTML = `<button type="button" class="btn-tasty-primary btn-ready" style="flex:1;" onclick="ajaxUpdateStatus('/dashboard/${RESTAURANT_ID}/orders/${o.id}/status', 'confirmed', this)">Mark as Ready</button>`;
            } else if (o.status === 'confirmed' || o.status === 'ready') {
                actionsBox.innerHTML = `<button type="button" class="btn-tasty-primary btn-complete" style="flex:1;" onclick="closeOrderDetailDrawer(); openDispatchModal('${o.id}', '${o.tracking_code}', '${escJs(o.customer_name)}', '${escJs(o.delivery_address)}');">Dispatch to Rider</button>`;
            } else if (o.status === 'out_for_delivery') {
                actionsBox.innerHTML = `<button type="button" class="btn-tasty-primary" style="flex:1; background:#36D161; color:#fff;" onclick="ajaxUpdateStatus('/dashboard/${RESTAURANT_ID}/orders/${o.id}/status', 'delivered', this)">Mark as Delivered</button>`;
            } else {
                actionsBox.innerHTML = '';
            }
        }

        document.getElementById('orderDrawerModal').classList.add('show');
    }

    function closeOrderDetailDrawer(e) {
        if (!e || e.target === document.getElementById('orderDrawerModal') || e.currentTarget?.classList.contains('drawer-close-btn')) {
            document.getElementById('orderDrawerModal').classList.remove('show');
        }
    }

    /* ── Confirm Order Modal Logic ── */
    let currentConfirmOrderId = null;
    let confirmFoodSubtotal   = 0;
    let selectedDeliveryFee   = 0;

    function openConfirmOrderModal(orderId) {
        currentConfirmOrderId = orderId;
        const o = currentOrdersMap[orderId];
        if (!o) return;

        document.getElementById('confirmModalCode').textContent     = '#' + (o.daily_order_number || o.id);
        document.getElementById('confirmModalCustomer').textContent = o.customer_name || 'Guest Customer';
        document.getElementById('confirmModalAddress').textContent  = o.delivery_address || 'Address from WhatsApp';

        confirmFoodSubtotal = (o.items || []).reduce((sum, it) => sum + (it.subtotal || 0), 0);
        if (confirmFoodSubtotal <= 0) confirmFoodSubtotal = o.total;

        document.getElementById('confirmModalSubtotal').textContent = 'Rs. ' + confirmFoodSubtotal.toLocaleString();

        const restDefaultFee = o.delivery_fee || 0;
        selectDeliveryCharge(restDefaultFee);

        document.getElementById('confirmOrderModal').style.display = 'flex';
    }

    function closeConfirmOrderModal() {
        document.getElementById('confirmOrderModal').style.display = 'none';
        currentConfirmOrderId = null;
    }

    function selectDeliveryCharge(amount) {
        selectedDeliveryFee = parseInt(amount, 10) || 0;
        document.querySelectorAll('#confirmOrderModal .filter-stage-pill').forEach(b => b.classList.remove('active'));

        const customRow = document.getElementById('customDeliveryChargeRow');
        if (amount === 0) {
            document.getElementById('btnDelivFree')?.classList.add('active');
            if (customRow) customRow.style.display = 'none';
        } else if (amount === 250) {
            document.getElementById('btnDelivStandard')?.classList.add('active');
            if (customRow) customRow.style.display = 'none';
        }
        updateConfirmTotalPreview();
    }

    function enableCustomDeliveryCharge() {
        document.querySelectorAll('#confirmOrderModal .filter-stage-pill').forEach(b => b.classList.remove('active'));
        document.getElementById('btnDelivCustom')?.classList.add('active');
        const customRow = document.getElementById('customDeliveryChargeRow');
        if (customRow) customRow.style.display = 'block';
        const input = document.getElementById('inputCustomDeliveryCharge');
        if (input) {
            input.focus();
            selectedDeliveryFee = parseInt(input.value, 10) || 0;
        }
        updateConfirmTotalPreview();
    }

    function onCustomDeliveryInput(val) {
        selectedDeliveryFee = parseInt(val, 10) || 0;
        updateConfirmTotalPreview();
    }

    function updateConfirmTotalPreview() {
        const total = confirmFoodSubtotal + selectedDeliveryFee;
        const preview = document.getElementById('confirmModalTotalPreview');
        if (preview) preview.textContent = 'Rs. ' + total.toLocaleString();
    }

    async function submitConfirmOrder() {
        if (!currentConfirmOrderId) return;
        const btn = document.getElementById('btnSubmitConfirmOrder');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Accepting...';
        }

        try {
            await ajaxUpdateStatus(
                '/dashboard/' + RESTAURANT_ID + '/orders/' + currentConfirmOrderId + '/status',
                'confirmed',
                btn,
                { delivery_charge: selectedDeliveryFee }
            );
            closeConfirmOrderModal();
        } catch (e) {
            console.error(e);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = '✓ Accept & Send WhatsApp';
            }
        }
    }

    /* ── Dispatch Modal Logic ── */
    function openDispatchModal(orderId, orderCode, customerName, address) {
        document.getElementById('dispatchOrderCode').textContent    = '#' + orderCode;
        document.getElementById('dispatchCustomerName').textContent = customerName;
        document.getElementById('dispatchAddress').textContent      = address || 'Address provided in WhatsApp chat';
        document.getElementById('dispatchForm').action              = '/dashboard/' + RESTAURANT_ID + '/orders/' + orderId + '/status';

        const riderSelect  = document.getElementById('riderSelect');
        const customFields = document.getElementById('customRiderFields');
        const nameInput    = document.getElementById('inputRiderName');
        const phoneInput   = document.getElementById('inputRiderPhone');

        if (riderSelect && riderSelect.options.length > 2) {
            riderSelect.selectedIndex = 1;
            const opt = riderSelect.options[1];
            nameInput.value  = opt.value;
            phoneInput.value = opt.getAttribute('data-phone') || '';
            customFields.style.display = 'none';
        } else {
            if (customFields) customFields.style.display = 'block';
        }

        document.getElementById('dispatchModal').style.display = 'flex';
    }

    function handleRiderSelect(select) {
        const customFields = document.getElementById('customRiderFields');
        const nameInput    = document.getElementById('inputRiderName');
        const phoneInput   = document.getElementById('inputRiderPhone');

        if (select.value === '__custom__' || !select.value) {
            customFields.style.display = 'block';
            nameInput.value  = '';
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
        event.preventDefault();
        const form = document.getElementById('dispatchForm');
        const btn  = form.querySelector('button[type="submit"]');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Dispatching...';

        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                closeDispatchModal();
                pollLiveFeed();
            } else {
                alert(data.message || 'Error dispatching order');
            }
        } catch (e) {
            console.error(e);
            alert('Failed to dispatch. Check connection.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
        return false;
    }

    /* ── Universal AJAX Status Transition ── */
    async function ajaxUpdateStatus(url, status, btn, extra = {}) {
        let origHtml = '';
        if (btn) {
            origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Updating...';
        }

        try {
            const bodyPayload = { status: status, ...extra };
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(bodyPayload)
            });

            const data = await res.json();
            if (data.success) {
                pollLiveFeed();
                closeOrderDetailDrawer();
            } else {
                alert(data.message || 'Could not update status');
            }
        } catch (e) {
            console.error(e);
            alert('Network error updating status.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    }

    /* ── Live Real-Time Feed Poller ── */
    async function pollLiveFeed() {
        try {
            const res  = await fetch(LIVE_FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            const allFeedOrders = data.orders ?? [];
            const activeOrders  = allFeedOrders.filter(o => ACTIVE_LIVE_STATUSES.includes(o.status));

            // Update top KPIs
            const pipePend = document.getElementById('pipe-pending');
            const pipePrep = document.getElementById('pipe-preparing');
            const pipeRdy  = document.getElementById('pipe-ready');
            const delivEl  = document.getElementById('metric-delivered-count');
            const totEl    = document.getElementById('metric-total-count');
            const barFill  = document.getElementById('milestone-progress-fill');

            const pendingCount   = allFeedOrders.filter(o => o.status === 'pending').length;
            const preparingCount = allFeedOrders.filter(o => o.status === 'preparing').length;
            const readyCount     = allFeedOrders.filter(o => o.status === 'confirmed' || o.status === 'ready').length;
            const deliveredCount = allFeedOrders.filter(o => o.status === 'delivered').length;
            const totalCount     = allFeedOrders.length;

            if (pipePend) pipePend.textContent = pendingCount;
            if (pipePrep) pipePrep.textContent = preparingCount;
            if (pipeRdy)  pipeRdy.textContent  = readyCount;
            if (delivEl)  delivEl.textContent  = deliveredCount;
            if (totEl)    totEl.textContent    = totalCount > 0 ? totalCount : max(1, deliveredCount);

            if (barFill && totalCount > 0) {
                const pct = Math.min(100, Math.round((deliveredCount / totalCount) * 100));
                barFill.style.width = pct + '%';
            }

            // Sync currentOrdersMap
            currentOrdersMap = {};
            activeOrders.forEach(o => {
                currentOrdersMap[o.id] = {
                    id: o.id,
                    daily_order_number: o.daily_order_number || o.id,
                    tracking_code: o.tracking_code,
                    status: o.status,
                    status_label: o.status_label,
                    total: o.total,
                    customer_name: o.customer_name || 'John doe',
                    customer_phone: o.customer_phone || '',
                    created_at_time: o.created_at_time || '12:34',
                    created_at_humans: o.created_at_humans || '',
                    rider_name: o.rider_name || '',
                    rider_phone: o.rider_phone || '',
                    delivery_address: o.delivery_address || '',
                    items: o.items || []
                };
            });

            applyLiveOrdersFilter();
        } catch (e) {
            console.error('Error polling live feed:', e);
        }
    }

    /* ── Kitchen Audio Chime System ── */
    let kitchenSoundEnabled = (localStorage.getItem('kitchen_sound_active') !== 'false');

    function updateSoundButtonUI() {
        const btn = document.getElementById('btn-sound-toggle');
        const icon = document.getElementById('sound-icon');
        const lbl = document.getElementById('sound-label');
        if (!btn || !icon || !lbl) return;

        if (kitchenSoundEnabled) {
            icon.textContent = '🔔';
            lbl.textContent  = 'Chime: ON';
        } else {
            icon.textContent = '🔕';
            lbl.textContent  = 'Chime: OFF';
        }
    }

    function toggleKitchenChime() {
        kitchenSoundEnabled = !kitchenSoundEnabled;
        localStorage.setItem('kitchen_sound_active', kitchenSoundEnabled ? 'true' : 'false');
        updateSoundButtonUI();
        if (kitchenSoundEnabled) playKitchenChime();
    }

    let audioCtx = null;
    function playKitchenChime() {
        if (!kitchenSoundEnabled) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            if (!audioCtx) audioCtx = new AudioContext();

            const notes = [587.33, 880.00, 1174.66];
            notes.forEach((freq, idx) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime + (idx * 0.12));
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime + (idx * 0.12));
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + (idx * 0.12) + 0.5);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(audioCtx.currentTime + (idx * 0.12));
                osc.stop(audioCtx.currentTime + (idx * 0.12) + 0.5);
            });
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateSoundButtonUI();
        setInterval(pollLiveFeed, 6000);
    });
</script>
@endsection
