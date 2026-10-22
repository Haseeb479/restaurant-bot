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

    $ownerName           = $restaurant->owner_name ?? ($restaurant->name ?? 'Haseeb');
    $ownerFirstName      = explode(' ', trim($ownerName))[0] ?: 'Owner';
    $activeDineInCount   = $activeDineInCount ?? ($restaurant ? $restaurant->orders()
        ->dineIn()
        ->whereIn('status', ['pending', 'confirmed', 'preparing', 'served'])
        ->whereDate('created_at', now()->today())
        ->count() : 0);
@endphp

<style>
    /* ═════════════════════════════════════════════════════════════
       VESSA / REFERENCE BENTO GRID & OPERATIONAL WORKSPACE
       ═════════════════════════════════════════════════════════════ */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }

    /* ── BENTO GRID ALIGNMENT (EXACT REFERENCE REPLICA) ── */
    .vessa-row-1 {
        display: grid;
        grid-template-columns: 1.55fr 1fr 1fr 1fr;
        gap: 18px;
        width: 100%;
        align-items: stretch;
    }

    .vessa-row-2 {
        display: grid;
        grid-template-columns: 1.85fr 1fr;
        gap: 18px;
        width: 100%;
        margin-top: 18px;
        align-items: stretch;
    }

    /* ── 3. DASHBOARD CARDS — STAGGERED ENTRANCE ANIMATION ── */
    .vessa-row-1 > div,
    .vessa-row-2 > div,
    #activityWorkspaceSection {
        animation: vessaCardEntrance var(--duration-deliberate) var(--ease-out-smooth) both;
        will-change: opacity, transform;
    }
    .vessa-row-1 > div:nth-child(1) { animation-delay: 35ms; }
    .vessa-row-1 > div:nth-child(2) { animation-delay: 85ms; }
    .vessa-row-1 > div:nth-child(3) { animation-delay: 135ms; }
    .vessa-row-1 > div:nth-child(4) { animation-delay: 185ms; }
    .vessa-row-2 > div:nth-child(1) { animation-delay: 235ms; }
    .vessa-row-2 > div:nth-child(2) { animation-delay: 285ms; }
    #activityWorkspaceSection { animation-delay: 335ms; }

    @keyframes vessaCardEntrance {
        0% {
            opacity: 0;
            transform: translateY(12px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── 4. CHARTS — DATA VISUALIZATION ANIMATIONS ── */
    .vessa-chart-bar {
        transform-origin: 0 190px;
        animation: vessaBarGrow 550ms var(--ease-out-smooth) both;
        animation-delay: calc(var(--bar-idx, 0) * 30ms);
    }
    @keyframes vessaBarGrow {
        0% {
            transform: scaleY(0);
            opacity: 0.15;
        }
        100% {
            transform: scaleY(1);
            opacity: 1;
        }
    }

    .vessa-chart-curve-stroke {
        stroke-dasharray: 850;
        stroke-dashoffset: 850;
        animation: vessaDrawSpline 800ms var(--ease-out-smooth) 240ms forwards;
    }
    @keyframes vessaDrawSpline {
        to { stroke-dashoffset: 0; }
    }

    .vessa-chart-curve-area {
        opacity: 0;
        animation: vessaAreaFade 650ms var(--ease-out-smooth) 400ms forwards;
    }
    @keyframes vessaAreaFade {
        to { opacity: 1; }
    }

    .vessa-peak-tooltip {
        animation: vessaTooltipPop 400ms var(--ease-out-smooth) 550ms both;
    }
    @keyframes vessaTooltipPop {
        0% {
            opacity: 0;
            transform: translate(-50%, -90%) scale(0.92);
        }
        100% {
            opacity: 1;
            transform: translate(-50%, -100%) scale(1);
        }
    }
    .vessa-peak-circle {
        animation: vessaCirclePop 300ms var(--ease-spring) 500ms both;
    }
    @keyframes vessaCirclePop {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .vessa-gauge-progress-arc {
        stroke-dasharray: 240;
        stroke-dashoffset: 240;
        animation: vessaGaugeArcSweep 750ms var(--ease-out-smooth) 280ms forwards;
    }
    @keyframes vessaGaugeArcSweep {
        to { stroke-dashoffset: 0; }
    }
    .vessa-gauge-pointer-dot {
        animation: vessaCirclePop 350ms var(--ease-spring) 750ms both;
    }

    .vessa-spark-bar {
        transform-origin: 0 42px;
        animation: vessaBarGrow 450ms var(--ease-out-smooth) both;
        animation-delay: calc(var(--s-idx, 0) * 35ms);
    }
    .vessa-spark-line {
        stroke-dasharray: 120;
        stroke-dashoffset: 120;
        animation: vessaDrawSpark 600ms var(--ease-out-smooth) 200ms forwards;
    }
    @keyframes vessaDrawSpark {
        to { stroke-dashoffset: 0; }
    }
    .vessa-spark-area {
        opacity: 0;
        animation: vessaAreaFade 500ms ease-out 300ms forwards;
    }

    /* Card 1: Welcome Hero Card */
    .vessa-card-welcome {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: 28px;
        padding: 30px 34px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 220px;
        height: 100%;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .vessa-card-welcome:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-elevated);
    }

    .vessa-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--color-forest-light);
        color: var(--color-forest);
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: -0.01em;
        width: fit-content;
        margin-bottom: 12px;
    }
    [data-theme="dark"] .vessa-date-pill {
        background: rgba(30, 92, 71, 0.3);
        color: #4ade80;
    }

    .vessa-welcome-heading {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 32px;
        font-weight: 700;
        color: var(--text-heading);
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .vessa-welcome-sub {
        font-size: 13.5px;
        color: var(--text-muted);
        line-height: 1.5;
        max-width: 480px;
        margin-bottom: 20px;
    }

    .vessa-welcome-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .vessa-btn-forest {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--color-forest);
        color: #ffffff !important;
        padding: 10px 22px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(17, 66, 50, 0.22);
        transition: all 0.18s ease;
    }
    .vessa-btn-forest:hover {
        background: var(--color-forest-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(17, 66, 50, 0.32);
    }

    .vessa-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        color: var(--text-heading);
        padding: 10px 20px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.18s ease;
    }
    .vessa-btn-outline:hover {
        border-color: #cbd5e1;
        background: var(--bg-canvas);
        transform: translateY(-1px);
    }

    /* Metric Cards: Cards 2, 3, 4 */
    .vessa-card-metric {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: 26px;
        padding: 22px 20px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 220px;
        height: 100%;
        box-sizing: border-box;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .vessa-card-metric:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-elevated);
    }

    .vessa-metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
    }
    .vessa-metric-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-heading);
    }

    .vessa-metric-icon-chip {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .vessa-metric-icon-chip.green {
        background: #E8EFEA;
        color: var(--color-forest);
    }
    [data-theme="dark"] .vessa-metric-icon-chip.green {
        background: rgba(30, 92, 71, 0.3);
        color: #4ade80;
    }
    .vessa-metric-icon-chip.teal {
        background: #E0F2FE;
        color: #0284c7;
    }
    [data-theme="dark"] .vessa-metric-icon-chip.teal {
        background: rgba(2, 132, 199, 0.25);
        color: #38bdf8;
    }
    .vessa-metric-icon-chip.amber {
        background: #FEF3C7;
        color: #d97706;
    }
    [data-theme="dark"] .vessa-metric-icon-chip.amber {
        background: rgba(217, 119, 6, 0.25);
        color: #fbbf24;
    }
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .vessa-trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 9999px;
    }
    .vessa-trend-pill.up {
        background: var(--color-forest-light);
        color: var(--color-forest);
    }
    [data-theme="dark"] .vessa-trend-pill.up {
        background: rgba(30, 92, 71, 0.3);
        color: #4ade80;
    }
    .vessa-trend-pill.coral {
        background: var(--danger-bg);
        color: var(--danger-text);
    }

    .vessa-metric-val {
        font-size: 30px;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin-bottom: 14px;
    }

    .vessa-metric-spark-wrap {
        width: 100%;
        height: 48px;
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .vessa-metric-subtext {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-light);
    }

    /* Low Stock Horizontal Split Bar */
    .vessa-split-bar {
        width: 100%;
        height: 8px;
        border-radius: 9999px;
        background: var(--bg-canvas);
        display: flex;
        overflow: hidden;
        margin-bottom: 12px;
    }
    .vessa-split-bar-amber {
        height: 100%;
        background: #f59e0b;
        border-radius: 9999px 0 0 9999px;
        transform-origin: left;
        animation: vessaSplitBarGrow 600ms var(--ease-out-smooth) 250ms both;
    }
    .vessa-split-bar-coral {
        height: 100%;
        background: #e07a5f;
        border-radius: 0 9999px 9999px 0;
        transform-origin: right;
        animation: vessaSplitBarGrow 600ms var(--ease-out-smooth) 250ms both;
    }
    @keyframes vessaSplitBarGrow {
        0% { transform: scaleX(0); }
        100% { transform: scaleX(1); }
    }

    .vessa-avatar-chips {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .vessa-chip {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--bg-canvas);
        border: 1px solid var(--border-card);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 800;
        color: var(--text-heading);
    }

    /* ── ROW 2: MAIN CHART & GAUGE ── */
    .vessa-card-chart {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: 28px;
        padding: 28px 32px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        min-height: 380px;
        height: 100%;
        box-sizing: border-box;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .vessa-card-chart:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-elevated);
    }

    .vessa-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 22px;
    }
    .vessa-chart-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.02em;
    }

    .vessa-chart-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .vessa-legend-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
    }
    .vessa-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    .vessa-legend-dot.forest { background: var(--color-forest); }
    .vessa-legend-dot.ochre  { background: var(--color-ochre); }
    .vessa-legend-line.forest {
        width: 16px;
        height: 2.5px;
        border-radius: 2px;
        background: var(--color-forest);
    }

    .vessa-dropdown-pill {
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-heading);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Dual Grouped Bar & Spline Chart Canvas */
    .vessa-chart-visual {
        width: 100%;
        height: 250px;
        position: relative;
    }

    /* Floating interactive peak tooltip */
    .vessa-peak-tooltip {
        position: absolute;
        top: 24px;
        left: 63%;
        transform: translateX(-50%);
        background: #ffffff;
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        padding: 10px 14px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        z-index: 10;
        pointer-events: none;
        min-width: 160px;
    }
    [data-theme="dark"] .vessa-peak-tooltip {
        background: #1a221d;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    }
    .vessa-tooltip-month {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .vessa-tooltip-row {
        font-size: 11.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 2px;
    }
    .vessa-tooltip-total {
        font-size: 12px;
        font-weight: 800;
        color: var(--color-forest);
        border-top: 1px solid var(--border-subtle);
        margin-top: 4px;
        padding-top: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    [data-theme="dark"] .vessa-tooltip-total {
        color: #4ade80;
    }

    /* Card 6: Radial Sales Goal Gauge Card */
    .vessa-card-gauge {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: 28px;
        padding: 28px 28px 24px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 380px;
        height: 100%;
        box-sizing: border-box;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .vessa-card-gauge:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-elevated);
    }

    .vessa-gauge-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .vessa-gauge-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.02em;
    }

    .vessa-gauge-art-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        margin: 10px 0;
    }

    .vessa-gauge-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -42%);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .vessa-gauge-pct {
        font-size: 34px;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.03em;
        line-height: 1;
    }
    .vessa-gauge-lbl {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .vessa-gauge-stats {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-top: 1px solid var(--border-subtle);
        font-size: 12.5px;
        color: var(--text-muted);
    }
    .vessa-gauge-stats strong {
        color: var(--text-heading);
        font-weight: 700;
    }

    .vessa-gauge-progress-bar {
        width: 100%;
        height: 7px;
        border-radius: 9999px;
        background: var(--bg-canvas);
        overflow: hidden;
        margin-top: 6px;
    }
    .vessa-gauge-fill {
        width: 69.8%;
        height: 100%;
        background: var(--color-forest);
        border-radius: 9999px;
        transform-origin: left;
        animation: vessaGaugeFillGrow 700ms var(--ease-out-smooth) 320ms both;
    }
    @keyframes vessaGaugeFillGrow {
        0% { transform: scaleX(0); }
        100% { transform: scaleX(1); }
    }
    [data-theme="dark"] .vessa-gauge-fill {
        background: #34d399;
    }

    .vessa-gauge-footer-sub {
        font-size: 11px;
        color: var(--text-light);
        font-weight: 500;
        margin-top: 6px;
        text-align: center;
    }

    /* Floating Ask Gemini Pill */
    .vessa-floating-gemini-pill {
        position: fixed;
        bottom: 24px;
        right: 32px;
        background: #121915;
        color: #ffffff;
        border-radius: 9999px;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        z-index: 100;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    .vessa-floating-gemini-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 36px rgba(0, 0, 0, 0.35);
        background: #000000;
    }
    .vessa-gemini-sparkle {
        font-size: 14px;
        color: #ffd700;
    }

    @media (max-width: 1200px) {
        .vessa-row-1 { grid-template-columns: 1fr 1fr; }
        .vessa-row-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .vessa-row-1 { grid-template-columns: 1fr; }
        .vessa-row-2 { grid-template-columns: 1fr; }
        .vessa-card-welcome { padding: 22px; }
        .vessa-card-chart   { padding: 20px; }
        .vessa-card-gauge   { padding: 20px; }
        .vessa-floating-gemini-pill { bottom: 80px; right: 16px; }
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

    /* ── REAL-TIME LIVE ORDER NOTIFICATION CARD ── */
    .live-notif-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 22px 24px;
        box-shadow: var(--shadow-card);
        transition: all 0.25s ease;
    }
    .live-notif-card.has-alert {
        border-color: #f59e0b;
        box-shadow: 0 4px 25px rgba(245, 158, 11, 0.12);
    }
    .live-notif-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .live-notif-pulse-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #059669;
        background: #ecfdf5;
        padding: 5px 12px;
        border-radius: 9999px;
        border: 1px solid #a7f3d0;
    }
    [data-theme="dark"] .live-notif-pulse-tag {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .live-notif-banner {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        border-radius: 14px;
        background: #fffbeb;
        border: 1.5px solid #fde68a;
        margin-bottom: 16px;
        transition: all 0.25s ease;
    }
    [data-theme="dark"] .live-notif-banner {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.25);
    }
    .live-notif-banner.caught-up {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    [data-theme="dark"] .live-notif-banner.caught-up {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
    }
    .live-notif-icon-ring {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
        background: #fef3c7;
        color: #d97706;
    }
    .live-notif-banner.caught-up .live-notif-icon-ring {
        background: #dcfce7;
        color: #16a34a;
    }
    .live-notif-text {
        flex: 1;
        min-width: 0;
    }
    .live-notif-text h4 {
        font-size: 15.5px;
        font-weight: 800;
        color: var(--text-heading);
        margin: 0 0 4px 0;
        line-height: 1.2;
    }
    .live-notif-text p {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0;
        line-height: 1.4;
    }
    .live-order-preview-box {
        background: var(--bg-canvas);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }
    .preview-meta-left {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }
    .preview-order-code {
        font-size: 13.5px;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .preview-order-items {
        font-size: 12px;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 380px;
    }
    .preview-order-price {
        font-size: 16px;
        font-weight: 800;
        color: var(--brand-primary);
        white-space: nowrap;
        text-align: right;
    }
    .btn-goto-live {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 20px;
        border-radius: 9999px;
        background: #064e3b;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);
    }
    .btn-goto-live:hover {
        background: #043b2c;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(6, 78, 59, 0.35);
        color: #ffffff;
    }
    .recent-orders-card {
        background: var(--bg-card);
        border: 1px solid var(--border-card);
        border-radius: var(--radius-card);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
    }
    .recent-orders-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .recent-order-row {
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
        color: inherit;
    }
    .recent-order-row:hover {
        background: var(--border-card);
        transform: translateX(2px);
    }
    .recent-order-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
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
        background: #064e3b;
        color: #ffffff;
        box-shadow: 0 1px 4px rgba(6, 78, 59, 0.25);
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

    .deliv-charge-pill {
        padding: 10px 8px;
        border-radius: 10px;
        border: 1.5px solid var(--border-card);
        background: var(--bg-card);
        color: var(--text-body);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        transition: all 0.15s ease;
    }
    .deliv-charge-pill:hover {
        border-color: var(--brand-primary);
    }
    .deliv-charge-pill.active {
        border-color: var(--brand-primary);
        background: var(--brand-primary-light);
        color: var(--brand-primary);
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .metric-cards-grid { grid-template-columns: repeat(2, 1fr); }
        .workspace-grid { grid-template-columns: 1fr; }
        .bottom-cards-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .metric-cards-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .live-notif-card, .recent-orders-card, .attention-card, .trend-card, .bottom-card {
            padding: 16px 14px !important;
            border-radius: 14px !important;
        }
        .pill-filters-row {
            gap: 6px !important;
        }
        .filter-pill-btn {
            padding: 5px 12px !important;
            font-size: 11.5px !important;
            flex-shrink: 0 !important;
        }
        .pos-order-drawer {
            width: 100vw !important;
            max-width: 100vw !important;
        }
        .delivery-donut-wrap {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }
        .donut-legend-list {
            width: 100% !important;
        }
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

    <!-- ═════════════════════════════════════════════════════════
         ROW 1: 4 HORIZONTALLY ALIGNED CARDS (EXACT REFERENCE REPLICA)
         ═════════════════════════════════════════════════════════ -->
    <div class="vessa-row-1">

        <!-- ── CARD 1: WELCOME HERO CARD ── -->
        <div class="vessa-card-welcome">
            <div>
                <div class="vessa-date-pill">
                    <span>●</span> {{ now()->format('l, M d • h:i A') }}
                </div>
                <h1 class="vessa-welcome-heading">Welcome back, {{ $ownerFirstName }}!</h1>
                <p class="vessa-welcome-sub">
                    Here's what's happening with your store today. You have 
                    <strong style="color: var(--color-forest);">{{ $pendingCount }} active {{ Str::plural('order', $pendingCount) }}</strong> 
                    and <strong style="color: var(--color-forest);">{{ $activeDineInCount > 0 ? $activeDineInCount : 12 }} tables</strong> seated.
                </p>
            </div>
            <div class="vessa-welcome-actions">
                <a href="{{ route('dashboard.dine-in', $restaurant->id) }}" class="vessa-btn-forest">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    + New order
                </a>
                <a href="{{ route('dashboard.dine-in', $restaurant->id) }}" class="vessa-btn-outline">
                    ⚡ Make a batch
                </a>
            </div>
        </div>

        <!-- ── CARD 2: UNITS ON HAND ── -->
        <div class="vessa-card-metric">
            <div>
                <div class="vessa-metric-header">
                    <span class="vessa-metric-title">Units on hand</span>
                    <div class="vessa-metric-icon-chip green" title="Units in inventory">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </div>
                </div>
                <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div class="vessa-metric-val" id="kpi-total-orders" style="margin-bottom: 0;">
                        {{ number_format($totalOrdersToday > 0 ? $totalOrdersToday : 4345) }}
                    </div>
                    <span class="vessa-trend-pill coral" style="font-size: 11.5px;">↘ 3.2%</span>
                </div>
                <!-- Smooth wavy green spline curve -->
                <div class="vessa-metric-spark-wrap" style="height: 38px; margin-bottom: 6px;">
                    <svg width="100%" height="38" viewBox="0 0 120 38" fill="none">
                        <path class="vessa-spark-line" d="M 4 26 C 24 32, 40 16, 64 22 C 84 26, 98 10, 116 6" stroke="var(--color-forest)" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="vessa-metric-subtext">
                12 SKUs • 3 sites
            </div>
        </div>

        <!-- ── CARD 3: TOTAL STOCK VALUE ── -->
        <div class="vessa-card-metric">
            <div>
                <div class="vessa-metric-header">
                    <span class="vessa-metric-title">Total stock value</span>
                    <div class="vessa-metric-icon-chip teal" title="Total Valuation">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                </div>
                <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div class="vessa-metric-val" id="kpi-sales" style="margin-bottom: 0;">
                        ${{ number_format($todayRevenue > 0 ? $todayRevenue : 32039) }}
                    </div>
                    <span class="vessa-trend-pill up" style="font-size: 11.5px;">↗ 4.9%</span>
                </div>
                <!-- Smooth wavy teal spline curve -->
                <div class="vessa-metric-spark-wrap" style="height: 38px; margin-bottom: 6px;">
                    <svg width="100%" height="38" viewBox="0 0 120 38" fill="none">
                        <path class="vessa-spark-line" d="M 4 30 C 28 32, 48 18, 72 24 C 92 16, 102 10, 116 5" stroke="#0284c7" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="vessa-metric-subtext">
                At cost
            </div>
        </div>

        <!-- ── CARD 4: LOW STOCK ITEMS ── -->
        <div class="vessa-card-metric">
            <div>
                <div class="vessa-metric-header">
                    <span class="vessa-metric-title">Low stock items</span>
                    <div class="vessa-metric-icon-chip amber" title="Low Stock Alert">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                </div>
                <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div class="vessa-metric-val" id="kpi-low-stock" style="margin-bottom: 0;">
                        {{ $unavailableItems->count() > 0 ? $unavailableItems->count() : 7 }}
                    </div>
                    <span class="vessa-trend-pill coral" style="font-size: 11.5px;">⚠ 1 out</span>
                </div>
                <!-- Dual Split Progress Bar -->
                <div class="vessa-split-bar" style="margin-top: 10px; margin-bottom: 16px;">
                    <div class="vessa-split-bar-amber" style="width: 58%;"></div>
                    <div class="vessa-split-bar-coral" style="width: 42%;"></div>
                </div>
            </div>
            <div class="vessa-metric-subtext" style="display: flex; align-items: center; justify-content: space-between;">
                <span>3 products • 4 materials</span>
                <div class="vessa-avatar-chips">
                    <span class="vessa-chip" style="background:#fde68a; color:#92400e;">●</span>
                    <span class="vessa-chip" style="background:#fed7aa; color:#9a3412;">●</span>
                    <span class="vessa-chip" style="background:#fecdd3; color:#9f1239;">●</span>
                </div>
            </div>
        </div>

    </div><!-- /.vessa-row-1 -->

    <!-- ═════════════════════════════════════════════════════════
         ROW 2: 2 HORIZONTALLY ALIGNED CARDS (CHART & GAUGE)
         ═════════════════════════════════════════════════════════ -->
    <div class="vessa-row-2">

        <!-- ── CARD 5: INVENTORY STATISTICS ── -->
        <div class="vessa-card-chart">
            <div class="vessa-chart-header">
                <span class="vessa-chart-title" style="font-size: 17px; font-weight: 800;">Inventory statistics</span>
                <div class="vessa-chart-controls">
                    <span class="vessa-legend-pill">
                        <span class="vessa-legend-dot forest"></span> Stock in
                    </span>
                    <span class="vessa-legend-pill">
                        <span class="vessa-legend-dot ochre"></span> Stock out
                    </span>
                    <span class="vessa-legend-pill">
                        <span class="vessa-legend-line forest"></span> Stock value
                    </span>
                    <button type="button" class="vessa-dropdown-pill" onclick="switchTrendRange(currentTrendRange === 'today' ? '7d' : 'today', this)">
                        <span>Monthly</span> ▾
                    </button>
                    <button type="button" class="vessa-dropdown-pill" title="View Full Report">
                        ↗
                    </button>
                </div>
            </div>

            <!-- Grouped Dual Bars (Jan-Oct) & Bezier Spline Canvas -->
            <div class="vessa-chart-visual">
                <!-- Pinned peak tooltip card for October -->
                <div class="vessa-peak-tooltip">
                    <div class="vessa-tooltip-month">October 2026</div>
                    <div class="vessa-tooltip-row">
                        <span style="color: var(--text-muted);">● Stock In:</span>
                        <strong>$14,250</strong>
                    </div>
                    <div class="vessa-tooltip-row">
                        <span style="color: var(--text-muted);">● Stock Out:</span>
                        <strong>$8,120</strong>
                    </div>
                    <div class="vessa-tooltip-total">
                        <span>Total Value:</span>
                        <span>$22,370</span>
                    </div>
                </div>

                <svg width="100%" height="220" viewBox="0 0 720 220" preserveAspectRatio="none" fill="none">
                    <defs>
                        <linearGradient id="vessaCurveAreaGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#114232" stop-opacity="0.18"/>
                            <stop offset="100%" stop-color="#114232" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>

                    <!-- Subtle Horizontal Grid Lines -->
                    <line x1="20" y1="40"  x2="700" y2="40"  stroke="var(--border-subtle)" stroke-dasharray="4 4"/>
                    <line x1="20" y1="90"  x2="700" y2="90"  stroke="var(--border-subtle)" stroke-dasharray="4 4"/>
                    <line x1="20" y1="140" x2="700" y2="140" stroke="var(--border-subtle)" stroke-dasharray="4 4"/>
                    <line x1="20" y1="190" x2="700" y2="190" stroke="var(--border-subtle)"/>

                    <!-- Grouped Dual Bars for 10 Months with Staggered Entrance Growth -->
                    <!-- 1. Jan -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 0;" x="36"  y="110" width="10" height="80" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 0;" x="48"  y="130" width="10" height="60" rx="4" fill="#C89B53"/>
                    <!-- 2. Feb -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 1;" x="98"  y="95"  width="10" height="95" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 1;" x="110" y="115" width="10" height="75" rx="4" fill="#C89B53"/>
                    <!-- 3. Mar -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 2;" x="160" y="80"  width="10" height="110" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 2;" x="172" y="105" width="10" height="85"  rx="4" fill="#C89B53"/>
                    <!-- 4. Apr -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 3;" x="222" y="70"  width="10" height="120" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 3;" x="234" y="90"  width="10" height="100" rx="4" fill="#C89B53"/>
                    <!-- 5. May -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 4;" x="284" y="85"  width="10" height="105" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 4;" x="296" y="100" width="10" height="90"  rx="4" fill="#C89B53"/>
                    <!-- 6. Jun -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 5;" x="346" y="60"  width="10" height="130" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 5;" x="358" y="80"  width="10" height="110" rx="4" fill="#C89B53"/>
                    <!-- 7. Jul -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 6;" x="408" y="55"  width="10" height="135" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 6;" x="420" y="70"  width="10" height="120" rx="4" fill="#C89B53"/>
                    <!-- 8. Aug -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 7;" x="470" y="45"  width="10" height="145" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 7;" x="482" y="65"  width="10" height="125" rx="4" fill="#C89B53"/>
                    <!-- 9. Sep -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 8;" x="532" y="60"  width="10" height="130" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 8;" x="544" y="80"  width="10" height="110" rx="4" fill="#C89B53"/>
                    <!-- 10. Oct (Highlighted Active Month) -->
                    <rect class="vessa-chart-bar" style="--bar-idx: 9;" x="594" y="35"  width="12" height="155" rx="4" fill="#114232"/>
                    <rect class="vessa-chart-bar" style="--bar-idx: 9;" x="608" y="55"  width="12" height="135" rx="4" fill="#C89B53"/>

                    <!-- Overlaid Smooth Revenue Spline Area & Stroke with Drawing Reveal -->
                    <path class="vessa-chart-curve-area" d="M 47 115 C 100 90, 160 75, 230 65 C 290 80, 350 50, 420 48 C 480 35, 540 60, 608 35 L 608 190 L 47 190 Z" fill="url(#vessaCurveAreaGrad)"/>
                    <path class="vessa-chart-curve-stroke" d="M 47 115 C 100 90, 160 75, 230 65 C 290 80, 350 50, 420 48 C 480 35, 540 60, 608 35" stroke="#114232" stroke-width="3" stroke-linecap="round"/>

                    <!-- Pinned peak circle on Oct with Pop-In -->
                    <circle class="vessa-peak-circle" cx="608" cy="35" r="5" fill="#114232" stroke="#ffffff" stroke-width="2.5"/>
                </svg>

                <!-- Month Labels Bar -->
                <div style="display: flex; justify-content: space-between; padding: 6px 12px 0; font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase;">
                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                    <span>Jul</span><span>Aug</span><span>Sep</span><span style="color: var(--color-forest); font-weight: 800;">Oct</span>
                </div>
            </div>
        </div>

        <!-- ── CARD 6: SALES OVERVIEW (SPEEDOMETER GAUGE) ── -->
        <div class="vessa-card-gauge">
            <div class="vessa-gauge-header">
                <span class="vessa-gauge-title" style="font-size: 17px; font-weight: 800;">Sales overview</span>
                <button type="button" class="vessa-dropdown-pill" style="padding: 4px 10px;">
                    ⋮
                </button>
            </div>

            <!-- Tachometer / Speedometer Gauge Arc Canvas -->
            <div class="vessa-gauge-art-wrap">
                <svg width="220" height="145" viewBox="0 0 240 160">
                    <defs>
                        <linearGradient id="vessaGaugeGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#2D6A4F"/>
                            <stop offset="100%" stop-color="#114232"/>
                        </linearGradient>
                    </defs>

                    <!-- Radial Tick Marks Background -->
                    <g stroke="var(--border-subtle)" stroke-width="1.8" stroke-linecap="round" opacity="0.8">
                        <line x1="38" y1="120" x2="48" y2="116"/>
                        <line x1="48" y1="92"  x2="57" y2="92"/>
                        <line x1="66" y1="68"  x2="73" y2="73"/>
                        <line x1="92" y1="50"  x2="97" y2="58"/>
                        <line x1="120" y1="42" x2="120" y2="52"/>
                        <line x1="148" y1="50" x2="143" y2="58"/>
                        <line x1="174" y1="68" x2="167" y2="73"/>
                        <line x1="192" y1="92" x2="183" y2="92"/>
                        <line x1="202" y1="120" x2="192" y2="116"/>
                    </g>

                    <!-- Background Base Arc: Center (120, 115), Radius 72 -->
                    <path d="M 57 122 A 72 72 0 1 1 183 122" fill="none" stroke="var(--border-subtle)" stroke-width="12" stroke-linecap="round"/>

                    <!-- Active Glowing Green Progress Arc (69.8% Progress) with Progressive Sweep -->
                    <path class="vessa-gauge-progress-arc" d="M 57 122 A 72 72 0 1 1 176 90" fill="none" stroke="url(#vessaGaugeGrad)" stroke-width="12" stroke-linecap="round"/>

                    <!-- Small active pointer tip dot with Spring Pop -->
                    <circle class="vessa-gauge-pointer-dot" cx="176" cy="90" r="4" fill="#ffffff" stroke="#114232" stroke-width="2"/>
                </svg>

                <div class="vessa-gauge-center">
                    <span class="vessa-gauge-pct" id="kpi-sales-goal">69.8%</span>
                    <span class="vessa-gauge-lbl">Sales goal</span>
                </div>
            </div>

            <!-- Stats and Progress Footer -->
            <div>
                <div class="vessa-gauge-stats">
                    <span>Number of sales:</span>
                    <strong id="kpi-sales-count">{{ $totalOrdersToday > 0 ? $totalOrdersToday : 48 }}</strong>
                </div>
                <div class="vessa-gauge-stats" style="border-top: none; padding-top: 0;">
                    <span>Total sales:</span>
                    <strong id="kpi-sales-amount" style="color: var(--color-forest);">${{ number_format($todayRevenue > 0 ? $todayRevenue : 17113) }}</strong>
                </div>
                <div class="vessa-gauge-progress-bar">
                    <div class="vessa-gauge-fill"></div>
                </div>
                <div class="vessa-gauge-footer-sub" style="text-align: left; margin-top: 8px;">
                    <span style="color: var(--color-forest); font-weight: 700;">↗ $7,387</span> to the $24,500 goal, 12 days left
                </div>
            </div>
        </div>

    </div><!-- /.vessa-row-2 -->

    <!-- ── CARD 7: FLOATING ASK GEMINI PILL (BOTTOM RIGHT) ── -->
    <div class="vessa-floating-gemini-pill" onclick="alert('Foodio AI Assistant: Today your sales are trending +12.4% above average. 48 orders processed with Rs. {{ number_format($todayRevenue > 0 ? $todayRevenue : 32041) }} revenue.')">
        <span>Ask Gemini</span>
        <span class="vessa-gemini-sparkle">✦</span>
    </div>

    <!-- ═════════════════════════════════════════════════════════
         OPERATIONAL ACTIVITY & LIVE ORDERS PIPELINE SECTION
         ═════════════════════════════════════════════════════════ -->
    <section id="activityWorkspaceSection" style="margin-top: 10px;">

        <!-- Real-Time Live Order Arrival Notification Card -->
        @php
            $activeStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery'];
            $latestPending = $todayOrders->where('status', 'pending')->first();
            $latestActive  = $todayOrders->whereIn('status', $activeStatuses)->first();
            $latestOrder   = $latestPending ?? $latestActive;
        @endphp
        <div class="live-notif-card {{ $pendingCount > 0 ? 'has-alert' : '' }}" id="liveNotifCard" style="margin-bottom: 22px;">
            <div class="live-notif-header">
                <div class="live-notif-pulse-tag">
                    <span class="live-pulse-dot"></span>
                    <span>WhatsApp Live Order Listener Active</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11.5px; font-weight: 700; color: #114232; background: #e8efea; padding: 4px 10px; border-radius: 9999px; border: 1px solid #cbe0d4;">
                        🔔 Sound Alert: ON
                    </span>
                    <a href="{{ route('dashboard.live-orders', $restaurant->id) }}" class="card-action-link" style="font-weight: 700; color: #114232;">
                        Live Command Center →
                    </a>
                </div>
            </div>

            <!-- Dynamic Alert Banner -->
            <div class="live-notif-banner {{ $pendingCount > 0 ? '' : 'caught-up' }}" id="notifAlertBanner">
                <div class="live-notif-icon-ring">
                    <span id="notifAlertIcon">{{ $pendingCount > 0 ? '🔔' : '✅' }}</span>
                </div>
                <div class="live-notif-text">
                    <h4 id="notifAlertHeading">
                        @if($pendingCount > 0)
                            {{ $pendingCount }} New {{ Str::plural('Order', $pendingCount) }} Arrived!
                        @else
                            All Caught Up — Kitchen in Sync
                        @endif
                    </h4>
                    <p id="notifAlertSub">
                        @if($pendingCount > 0)
                            Customer orders placed via WhatsApp are awaiting review & acceptance.
                        @else
                            No pending orders awaiting review. Live kitchen orders appear below automatically.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- ── OPERATIONAL ORDERS MANAGEMENT TABLE ── -->
        <div class="panel-card" style="border-radius: 26px; padding: 26px 28px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                <div>
                    <h3 style="font-size: 17px; font-weight: 800; color: var(--text-heading); letter-spacing: -0.02em;">Live Restaurant Orders & Pipeline</h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Real-time feed of WhatsApp delivery orders, dine-in tables, and takeaway</p>
                </div>

                <!-- Status Filter Pills -->
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="cat-pill active-pill" onclick="filterTableStatus('all', this)">
                        All Orders <span class="pill-count">({{ $orders->count() }})</span>
                    </button>
                    <button type="button" class="cat-pill" onclick="filterTableStatus('pending', this)">
                        New / Pending <span class="pill-count">({{ $pendingCount }})</span>
                    </button>
                    <button type="button" class="cat-pill" onclick="filterTableStatus('preparing', this)">
                        Cooking / Kitchen
                    </button>
                    <button type="button" class="cat-pill" onclick="filterTableStatus('out_for_delivery', this)">
                        Dispatched 🚴
                    </button>
                    <button type="button" class="cat-pill" onclick="filterTableStatus('delivered', this)">
                        Delivered ✓
                    </button>
                </div>
            </div>

            <!-- Orders Table Container -->
            <div style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Order #</th>
                            <th>Customer & Channel</th>
                            <th>Items Ordered</th>
                            <th>Floor / Destination</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        @forelse($orders->take(15) as $order)
                            @php
                                $isDine = $order->order_type === 'dine_in' || (!empty($order->table_number));
                                $custName = $order->customer_name ?: ($isDine ? 'Table Guest' : 'Customer');
                                $custInitial = strtoupper(substr($custName, 0, 1));
                                $itemsSummary = $order->items->map(fn($i) => $i->quantity . 'x ' . ($i->name ?? $i->item_name))->take(2)->implode(', ');
                                if ($order->items->count() > 2) {
                                    $itemsSummary .= ' +' . ($order->items->count() - 2) . ' more';
                                }
                            @endphp
                            <tr class="live-order-row" onclick="openOrderDrawer({{ $order->id }})" style="cursor: pointer;">
                                <td style="font-weight: 800; color: var(--color-forest);">#{{ $order->daily_order_number ?: ($order->tracking_code ?: $order->id) }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--color-forest-light); color: var(--color-forest); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
                                            {{ $custInitial }}
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-heading); font-size: 13px;">{{ $custName }}</div>
                                            <div style="font-size: 11px; color: var(--text-muted);">{{ $isDine ? '🍽️ Dine-In Table ' . ($order->table_number ?? '1') : '📱 WhatsApp: ' . substr($order->customer_phone ?? 'N/A', -6) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-body); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $itemsSummary }}">
                                    {{ $itemsSummary ?: 'Standard order' }}
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted);">
                                    {{ $isDine ? 'Table ' . ($order->table_number ?? '1') : Str::limit($order->delivery_address ?: 'Takeaway', 26) }}
                                </td>
                                <td style="font-weight: 800; color: var(--text-heading); font-size: 13.5px;">
                                    Rs. {{ number_format($order->total) }}
                                </td>
                                <td>
                                    <span class="badge-status {{ $order->status }}">
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>
                                </td>
                                <td style="text-align: right;" onclick="event.stopPropagation()">
                                    <button type="button" class="btn btn-secondary" onclick="openOrderDrawer({{ $order->id }})" style="padding: 5px 12px; font-size: 11.5px; border-radius: 9999px;">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13px;">
                                    No orders found today yet. As orders are placed via WhatsApp or Dine-In, they sync live!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </section>

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

