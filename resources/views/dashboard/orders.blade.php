@extends('layouts.dashboard')
@section('title', 'Dashboard — ' . ($restaurant->name ?? 'Foodio'))

@section('content')

@php
    $restaurant          = $restaurant ?? ($r ?? null);
    $todayOrders         = $todayOrders ?? ($today ?? collect());
    $orders              = $orders ?? collect();
    $liveOrdersCount     = $liveOrdersCount ?? (isset($liveOrders) ? $liveOrders->count() : 0);
    $todayRevenue        = $todayRevenue ?? 0;
    $totalOrdersToday    = $totalOrdersToday ?? $todayOrders->count();
    $pendingCount        = $pendingCount ?? $todayOrders->where('status', 'pending')->count();
    $statusCounts        = $statusCounts ?? [];
    $unavailableItems    = $unavailableItems ?? collect();
    $waitingRidersOrders = $waitingRidersOrders ?? collect();
    $topSellingItems     = $topSellingItems ?? collect();
    $riders              = $riders ?? collect();
@endphp

<style>
    /* ═════════════════════════════════════════════════════════════
       FOODIO MODERN POS DASHBOARD (CONCEPT UI MATCH)
       ═════════════════════════════════════════════════════════════ */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }

    /* ── 1. TOP METRIC CARDS GRID (4 COLUMNS) ── */
    .metric-cards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .metric-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-card);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-elevated);
    }

    .metric-card-left {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .metric-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 6px;
    }
    .metric-icon-wrap.green  { background: #ecfdf5; color: #059669; }
    .metric-icon-wrap.blue   { background: #f0f9ff; color: #0284c7; }
    .metric-icon-wrap.purple { background: #faf5ff; color: #9333ea; }
    .metric-icon-wrap.orange { background: #fff7ed; color: #ea580c; }

    [data-theme="dark"] .metric-icon-wrap.green  { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    [data-theme="dark"] .metric-icon-wrap.blue   { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
    [data-theme="dark"] .metric-icon-wrap.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
    [data-theme="dark"] .metric-icon-wrap.orange { background: rgba(249, 115, 22, 0.15); color: #fb923c; }

    .metric-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .metric-value {
        font-size: 26px;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.03em;
        line-height: 1.1;
    }

    .metric-trend-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 700;
        color: #059669;
        margin-top: 4px;
    }
    .metric-trend-badge span.muted {
        color: var(--text-light);
        font-weight: 500;
    }

    .metric-sparkline {
        width: 100px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    /* ── 2. TWO-COLUMN OPERATIONAL WORKSPACE (68% / 32%) ── */
    .workspace-grid {
        display: grid;
        grid-template-columns: 2.1fr 1fr;
        gap: 22px;
        align-items: start;
    }

    .workspace-left {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    .workspace-right {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }

    /* ── ORDER PIPELINE CARD ── */
    .pipeline-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
    }

    .card-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .card-title-group {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 800;
        color: var(--text-heading);
    }

    .card-action-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .card-action-link:hover {
        color: var(--brand-primary);
    }

    .pipeline-steps-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .pipeline-step-box {
        flex: 1;
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .pipeline-step-box:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
    }
    .pipeline-step-box.active {
        box-shadow: 0 0 0 2px var(--brand-primary);
    }

    .pipeline-step-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .pipeline-step-count {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
    }

    .pipeline-arrow {
        color: var(--text-light);
        font-size: 14px;
        opacity: 0.6;
        user-select: none;
    }

    /* Step Color Accents */
    .step-new       { background: #fffbeb; border-color: #fef3c7; color: #b45309; }
    .step-confirmed { background: #f0f9ff; border-color: #e0f2fe; color: #0284c7; }
    .step-preparing { background: #faf5ff; border-color: #f3e8ff; color: #9333ea; }
    .step-ready     { background: #ecfdf5; border-color: #d1fae5; color: #059669; }
    .step-delivery  { background: #ecfeff; border-color: #cffafe; color: #0891b2; }
    .step-delivered { background: #f8fafc; border-color: #f1f5f9; color: #64748b; }

    [data-theme="dark"] .step-new       { background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    [data-theme="dark"] .step-confirmed { background: rgba(14, 165, 233, 0.12); border-color: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    [data-theme="dark"] .step-preparing { background: rgba(168, 85, 247, 0.12); border-color: rgba(168, 85, 247, 0.2); color: #c084fc; }
    [data-theme="dark"] .step-ready     { background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.2); color: #34d399; }
    [data-theme="dark"] .step-delivery  { background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.2); color: #22d3ee; }
    [data-theme="dark"] .step-delivered { background: rgba(100, 116, 139, 0.12); border-color: rgba(100, 116, 139, 0.2); color: #94a3b8; }

    /* ── LIVE ORDERS TABLE CARD ── */
    .live-orders-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 22px;
        box-shadow: var(--shadow-card);
    }

    .pill-filters-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .filter-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .filter-pill-btn:hover {
        background: var(--border-card);
        color: var(--text-heading);
    }
    .filter-pill-btn.active {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }

    .live-orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .live-orders-table th {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 10px 12px;
        border-bottom: 1.5px solid var(--border-subtle);
        text-align: left;
    }

    .live-orders-table td {
        padding: 14px 12px;
        border-bottom: 1px solid var(--border-subtle);
        font-size: 13px;
        color: var(--text-body);
        vertical-align: middle;
    }

    .live-order-row {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .live-order-row:hover {
        background: var(--border-subtle);
    }

    .order-id-cell {
        font-weight: 800;
        color: var(--text-heading);
        font-size: 13px;
    }

    .customer-info-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .customer-initial-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
        flex-shrink: 0;
    }
    [data-theme="dark"] .customer-initial-avatar {
        background: #1e293b;
        color: #f1f5f9;
    }

    .customer-meta h4 {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .customer-meta span {
        font-size: 11px;
        color: #16a34a;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .order-items-preview {
        max-width: 240px;
        line-height: 1.35;
        font-size: 12.5px;
        color: var(--text-body);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .order-total-cell {
        font-weight: 800;
        color: var(--text-heading);
        white-space: nowrap;
        font-size: 13px;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-pill.new       { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }
    .status-pill.preparing { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; }
    .status-pill.ready     { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
    .status-pill.delivery  { background: #ecfeff; color: #0891b2; border: 1px solid #cffafe; }
    .status-pill.delivered { background: #f8fafc; color: #64748b; border: 1px solid #f1f5f9; }
    .status-pill.cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

    [data-theme="dark"] .status-pill.new       { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.25); }
    [data-theme="dark"] .status-pill.preparing { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border-color: rgba(14, 165, 233, 0.25); }
    [data-theme="dark"] .status-pill.ready     { background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.25); }
    [data-theme="dark"] .status-pill.delivery  { background: rgba(6, 182, 212, 0.15); color: #22d3ee; border-color: rgba(6, 182, 212, 0.25); }
    [data-theme="dark"] .status-pill.delivered { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border-color: rgba(100, 116, 139, 0.25); }
    [data-theme="dark"] .status-pill.cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.25); }

    .order-time-cell {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
    }

    .chevron-icon {
        color: var(--text-light);
        font-size: 16px;
        transition: transform 0.15s ease;
    }
    .live-order-row:hover .chevron-icon {
        transform: translateX(2px);
        color: var(--text-heading);
    }

    /* ── 3. NEEDS ATTENTION CARD (RIGHT COLUMN TOP) ── */
    .attention-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
    }

    .attention-badge-count {
        background: #ef4444;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .attention-items-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .attention-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 12px;
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .attention-item-row:hover {
        background: var(--border-card);
        transform: translateX(2px);
    }

    .attention-item-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .attention-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .attention-icon-box.orange { background: #fff7ed; color: #ea580c; }
    .attention-icon-box.blue   { background: #f0f9ff; color: #0284c7; }
    .attention-icon-box.pink   { background: #fdf2f8; color: #db2777; }
    .attention-icon-box.purple { background: #faf5ff; color: #9333ea; }

    [data-theme="dark"] .attention-icon-box.orange { background: rgba(249, 115, 22, 0.15); color: #fb923c; }
    [data-theme="dark"] .attention-icon-box.blue   { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
    [data-theme="dark"] .attention-icon-box.pink   { background: rgba(219, 39, 119, 0.15); color: #f472b6; }
    [data-theme="dark"] .attention-icon-box.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }

    .attention-text-wrap h5 {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .attention-text-wrap p {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 1px;
    }

    /* ── 4. SALES TREND CARD (RIGHT COLUMN MIDDLE) ── */
    .trend-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
    }

    .trend-toggle-strip {
        display: inline-flex;
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        border-radius: 9999px;
        padding: 2px;
    }

    .trend-toggle-btn {
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .trend-toggle-btn.active {
        background: #4f46e5;
        color: #ffffff;
        box-shadow: 0 1px 4px rgba(79, 70, 229, 0.25);
    }

    .sales-trend-chart-area {
        margin-top: 16px;
        position: relative;
        width: 100%;
        height: 160px;
    }

    /* ── 5. BOTTOM 3 CARDS ROW (EQUAL COLUMNS) ── */
    .bottom-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .bottom-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Top Selling Items */
    .top-selling-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .top-selling-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12.5px;
    }

    .top-selling-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .top-item-rank {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-light);
        width: 18px;
    }

    .top-item-thumb {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .top-item-name {
        font-weight: 700;
        color: var(--text-heading);
    }

    .top-item-sold {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted);
    }

    /* Delivery Donut Overview */
    .delivery-donut-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 10px 0;
    }

    .donut-chart-box {
        position: relative;
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .donut-center-text {
        position: absolute;
        text-align: center;
    }
    .donut-center-text h4 {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1;
    }
    .donut-center-text p {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .donut-legend-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
    }

    .donut-legend-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
    }

    .donut-legend-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-weight: 600;
    }

    .donut-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .donut-legend-dot.pending   { background: #f59e0b; }
    .donut-legend-dot.preparing { background: #ea580c; }
    .donut-legend-dot.ready     { background: #10b981; }
    .donut-legend-dot.onroute   { background: #6366f1; }

    .donut-legend-val {
        font-weight: 800;
        color: var(--text-heading);
    }

    /* Recent Activity Feed */
    .recent-activity-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .activity-feed-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
    }

    .activity-time {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        width: 44px;
        flex-shrink: 0;
    }

    .activity-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .activity-dot.green  { background: #10b981; }
    .activity-dot.blue   { background: #0ea5e9; }
    .activity-dot.purple { background: #a855f7; }
    .activity-dot.orange { background: #f59e0b; }
    .activity-dot.slate  { background: #94a3b8; }

    .activity-desc {
        color: var(--text-heading);
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ── 6. SLIDE-OVER POS ORDER DRAWER ── */
    .drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        z-index: 9990;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }
    .drawer-backdrop.open {
        opacity: 1;
        pointer-events: auto;
    }

    .pos-order-drawer {
        position: fixed;
        top: 0; right: 0; bottom: 0;
        width: 460px;
        max-width: 100vw;
        background: var(--bg-card);
        border-left: 1px solid var(--border-card);
        box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
        z-index: 9991;
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    .pos-order-drawer.open {
        transform: translateX(0);
    }

    .drawer-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .drawer-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
    }

    .drawer-body {
        padding: 20px 24px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .drawer-card-box {
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .drawer-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border-subtle);
        background: var(--bg-card);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Modal Backdrop */
    .modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .modal-backdrop.open { display: flex; }

    .modal-dialog-box {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: 16px;
        width: 480px;
        max-width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .metric-cards-grid { grid-template-columns: repeat(2, 1fr); }
        .workspace-grid { grid-template-columns: 1fr; }
        .bottom-cards-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .metric-cards-grid { grid-template-columns: 1fr; }
        .pipeline-steps-strip { flex-direction: column; }
        .pipeline-arrow { transform: rotate(90deg); }
    }

    /* Real-Time Live Order Row Pulse Animation */
    @keyframes orderRowHighlight {
        0%   { background-color: rgba(245, 158, 11, 0.35); }
        50%  { background-color: rgba(245, 158, 11, 0.15); }
        100% { background-color: transparent; }
    }
    .new-order-highlight {
        animation: orderRowHighlight 3.5s ease-out forwards;
    }

    /* Live Status Pulse Dot */
    @keyframes liveDotPulse {
        0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70%  { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .live-pulse-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
        animation: liveDotPulse 2s infinite;
    }

    /* Floating Toast Notification */
    .live-order-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        padding: 12px 18px;
        display: none;
        align-items: center;
        gap: 14px;
        box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.45);
        z-index: 99999;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .live-order-toast:hover {
        transform: translateY(-2px);
    }
    .toast-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .toast-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .toast-title {
        font-size: 13.5px;
        font-weight: 800;
        color: #ffffff;
    }
    .toast-desc {
        font-size: 12px;
        color: #94a3b8;
    }
    .toast-action-btn {
        background: #10b981;
        color: #ffffff;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 11.5px;
        font-weight: 700;
        white-space: nowrap;
        margin-left: 6px;
    }
</style>

<div class="dashboard-container">

    <!-- ── 1. TOP METRIC CARDS (4 CARDS MATCHING CONCEPT) ── -->
    <div class="metric-cards-grid">
        <!-- 1. Today's Sales -->
        <div class="metric-card">
            <div class="metric-card-left">
                <div class="metric-icon-wrap green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="12" x="2" y="6" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg>
                </div>
                <span class="metric-label">Today's Sales</span>
                <span class="metric-value" id="kpi-sales">Rs {{ number_format($todayRevenue) }}</span>
                <div class="metric-trend-badge">
                    <span>↑ 12%</span> <span class="muted">vs yesterday</span>
                </div>
            </div>
            <!-- Emerald Sparkline SVG -->
            <div class="metric-sparkline">
                <svg width="90" height="40" viewBox="0 0 90 40" fill="none">
                    <path d="M5 32 Q 25 35, 40 22 T 70 18 T 85 8" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- 2. Total Orders -->
        <div class="metric-card">
            <div class="metric-card-left">
                <div class="metric-icon-wrap blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" x2="8" y1="13" y2="13"/>
                        <line x1="16" x2="8" y1="17" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <span class="metric-label">Total Orders</span>
                <span class="metric-value" id="kpi-total-orders">{{ $totalOrdersToday }}</span>
                <div class="metric-trend-badge">
                    <span>↑ 8%</span> <span class="muted">vs yesterday</span>
                </div>
            </div>
            <!-- Sky Blue Sparkline SVG -->
            <div class="metric-sparkline">
                <svg width="90" height="40" viewBox="0 0 90 40" fill="none">
                    <path d="M5 30 Q 20 28, 38 20 T 65 24 T 85 10" stroke="#0ea5e9" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- 3. Average Order Value (AOV) -->
        <div class="metric-card">
            <div class="metric-card-left">
                <div class="metric-icon-wrap purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                </div>
                <span class="metric-label">Average Order Value</span>
                <span class="metric-value" id="kpi-aov">
                    Rs {{ number_format($totalOrdersToday > 0 ? round($todayRevenue / $totalOrdersToday) : 0) }}
                </span>
                <div class="metric-trend-badge">
                    <span>↑ 6%</span> <span class="muted">vs yesterday</span>
                </div>
            </div>
            <!-- Purple Sparkline SVG -->
            <div class="metric-sparkline">
                <svg width="90" height="40" viewBox="0 0 90 40" fill="none">
                    <path d="M5 28 Q 25 32, 42 16 T 68 22 T 85 12" stroke="#a855f7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- 4. Completed Orders -->
        <div class="metric-card">
            <div class="metric-card-left">
                <div class="metric-icon-wrap orange">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <span class="metric-label">Completed Orders</span>
                <span class="metric-value" id="kpi-completed">{{ $statusCounts['delivered'] ?? 0 }}</span>
                <div class="metric-trend-badge">
                    <span>↑ 10%</span> <span class="muted">vs yesterday</span>
                </div>
            </div>
            <!-- Orange Sparkline SVG -->
            <div class="metric-sparkline">
                <svg width="90" height="40" viewBox="0 0 90 40" fill="none">
                    <path d="M5 32 Q 22 26, 45 22 T 70 14 T 85 8" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- ── 2. TWO-COLUMN WORKSPACE (68% / 32%) ── -->
    <div class="workspace-grid">

        <!-- LEFT COLUMN: Pipeline + Live Orders -->
        <div class="workspace-left">
            <!-- Order Pipeline Strip -->
            <div class="pipeline-card">
                <div class="card-header-row">
                    <div class="card-title-group">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="6" x2="10" y1="12" y2="12"/>
                            <line x1="8" x2="8" y1="9" y2="15"/>
                            <circle cx="4" cy="4" r="2"/>
                            <path d="M4 6v12a2 2 0 0 0 2 2h14"/>
                        </svg>
                        <span>Order Pipeline</span>
                    </div>
                    <a href="javascript:void(0)" onclick="filterTableStatus('all')" class="card-action-link">View all →</a>
                </div>

                <div class="pipeline-steps-strip">
                    <!-- Step 1: New -->
                    <div class="pipeline-step-box step-new" onclick="filterTableStatus('pending')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">🟡</span> New
                        </div>
                        <div class="pipeline-step-count" id="pipe-pending">{{ $pendingCount }}</div>
                    </div>

                    <span class="pipeline-arrow">→</span>

                    <!-- Step 2: Confirmed -->
                    <div class="pipeline-step-box step-confirmed" onclick="filterTableStatus('confirmed')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">🔵</span> Confirmed
                        </div>
                        <div class="pipeline-step-count" id="pipe-confirmed">{{ $statusCounts['confirmed'] ?? 0 }}</div>
                    </div>

                    <span class="pipeline-arrow">→</span>

                    <!-- Step 3: Preparing -->
                    <div class="pipeline-step-box step-preparing" onclick="filterTableStatus('preparing')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">🟣</span> Preparing
                        </div>
                        <div class="pipeline-step-count" id="pipe-preparing">{{ $statusCounts['preparing'] ?? 0 }}</div>
                    </div>

                    <span class="pipeline-arrow">→</span>

                    <!-- Step 4: Ready -->
                    @php
                        $readyCount = $todayOrders->where('status', 'confirmed')->whereNotNull('rider_name')->count();
                    @endphp
                    <div class="pipeline-step-box step-ready" onclick="filterTableStatus('ready')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">🟢</span> Ready
                        </div>
                        <div class="pipeline-step-count" id="pipe-ready">{{ $readyCount }}</div>
                    </div>

                    <span class="pipeline-arrow">→</span>

                    <!-- Step 5: Out for Delivery -->
                    @php
                        $deliveryCount = $todayOrders->where('status', 'out_for_delivery')->count();
                    @endphp
                    <div class="pipeline-step-box step-delivery" onclick="filterTableStatus('out_for_delivery')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">🔷</span> Out for Delivery
                        </div>
                        <div class="pipeline-step-count" id="pipe-delivery">{{ $deliveryCount }}</div>
                    </div>

                    <span class="pipeline-arrow">→</span>

                    <!-- Step 6: Delivered -->
                    <div class="pipeline-step-box step-delivered" onclick="filterTableStatus('delivered')">
                        <div class="pipeline-step-label">
                            <span style="font-size: 8px;">⚪</span> Delivered
                        </div>
                        <div class="pipeline-step-count" id="pipe-delivered">{{ $statusCounts['delivered'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Live Orders Table Card -->
            <div class="live-orders-card">
                <div class="card-header-row">
                    <div class="card-title-group">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                        <span>Live Orders</span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #10b981; font-weight: 700; margin-left: 2px;">
                            <span class="live-pulse-dot"></span> Live
                        </span>
                        <span id="liveOrdersBadgeCount" style="background: #ef4444; color: #fff; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">
                            {{ $liveOrdersCount }}
                        </span>
                    </div>
                    <a href="{{ route('dashboard.live-orders', $restaurant->id) }}" class="card-action-link">View all →</a>
                </div>

                <!-- Filter Pills -->
                <div class="pill-filters-row">
                    <button type="button" class="filter-pill-btn active" onclick="filterTableStatus('all')" id="btn-tab-all">
                        All <span id="pillCountAll">{{ $liveOrdersCount }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" onclick="filterTableStatus('pending')" id="btn-tab-pending">
                        New <span id="pillCountPending">{{ $pendingCount }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" onclick="filterTableStatus('preparing')" id="btn-tab-preparing">
                        Preparing <span id="pillCountPreparing">{{ $statusCounts['preparing'] ?? 0 }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" onclick="filterTableStatus('ready')" id="btn-tab-ready">
                        Ready <span id="pillCountReady">{{ $readyCount }}</span>
                    </button>
                    <button type="button" class="filter-pill-btn" onclick="filterTableStatus('out_for_delivery')" id="btn-tab-delivery">
                        Delivery <span id="pillCountDelivery">{{ $deliveryCount }}</span>
                    </button>
                </div>

                <!-- Orders Table -->
                <div style="overflow-x: auto;">
                    <table class="live-orders-table" id="ordersMainTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th style="width: 24px;"></th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            @forelse($orders->take(8) as $order)
                                @php
                                    $custName = $order->customer_name ?: 'Customer';
                                    $custInitial = strtoupper(substr($custName, 0, 1));
                                    $itemsSummary = $order->items->map(function($i) {
                                        return $i->quantity . 'x ' . ($i->name ?? $i->item_name);
                                    })->take(2)->implode(', ');
                                    if ($order->items->count() > 2) {
                                        $itemsSummary .= ' +' . ($order->items->count() - 2) . ' more';
                                    }

                                    $badgeClass = match($order->status) {
                                        'pending'          => 'new',
                                        'confirmed'        => 'preparing',
                                        'preparing'        => 'preparing',
                                        'out_for_delivery' => 'delivery',
                                        'delivered'        => 'delivered',
                                        'cancelled'        => 'cancelled',
                                        default            => 'cancelled',
                                    };
                                    $label = match($order->status) {
                                        'pending'          => 'New',
                                        'confirmed'        => 'Confirmed',
                                        'preparing'        => 'Preparing',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered'        => 'Delivered',
                                        'cancelled'        => 'CANCELLED',
                                        default            => strtoupper($order->status),
                                    };
                                @endphp
                                <tr class="live-order-row" data-status="{{ $order->status }}" onclick="openOrderDrawer({{ $order->id }})">
                                    <td class="order-id-cell">#{{ $order->id }}</td>
                                    <td>
                                        <div class="customer-info-cell">
                                            <div class="customer-initial-avatar">{{ $custInitial }}</div>
                                            <div class="customer-meta">
                                                <h4>{{ $custName }}</h4>
                                                <span>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                                    WhatsApp
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-items-preview" title="{{ $itemsSummary }}">
                                            {{ $itemsSummary ?: 'Standard order' }}
                                        </div>
                                    </td>
                                    <td class="order-total-cell">Rs {{ number_format($order->total) }}</td>
                                    <td>
                                        <span class="status-pill {{ $badgeClass }}">{{ $label }}</span>
                                    </td>
                                    <td class="order-time-cell">{{ $order->created_at->format('g:i A') }}</td>
                                    <td style="text-align: right;">
                                        <span class="chevron-icon">›</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No active orders currently. New orders will appear here automatically!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Needs Attention + Sales Trend -->
        <div class="workspace-right">
            <!-- Needs Attention Card -->
            @php
                $pendingOrders = $todayOrders->where('status', 'pending');
                $waitingRiders = $todayOrders->whereIn('status', ['confirmed', 'preparing'])->whereNull('rider_name');
                $unavailableList = $unavailableItems ?? collect();
                $isBotOffline = ($restaurant->bot_status ?? 'connected') !== 'connected';

                $attentionIssuesCount = 0;
                if ($pendingOrders->count() > 0) $attentionIssuesCount++;
                if ($waitingRiders->count() > 0) $attentionIssuesCount++;
                if ($unavailableList->count() > 0) $attentionIssuesCount++;
                if ($isBotOffline) $attentionIssuesCount++;
            @endphp
            <div class="attention-card">
                <div class="card-header-row">
                    <div class="card-title-group" id="attentionHeaderGroup" style="color: {{ $attentionIssuesCount > 0 ? '#ef4444' : '#10b981' }};">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @if($attentionIssuesCount > 0)
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" x2="12" y1="9" y2="13"/>
                                <line x1="12" x2="12.01" y1="17" y2="17"/>
                            @else
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            @endif
                        </svg>
                        <span style="color: var(--text-heading);">Needs Attention</span>
                    </div>
                    <span class="attention-badge-count" id="attentionBadgeCount" style="background: {{ $attentionIssuesCount > 0 ? '#ef4444' : '#10b981' }};">{{ $attentionIssuesCount }}</span>
                </div>

                <div class="attention-items-list" id="attentionItemsList">
                    @if($attentionIssuesCount === 0)
                        <div style="text-align: center; padding: 26px 16px;">
                            <div style="font-size: 26px; margin-bottom: 6px;">✅</div>
                            <h5 style="font-size: 13.5px; font-weight: 800; color: var(--text-heading); margin-bottom: 4px;">All caught up!</h5>
                            <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">No urgent actions needed. Your orders and restaurant are running smoothly.</p>
                        </div>
                    @else
                        <!-- Issue 1: Pending Orders Waiting -->
                        @if($pendingOrders->count() > 0)
                            <div class="attention-item-row" onclick="filterTableStatus('pending')">
                                <div class="attention-item-left">
                                    <div class="attention-icon-box orange">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </div>
                                    <div class="attention-text-wrap">
                                        <h5>{{ $pendingOrders->count() }} new {{ Str::plural('order', $pendingOrders->count()) }} waiting</h5>
                                        <p>{{ $pendingOrders->take(3)->map(fn($o) => '#' . $o->id)->implode(', ') }}</p>
                                    </div>
                                </div>
                                <span class="chevron-icon">›</span>
                            </div>
                        @endif

                        <!-- Issue 2: Orders Waiting for Rider Assignment -->
                        @if($waitingRiders->count() > 0)
                            <div class="attention-item-row" onclick="openDispatchModalDirect()">
                                <div class="attention-item-left">
                                    <div class="attention-icon-box blue">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/></svg>
                                    </div>
                                    <div class="attention-text-wrap">
                                        <h5>{{ $waitingRiders->count() }} {{ Str::plural('delivery', $waitingRiders->count()) }} waiting for rider</h5>
                                        <p>{{ $waitingRiders->take(3)->map(fn($o) => '#' . $o->id)->implode(', ') }}</p>
                                    </div>
                                </div>
                                <span class="chevron-icon">›</span>
                            </div>
                        @endif

                        <!-- Issue 3: Unavailable Menu Items -->
                        @if($unavailableList->count() > 0)
                            <a href="{{ route('dashboard.menu', $restaurant->id) }}" class="attention-item-row">
                                <div class="attention-item-left">
                                    <div class="attention-icon-box pink">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/></svg>
                                    </div>
                                    <div class="attention-text-wrap">
                                        <h5>{{ $unavailableList->count() }} menu {{ Str::plural('item', $unavailableList->count()) }} unavailable</h5>
                                        <p>{{ Str::limit($unavailableList->pluck('name')->implode(', '), 30) }}</p>
                                    </div>
                                </div>
                                <span class="chevron-icon">›</span>
                            </a>
                        @endif

                        <!-- Issue 4: WhatsApp Bot Connection Offline -->
                        @if($isBotOffline)
                            <a href="{{ route('dashboard.connect-whatsapp', $restaurant->id) }}" class="attention-item-row">
                                <div class="attention-item-left">
                                    <div class="attention-icon-box purple">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                    </div>
                                    <div class="attention-text-wrap">
                                        <h5 style="color: #dc2626;">WhatsApp Bot Offline</h5>
                                        <p>Click to scan QR code & reconnect</p>
                                    </div>
                                </div>
                                <span class="chevron-icon">›</span>
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Sales Trend Area Chart -->
            <div class="trend-card">
                <div class="card-header-row">
                    <div class="card-title-group">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m19 9-5 5-4-4-3 3"/>
                        </svg>
                        <span>Sales Trend</span>
                    </div>
                    <div class="trend-toggle-strip">
                        <button type="button" class="trend-toggle-btn active" onclick="switchTrendRange('today', this)">Today</button>
                        <button type="button" class="trend-toggle-btn" onclick="switchTrendRange('7d', this)">7D</button>
                        <button type="button" class="trend-toggle-btn" onclick="switchTrendRange('30d', this)">30D</button>
                    </div>
                </div>

                <!-- Smooth Purple Area Chart SVG -->
                <div class="sales-trend-chart-area">
                    <svg width="100%" height="150" viewBox="0 0 320 150" preserveAspectRatio="none" fill="none">
                        <defs>
                            <linearGradient id="purpleAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <!-- Grid lines -->
                        <line x1="30" y1="30" x2="310" y2="30" stroke="var(--border-subtle)" stroke-dasharray="3 3"/>
                        <line x1="30" y1="70" x2="310" y2="70" stroke="var(--border-subtle)" stroke-dasharray="3 3"/>
                        <line x1="30" y1="110" x2="310" y2="110" stroke="var(--border-subtle)" stroke-dasharray="3 3"/>

                        <!-- Left Y Axis Labels -->
                        <text x="5" y="34" font-size="9" font-weight="600" fill="var(--text-light)">60k</text>
                        <text x="5" y="74" font-size="9" font-weight="600" fill="var(--text-light)">40k</text>
                        <text x="5" y="114" font-size="9" font-weight="600" fill="var(--text-light)">20k</text>
                        <text x="14" y="140" font-size="9" font-weight="600" fill="var(--text-light)">0</text>

                        <!-- Area Fill -->
                        <path d="M 35 110 Q 75 100, 110 80 T 170 85 T 230 65 T 280 40 L 310 45 L 310 135 L 35 135 Z" fill="url(#purpleAreaGrad)"/>

                        <!-- Top Line Curve -->
                        <path d="M 35 110 Q 75 100, 110 80 T 170 85 T 230 65 T 280 40 L 310 45" stroke="#8b5cf6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                        <!-- Endpoint dot -->
                        <circle cx="310" cy="45" r="3.5" fill="#8b5cf6"/>
                    </svg>

                    <!-- X-Axis Labels -->
                    <div style="display: flex; justify-content: space-between; padding-left: 30px; font-size: 9px; font-weight: 600; color: var(--text-light); margin-top: 4px;">
                        <span>10 AM</span>
                        <span>12 PM</span>
                        <span>2 PM</span>
                        <span>4 PM</span>
                        <span>6 PM</span>
                        <span>8 PM</span>
                        <span>10 PM</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. BOTTOM 3 CARDS ROW (EQUAL 3 COLUMNS) ── -->
    <div class="bottom-cards-grid">
        <!-- Card 1: Top Selling Items -->
        <div class="bottom-card">
            <div class="card-header-row">
                <div class="card-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                        <path d="M4 22h16"/>
                        <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                        <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                    </svg>
                    <span>Top Selling Items</span>
                </div>
                <a href="{{ route('dashboard.menu', $restaurant->id) }}" class="card-action-link">View all →</a>
            </div>

            <div class="top-selling-list">
                @forelse($topSellingItems->take(5) as $idx => $realItem)
                    <div class="top-selling-row">
                        <div class="top-selling-left">
                            <span class="top-item-rank">0{{ $idx + 1 }}</span>
                            <div class="top-item-thumb">🍽️</div>
                            <span class="top-item-name">{{ $realItem->name }}</span>
                        </div>
                        <span class="top-item-sold">{{ (int)$realItem->total_qty }} sold</span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 30px 16px; color: var(--text-muted); font-size: 12.5px;">
                        No item sales recorded today yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Card 2: Delivery Overview -->
        <div class="bottom-card">
            <div class="card-header-row">
                <div class="card-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                        <path d="M15 18H9"/>
                        <path d="M19 18h2a1 1 0 0 0 1-1v-5l-3-4h-5v10h1"/>
                        <circle cx="7" cy="18" r="2"/>
                        <circle cx="17" cy="18" r="2"/>
                    </svg>
                    <span>Delivery Overview</span>
                </div>
                <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="card-action-link">View all →</a>
            </div>

            <div class="delivery-donut-wrap">
                <!-- SVG Donut Ring -->
                <div class="donut-chart-box">
                    <svg width="110" height="110" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="38" stroke="var(--border-subtle)" stroke-width="9" fill="none"/>
                        <!-- Segment 1: Pending (Amber) -->
                        <circle cx="50" cy="50" r="38" stroke="#f59e0b" stroke-width="9" stroke-dasharray="100 238" stroke-dashoffset="0" fill="none" stroke-linecap="round"/>
                        <!-- Segment 2: Preparing (Orange) -->
                        <circle cx="50" cy="50" r="38" stroke="#ea580c" stroke-width="9" stroke-dasharray="60 238" stroke-dashoffset="-105" fill="none" stroke-linecap="round"/>
                        <!-- Segment 3: Ready (Green) -->
                        <circle cx="50" cy="50" r="38" stroke="#10b981" stroke-width="9" stroke-dasharray="30 238" stroke-dashoffset="-170" fill="none" stroke-linecap="round"/>
                        <!-- Segment 4: On Route (Indigo) -->
                        <circle cx="50" cy="50" r="38" stroke="#6366f1" stroke-width="9" stroke-dasharray="25 238" stroke-dashoffset="-205" fill="none" stroke-linecap="round"/>
                    </svg>
                    <div class="donut-center-text">
                        <h4 id="donutActiveCount">{{ $liveOrdersCount }}</h4>
                        <p>Active</p>
                    </div>
                </div>

                <!-- Legend -->
                <div class="donut-legend-list">
                    <div class="donut-legend-row">
                        <div class="donut-legend-label">
                            <span class="donut-legend-dot pending"></span>
                            <span>Pending</span>
                        </div>
                        <span class="donut-legend-val" id="donutLegendPending">{{ $pendingCount }}</span>
                    </div>

                    <div class="donut-legend-row">
                        <div class="donut-legend-label">
                            <span class="donut-legend-dot preparing"></span>
                            <span>Preparing</span>
                        </div>
                        <span class="donut-legend-val" id="donutLegendPreparing">{{ $statusCounts['preparing'] ?? 0 }}</span>
                    </div>

                    <div class="donut-legend-row">
                        <div class="donut-legend-label">
                            <span class="donut-legend-dot ready"></span>
                            <span>Ready</span>
                        </div>
                        <span class="donut-legend-val" id="donutLegendReady">{{ $readyCount }}</span>
                    </div>

                    <div class="donut-legend-row">
                        <div class="donut-legend-label">
                            <span class="donut-legend-dot onroute"></span>
                            <span>On Route</span>
                        </div>
                        <span class="donut-legend-val" id="donutLegendDelivery">{{ $deliveryCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Recent Activity -->
        <div class="bottom-card">
            <div class="card-header-row">
                <div class="card-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Recent Activity</span>
                </div>
                <a href="{{ route('dashboard.orders', $restaurant->id) }}" class="card-action-link">View all →</a>
            </div>

            <div class="recent-activity-list" id="recentActivityList">
                @forelse($recentActivity as $act)
                    @php
                        $dotClass = match($act->status) {
                            'pending'          => 'orange',
                            'confirmed'        => 'blue',
                            'preparing'        => 'purple',
                            'out_for_delivery' => 'blue',
                            'delivered'        => 'green',
                            'cancelled'        => 'slate',
                            default            => 'blue',
                        };
                        $statusText = match($act->status) {
                            'pending'          => 'placed / waiting confirmation',
                            'confirmed'        => 'confirmed',
                            'preparing'        => 'in preparation',
                            'out_for_delivery' => 'out for delivery',
                            'delivered'        => 'delivered',
                            'cancelled'        => 'cancelled',
                            default            => $act->status,
                        };
                    @endphp
                    <div class="activity-feed-row" onclick="openOrderDrawer({{ $act->id }})" style="cursor: pointer;">
                        <span class="activity-time">{{ $act->created_at->format('h:i A') }}</span>
                        <span class="activity-dot {{ $dotClass }}"></span>
                        <span class="activity-desc">Order #{{ $act->id }} {{ $statusText }}</span>
                    </div>
                @empty
                    <div style="text-align: center; padding: 30px 16px; color: var(--text-muted); font-size: 12.5px;">
                        No recent activity recorded today.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- ── 7. SLIDE-OVER POS ORDER DRAWER ── -->
<div id="posDrawerBackdrop" class="drawer-backdrop" onclick="closeOrderDrawer()"></div>

<div id="posOrderDrawer" class="pos-order-drawer">
    <div class="drawer-header">
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading);" id="drawerOrderTitle">Order #---</h3>
            <p style="font-size: 11px; color: var(--text-muted);" id="drawerOrderSub">Customer Details & Live Status</p>
        </div>
        <button type="button" class="drawer-close-btn" onclick="closeOrderDrawer()">✕</button>
    </div>

    <div class="drawer-body" id="drawerBodyContent">
        <!-- Customer & Contact -->
        <div class="drawer-card-box">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Customer</span>
                <span id="drawerStatusPill" class="status-pill new">New</span>
            </div>
            <div style="font-size: 15px; font-weight: 800; color: var(--text-heading);" id="drawerCustomerName">Guest</div>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted);" id="drawerPhoneRow">
                <span>📱</span> <span id="drawerCustomerPhone">N/A</span>
            </div>
        </div>

        <!-- Verified GPS Delivery Address -->
        <div class="drawer-card-box">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Delivery Location</span>
            <p style="font-size: 13px; color: var(--text-body); line-height: 1.4;" id="drawerDeliveryAddress">Address not specified</p>
            <div style="display: flex; gap: 8px; margin-top: 4px;" id="drawerLocationButtons">
                <a href="javascript:void(0)" target="_blank" id="drawerMapLink" class="btn" style="background: #eff6ff; color: #1d4ed8; padding: 4px 10px; font-size: 11px;">
                    📍 View on Google Maps
                </a>
            </div>
        </div>

        <!-- Order Items Receipt -->
        <div class="drawer-card-box">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Items Ordered</span>
            <div id="drawerItemsList" style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                <!-- Dynamically populated -->
            </div>
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 10px; margin-top: 6px; display: flex; justify-content: space-between; font-weight: 800; font-size: 14px;">
                <span>Total Amount:</span>
                <span id="drawerOrderTotal" style="color: var(--brand-primary);">Rs 0</span>
            </div>
        </div>

        <!-- Assigned Rider Info -->
        <div class="drawer-card-box" id="drawerRiderBox">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Delivery Rider</span>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2px;">
                <span id="drawerRiderName" style="font-size: 13px; font-weight: 700; color: var(--text-heading);">Not Assigned</span>
                <button type="button" onclick="openDispatchModalFromDrawer()" class="btn" style="padding: 4px 8px; font-size: 11px; background: var(--bg-card); border: 1px solid var(--border-card);">
                    Assign Rider 🚴
                </button>
            </div>
        </div>
    </div>

    <!-- Drawer Action Footer -->
    <div class="drawer-footer">
        <button type="button" id="drawerAdvanceBtn" onclick="advanceCurrentOrderStatus()" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 13px;">
            Advance Status ➔
        </button>
        <div style="display: flex; gap: 8px;">
            <a href="javascript:void(0)" id="drawerWhatsAppLink" target="_blank" class="btn" style="flex: 1; justify-content: center; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 11.5px;">
                💬 WhatsApp Chat
            </a>
            <a href="javascript:void(0)" id="drawerPrintLink" target="_blank" class="btn" style="flex: 1; justify-content: center; background: var(--bg-canvas); border: 1px solid var(--border-card); font-size: 11.5px;">
                🖨️ Print Bill
            </a>
            <button type="button" id="drawerCancelBtn" onclick="openCancelModalFromDrawer()" class="btn" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 11.5px;">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- ── DISPATCH RIDER MODAL ── -->
<div id="dispatchRiderModal" class="modal-backdrop">
    <div class="modal-dialog-box" style="padding: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading);">Assign Delivery Rider</h3>
            <button type="button" onclick="closeDispatchModal()" style="background: none; border: none; font-size: 16px; cursor: pointer; color: var(--text-muted);">✕</button>
        </div>
        <form id="dispatchForm" onsubmit="submitDispatch(event)">
            <input type="hidden" id="dispatchOrderId" value="">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Select Rider</label>
                <select id="dispatchRiderSelect" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid var(--border-card); background: var(--bg-card); color: var(--text-heading);">
                    <option value="">-- Choose Rider --</option>
                    @foreach($riders as $rider)
                        <option value="{{ $rider->name }}" data-phone="{{ $rider->phone }}">{{ $rider->name }} ({{ $rider->phone }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Estimated Delivery Minutes</label>
                <input type="number" id="dispatchEstMinutes" value="30" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid var(--border-card); background: var(--bg-card); color: var(--text-heading);">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeDispatchModal()" class="btn" style="background: var(--bg-canvas); border: 1px solid var(--border-card);">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm & Notify ➔</button>
            </div>
        </form>
    </div>
</div>

<!-- ── FLOATING LIVE ORDER TOAST NOTIFICATION ── -->
<div id="liveOrderToast" class="live-order-toast" role="alert" aria-live="assertive">
    <div class="toast-icon-box">🔔</div>
    <div class="toast-details">
        <span class="toast-title" id="toastTitle">New Order Placed!</span>
        <span class="toast-desc" id="toastBody">Customer Name • Rs 0</span>
    </div>
    <span class="toast-action-btn">View Order ➔</span>
</div>

<!-- ── CLIENT SCRIPT ENGINE ── -->
<script>
let currentDrawerOrderId = null;
let currentDrawerOrderStatus = null;
let currentFilterStatus = 'all';
let latestKnownOrderId = {{ (int) ($orders->first()?->id ?? 0) }};
const allOrdersMap = @json($orders->keyBy('id'));
const newlyArrivedOrderIds = new Set();
let isPollingActive = false;

// ── Web Audio API Synthesizer Chime ──
function playNewOrderSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }
        const now = ctx.currentTime;

        // Tone 1: 587.33 Hz (D5)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.18, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.35);

        // Tone 2: 880 Hz (A5)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.15);
        gain2.gain.setValueAtTime(0.22, now + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.15);
        osc2.stop(now + 0.55);
    } catch (e) {
        console.warn('Audio chime notice:', e);
    }
}

// ── Floating Toast Notification ──
let toastTimeoutId = null;
function showLiveOrderToast(order) {
    const toast = document.getElementById('liveOrderToast');
    if (!toast) return;

    document.getElementById('toastTitle').textContent = `New Order #${order.id} Placed!`;
    document.getElementById('toastBody').textContent = `${order.customer_name || 'Customer'} • Rs ${Number(order.total || 0).toLocaleString()}`;
    toast.style.display = 'flex';
    toast.onclick = () => {
        openOrderDrawer(order.id);
        toast.style.display = 'none';
    };

    if (toastTimeoutId) clearTimeout(toastTimeoutId);
    toastTimeoutId = setTimeout(() => {
        if (toast.style.display === 'flex') {
            toast.style.display = 'none';
        }
    }, 8000);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function formatItemsSummary(items) {
    if (!items || !items.length) return 'Standard order';
    const firstTwo = items.slice(0, 2).map(i => `${i.quantity}x ${i.name || i.item_name || 'Item'}`).join(', ');
    if (items.length > 2) {
        return firstTwo + ` +${items.length - 2} more`;
    }
    return firstTwo;
}

function getStatusMeta(status) {
    switch (status) {
        case 'pending':
            return { badgeClass: 'new', label: 'New' };
        case 'confirmed':
            return { badgeClass: 'preparing', label: 'Confirmed' };
        case 'preparing':
            return { badgeClass: 'preparing', label: 'Preparing' };
        case 'ready':
            return { badgeClass: 'ready', label: 'Ready' };
        case 'out_for_delivery':
            return { badgeClass: 'delivery', label: 'Out for Delivery' };
        case 'delivered':
            return { badgeClass: 'delivered', label: 'Delivered' };
        case 'cancelled':
            return { badgeClass: 'cancelled', label: 'CANCELLED' };
        default:
            return { badgeClass: 'cancelled', label: (status || '').toUpperCase() };
    }
}

// ── Render dynamic rows in Live Orders table ──
function renderOrdersTable(orders) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    let filtered = orders;
    if (currentFilterStatus === 'pending') {
        filtered = orders.filter(o => o.status === 'pending');
    } else if (currentFilterStatus === 'preparing') {
        filtered = orders.filter(o => o.status === 'preparing');
    } else if (currentFilterStatus === 'ready') {
        filtered = orders.filter(o => (o.status === 'confirmed' || o.status === 'preparing') && o.rider_name);
    } else if (currentFilterStatus === 'out_for_delivery') {
        filtered = orders.filter(o => o.status === 'out_for_delivery');
    } else if (currentFilterStatus === 'delivered') {
        filtered = orders.filter(o => o.status === 'delivered');
    }

    if (!filtered.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                    No active orders found in this view. New orders will appear here automatically!
                </td>
            </tr>
        `;
        return;
    }

    const slice = filtered.slice(0, 15);
    let html = '';
    slice.forEach(order => {
        const custName = escapeHtml(order.customer_name || 'Customer');
        const custInitial = custName.charAt(0).toUpperCase() || 'C';
        const itemsSummary = escapeHtml(formatItemsSummary(order.items));
        const meta = getStatusMeta(order.status);
        const isNewHighlight = newlyArrivedOrderIds.has(order.id) ? 'new-order-highlight' : '';
        const orderTime = order.created_at_time || (order.created_at ? new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '');

        html += `
            <tr class="live-order-row ${isNewHighlight}" data-status="${escapeHtml(order.status)}" onclick="openOrderDrawer(${order.id})">
                <td class="order-id-cell">#${order.id}</td>
                <td>
                    <div class="customer-info-cell">
                        <div class="customer-initial-avatar">${custInitial}</div>
                        <div class="customer-meta">
                            <h4>${custName}</h4>
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                WhatsApp
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="order-items-preview" title="${itemsSummary}">
                        ${itemsSummary}
                    </div>
                </td>
                <td class="order-total-cell">Rs ${Number(order.total || 0).toLocaleString()}</td>
                <td>
                    <span class="status-pill ${meta.badgeClass}">${meta.label}</span>
                </td>
                <td class="order-time-cell">${orderTime}</td>
                <td style="text-align: right;">
                    <span class="chevron-icon">›</span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ── Filter Table by Pipeline Step / Tabs ──
function filterTableStatus(status) {
    currentFilterStatus = status;
    document.querySelectorAll('.filter-pill-btn').forEach(b => b.classList.remove('active'));
    const targetBtn = document.getElementById('btn-tab-' + (status === 'out_for_delivery' ? 'delivery' : status));
    if (targetBtn) targetBtn.classList.add('active');

    const ordersList = Object.values(allOrdersMap).sort((a,b) => b.id - a.id);
    renderOrdersTable(ordersList);
}

// ── Real-Time Live Orders Poller ──
async function fetchLiveOrdersFeed() {
    if (isPollingActive) return;
    isPollingActive = true;
    try {
        const res = await fetch(`/dashboard/{{ $restaurant->id }}/orders/live-feed`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data || !data.success) return;

        // Detect newly arrived orders
        let hasNewOrder = false;
        let latestNewOrder = null;

        if (Array.isArray(data.orders)) {
            data.orders.forEach(o => {
                if (!allOrdersMap[o.id] && latestKnownOrderId > 0 && o.id > latestKnownOrderId) {
                    hasNewOrder = true;
                    newlyArrivedOrderIds.add(o.id);
                    if (!latestNewOrder || o.id > latestNewOrder.id) {
                        latestNewOrder = o;
                    }
                }
                allOrdersMap[o.id] = o;
            });
        }

        if (hasNewOrder && latestNewOrder) {
            playNewOrderSound();
            showLiveOrderToast(latestNewOrder);
        }

        if (data.latest_order_id && data.latest_order_id > latestKnownOrderId) {
            latestKnownOrderId = data.latest_order_id;
        }

        // Update Top 4 KPI Metrics
        const kpiSales = document.getElementById('kpi-sales');
        if (kpiSales) kpiSales.textContent = 'Rs ' + Number(data.revenue || 0).toLocaleString();

        const kpiTotal = document.getElementById('kpi-total-orders');
        if (kpiTotal) kpiTotal.textContent = data.today_count ?? 0;

        const kpiAov = document.getElementById('kpi-aov');
        if (kpiAov) kpiAov.textContent = 'Rs ' + Number(data.aov || 0).toLocaleString();

        const kpiComp = document.getElementById('kpi-completed');
        if (kpiComp) kpiComp.textContent = data.delivered_count ?? 0;

        // Update Order Pipeline Stage Counters
        const sc = data.status_counts || {};
        const pPending = document.getElementById('pipe-pending');
        if (pPending) pPending.textContent = sc.pending ?? 0;

        const pConfirmed = document.getElementById('pipe-confirmed');
        if (pConfirmed) pConfirmed.textContent = sc.confirmed ?? 0;

        const pPrep = document.getElementById('pipe-preparing');
        if (pPrep) pPrep.textContent = sc.preparing ?? 0;

        const pReady = document.getElementById('pipe-ready');
        if (pReady) pReady.textContent = sc.ready ?? 0;

        const pDel = document.getElementById('pipe-delivery');
        if (pDel) pDel.textContent = sc.out_for_delivery ?? 0;

        const pDelivered = document.getElementById('pipe-delivered');
        if (pDelivered) pDelivered.textContent = sc.delivered ?? 0;

        // Update Live Orders header badge & filter pill counts
        const badgeCount = document.getElementById('liveOrdersBadgeCount');
        if (badgeCount) badgeCount.textContent = data.active_count ?? 0;

        const pillAll = document.getElementById('pillCountAll');
        if (pillAll) pillAll.textContent = data.active_count ?? 0;

        const pillPending = document.getElementById('pillCountPending');
        if (pillPending) pillPending.textContent = sc.pending ?? 0;

        const pillPrep = document.getElementById('pillCountPreparing');
        if (pillPrep) pillPrep.textContent = sc.preparing ?? 0;

        const pillReady = document.getElementById('pillCountReady');
        if (pillReady) pillReady.textContent = sc.ready ?? 0;

        const pillDelivery = document.getElementById('pillCountDelivery');
        if (pillDelivery) pillDelivery.textContent = sc.out_for_delivery ?? 0;

        // Update Needs Attention Card dynamically
        const attBadge  = document.getElementById('attentionBadgeCount');
        const attList   = document.getElementById('attentionItemsList');
        const attHeader = document.getElementById('attentionHeaderGroup');
        if (attBadge && attList) {
            const pendingOrders = (data.orders || []).filter(o => o.status === 'pending');
            const waitingRiders = (data.orders || []).filter(o => (o.status === 'confirmed' || o.status === 'preparing') && !o.rider_name);
            const unavailableCount = data.attention ? (data.attention.unavailable_count || 0) : 0;
            const botOffline = data.attention ? (data.attention.bot_offline || false) : false;

            let issues = 0;
            let rowsHtml = '';

            if (pendingOrders.length > 0) {
                issues++;
                const pIds = pendingOrders.slice(0, 3).map(o => '#' + o.id).join(', ');
                rowsHtml += `
                    <div class="attention-item-row" onclick="filterTableStatus('pending')">
                        <div class="attention-item-left">
                            <div class="attention-icon-box orange">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div class="attention-text-wrap">
                                <h5>${pendingOrders.length} new ${pendingOrders.length === 1 ? 'order' : 'orders'} waiting</h5>
                                <p>${pIds}</p>
                            </div>
                        </div>
                        <span class="chevron-icon">›</span>
                    </div>
                `;
            }

            if (waitingRiders.length > 0) {
                issues++;
                const wIds = waitingRiders.slice(0, 3).map(o => '#' + o.id).join(', ');
                rowsHtml += `
                    <div class="attention-item-row" onclick="openDispatchModalDirect()">
                        <div class="attention-item-left">
                            <div class="attention-icon-box blue">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/></svg>
                            </div>
                            <div class="attention-text-wrap">
                                <h5>${waitingRiders.length} ${waitingRiders.length === 1 ? 'delivery' : 'deliveries'} waiting for rider</h5>
                                <p>${wIds}</p>
                            </div>
                        </div>
                        <span class="chevron-icon">›</span>
                    </div>
                `;
            }

            if (unavailableCount > 0) {
                issues++;
                rowsHtml += `
                    <a href="/dashboard/{{ $restaurant->id }}/menu" class="attention-item-row">
                        <div class="attention-item-left">
                            <div class="attention-icon-box pink">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/></svg>
                            </div>
                            <div class="attention-text-wrap">
                                <h5>${unavailableCount} menu ${unavailableCount === 1 ? 'item' : 'items'} unavailable</h5>
                                <p>Check out-of-stock items</p>
                            </div>
                        </div>
                        <span class="chevron-icon">›</span>
                    </a>
                `;
            }

            if (botOffline) {
                issues++;
                rowsHtml += `
                    <a href="/dashboard/{{ $restaurant->id }}/connect-whatsapp" class="attention-item-row">
                        <div class="attention-item-left">
                            <div class="attention-icon-box purple">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            </div>
                            <div class="attention-text-wrap">
                                <h5 style="color: #dc2626;">WhatsApp Bot Offline</h5>
                                <p>Click to scan QR code & reconnect</p>
                            </div>
                        </div>
                        <span class="chevron-icon">›</span>
                    </a>
                `;
            }

            attBadge.textContent = issues;
            attBadge.style.background = issues > 0 ? '#ef4444' : '#10b981';
            if (attHeader) attHeader.style.color = issues > 0 ? '#ef4444' : '#10b981';

            if (issues === 0) {
                attList.innerHTML = `
                    <div style="text-align: center; padding: 26px 16px;">
                        <div style="font-size: 26px; margin-bottom: 6px;">✅</div>
                        <h5 style="font-size: 13.5px; font-weight: 800; color: var(--text-heading); margin-bottom: 4px;">All caught up!</h5>
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">No urgent actions needed. Your orders and restaurant are running smoothly.</p>
                    </div>
                `;
            } else {
                attList.innerHTML = rowsHtml;
            }
        }

        // Update Delivery Overview Donut
        const donutActive = document.getElementById('donutActiveCount');
        if (donutActive) donutActive.textContent = data.active_count ?? 0;

        const dPending = document.getElementById('donutLegendPending');
        if (dPending) dPending.textContent = sc.pending ?? 0;

        const dPrep = document.getElementById('donutLegendPreparing');
        if (dPrep) dPrep.textContent = sc.preparing ?? 0;

        const dReady = document.getElementById('donutLegendReady');
        if (dReady) dReady.textContent = sc.ready ?? 0;

        const dDel = document.getElementById('donutLegendDelivery');
        if (dDel) dDel.textContent = sc.out_for_delivery ?? 0;

        // Update Drawer if currently inspecting an order whose status changed
        if (currentDrawerOrderId && allOrdersMap[currentDrawerOrderId]) {
            const currentO = allOrdersMap[currentDrawerOrderId];
            if (currentO.status !== currentDrawerOrderStatus) {
                openOrderDrawer(currentDrawerOrderId);
            }
        }

        // Render updated table rows
        const ordersList = Object.values(allOrdersMap).sort((a,b) => b.id - a.id);
        renderOrdersTable(ordersList);

    } catch (err) {
        console.error('Live orders feed poll error:', err);
    } finally {
        isPollingActive = false;
    }
}

// ── Open Slide-over Order Drawer ──
function openOrderDrawer(orderId) {
    const o = allOrdersMap[orderId];
    if (!o) return;

    currentDrawerOrderId = orderId;
    currentDrawerOrderStatus = o.status;

    // Clear highlight if user clicks on this order
    newlyArrivedOrderIds.delete(orderId);

    document.getElementById('drawerOrderTitle').textContent = 'Order #' + o.id;
    const timeFormatted = o.created_at_time || (o.created_at ? new Date(o.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '');
    document.getElementById('drawerOrderSub').textContent = 'Received ' + timeFormatted;
    document.getElementById('drawerCustomerName').textContent = o.customer_name || 'Guest';
    document.getElementById('drawerCustomerPhone').textContent = o.customer_phone || 'N/A';
    document.getElementById('drawerDeliveryAddress').textContent = o.delivery_address || 'No address provided';

    // Map link
    const mapLink = document.getElementById('drawerMapLink');
    if (o.delivery_lat && o.delivery_lng) {
        mapLink.href = `https://www.google.com/maps?q=${o.delivery_lat},${o.delivery_lng}`;
        mapLink.style.display = 'inline-flex';
    } else {
        mapLink.style.display = 'none';
    }

    // Status pill
    const pill = document.getElementById('drawerStatusPill');
    const meta = getStatusMeta(o.status);
    pill.textContent = meta.label.toUpperCase();
    pill.className = 'status-pill ' + meta.badgeClass;

    // Items list
    const itemsCont = document.getElementById('drawerItemsList');
    itemsCont.innerHTML = '';
    if (o.items && o.items.length) {
        o.items.forEach(it => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.fontSize = '12.5px';
            row.innerHTML = `<span><strong>${it.quantity}x</strong> ${escapeHtml(it.name || it.item_name)}</span><span>Rs ${Number(it.subtotal || 0).toLocaleString()}</span>`;
            itemsCont.appendChild(row);
        });
    } else {
        itemsCont.innerHTML = '<span style="font-size: 12px; color: var(--text-muted);">No line items recorded</span>';
    }

    document.getElementById('drawerOrderTotal').textContent = 'Rs ' + (parseFloat(o.total) || 0).toLocaleString();
    document.getElementById('drawerRiderName').textContent = o.rider_name || 'Not Assigned';

    // Links
    document.getElementById('drawerWhatsAppLink').href = o.customer_phone ? `https://wa.me/${o.customer_phone.replace(/[^0-9]/g, '')}` : '#';
    document.getElementById('drawerPrintLink').href = `/dashboard/{{ $restaurant->id }}/orders/${o.id}/print-bill`;

    // Advance button label
    const advBtn = document.getElementById('drawerAdvanceBtn');
    if (o.status === 'pending') {
        advBtn.textContent = 'Confirm Order ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'confirmed') {
        advBtn.textContent = 'Start Preparing ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'preparing') {
        advBtn.textContent = 'Dispatch for Delivery ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'out_for_delivery') {
        advBtn.textContent = 'Mark Delivered ✓';
        advBtn.style.display = 'flex';
    } else {
        advBtn.style.display = 'none';
    }

    const cancelBtn = document.getElementById('drawerCancelBtn');
    if (cancelBtn) {
        cancelBtn.style.display = (o.status === 'cancelled' || o.status === 'delivered') ? 'none' : 'inline-flex';
    }

    document.getElementById('posOrderDrawer').classList.add('open');
    document.getElementById('posDrawerBackdrop').classList.add('open');
}

function closeOrderDrawer() {
    document.getElementById('posOrderDrawer').classList.remove('open');
    document.getElementById('posDrawerBackdrop').classList.remove('open');
}

// ── Advance Order Status ──
async function advanceCurrentOrderStatus() {
    if (!currentDrawerOrderId) return;
    const nextStatusMap = {
        'pending': 'confirmed',
        'confirmed': 'preparing',
        'preparing': 'out_for_delivery',
        'out_for_delivery': 'delivered'
    };
    const nextStatus = nextStatusMap[currentDrawerOrderStatus];
    if (!nextStatus) return;

    if (nextStatus === 'out_for_delivery') {
        openDispatchModalFromDrawer();
        return;
    }

    await postStatusUpdate(currentDrawerOrderId, nextStatus);
}

async function postStatusUpdate(orderId, status, extra = {}) {
    // Immediate zero-delay optimistic update in local map & UI
    if (allOrdersMap[orderId]) {
        allOrdersMap[orderId].status = status;
        allOrdersMap[orderId].status_label = status.toUpperCase();
        if (extra.rider_name) allOrdersMap[orderId].rider_name = extra.rider_name;
        if (extra.rider_phone) allOrdersMap[orderId].rider_phone = extra.rider_phone;
        const ordersList = Object.values(allOrdersMap).sort((a,b) => b.id - a.id);
        renderOrdersTable(ordersList);
    }

    try {
        const res = await fetch(`/dashboard/{{ $restaurant->id }}/orders/${orderId}/status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status, ...extra })
        });
        const data = await res.json();
        if (data.success || res.ok) {
            closeOrderDrawer();
            closeDispatchModal();
            if (data.status && allOrdersMap[orderId]) {
                allOrdersMap[orderId].status = data.status;
                allOrdersMap[orderId].status_label = data.status_label || data.status.toUpperCase();
                const ordersList = Object.values(allOrdersMap).sort((a,b) => b.id - a.id);
                renderOrdersTable(ordersList);
            }
            // Instantly refresh live orders feed without full page reload
            await fetchLiveOrdersFeed();
        } else {
            alert(data.error || 'Failed to update order status.');
            await fetchLiveOrdersFeed();
        }
    } catch (e) {
        console.error(e);
        closeOrderDrawer();
        closeDispatchModal();
        await fetchLiveOrdersFeed();
    }
}

// ── Dispatch Modal ──
function openDispatchModalDirect() {
    const firstPendingOrder = @json($orders->firstWhere('status', 'confirmed')?->id ?? $orders->first()?->id);
    if (firstPendingOrder) {
        document.getElementById('dispatchOrderId').value = firstPendingOrder;
        document.getElementById('dispatchRiderModal').classList.add('open');
    }
}

function openDispatchModalFromDrawer() {
    if (!currentDrawerOrderId) return;
    document.getElementById('dispatchOrderId').value = currentDrawerOrderId;
    document.getElementById('dispatchRiderModal').classList.add('open');
}

function closeDispatchModal() {
    document.getElementById('dispatchRiderModal').classList.remove('open');
}

async function submitDispatch(e) {
    e.preventDefault();
    const orderId = document.getElementById('dispatchOrderId').value;
    const select = document.getElementById('dispatchRiderSelect');
    const riderName = select.value;
    const riderPhone = select.options[select.selectedIndex]?.getAttribute('data-phone') || '';
    const estMinutes = document.getElementById('dispatchEstMinutes').value || 30;

    await postStatusUpdate(orderId, 'out_for_delivery', {
        rider_name: riderName,
        rider_phone: riderPhone,
        estimated_minutes: estMinutes
    });
}

function openCancelModalFromDrawer() {
    if (!currentDrawerOrderId) return;
    if (confirm("Are you sure you want to cancel Order #" + currentDrawerOrderId + "?")) {
        postStatusUpdate(currentDrawerOrderId, 'cancelled');
    }
}

function switchTrendRange(range, btn) {
    document.querySelectorAll('.trend-toggle-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Start polling every 4 seconds ──
setInterval(fetchLiveOrdersFeed, 4000);
</script>

@endsection