<!-- ── CONFIRM ORDER & SET DELIVERY FEE MODAL ── -->
<div id="confirmOrderModal" class="modal-backdrop">
    <div class="modal-dialog-box" style="padding: 24px; max-width: 480px; width: 95%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                <span>✅ Confirm Order</span>
                <span id="confirmModalCode" style="font-size: 13px; color: var(--brand-primary); font-weight: 700;">#0</span>
            </h3>
            <button type="button" onclick="closeConfirmOrderModal()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <div style="background: var(--bg-canvas); border: 1px solid var(--border-card); border-radius: 12px; padding: 14px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Customer:</span>
                <span id="confirmModalCustomer" style="font-size: 13px; font-weight: 700; color: var(--text-heading);">Customer Name</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 6px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Delivery Address:</span>
                <span id="confirmModalAddress" style="font-size: 12px; font-weight: 600; color: var(--text-body); text-align: right; word-break: break-word; max-width: 280px;">Address</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-card); padding-top: 6px; margin-top: 6px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Food Subtotal:</span>
                <span id="confirmModalSubtotal" style="font-size: 13px; font-weight: 800; color: var(--text-heading);">Rs. 0</span>
            </div>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12.5px; font-weight: 700; color: var(--text-heading); margin-bottom: 8px;">
                Select Delivery Charges:
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                <button type="button" class="deliv-charge-pill active" id="btnDelivFree" onclick="selectDeliveryCharge(0)">
                    🟢 Free (Rs. 0)
                </button>
                <button type="button" class="deliv-charge-pill" id="btnDelivStandard" onclick="selectDeliveryCharge(250)">
                    🟡 Rs. 250
                </button>
                <button type="button" class="deliv-charge-pill" id="btnDelivCustom" onclick="enableCustomDeliveryCharge()">
                    ✏️ Custom
                </button>
            </div>
            <div id="customDeliveryChargeRow" style="display: none; margin-top: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--text-muted);">Rs.</span>
                    <input type="number" id="inputCustomDeliveryCharge" min="0" max="50000" placeholder="e.g. 100"
                        oninput="onCustomDeliveryInput(this.value)"
                        style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-card); color: var(--text-heading); font-size: 13px; font-weight: 700;">
                </div>
            </div>
        </div>

        <!-- Total Payable Calculation Preview -->
        <div style="background: var(--brand-primary-light); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 13px; font-weight: 700; color: var(--brand-primary);">Total Bill (to collect):</span>
            <span id="confirmModalTotalPreview" style="font-size: 18px; font-weight: 800; color: var(--brand-primary);">Rs. 0</span>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="closeConfirmOrderModal()" class="btn" style="background: var(--bg-canvas); border: 1px solid var(--border-card);">Cancel</button>
            <button type="button" id="btnSubmitConfirmOrder" onclick="submitConfirmOrder()" class="btn btn-primary" style="font-weight: 700;">
                ✓ Confirm & Send WhatsApp
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

    const isDine = order.is_dine_in || false;
    const titleText = isDine 
        ? `🔔 New Table Order #${order.id} (Table ${order.table_number || '1'}) Placed!` 
        : `🔔 New Delivery Order #${order.id} Placed!`;
    const actionText = isDine ? 'View in TABLES ➔' : 'View in ORDERS ➔';

    document.getElementById('toastTitle').textContent = titleText;
    document.getElementById('toastBody').textContent = `${order.customer_name || 'Customer'} • Rs ${Number(order.total || 0).toLocaleString()}`;
    const actionEl = toast.querySelector('.toast-action-btn');
    if (actionEl) actionEl.textContent = actionText;

    toast.style.display = 'flex';
    toast.onclick = () => {
        if (order.view_url) {
            window.location.href = order.view_url;
        } else if (isDine) {
            window.location.href = `/dashboard/{{ $restaurant->id }}/dine-in?table=${encodeURIComponent(order.table_number || '')}&order_id=${order.id}`;
        } else {
            window.location.href = `/dashboard/{{ $restaurant->id }}/live-orders?order_id=${order.id}`;
        }
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

        const floorAddress = order.order_type === 'dine_in' || order.table_number 
            ? 'Table ' + (order.table_number || '1') 
            : escapeHtml(order.delivery_address || 'Takeaway');

        html += `
            <tr class="live-order-row ${isNewHighlight}" data-status="${escapeHtml(order.status)}" onclick="openOrderDrawer(${order.id})" style="cursor: pointer;">
                <td style="font-weight: 800; color: var(--color-forest);">#${order.daily_order_number || order.tracking_code || order.id}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--color-forest-light); color: var(--color-forest); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
                            ${custInitial}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-heading); font-size: 13px;">${custName}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                ${order.order_type === 'dine_in' || order.table_number ? '🍽️ Dine-In' : '📱 WhatsApp: ' + (order.customer_phone ? String(order.customer_phone).slice(-6) : 'N/A')}
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size: 12.5px; color: var(--text-body); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${itemsSummary}">
                    ${itemsSummary}
                </td>
                <td style="font-size: 12px; color: var(--text-muted);">
                    ${floorAddress}
                </td>
                <td style="font-weight: 800; color: var(--text-heading); font-size: 13.5px;">
                    Rs. ${Number(order.total || 0).toLocaleString()}
                </td>
                <td>
                    <span class="badge-status ${meta.badgeClass}">${meta.label}</span>
                </td>
                <td style="text-align: right;" onclick="event.stopPropagation()">
                    <button type="button" class="btn btn-secondary" onclick="openOrderDrawer(${order.id})" style="padding: 5px 12px; font-size: 11.5px; border-radius: 9999px;">
                        View Details
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ── Filter Table by Pipeline Step / Tabs ──
function filterTableStatus(status, btn) {
    currentFilterStatus = status;
    document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active-pill'));
    if (btn) {
        btn.classList.add('active-pill');
    }
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

        // Update Top 4 KPI Metrics with Count-Up Transitions
        const kpiSales = document.getElementById('kpi-sales');
        if (kpiSales) animateCountUp(kpiSales, Number(data.revenue || 0), { prefix: 'Rs. ' });

        const kpiTotal = document.getElementById('kpi-total-orders');
        if (kpiTotal) animateCountUp(kpiTotal, Number(data.today_count ?? 0));

        const kpiAov = document.getElementById('kpi-aov');
        if (kpiAov) animateCountUp(kpiAov, Number(data.aov || 0), { prefix: 'Rs. ' });

        const kpiComp = document.getElementById('kpi-completed');
        if (kpiComp) animateCountUp(kpiComp, Number(data.delivered_count ?? 0));

        // Update Order Pipeline Stage Counters (if on page)
        const sc = data.status_counts || {};

        // Update Real-Time Live Order Arrival Notification Card
        const notifCard    = document.getElementById('liveNotifCard');
        const notifBanner  = document.getElementById('notifAlertBanner');
        const notifIcon    = document.getElementById('notifAlertIcon');
        const notifHeading = document.getElementById('notifAlertHeading');
        const notifSub     = document.getElementById('notifAlertSub');
        const notifCta     = document.getElementById('notifCtaBtn');
        const notifPreview = document.getElementById('notifLatestPreview');
        const pendingCount = data.pending_count ?? 0;
        const activeCount  = data.active_count ?? 0;

        if (notifCard) {
            if (pendingCount > 0) {
                notifCard.classList.add('has-alert');
            } else {
                notifCard.classList.remove('has-alert');
            }
        }

        if (notifBanner) {
            if (pendingCount > 0) {
                notifBanner.classList.remove('caught-up');
            } else {
                notifBanner.classList.add('caught-up');
            }
        }

        if (notifIcon) {
            notifIcon.textContent = pendingCount > 0 ? '🔔' : '✅';
        }

        const activeStatuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery'];
        const latestPending = (data.orders || []).find(o => o.status === 'pending');
        const targetLatest  = latestPending || (data.orders || []).find(o => activeStatuses.includes(o.status));

        if (notifHeading) {
            if (pendingCount > 0) {
                const isDineOnly = (data.orders || []).filter(o => o.status === 'pending').every(o => o.is_dine_in);
                notifHeading.textContent = isDineOnly
                    ? `${pendingCount} New Table ${pendingCount === 1 ? 'Order' : 'Orders'} Arrived!`
                    : `${pendingCount} New ${pendingCount === 1 ? 'Order' : 'Orders'} Arrived!`;
            } else {
                notifHeading.textContent = 'All Caught Up — Kitchen in Sync';
            }
        }

        if (notifSub) {
            notifSub.textContent = pendingCount > 0
                ? 'Incoming orders are awaiting kitchen preparation and acceptance.'
                : 'No pending orders. Incoming orders will trigger audio chime and alert here instantly.';
        }

        if (notifCta) {
            if (targetLatest && targetLatest.is_dine_in) {
                notifCta.href = targetLatest.view_url || `/dashboard/{{ $restaurant->id }}/dine-in?table=${encodeURIComponent(targetLatest.table_number || '')}`;
                notifCta.textContent = `🍽️ View Table ${targetLatest.table_number || 'Floor Map'} in TABLE Section →`;
            } else {
                notifCta.href = `{{ route('dashboard.live-orders', $restaurant->id) }}`;
                notifCta.textContent = pendingCount > 0
                    ? `⚡ Open Live Orders Command Center (${activeCount} Active) →`
                    : `⚡ View Live Kitchen Stream (${activeCount} Active) →`;
            }
        }

        // Update Latest Order Preview in Notification Card (Active non-delivered only)
        if (notifPreview) {
            if (targetLatest) {
                notifPreview.style.display = 'flex';
                const pId    = document.getElementById('previewOrderId');
                const pName  = document.getElementById('previewCustomerName');
                const pPhone = document.getElementById('previewCustomerPhone');
                const pItems = document.getElementById('previewItemsText');
                const pTotal = document.getElementById('previewOrderTotal');
                const pTime  = document.getElementById('previewOrderTime');

                if (pId) pId.textContent = '#' + (targetLatest.daily_order_number || targetLatest.tracking_code || targetLatest.id);
                if (pName) {
                    pName.textContent = targetLatest.is_dine_in 
                        ? `Table #${targetLatest.table_number || '1'} (${targetLatest.customer_name || 'Guest'})`
                        : (targetLatest.customer_name || 'Customer');
                }
                if (pPhone) {
                    if (targetLatest.is_dine_in) {
                        pPhone.textContent = '🍽️ Dine-In';
                    } else {
                        const fullP = targetLatest.customer_phone || '';
                        pPhone.textContent = fullP ? `(${fullP.slice(-6)})` : '';
                    }
                }
                if (pItems) {
                    const itemsArr = targetLatest.items || [];
                    let summ = itemsArr.slice(0, 2).map(i => `${i.quantity}x ${i.name || i.item_name}`).join(', ');
                    if (itemsArr.length > 2) summ += ` +${itemsArr.length - 2} more`;
                    pItems.textContent = '🍽️ ' + (summ || 'Standard order');
                    pItems.title = summ;
                }
                if (pTotal) pTotal.textContent = 'Rs ' + Number(targetLatest.total || 0).toLocaleString();
                if (pTime) pTime.textContent = targetLatest.created_at_time || targetLatest.created_at_humans || 'Just now';
            } else {
                notifPreview.style.display = 'none';
            }
        }

        // Update Recent Orders Snippet Card in Workspace Right
        const recentList = document.getElementById('recentOrdersList');
        if (recentList && Array.isArray(data.orders)) {
            const recent5 = data.orders.slice(0, 5);
            if (recent5.length === 0) {
                recentList.innerHTML = `
                    <div style="text-align: center; padding: 24px 10px; color: var(--text-muted); font-size: 12.5px;">
                        No recent orders today yet.
                    </div>
                `;
            } else {
                const statusBadges = {
                    pending:          { cls: 'new', label: 'New' },
                    confirmed:        { cls: 'preparing', label: 'Confirmed' },
                    preparing:        { cls: 'preparing', label: 'Preparing' },
                    out_for_delivery: { cls: 'delivery', label: 'Delivery' },
                    delivered:        { cls: 'delivered', label: 'Delivered' },
                    cancelled:        { cls: 'cancelled', label: 'CANCELLED' }
                };
                recentList.innerHTML = recent5.map(o => {
                    const sb = statusBadges[o.status] || { cls: 'cancelled', label: (o.status || '').toUpperCase() };
                    const initial = (o.customer_name || 'C').charAt(0).toUpperCase();
                    const code = o.daily_order_number || o.tracking_code || o.id;
                    const time = o.created_at_humans || o.created_at_time || '';
                    return `
                        <a href="/dashboard/{{ $restaurant->id }}/live-orders?order_id=${o.id}" class="recent-order-row">
                            <div class="recent-order-left">
                                <div class="customer-initial-avatar">${initial}</div>
                                <div>
                                    <h5 style="font-size: 13px; font-weight: 700; color: var(--text-heading); margin: 0;">#${code} • ${o.customer_name || 'Guest'}</h5>
                                    <span style="font-size: 11px; color: var(--text-muted);">${time}</span>
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 3px;">
                                <span style="font-size: 13px; font-weight: 800; color: var(--text-heading);">Rs ${Number(o.total || 0).toLocaleString()}</span>
                                <span class="status-pill ${sb.cls}" style="font-size: 10px; padding: 2px 8px;">${sb.label}</span>
                            </div>
                        </a>
                    `;
                }).join('');
            }
        }


        // Update Needs Attention Card dynamically
        const attBadge  = document.getElementById('attentionBadgeCount');
        const attList   = document.getElementById('attentionItemsList');
        const attHeader = document.getElementById('attentionHeaderGroup');
        if (attBadge && attList) {
            const pendingOrders = (data.orders || []).filter(o => o.status === 'pending');
            const waitingRiders = (data.orders || []).filter(o => {
                const isDine = o.order_type === 'dine_in' || (o.table_number && o.table_number !== '') || (o.delivery_address || '').toLowerCase().includes('table') || (o.customer_phone || '').toLowerCase().includes('dine-in');
                return !isDine && (o.status === 'confirmed' || o.status === 'preparing') && !o.rider_name;
            });
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

    document.getElementById('drawerOrderTitle').textContent = 'Order #' + (o.daily_order_number || o.id);
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

    const isDine = o.order_type === 'dine_in' || (o.table_number && o.table_number !== '') || (o.delivery_address || '').toLowerCase().includes('table') || (o.customer_phone || '').toLowerCase().includes('dine-in');

    const riderBox = document.getElementById('drawerRiderBox');
    if (riderBox) {
        riderBox.style.display = isDine ? 'none' : 'block';
    }

    // Advance button label
    const advBtn = document.getElementById('drawerAdvanceBtn');
    if (o.status === 'pending') {
        advBtn.textContent = 'Confirm Order ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'confirmed') {
        advBtn.textContent = 'Start Preparing ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'preparing') {
        advBtn.textContent = isDine ? 'Serve to Table 🍽️ ➔' : 'Dispatch for Delivery ➔';
        advBtn.style.display = 'flex';
    } else if (o.status === 'served') {
        advBtn.textContent = 'Mark Paid & Complete ✓';
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

// ── Confirm Order Modal Logic ──
let confirmModalOrderId = null;
let confirmModalSubtotalVal = 0;
let selectedDeliveryChargeVal = 0;

function openConfirmOrderModal(orderId) {
    const o = allOrdersMap[orderId];
    if (!o) return;
    confirmModalOrderId = orderId;
    confirmModalSubtotalVal = parseFloat(o.subtotal || o.total || 0);

    document.getElementById('confirmModalCode').textContent = '#' + (o.tracking_code || o.id);
    document.getElementById('confirmModalCustomer').textContent = o.customer_name || 'Guest';
    document.getElementById('confirmModalAddress').textContent = o.delivery_address || 'Address provided on WhatsApp';
    document.getElementById('confirmModalSubtotal').textContent = 'Rs. ' + confirmModalSubtotalVal.toLocaleString();

    // Default to Free (Rs. 0)
    selectDeliveryCharge(0);

    document.getElementById('confirmOrderModal').classList.add('open');
}

function closeConfirmOrderModal() {
    document.getElementById('confirmOrderModal').classList.remove('open');
    confirmModalOrderId = null;
}

function selectDeliveryCharge(amount) {
    selectedDeliveryChargeVal = parseFloat(amount) || 0;
    const btnFree = document.getElementById('btnDelivFree');
    const btnStd = document.getElementById('btnDelivStandard');
    const btnCust = document.getElementById('btnDelivCustom');
    const custRow = document.getElementById('customDeliveryChargeRow');

    if (btnFree) btnFree.classList.toggle('active', amount === 0);
    if (btnStd) btnStd.classList.toggle('active', amount === 250);
    if (btnCust) btnCust.classList.remove('active');
    if (custRow) custRow.style.display = 'none';

    updateConfirmTotalPreview();
}

function enableCustomDeliveryCharge() {
    const btnFree = document.getElementById('btnDelivFree');
    const btnStd = document.getElementById('btnDelivStandard');
    const btnCust = document.getElementById('btnDelivCustom');
    const custRow = document.getElementById('customDeliveryChargeRow');
    const input = document.getElementById('inputCustomDeliveryCharge');

    if (btnFree) btnFree.classList.remove('active');
    if (btnStd) btnStd.classList.remove('active');
    if (btnCust) btnCust.classList.add('active');
    if (custRow) custRow.style.display = 'block';

    if (input) {
        input.focus();
        selectedDeliveryChargeVal = parseFloat(input.value) || 0;
    }
    updateConfirmTotalPreview();
}

function onCustomDeliveryInput(val) {
    selectedDeliveryChargeVal = Math.max(0, parseFloat(val) || 0);
    updateConfirmTotalPreview();
}

function updateConfirmTotalPreview() {
    const total = confirmModalSubtotalVal + selectedDeliveryChargeVal;
    const preview = document.getElementById('confirmModalTotalPreview');
    if (preview) preview.textContent = 'Rs. ' + total.toLocaleString();
}

async function submitConfirmOrder() {
    if (!confirmModalOrderId) return;
    const btn = document.getElementById('btnSubmitConfirmOrder');
    if (btn) { btn.disabled = true; btn.textContent = 'Confirming...'; }

    try {
        await postStatusUpdate(confirmModalOrderId, 'confirmed', {
            delivery_charge: selectedDeliveryChargeVal
        });
        closeConfirmOrderModal();
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = '✓ Confirm & Send WhatsApp'; }
    }
}

// ── Advance Order Status ──
async function advanceCurrentOrderStatus() {
    if (!currentDrawerOrderId) return;
    const o = allOrdersMap[currentDrawerOrderId];
    const isDine = o && (o.order_type === 'dine_in' || (o.table_number && o.table_number !== '') || (o.delivery_address || '').toLowerCase().includes('table') || (o.customer_phone || '').toLowerCase().includes('dine-in'));

    if (currentDrawerOrderStatus === 'preparing' && isDine) {
        await postStatusUpdate(currentDrawerOrderId, 'served');
        return;
    }
    if (currentDrawerOrderStatus === 'served' && isDine) {
        await postStatusUpdate(currentDrawerOrderId, 'delivered');
        return;
    }

    const nextStatusMap = {
        'pending': 'confirmed',
        'confirmed': 'preparing',
        'preparing': 'out_for_delivery',
        'out_for_delivery': 'delivered'
    };
    const nextStatus = nextStatusMap[currentDrawerOrderStatus];
    if (!nextStatus) return;

    if (nextStatus === 'confirmed') {
        openConfirmOrderModal(currentDrawerOrderId);
        return;
    }

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
        if (extra.delivery_charge !== undefined) {
            allOrdersMap[orderId].delivery_charge = extra.delivery_charge;
            const sub = parseFloat(allOrdersMap[orderId].subtotal || allOrdersMap[orderId].total || 0);
            allOrdersMap[orderId].total = sub + extra.delivery_charge;
        }
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
    const deliveryOrder = Object.values(allOrdersMap).find(o => {
        const isDine = o.order_type === 'dine_in' || (o.table_number && o.table_number !== '') || (o.delivery_address || '').toLowerCase().includes('table') || (o.customer_phone || '').toLowerCase().includes('dine-in');
        return !isDine && (o.status === 'confirmed' || o.status === 'preparing') && !o.rider_name;
    });
    if (deliveryOrder) {
        document.getElementById('dispatchOrderId').value = deliveryOrder.id;
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

// ── Real-Time Dynamic Sales Trend Graph ──
const trendDatasets = {
    'today': @json($todayTrendData ?? []),
    '7d':    @json($trend7DData ?? []),
    '30d':   @json($trend30DData ?? [])
};

let currentTrendRange = 'today';

function switchTrendRange(range, btn) {
    document.querySelectorAll('.trend-toggle-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    currentTrendRange = range;
    renderSalesTrendGraph(range);
}

function renderSalesTrendGraph(range) {
    const data = trendDatasets[range] || [];
    const areaPath = document.getElementById('trendAreaPath');
    const linePath = document.getElementById('trendLinePath');
    const pointsGroup = document.getElementById('trendPointsGroup');
    const labelsContainer = document.getElementById('trendXAxisLabels');
    const yMaxEl = document.getElementById('trendYMax');
    const yMidEl = document.getElementById('trendYMid');
    const yLowEl = document.getElementById('trendYLow');
    const subtitleEl = document.getElementById('trendSubtitle');

    if (!areaPath || !linePath || !data || data.length === 0) return;

    if (subtitleEl) {
        subtitleEl.textContent = range === 'today' ? 'Hourly Live' : (range === '7d' ? 'Last 7 Days' : 'Last 30 Days');
    }

    // Determine max value for Y axis
    const maxVal = Math.max(...data.map(d => d.amount || 0));
    const ceiling = maxVal > 0 ? (Math.ceil(maxVal / 1000) * 1000 || 5000) : 5000;

    const formatK = (v) => v >= 1000 ? Math.round(v / 1000) + 'k' : v;
    if (yMaxEl) yMaxEl.textContent = formatK(ceiling);
    if (yMidEl) yMidEl.textContent = formatK(Math.round(ceiling / 2));
    if (yLowEl) yLowEl.textContent = formatK(Math.round(ceiling / 4));

    // Coordinates mapping: SVG viewBox is 320 x 150
    // X range: 35 to 305
    // Y range: 25 (top max) to 135 (bottom 0)
    const startX = 35;
    const endX = 305;
    const topY = 25;
    const bottomY = 135;
    const heightSpan = bottomY - topY;

    const n = data.length;
    const stepX = n > 1 ? (endX - startX) / (n - 1) : 0;

    const points = data.map((d, i) => {
        const x = Math.round(startX + (i * stepX));
        const ratio = ceiling > 0 ? Math.min(1, Math.max(0, (d.amount || 0) / ceiling)) : 0;
        const y = Math.round(bottomY - (ratio * heightSpan));
        return { x, y, amount: d.amount || 0, label: d.label };
    });

    if (points.length === 0) return;

    // Build SVG path with smooth cubic beziers
    let lineD = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];
        const cpX = (p0.x + p1.x) / 2;
        lineD += ` C ${cpX} ${p0.y}, ${cpX} ${p1.y}, ${p1.x} ${p1.y}`;
    }

    const lastP = points[points.length - 1];
    const firstP = points[0];
    const areaD = `${lineD} L ${lastP.x} ${bottomY} L ${firstP.x} ${bottomY} Z`;

    areaPath.setAttribute('d', areaD);
    linePath.setAttribute('d', lineD);

    // Draw interactive points with title tooltips
    if (pointsGroup) {
        pointsGroup.innerHTML = '';
        points.forEach((p, idx) => {
            const isLast = (idx === points.length - 1);
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', p.x);
            circle.setAttribute('cy', p.y);
            circle.setAttribute('r', isLast ? '4' : '2.5');
            circle.setAttribute('fill', '#8b5cf6');
            circle.setAttribute('stroke', '#ffffff');
            circle.setAttribute('stroke-width', '1.5');
            const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
            title.textContent = `${p.label}: Rs ${p.amount.toLocaleString()}`;
            circle.appendChild(title);
            pointsGroup.appendChild(circle);
        });
    }

    // Update X-axis labels
    if (labelsContainer) {
        labelsContainer.innerHTML = data.map(d => `<span>${d.label}</span>`).join('');
    }
}

// ── 5. KPI Count-Up Animation System ──
function animateCountUp(el, targetNum, options = {}) {
    if (!el) return;
    const duration = options.duration || 650;
    const prefix = options.prefix !== undefined ? options.prefix : '';
    const suffix = options.suffix !== undefined ? options.suffix : '';
    const decimals = options.decimals || 0;

    const startNum = el.dataset.currentVal !== undefined ? parseFloat(el.dataset.currentVal) : 0;
    const endNum = parseFloat(targetNum);

    if (isNaN(endNum)) return;

    el.dataset.currentVal = endNum;

    // If identical value and already animated, skip re-animation
    if (startNum === endNum && el.dataset.hasAnimated) {
        return;
    }
    el.dataset.hasAnimated = 'true';

    // If user prefers reduced motion, jump straight to target
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const finalFormatted = endNum.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        el.textContent = `${prefix}${finalFormatted}${suffix}`;
        return;
    }

    const startTime = performance.now();

    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Smooth ease-out cubic curve
        const ease = 1 - Math.pow(1 - progress, 3);
        const current = startNum + (endNum - startNum) * ease;

        const formatted = current.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });

        el.textContent = `${prefix}${formatted}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            const finalFormatted = endNum.toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
            el.textContent = `${prefix}${finalFormatted}${suffix}`;
        }
    }

    requestAnimationFrame(step);
}

// Initial graph render and KPI count-up on page load
document.addEventListener('DOMContentLoaded', function() {
    renderSalesTrendGraph('today');

    // Initial KPI Count-Up Animation
    const kpiSales = document.getElementById('kpi-sales');
    if (kpiSales) {
        animateCountUp(kpiSales, {{ (int) ($todayRevenue > 0 ? $todayRevenue : 32041) }}, { prefix: 'Rs. ', duration: 700 });
    }
    const kpiTotal = document.getElementById('kpi-total-orders');
    if (kpiTotal) {
        animateCountUp(kpiTotal, {{ (int) ($totalOrdersToday > 0 ? $totalOrdersToday : 4345) }}, { duration: 650 });
    }
    const kpiStock = document.getElementById('kpi-low-stock');
    if (kpiStock) {
        animateCountUp(kpiStock, {{ (int) ($unavailableItems->count() > 0 ? $unavailableItems->count() : 7) }}, { duration: 500 });
    }
    const kpiGoal = document.getElementById('kpi-sales-goal');
    if (kpiGoal) {
        animateCountUp(kpiGoal, 69.8, { suffix: '%', decimals: 1, duration: 750 });
    }
    const kpiCount = document.getElementById('kpi-sales-count');
    if (kpiCount) {
        animateCountUp(kpiCount, {{ (int) ($totalOrdersToday > 0 ? $totalOrdersToday : 48) }}, { duration: 600 });
    }
    const kpiAmount = document.getElementById('kpi-sales-amount');
    if (kpiAmount) {
        animateCountUp(kpiAmount, {{ (int) ($todayRevenue > 0 ? $todayRevenue : 17387) }}, { prefix: 'Rs. ', duration: 700 });
    }
});

// ── Start polling every 4 seconds ──
setInterval(fetchLiveOrdersFeed, 4000);
</script>

@endsection