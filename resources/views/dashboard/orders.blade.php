@extends('layouts.dashboard')
@section('title', 'Operations & POS Terminal — ' . ($restaurant->name ?? 'Foodio'))
@section('header_title', 'Operations Terminal')
@section('header_subtitle', 'Live Kitchen Orders & Real-Time Fulfillment')

@section('content')

<style>
    /* ═════════════════════════════════════════════════════════════
       FOODIO OPERATIONAL DASHBOARD — PLUS JAKARTA SANS & POS VIBES
       ═════════════════════════════════════════════════════════════ */
    :root {
        --pos-canvas: #f7f7f5;
        --pos-card: #ffffff;
        --pos-border: #e8e8e8;
        --pos-border-subtle: #f0f0ee;
        --pos-text: #181818;
        --pos-text-muted: #737373;
        --pos-text-faint: #a3a3a3;
        --pos-accent: #181818;
        --pos-accent-hover: #262626;

        /* Semantic Status Colors */
        --st-new-bg: #fff7ed;
        --st-new-text: #c2410c;
        --st-new-border: #ffedd5;

        --st-prep-bg: #eff6ff;
        --st-prep-text: #1d4ed8;
        --st-prep-border: #dbeafe;

        --st-ready-bg: #eef2ff;
        --st-ready-text: #4338ca;
        --st-ready-border: #e0e7ff;

        --st-deliv-bg: #f0fdf4;
        --st-deliv-text: #15803d;
        --st-deliv-border: #dcfce7;

        --st-cancel-bg: #fef2f2;
        --st-cancel-text: #b91c1c;
        --st-cancel-border: #fee2e2;

        --st-attn-bg: #fffbeb;
        --st-attn-text: #b45309;
        --st-attn-border: #fde68a;
    }

    [data-theme="dark"] {
        --pos-canvas: #0b0f19;
        --pos-card: #131b2e;
        --pos-border: rgba(255, 255, 255, 0.08);
        --pos-border-subtle: rgba(255, 255, 255, 0.04);
        --pos-text: #f8fafc;
        --pos-text-muted: #94a3b8;
        --pos-text-faint: #64748b;
        --pos-accent: #f8fafc;
        --pos-accent-hover: #e2e8f0;

        --st-new-bg: rgba(245, 158, 11, 0.15);
        --st-new-text: #f59e0b;
        --st-new-border: rgba(245, 158, 11, 0.25);

        --st-prep-bg: rgba(59, 130, 246, 0.15);
        --st-prep-text: #60a5fa;
        --st-prep-border: rgba(59, 130, 246, 0.25);

        --st-ready-bg: rgba(99, 102, 241, 0.15);
        --st-ready-text: #818cf8;
        --st-ready-border: rgba(99, 102, 241, 0.25);

        --st-deliv-bg: rgba(34, 197, 94, 0.15);
        --st-deliv-text: #4ade80;
        --st-deliv-border: rgba(34, 197, 94, 0.25);

        --st-cancel-bg: rgba(239, 68, 68, 0.15);
        --st-cancel-text: #f87171;
        --st-cancel-border: rgba(239, 68, 68, 0.25);

        --st-attn-bg: rgba(245, 158, 11, 0.12);
        --st-attn-text: #fbbf24;
        --st-attn-border: rgba(245, 158, 11, 0.25);
    }

    .pos-operational-layout {
        display: flex;
        flex-direction: column;
        gap: 18px;
        color: var(--pos-text);
        max-width: 1650px;
        margin: 0 auto;
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* ── 1. HEADER & QUICK ACTIONS COMMAND BAR ── */
    .op-command-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        padding-bottom: 2px;
    }

    .op-store-status-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .op-store-title {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        color: var(--pos-text);
    }

    .op-status-pills-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
    }

    .op-pill-online {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #15803d;
        font-weight: 700;
        border: 1px solid #dcfce7;
    }
    [data-theme="dark"] .op-pill-online {
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.3);
    }

    .op-pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #16a34a;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
        animation: opPulse 2s infinite;
    }

    @keyframes opPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    .op-pill-bot {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        background: var(--pos-card);
        color: var(--pos-text-muted);
        border: 1px solid var(--pos-border);
        font-weight: 600;
        text-decoration: none;
    }
    .op-pill-bot:hover { color: var(--pos-text); border-color: var(--pos-text-muted); }

    .op-quick-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .op-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid var(--pos-border);
        background: var(--pos-card);
        color: var(--pos-text);
        transition: all 0.15s ease;
    }
    .op-action-btn:hover {
        background: var(--pos-canvas);
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .op-action-btn.primary {
        background: var(--pos-accent);
        color: #ffffff;
        border-color: var(--pos-accent);
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    [data-theme="dark"] .op-action-btn.primary {
        background: #f8fafc;
        color: #0b0f19;
        border-color: #f8fafc;
    }
    .op-action-btn.primary:hover {
        opacity: 0.92;
    }

    /* ── 2. CORE OPERATIONAL METRIC CARDS (4 CARDS) ── */
    .op-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }

    .op-metric-card {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .op-metric-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .op-metric-card.alert-card {
        background: var(--st-attn-bg);
        border-color: var(--st-attn-border);
        cursor: pointer;
    }

    .op-metric-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .op-metric-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--pos-text-muted);
    }
    .op-metric-card.alert-card .op-metric-label {
        color: var(--st-attn-text);
    }

    .op-metric-val {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--pos-text);
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }
    .op-metric-card.alert-card .op-metric-val {
        color: var(--st-attn-text);
    }

    .op-metric-sub {
        font-size: 11px;
        color: var(--pos-text-muted);
        font-weight: 500;
    }
    .op-metric-card.alert-card .op-metric-sub {
        color: var(--st-attn-text);
        font-weight: 700;
    }

    /* ── 3. ORDER STATUS PIPELINE STRIP ── */
    .op-pipeline-strip {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .op-pipeline-strip::-webkit-scrollbar { display: none; }

    .op-pipeline-step {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        text-decoration: none;
    }
    .op-pipeline-step:hover {
        background: #eaeaea;
        color: var(--pos-text);
    }
    .op-pipeline-step.active {
        background: var(--pos-accent);
        color: #ffffff;
        border-color: var(--pos-accent);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }
    [data-theme="dark"] .op-pipeline-step.active {
        background: #f8fafc;
        color: #0b0f19;
        border-color: #f8fafc;
    }

    .op-step-count {
        font-size: 10.5px;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.08);
    }
    .op-pipeline-step.active .op-step-count {
        background: rgba(255, 255, 255, 0.25);
    }
    [data-theme="dark"] .op-pipeline-step.active .op-step-count {
        background: rgba(0, 0, 0, 0.15);
    }

    .op-step-arrow {
        color: var(--pos-text-faint);
        font-size: 13px;
        user-select: none;
    }

    /* ── 4. TWO-COLUMN OPERATIONS HUB (65% / 35%) ── */
    .op-workspace-grid {
        display: grid;
        grid-template-columns: 1.75fr 1fr;
        gap: 18px;
        align-items: start;
    }

    /* ── LEFT COLUMN: LIVE ORDERS & ATTENTION ── */
    .op-live-column {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* Needs Attention Alert Banner */
    .op-attention-banner {
        background: var(--st-attn-bg);
        border: 1px solid var(--st-attn-border);
        border-radius: 14px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        animation: pulseSubtle 3s infinite;
    }
    @keyframes pulseSubtle {
        0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.2); }
        50% { box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1); }
        100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.2); }
    }

    .op-attention-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--st-attn-text);
        font-size: 12.5px;
        font-weight: 600;
    }

    .op-attention-btn {
        padding: 5px 12px;
        border-radius: 8px;
        background: #d97706;
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .op-attention-btn:hover { background: #b45309; }

    /* Orders Filter & Search Toolbar */
    .op-queue-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .op-queue-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--pos-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .op-search-wrap {
        position: relative;
        flex: 1;
        max-width: 280px;
    }
    .op-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: var(--pos-text-muted);
        pointer-events: none;
    }
    .op-search-input {
        width: 100%;
        padding: 6px 10px 6px 30px;
        border-radius: 999px;
        border: 1px solid var(--pos-border);
        background: var(--pos-card);
        font-size: 12px;
        color: var(--pos-text);
        outline: none;
    }
    .op-search-input:focus {
        border-color: var(--pos-accent);
        box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.05);
    }

    .op-sound-btn {
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid var(--pos-border);
        background: var(--pos-card);
        font-size: 11px;
        font-weight: 700;
        color: var(--pos-text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .op-sound-btn.active {
        color: #15803d;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    /* POS Order Cards Grid */
    .pos-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 14px;
    }

    .pos-order-card {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    .pos-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }
    .pos-order-card.selected {
        border-color: #181818;
        box-shadow: 0 0 0 2px #181818, 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    [data-theme="dark"] .pos-order-card.selected {
        border-color: #f8fafc;
        box-shadow: 0 0 0 2px #f8fafc;
    }

    .pos-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .pos-card-code {
        font-size: 15px;
        font-weight: 800;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: var(--pos-text);
    }

    .pos-card-time {
        font-size: 11px;
        color: var(--pos-text-muted);
        font-weight: 600;
        display: block;
    }

    .pos-status-badge {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 2px 8px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .pos-status-badge.pending   { background: var(--st-new-bg); color: var(--st-new-text); border: 1px solid var(--st-new-border); }
    .pos-status-badge.confirmed { background: var(--st-new-bg); color: var(--st-new-text); border: 1px solid var(--st-new-border); }
    .pos-status-badge.preparing { background: var(--st-prep-bg); color: var(--st-prep-text); border: 1px solid var(--st-prep-border); }
    .pos-status-badge.out_for_delivery { background: var(--st-ready-bg); color: var(--st-ready-text); border: 1px solid var(--st-ready-border); }
    .pos-status-badge.delivered { background: var(--st-deliv-bg); color: var(--st-deliv-text); border: 1px solid var(--st-deliv-border); }
    .pos-status-badge.cancelled { background: var(--st-cancel-bg); color: var(--st-cancel-text); border: 1px solid var(--st-cancel-border); }

    .pos-card-cust-line {
        font-size: 12px;
        font-weight: 700;
        color: var(--pos-text);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pos-card-items-box {
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border-subtle);
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pos-card-item-line {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        color: var(--pos-text);
        line-height: 1.3;
    }
    .pos-card-item-qty {
        font-size: 10px;
        font-weight: 800;
        color: var(--pos-text-muted);
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 4px;
        padding: 0 4px;
    }
    .pos-card-item-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }

    .pos-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid var(--pos-border-subtle);
        gap: 6px;
    }

    .pos-card-price {
        font-size: 15px;
        font-weight: 800;
        color: var(--pos-text);
        font-variant-numeric: tabular-nums;
    }

    .pos-card-advance-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 11px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all 0.15s ease;
    }
    .pos-card-advance-btn.new      { background: #181818; color: #fff; }
    .pos-card-advance-btn.prep     { background: #2563eb; color: #fff; }
    .pos-card-advance-btn.dispatch { background: #4f46e5; color: #fff; }
    .pos-card-advance-btn.complete { background: #16a34a; color: #fff; }
    .pos-card-advance-btn.done     { background: var(--pos-canvas); color: var(--pos-text-muted); border: 1px solid var(--pos-border); cursor: default; }

    /* Empty queue state */
    .pos-empty-grid {
        grid-column: 1 / -1;
        background: var(--pos-card);
        border: 1px dashed var(--pos-border);
        border-radius: 14px;
        padding: 50px 20px;
        text-align: center;
    }

    /* ── RIGHT COLUMN: CONTEXT, DELIVERY & ACTIVITY ── */
    .op-context-column {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .op-panel-card {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .op-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--pos-border-subtle);
    }

    .op-panel-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--pos-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .op-panel-link {
        font-size: 11.5px;
        font-weight: 700;
        color: #2563eb;
        text-decoration: none;
    }
    .op-panel-link:hover { text-decoration: underline; }

    /* Delivery Overview */
    .op-rider-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        background: var(--pos-canvas);
        border-radius: 10px;
        font-size: 12px;
    }
    .op-rider-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .op-rider-avatar {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    /* Top Selling Items */
    .op-top-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        padding: 5px 0;
    }
    .op-top-item-left {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
    }
    .op-item-rank {
        width: 18px;
        height: 18px;
        border-radius: 5px;
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border);
        font-size: 10px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--pos-text-muted);
        flex-shrink: 0;
    }
    .op-item-rank.rank-1 { background: #fef3c7; color: #b45309; border-color: #fde68a; }

    /* Sales Rhythm / Trend Mini Bars */
    .op-trend-bars {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 6px;
        height: 60px;
        padding-top: 10px;
    }
    .op-trend-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        height: 100%;
        justify-content: flex-end;
    }
    .op-trend-bar {
        width: 100%;
        background: #e2e8f0;
        border-radius: 4px 4px 0 0;
        transition: height 0.3s ease;
        min-height: 4px;
    }
    .op-trend-bar.today { background: var(--pos-accent); }
    [data-theme="dark"] .op-trend-bar { background: rgba(255, 255, 255, 0.1); }
    [data-theme="dark"] .op-trend-bar.today { background: #f8fafc; }
    .op-trend-day {
        font-size: 9.5px;
        font-weight: 700;
        color: var(--pos-text-muted);
    }

    /* Recent Activity */
    .op-activity-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .op-activity-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 11.5px;
    }
    .op-activity-left {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow: hidden;
    }

    /* ── SLIDE-OVER POS ORDER DRAWER ── */
    .pos-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        z-index: 9990;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }
    .pos-drawer-backdrop.open { opacity: 1; pointer-events: auto; }

    .pos-drawer {
        position: fixed;
        top: 0; right: 0; bottom: 0;
        width: 440px;
        max-width: 100vw;
        background: var(--pos-card);
        border-left: 1px solid var(--pos-border);
        box-shadow: -10px 0 35px rgba(0, 0, 0, 0.12);
        z-index: 9991;
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .pos-drawer.open { transform: translateX(0); }

    .pos-drawer-head {
        padding: 16px 20px;
        border-bottom: 1px solid var(--pos-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .pos-drawer-close-btn {
        width: 30px; height: 30px;
        border-radius: 50%;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
    }

    .pos-drawer-body {
        padding: 18px 20px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .pos-drawer-section {
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border);
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pos-receipt-ticket {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 12px;
        padding: 14px;
        position: relative;
    }

    .pos-receipt-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        font-size: 12.5px;
        padding: 3px 0;
    }

    .pos-drawer-foot {
        padding: 14px 20px;
        border-top: 1px solid var(--pos-border);
        background: var(--pos-card);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pos-drawer-primary-btn {
        width: 100%;
        padding: 11px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .pos-drawer-sec-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .pos-drawer-sec-btn {
        flex: 1;
        padding: 8px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        text-decoration: none;
    }
    .pos-drawer-sec-btn.danger {
        color: #dc2626;
        border-color: #fecaca;
        background: #fef2f2;
    }

    /* Modals */
    .pos-modal-backdrop {
        display: none;
        position: fixed; inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center; justify-content: center;
        padding: 16px;
    }
    .pos-modal-backdrop.open { display: flex; }

    .pos-modal-window {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 16px;
        width: 480px; max-width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    /* Toast */
    #live-toast {
        position: fixed; bottom: 24px; right: 24px;
        z-index: 10000;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 12.5px; font-weight: 700;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
        transition: opacity 0.25s ease, transform 0.25s ease;
        opacity: 0; pointer-events: none;
        transform: translateY(8px);
    }
    #live-toast.show { opacity: 1; transform: translateY(0); }

    /* Responsive */
    @media (max-width: 1100px) {
        .op-workspace-grid { grid-template-columns: 1fr; }
        .op-metrics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 650px) {
        .op-metrics-grid { grid-template-columns: 1fr; }
        .pos-cards-grid { grid-template-columns: 1fr; }
        .pos-drawer { width: 100vw; }
    }
</style>

<div class="pos-operational-layout">

    <!-- ── 1. HEADER & QUICK ACTIONS COMMAND BAR ── -->
    <div class="op-command-bar">
        <div class="op-store-status-group">
            <h1 class="op-store-title">{{ $restaurant->name ?? 'Restaurant' }}</h1>
            <div class="op-status-pills-row">
                <span class="op-pill-online">
                    <span class="op-pulse-dot"></span>
                    {{ ($restaurant->is_open ?? true) ? 'Taking Orders' : 'Store Paused' }}
                </span>
                <a href="{{ route('dashboard.connect-whatsapp', $restaurant->id) }}" class="op-pill-bot">
                    <span>🤖</span>
                    <span>Bot: {{ $restaurant->bot_status === 'connected' ? 'Connected' : 'Active' }}</span>
                </a>
                <span style="color: var(--pos-text-muted); font-weight: 600;">
                    {{ now()->format('l, M j') }}
                </span>
            </div>
        </div>

        <div class="op-quick-actions">
            <a href="javascript:void(0)" onclick="quickCreateOrderPrompt()" class="op-action-btn primary" title="Manually record walk-in / phone order">
                <span>➕</span>
                <span>New Order</span>
            </a>
            <a href="javascript:void(0)" onclick="filterToLiveQueue()" class="op-action-btn" title="Jump to active kitchen queue">
                <span>📋</span>
                <span>Live Orders (<span id="btn-live-count">{{ $liveOrdersCount }}</span>)</span>
            </a>
            <a href="{{ route('dashboard.menu', $restaurant->id) }}" class="op-action-btn" title="View & update menu items">
                <span>🍔</span>
                <span>Menu</span>
            </a>
            <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="op-action-btn" title="Fleet & active riders">
                <span>🚴</span>
                <span>Delivery ({{ $activeRidersCount }})</span>
            </a>
        </div>
    </div>

    <!-- ── 2. OPERATIONAL KPI METRICS (4 CARDS) ── -->
    <div class="op-metrics-grid">
        <!-- 1. Today's Sales -->
        <div class="op-metric-card">
            <div class="op-metric-head">
                <span class="op-metric-label">Today's Sales</span>
                <span style="font-size: 15px;">💰</span>
            </div>
            <span class="op-metric-val" id="kpi-revenue">PKR {{ number_format($todayRevenue) }}</span>
            <span class="op-metric-sub" id="kpi-sales-sub">{{ $totalOrdersToday }} orders received today</span>
        </div>

        <!-- 2. Today's Total Orders -->
        <div class="op-metric-card">
            <div class="op-metric-head">
                <span class="op-metric-label">Total Orders Today</span>
                <span style="font-size: 15px;">🧾</span>
            </div>
            <span class="op-metric-val" id="kpi-today-count">{{ $totalOrdersToday }}</span>
            <span class="op-metric-sub">{{ $statusCounts['delivered'] ?? 0 }} delivered • {{ $statusCounts['cancelled'] ?? 0 }} cancelled</span>
        </div>

        <!-- 3. Average Order Value (AOV) -->
        <div class="op-metric-card">
            <div class="op-metric-head">
                <span class="op-metric-label">Average Ticket (AOV)</span>
                <span style="font-size: 15px;">🎯</span>
            </div>
            <span class="op-metric-val" id="kpi-aov">
                PKR {{ number_format($totalOrdersToday > 0 ? round($todayRevenue / $totalOrdersToday) : 0) }}
            </span>
            <span class="op-metric-sub">Average customer spend</span>
        </div>

        <!-- 4. Needs Attention Alert Card -->
        <div class="op-metric-card {{ $pendingCount > 0 ? 'alert-card' : '' }}" onclick="filterToPending()">
            <div class="op-metric-head">
                <span class="op-metric-label">Needs Attention</span>
                <span style="font-size: 15px;">{{ $pendingCount > 0 ? '⚠️' : '✅' }}</span>
            </div>
            <span class="op-metric-val" id="kpi-needs-attention">{{ $pendingCount }}</span>
            <span class="op-metric-sub" id="kpi-attn-sub">
                {{ $pendingCount > 0 ? 'Unconfirmed orders pending kitchen' : 'All live orders in progress' }}
            </span>
        </div>
    </div>

    <!-- ── 3. ORDER STATUS PIPELINE STRIP ── -->
    <div class="op-pipeline-strip">
        <button type="button" class="op-pipeline-step active" onclick="setPipelineFilter('all', this)">
            <span>All Orders</span>
            <span class="op-step-count" id="step-count-all">{{ $orders->total() ?? count($orders) }}</span>
        </button>
        <span class="op-step-arrow">→</span>

        <button type="button" class="op-pipeline-step" onclick="setPipelineFilter('pending', this)">
            <span>New</span>
            <span class="op-step-count" id="step-count-pending">{{ $statusCounts['pending'] ?? 0 }}</span>
        </button>
        <span class="op-step-arrow">→</span>

        <button type="button" class="op-pipeline-step" onclick="setPipelineFilter('confirmed', this)">
            <span>Confirmed</span>
            <span class="op-step-count" id="step-count-confirmed">{{ $statusCounts['confirmed'] ?? 0 }}</span>
        </button>
        <span class="op-step-arrow">→</span>

        <button type="button" class="op-pipeline-step" onclick="setPipelineFilter('preparing', this)">
            <span>Preparing</span>
            <span class="op-step-count" id="step-count-preparing">{{ $statusCounts['preparing'] ?? 0 }}</span>
        </button>
        <span class="op-step-arrow">→</span>

        <button type="button" class="op-pipeline-step" onclick="setPipelineFilter('out_for_delivery', this)">
            <span>Ready / Delivery</span>
            <span class="op-step-count" id="step-count-ready">{{ $statusCounts['out_for_delivery'] ?? 0 }}</span>
        </button>
        <span class="op-step-arrow">→</span>

        <button type="button" class="op-pipeline-step" onclick="setPipelineFilter('delivered', this)">
            <span>Delivered</span>
            <span class="op-step-count" id="step-count-delivered">{{ $statusCounts['delivered'] ?? 0 }}</span>
        </button>
    </div>

    <!-- ── 4. TWO-COLUMN OPERATIONS HUB (65% / 35%) ── -->
    <div class="op-workspace-grid">

        <!-- ── LEFT COLUMN (65%): LIVE ORDERS & ATTENTION ── -->
        <div class="op-live-column">

            <!-- Orders Requiring Attention Callout Banner -->
            <div class="op-attention-banner" id="attentionBanner" style="{{ $pendingCount > 0 ? '' : 'display: none;' }}">
                <div class="op-attention-info">
                    <span style="font-size: 20px;">⚠️</span>
                    <div>
                        <strong><span id="attnBannerCount">{{ $pendingCount }}</span> Orders Awaiting Immediate Confirmation</strong>
                        <div style="font-size: 11px; opacity: 0.9;">Customers are waiting on WhatsApp. Tap to confirm and notify kitchen.</div>
                    </div>
                </div>
                <button type="button" class="op-attention-btn" onclick="filterToPending()">View Pending Orders →</button>
            </div>

            <!-- Queue Toolbar -->
            <div class="op-queue-toolbar">
                <div class="op-queue-title">
                    <span>🍳</span>
                    <span>Live Order Queue</span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="op-search-wrap">
                        <span class="op-search-icon">🔍</span>
                        <input type="text" id="opSearchInput" class="op-search-input" placeholder="Search order #, customer, phone..." onkeyup="handleSearch(this.value)">
                    </div>

                    <button type="button" class="op-sound-btn active" id="audioToggleBtn" onclick="toggleAudioChime()" title="Audio chime on new order">
                        <span id="audioIcon">🔊</span>
                        <span id="audioText">Sound</span>
                    </button>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="pos-cards-grid" id="posCardsGrid">
                @forelse($orders as $order)
                    @php
                        $st = $order->status ?? 'pending';
                        $badgeClass = match($st) {
                            'pending', 'confirmed' => 'pending',
                            'preparing'            => 'preparing',
                            'out_for_delivery'     => 'out_for_delivery',
                            'delivered'            => 'delivered',
                            'cancelled'            => 'cancelled',
                            default                => 'pending'
                        };

                        $advanceBtnClass = match($st) {
                            'pending'          => 'new',
                            'confirmed'        => 'prep',
                            'preparing'        => 'dispatch',
                            'out_for_delivery' => 'complete',
                            default            => 'done'
                        };

                        $advanceText = match($st) {
                            'pending'          => 'Confirm ✓',
                            'confirmed'        => 'Start Prep 🍳',
                            'preparing'        => 'Dispatch 🛵',
                            'out_for_delivery' => 'Complete ✅',
                            'delivered'        => 'Delivered',
                            'cancelled'        => 'Cancelled',
                            default            => 'View'
                        };
                    @endphp
                    <div class="pos-order-card {{ ($selectedOrder && $selectedOrder->id === $order->id) ? 'selected' : '' }}" 
                         data-order-id="{{ $order->id }}"
                         data-status="{{ $st }}"
                         data-tracking="{{ strtolower($order->tracking_code) }}"
                         data-customer="{{ strtolower($order->customer_name ?? '') }}"
                         data-phone="{{ $order->customer_phone ?? '' }}"
                         onclick="openOrderDrawer({{ $order->id }})">

                        <!-- Card Head -->
                        <div class="pos-card-head">
                            <div>
                                <span class="pos-card-code">#{{ $order->tracking_code }}</span>
                                <span class="pos-card-time">{{ $order->created_at->format('h:i A') }} • {{ $order->created_at->diffForHumans(null, true, true) }}</span>
                            </div>
                            <span class="pos-status-badge {{ $badgeClass }}">
                                {{ $order->status_label ?? ucfirst(str_replace('_', ' ', $st)) }}
                            </span>
                        </div>

                        <!-- Customer Line -->
                        <div class="pos-card-cust-line">
                            <span>👤 {{ Str::limit($order->customer_name ?: 'Guest Customer', 18) }}</span>
                            <span style="font-size: 10.5px; color: var(--pos-text-muted); font-weight: 600;">
                                {{ $order->payment_method === 'online' ? '💳 Paid' : '💵 COD' }} • 🛵
                            </span>
                        </div>

                        <!-- Items Preview Box -->
                        <div class="pos-card-items-box">
                            @foreach($order->items->take(3) as $it)
                                <div class="pos-card-item-line">
                                    <span class="pos-card-item-qty">{{ $it->quantity }}x</span>
                                    <span class="pos-card-item-name">{{ $it->name ?? ($it->item_name ?? 'Dish') }}</span>
                                </div>
                            @endforeach
                            @if($order->items->count() > 3)
                                <span style="font-size: 10px; font-weight: 700; color: var(--pos-text-muted); margin-top: 1px;">
                                    + {{ $order->items->count() - 3 }} more dishes...
                                </span>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="pos-card-footer">
                            <span class="pos-card-price">PKR {{ number_format($order->total) }}</span>

                            @if(!in_array($st, ['delivered', 'cancelled']))
                                <button type="button" 
                                        class="pos-card-advance-btn {{ $advanceBtnClass }}"
                                        onclick="event.stopPropagation(); triggerCardAdvance({{ $order->id }}, '{{ $st }}', this)">
                                    <span>{{ $advanceText }}</span>
                                    <span>→</span>
                                </button>
                            @else
                                <span class="pos-card-advance-btn done">{{ $advanceText }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pos-empty-grid" id="posEmptyGrid">
                        <div style="font-size: 36px; margin-bottom: 6px;">🍽️</div>
                        <h3 style="font-size: 15px; font-weight: 800; color: var(--pos-text);">No Orders Right Now</h3>
                        <p style="font-size: 12px; color: var(--pos-text-muted); margin-top: 4px;">Orders placed on WhatsApp will appear here instantly with live sound.</p>
                    </div>
                @endforelse
            </div>

        </div>

        <!-- ── RIGHT COLUMN (35%): CONTEXT, DELIVERY & ACTIVITY ── -->
        <div class="op-context-column">

            <!-- 1. Delivery Fleet Overview -->
            <div class="op-panel-card">
                <div class="op-panel-head">
                    <div class="op-panel-title">
                        <span>🚴</span>
                        <span>Delivery Fleet ({{ $activeRidersCount }} Online)</span>
                    </div>
                    <a href="{{ route('dashboard.riders', $restaurant->id) }}" class="op-panel-link">Manage →</a>
                </div>

                @if($riders->isNotEmpty())
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        @foreach($riders->take(4) as $rider)
                            <div class="op-rider-row">
                                <div class="op-rider-info">
                                    <div class="op-rider-avatar">🚴</div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--pos-text);">{{ $rider->name }}</div>
                                        <div style="font-size: 10.5px; color: var(--pos-text-muted);">{{ $rider->phone }}</div>
                                    </div>
                                </div>
                                <span style="font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px; {{ $rider->is_active ? 'background:#dcfce7;color:#15803d;' : 'background:#f1f5f9;color:#64748b;' }}">
                                    {{ $rider->is_active ? 'Available' : 'Offline' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 12px; color: var(--pos-text-muted); font-size: 12px;">
                        No delivery fleet registered.
                        <a href="{{ route('dashboard.riders', $restaurant->id) }}" style="color: #2563eb; display: block; margin-top: 4px; font-weight: 700;">+ Add Riders</a>
                    </div>
                @endif
            </div>

            <!-- 2. Top-Selling Menu Items (Today) -->
            <div class="op-panel-card">
                <div class="op-panel-head">
                    <div class="op-panel-title">
                        <span>🔥</span>
                        <span>Top Selling Dishes</span>
                    </div>
                    <span style="font-size: 11px; color: var(--pos-text-muted); font-weight: 600;">Today</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    @php
                        $displayTop = (isset($topSellingItems) && count($topSellingItems) > 0)
                            ? $topSellingItems
                            : [];
                    @endphp

                    @forelse($displayTop as $idx => $dish)
                        <div class="op-top-item-row">
                            <div class="op-top-item-left">
                                <span class="op-item-rank {{ $idx === 0 ? 'rank-1' : '' }}">{{ $idx + 1 }}</span>
                                <span style="font-weight: 700; color: var(--pos-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $dish->name ?? ($dish->item_name ?? 'Dish') }}
                                </span>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: var(--pos-text); font-variant-numeric: tabular-nums;">
                                {{ $dish->total_qty }} orders
                            </span>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 10px; color: var(--pos-text-muted); font-size: 12px;">
                            Orders placed today will populate top sellers here.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Today's Sales Rhythm (Mini Bars) -->
            <div class="op-panel-card">
                <div class="op-panel-head">
                    <div class="op-panel-title">
                        <span>📈</span>
                        <span>Sales Rhythm (7 Days)</span>
                    </div>
                    <span style="font-size: 11px; color: var(--pos-text-muted); font-weight: 600;">Volume</span>
                </div>

                @php
                    $maxCount = 1;
                    foreach($weeklyTrend as $wt) {
                        if ($wt['count'] > $maxCount) $maxCount = $wt['count'];
                    }
                @endphp

                <div class="op-trend-bars">
                    @foreach($weeklyTrend as $idx => $day)
                        @php
                            $heightPct = max(8, round(($day['count'] / $maxCount) * 100));
                            $isToday = ($idx === count($weeklyTrend) - 1);
                        @endphp
                        <div class="op-trend-col" title="{{ $day['day'] }} ({{ $day['date'] }}): {{ $day['count'] }} orders">
                            <div class="op-trend-bar {{ $isToday ? 'today' : '' }}" style="height: {{ $heightPct }}%;"></div>
                            <span class="op-trend-day">{{ $day['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 4. Recent Activity Stream -->
            <div class="op-panel-card">
                <div class="op-panel-head">
                    <div class="op-panel-title">
                        <span>⚡</span>
                        <span>Recent Activity</span>
                    </div>
                    <span style="font-size: 11px; color: var(--pos-text-muted); font-weight: 600;">Live Feed</span>
                </div>

                <div class="op-activity-list">
                    @forelse($recentActivity as $act)
                        <div class="op-activity-item">
                            <div class="op-activity-left">
                                <span style="font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; color: var(--pos-text);">#{{ $act->tracking_code }}</span>
                                <span class="pos-status-badge {{ $act->status }}" style="font-size: 9px; padding: 1px 6px;">{{ $act->status_label }}</span>
                            </div>
                            <span style="font-size: 10.5px; color: var(--pos-text-muted);">
                                {{ $act->created_at->diffForHumans(null, true, true) }}
                            </span>
                        </div>
                    @empty
                        <div style="font-size: 11.5px; color: var(--pos-text-muted); text-align: center; padding: 8px;">
                            No recent order activity yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ── SLIDE-OVER POS ORDER DRAWER ── -->
<div class="pos-drawer-backdrop" id="posDrawerBackdrop" onclick="closeOrderDrawer()"></div>

<div class="pos-drawer" id="posDrawer">
    <div class="pos-drawer-head">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px; font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; color: var(--pos-text);" id="drawerTrackingCode">#ORDER</span>
            <span class="pos-status-badge pending" id="drawerStatusBadge">Pending</span>
        </div>
        <button type="button" class="pos-drawer-close-btn" onclick="closeOrderDrawer()" title="Close Drawer (Esc)">✕</button>
    </div>

    <div class="pos-drawer-body" id="drawerBody">
        <!-- Rendered dynamically -->
    </div>

    <div class="pos-drawer-foot" id="drawerFoot">
        <!-- Rendered dynamically -->
    </div>
</div>

<!-- ── DISPATCH MODAL ── -->
<div class="pos-modal-backdrop" id="dispatchModal">
    <div class="pos-modal-window">
        <div style="padding: 16px 20px; background: #181818; color: #fff; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px;">🛵</span>
                <div>
                    <h3 style="color: #fff; font-size: 14.5px; font-weight: 800;">Dispatch to Fleet Rider</h3>
                    <p style="font-size: 11px; color: #a3a3a3;" id="dispatchSubtext">Order #</p>
                </div>
            </div>
            <button type="button" onclick="closeDispatchModal()" style="background: none; border: none; color: #fff; font-size: 18px; cursor: pointer;">✕</button>
        </div>

        <form id="dispatchForm" method="POST" action="" onsubmit="return ajaxSubmitDispatch(event);">
            @csrf
            <input type="hidden" name="status" value="out_for_delivery">

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 11.5px; font-weight: 700; margin-bottom: 5px;">Select Fleet Rider *</label>
                    <select id="riderSelect" class="form-control" style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12.5px;" onchange="handleRiderSelect(this)">
                        <option value="">-- Choose registered rider --</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->name }}" data-phone="{{ $rider->phone }}">
                                {{ $rider->name }} ({{ $rider->phone }}) {{ $rider->is_active ? '• Active' : '' }}
                            </option>
                        @endforeach
                        <option value="__custom__">➕ Enter Other / Third-Party Rider</option>
                    </select>
                </div>

                <div id="customRiderFields" style="{{ $riders->isNotEmpty() ? 'display: none;' : '' }}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; color: var(--pos-text-muted); margin-bottom: 3px;">Rider Name *</label>
                            <input type="text" id="inputRiderName" name="rider_name" class="form-control" placeholder="e.g. Ali Khan" style="width: 100%; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; color: var(--pos-text-muted); margin-bottom: 3px;">Rider Phone Number</label>
                            <input type="text" id="inputRiderPhone" name="rider_phone" class="form-control" placeholder="e.g. 03001234567" style="width: 100%; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12px;">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; margin-bottom: 5px;">ETA (Minutes)</label>
                        <input type="number" name="estimated_minutes" class="form-control" value="25" min="5" max="180" style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11.5px; font-weight: 700; margin-bottom: 5px;">Delivery Notes</label>
                        <input type="text" name="rider_notes" class="form-control" placeholder="e.g. Ring bell twice" style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12px;">
                    </div>
                </div>

                <div style="background: var(--pos-canvas); border: 1px solid var(--pos-border); border-radius: 8px; padding: 8px 12px; font-size: 11.5px;">
                    <span style="font-weight: 700;">📍 Destination:</span>
                    <span id="dispatchAddress" style="color: var(--pos-text-muted); margin-left: 4px;"></span>
                </div>
            </div>

            <div style="padding: 12px 20px; border-top: 1px solid var(--pos-border); background: var(--pos-canvas); display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeDispatchModal()" class="pos-drawer-sec-btn" style="flex: none; width: 85px;">Cancel</button>
                <button type="submit" class="pos-card-advance-btn dispatch" style="padding: 8px 18px; font-size: 12.5px;">Confirm & Dispatch 🛵</button>
            </div>
        </form>
    </div>
</div>

<!-- ── CANCEL ORDER MODAL ── -->
<div class="pos-modal-backdrop" id="cancelOrderModal">
    <div class="pos-modal-window" style="max-width: 380px; text-align: center;">
        <div style="padding: 24px 20px 16px;">
            <div style="font-size: 36px; margin-bottom: 6px;">❌</div>
            <h3 style="font-size: 16px; font-weight: 800; color: var(--pos-text); margin-bottom: 4px;">Cancel This Order?</h3>
            <p style="font-size: 12px; color: var(--pos-text-muted); line-height: 1.4; margin-bottom: 12px;">
                Order <strong id="cancelOrderCode" style="color: var(--pos-text);"></strong> will be marked as Cancelled and customer notified via WhatsApp.
            </p>
            <textarea id="cancelReason" rows="2" placeholder="Reason for cancellation (optional)..." style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); background: var(--pos-canvas); font-size: 12px; resize: none; margin-bottom: 6px;"></textarea>
        </div>
        <div style="padding: 10px 20px 16px; display: flex; gap: 8px;">
            <button type="button" onclick="closeCancelModal()" class="pos-drawer-sec-btn" style="flex: 1;">Keep Order</button>
            <button type="button" onclick="executeCancelOrder()" class="pos-card-advance-btn" style="flex: 1; justify-content: center; background: #dc2626; color: #fff;">Yes, Cancel</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="live-toast"></div>

<!-- ── JAVASCRIPT ENGINE ── -->
<script>
    const RESTAURANT_ID     = '{{ $restaurant->id }}';
    const LIVE_FEED_URL     = '/dashboard/' + RESTAURANT_ID + '/orders/live-feed';
    const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let CURRENT_PIPELINE    = 'all';
    let SEARCH_QUERY        = '';
    let ACTIVE_DRAWER_ID    = null;
    let AUDIO_ENABLED       = true;
    let _cancelOrderId      = null;

    // Local Map of orders
    const currentOrdersMap  = {};

    @foreach($orders as $o)
    currentOrdersMap[{{ $o->id }}] = {
        id: {{ $o->id }},
        tracking_code: '{{ $o->tracking_code }}',
        status: '{{ $o->status }}',
        status_label: '{{ $o->status_label }}',
        total: {{ (float) $o->total }},
        customer_name: '{{ addslashes($o->customer_name ?: 'Guest Customer') }}',
        customer_phone: '{{ $o->customer_phone ?: '' }}',
        created_at_humans: '{{ $o->created_at->diffForHumans(null, true, true) }}',
        created_at_time: '{{ $o->created_at->format('h:i A') }}',
        created_at_ago: '{{ $o->created_at->diffForHumans() }}',
        rider_name: '{{ addslashes($o->rider_name ?? '') }}',
        rider_phone: '{{ addslashes($o->rider_phone ?? '') }}',
        delivery_address: '{{ addslashes($o->delivery_address ?: '') }}',
        delivery_fee: {{ (float) ($restaurant->delivery_charge ?? 0) }},
        delivery_lat: @json($o->delivery_lat ? (float) $o->delivery_lat : null),
        delivery_lng: @json($o->delivery_lng ? (float) $o->delivery_lng : null),
        estimated_minutes: {{ $o->estimated_minutes ?? 25 }},
        payment_method: '{{ $o->payment_method ?: 'cash_on_delivery' }}',
        items: [
            @foreach($o->items as $it)
            {
                name: '{{ addslashes($it->name ?: ($it->item_name ?? 'Dish')) }}',
                quantity: {{ (int) $it->quantity }},
                subtotal: {{ (float) $it->subtotal }}
            },
            @endforeach
        ]
    };
    @endforeach

    const STATUS_FLOW = {
        pending: {
            label: 'New Order',
            next: 'confirmed',
            btnText: 'Confirm ✓',
            drawerPrimary: '✓ Confirm Order',
            drawerColor: '#181818',
            textColor: '#ffffff'
        },
        confirmed: {
            label: 'Confirmed',
            next: 'preparing',
            btnText: 'Start Prep 🍳',
            drawerPrimary: '🍳 Start Kitchen Preparation',
            drawerColor: '#2563eb',
            textColor: '#ffffff'
        },
        preparing: {
            label: 'Preparing',
            next: 'out_for_delivery',
            btnText: 'Dispatch 🛵',
            drawerPrimary: '🛵 Assign Rider & Dispatch',
            drawerColor: '#4f46e5',
            textColor: '#ffffff'
        },
        out_for_delivery: {
            label: 'Out for Delivery',
            next: 'delivered',
            btnText: 'Complete ✅',
            drawerPrimary: '✅ Mark Order Delivered',
            drawerColor: '#16a34a',
            textColor: '#ffffff'
        },
        delivered: {
            label: 'Delivered',
            next: null,
            btnText: 'Delivered',
            drawerPrimary: null,
            drawerColor: '#15803d',
            textColor: '#ffffff'
        },
        cancelled: {
            label: 'Cancelled',
            next: null,
            btnText: 'Cancelled',
            drawerPrimary: null,
            drawerColor: '#dc2626',
            textColor: '#ffffff'
        }
    };

    // ── Web Audio API POS Chime ──
    function playPosChime() {
        if (!AUDIO_ENABLED) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
            gain1.gain.setValueAtTime(0.2, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.35);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.12);
            gain2.gain.setValueAtTime(0.25, ctx.currentTime + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.12);
            osc2.stop(ctx.currentTime + 0.55);
        } catch (_) {}
    }

    function toggleAudioChime() {
        AUDIO_ENABLED = !AUDIO_ENABLED;
        const btn = document.getElementById('audioToggleBtn');
        const icon = document.getElementById('audioIcon');
        const text = document.getElementById('audioText');
        if (AUDIO_ENABLED) {
            btn.classList.add('active');
            icon.textContent = '🔊';
            text.textContent = 'Sound';
            playPosChime();
            showToast('🔊 Order chime active');
        } else {
            btn.classList.remove('active');
            icon.textContent = '🔇';
            text.textContent = 'Muted';
            showToast('🔇 Order chime muted');
        }
    }

    // ── Pipeline Filter & Search ──
    function setPipelineFilter(status, stepElement) {
        CURRENT_PIPELINE = status;
        document.querySelectorAll('.op-pipeline-step').forEach(p => p.classList.remove('active'));
        if (stepElement) stepElement.classList.add('active');
        applyGridFilters();
    }

    function filterToPending() {
        const step = document.querySelector('.op-pipeline-step[onclick*="pending"]');
        setPipelineFilter('pending', step);
    }

    function filterToLiveQueue() {
        setPipelineFilter('all', document.querySelector('.op-pipeline-step[onclick*="all"]'));
        const el = document.getElementById('posCardsGrid');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    }

    function handleSearch(val) {
        SEARCH_QUERY = (val || '').trim().toLowerCase();
        applyGridFilters();
    }

    function applyGridFilters() {
        const cards = document.querySelectorAll('.pos-order-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const st = card.dataset.status;
            const tracking = card.dataset.tracking;
            const customer = card.dataset.customer;
            const phone = card.dataset.phone;

            let matchesPipeline = true;
            if (CURRENT_PIPELINE !== 'all') {
                matchesPipeline = (st === CURRENT_PIPELINE);
            }

            let matchesSearch = true;
            if (SEARCH_QUERY) {
                matchesSearch = tracking.includes(SEARCH_QUERY) || 
                                customer.includes(SEARCH_QUERY) || 
                                phone.includes(SEARCH_QUERY);
            }

            if (matchesPipeline && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        let emptyState = document.getElementById('posEmptyGrid');
        if (!emptyState && visibleCount === 0) {
            const grid = document.getElementById('posCardsGrid');
            emptyState = document.createElement('div');
            emptyState.id = 'posEmptyGrid';
            emptyState.className = 'pos-empty-grid';
            emptyState.innerHTML = `
                <div style="font-size: 32px; margin-bottom: 6px;">🍽️</div>
                <h3 style="font-size: 14px; font-weight: 800; color: var(--pos-text);">No orders match "${CURRENT_PIPELINE}"</h3>
                <p style="font-size: 11.5px; color: var(--pos-text-muted); margin-top: 3px;">No orders currently exist under this pipeline stage.</p>
            `;
            grid.appendChild(emptyState);
        } else if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    // ── Quick Create Order Prompt ──
    function quickCreateOrderPrompt() {
        showToast('💡 Customers order via WhatsApp. You can also share your menu link to start orders!', 'info');
        window.open('https://wa.me/{{ preg_replace("/\D/", "", $restaurant->phone ?? "") }}', '_blank');
    }

    // ── Slide-Over POS Order Drawer ──
    function openOrderDrawer(orderId) {
        ACTIVE_DRAWER_ID = orderId;
        const o = currentOrdersMap[orderId];
        if (!o) return;

        document.querySelectorAll('.pos-order-card').forEach(c => {
            c.classList.toggle('selected', parseInt(c.dataset.orderId) === orderId);
        });

        document.getElementById('drawerTrackingCode').textContent = '#' + o.tracking_code;
        const badge = document.getElementById('drawerStatusBadge');
        badge.className = 'pos-status-badge ' + (o.status === 'out_for_delivery' ? 'out_for_delivery' : (o.status || 'pending'));
        badge.textContent = o.status_label || (STATUS_FLOW[o.status]?.label || o.status);

        renderDrawerContent(o);

        document.getElementById('posDrawerBackdrop').classList.add('open');
        document.getElementById('posDrawer').classList.add('open');
    }

    function closeOrderDrawer() {
        document.getElementById('posDrawerBackdrop').classList.remove('open');
        document.getElementById('posDrawer').classList.remove('open');
        document.querySelectorAll('.pos-order-card').forEach(c => c.classList.remove('selected'));
        ACTIVE_DRAWER_ID = null;
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeOrderDrawer();
            closeDispatchModal();
            closeCancelModal();
        }
    });

    function renderDrawerContent(o) {
        const body = document.getElementById('drawerBody');
        const foot = document.getElementById('drawerFoot');
        const cleanPhone = (o.customer_phone || '').replace(/\D/g, '');

        const itemsHtml = (o.items || []).map(it => `
            <div class="pos-receipt-row">
                <div style="display:flex;align-items:flex-start;gap:6px;font-weight:600;color:var(--pos-text);">
                    <span style="font-size:10px;font-weight:800;padding:0 4px;border-radius:3px;background:var(--pos-canvas);border:1px solid var(--pos-border);">${it.quantity}x</span>
                    <span>${escHtml(it.name)}</span>
                </div>
                <span style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--pos-text);">PKR ${Number(it.subtotal).toLocaleString()}</span>
            </div>
        `).join('');

        const subtotal = (o.items || []).reduce((acc, it) => acc + (it.subtotal || 0), 0);
        const deliveryFee = o.delivery_fee || 0;

        let riderHtml = '';
        if (o.rider_name || o.rider_phone) {
            riderHtml = `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--pos-canvas);border:1px solid var(--pos-border);border-radius:10px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:16px;">🚴</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:var(--pos-text);">${escHtml(o.rider_name || 'Assigned Rider')}</div>
                            <div style="font-size:10.5px;color:var(--pos-text-muted);">${escHtml(o.rider_phone || 'Fleet')}</div>
                        </div>
                    </div>
                    ${o.rider_phone ? `<a href="tel:${escHtml(o.rider_phone)}" class="op-action-btn" style="padding:4px 8px;font-size:11px;background:#0284c7;color:#fff;border:none;">📞 Call</a>` : ''}
                </div>
            `;
        } else if (o.status === 'preparing') {
            riderHtml = `
                <button type="button" class="pos-drawer-sec-btn" onclick="openDispatchModal(${o.id}, '${o.tracking_code}', '${escJs(o.customer_name)}', '${escJs(o.delivery_address)}')">
                    <span>🚴</span>
                    <span>Assign Rider for Dispatch</span>
                </button>
            `;
        }

        body.innerHTML = `
            <!-- Customer Section -->
            <div class="pos-drawer-section">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;color:var(--pos-text-muted);">Customer & Dispatch</span>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-size:13.5px;font-weight:700;color:var(--pos-text);">${escHtml(o.customer_name || 'Guest Customer')}</div>
                        <div style="font-size:11.5px;color:var(--pos-text-muted);">📞 ${escHtml(o.customer_phone || 'Not provided')}</div>
                    </div>
                    ${cleanPhone ? `
                        <a href="https://wa.me/${cleanPhone}" target="_blank" class="op-action-btn" style="background:#16a34a;color:#fff;border:none;padding:5px 10px;font-size:11px;">
                            <span>💬</span>
                            <span>WhatsApp</span>
                        </a>
                    ` : ''}
                </div>
                <div style="font-size:12px;color:var(--pos-text);line-height:1.4;margin-top:2px;">
                    <div>📍 <strong>Address:</strong> ${escHtml(o.delivery_address || 'Counter Pickup')}</div>
                    ${o.delivery_lat && o.delivery_lng ? `
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(o.delivery_lat)},${encodeURIComponent(o.delivery_lng)}" 
                           target="_blank" style="display:inline-flex;align-items:center;gap:3px;font-size:11px;color:#0284c7;font-weight:700;text-decoration:none;margin-top:2px;">
                            📍 GPS Route (Open in Maps ↗)
                        </a>
                    ` : ''}
                </div>
            </div>

            <!-- Receipt Ticket -->
            <div class="pos-receipt-ticket">
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;border-bottom:1px dashed var(--pos-border);margin-bottom:10px;font-size:10.5px;color:var(--pos-text-muted);font-weight:700;">
                    <span>PLACED AT ${escHtml(o.created_at_time || '')}</span>
                    <span>${o.payment_method === 'online' ? '💳 PREPAID' : '💵 CASH ON DELIVERY'}</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;">
                    ${itemsHtml || '<div style="color:var(--pos-text-muted);font-size:12px;">No items listed</div>'}
                </div>

                <div style="border-top:1px dashed var(--pos-border);padding-top:8px;display:flex;flex-direction:column;gap:4px;">
                    <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--pos-text-muted);">
                        <span>Subtotal</span>
                        <span>PKR ${Number(subtotal).toLocaleString()}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--pos-text-muted);">
                        <span>Delivery Fee</span>
                        <span>PKR ${Number(deliveryFee).toLocaleString()}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding-top:6px;margin-top:2px;border-top:1.5px solid var(--pos-border);font-size:15px;font-weight:800;color:var(--pos-text);">
                        <span>Total Bill</span>
                        <span>PKR ${Number(o.total).toLocaleString()}</span>
                    </div>
                </div>
            </div>

            ${riderHtml}
        `;

        const flow = STATUS_FLOW[o.status] || {};
        let primaryBtnHtml = '';

        if (flow.next && flow.drawerPrimary) {
            if (o.status === 'preparing') {
                primaryBtnHtml = `
                    <button type="button" class="pos-drawer-primary-btn" 
                            style="background: ${flow.drawerColor}; color: ${flow.textColor};"
                            onclick="openDispatchModal(${o.id}, '${o.tracking_code}', '${escJs(o.customer_name)}', '${escJs(o.delivery_address)}')">
                        ${flow.drawerPrimary} →
                    </button>
                `;
            } else {
                primaryBtnHtml = `
                    <button type="button" class="pos-drawer-primary-btn" 
                            style="background: ${flow.drawerColor}; color: ${flow.textColor};"
                            onclick="ajaxUpdateStatus('/dashboard/${RESTAURANT_ID}/orders/${o.id}/status', '${flow.next}', this)">
                        ${flow.drawerPrimary} →
                    </button>
                `;
            }
        }

        foot.innerHTML = `
            ${primaryBtnHtml}
            <div class="pos-drawer-sec-actions">
                <a href="/dashboard/${RESTAURANT_ID}/orders/${o.id}/print-bill" target="_blank" class="pos-drawer-sec-btn">
                    <span>🖨️</span>
                    <span>Print Bill</span>
                </a>
                <a href="/track/${escHtml(o.tracking_code)}" target="_blank" class="pos-drawer-sec-btn">
                    <span>🌐</span>
                    <span>Live Track</span>
                </a>
                ${!['delivered', 'cancelled'].includes(o.status) ? `
                    <button type="button" class="pos-drawer-sec-btn danger" onclick="confirmCancelOrder(${o.id}, '${escHtml(o.tracking_code)}')">
                        <span>✕</span>
                        <span>Cancel</span>
                    </button>
                ` : ''}
            </div>
        `;
    }

    // ── 1-Tap Advance from Order Card ──
    function triggerCardAdvance(orderId, currentStatus, btn) {
        const flow = STATUS_FLOW[currentStatus];
        if (!flow || !flow.next) return;
        const o = currentOrdersMap[orderId];

        if (currentStatus === 'preparing') {
            openDispatchModal(orderId, o.tracking_code, o.customer_name, o.delivery_address);
        } else {
            const url = `/dashboard/${RESTAURANT_ID}/orders/${orderId}/status`;
            ajaxUpdateStatus(url, flow.next, btn);
        }
    }

    // ── AJAX: Update Order Status ──
    async function ajaxUpdateStatus(url, status, btn) {
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
        }

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
            if (!data.success) throw new Error(data.message || 'Status update failed');

            showToast('✅ ' + (data.message || 'Status updated!'));

            const urlMatch = url.match(/\/orders\/(\d+)\/status/);
            const orderId = urlMatch ? parseInt(urlMatch[1]) : ACTIVE_DRAWER_ID;

            if (orderId && currentOrdersMap[orderId]) {
                currentOrdersMap[orderId].status = data.status || status;
                currentOrdersMap[orderId].status_label = data.status_label || (STATUS_FLOW[data.status || status]?.label);

                updateCardInDom(currentOrdersMap[orderId]);

                if (ACTIVE_DRAWER_ID === orderId) {
                    openOrderDrawer(orderId);
                }
            }

            pollLiveFeed();

        } catch (e) {
            showToast('❌ ' + e.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }
    }

    function updateCardInDom(o) {
        const card = document.querySelector(`.pos-order-card[data-order-id="${o.id}"]`);
        if (!card) return;

        card.dataset.status = o.status;
        const badge = card.querySelector('.pos-status-badge');
        if (badge) {
            badge.className = 'pos-status-badge ' + (o.status === 'out_for_delivery' ? 'out_for_delivery' : o.status);
            badge.textContent = o.status_label || ucfirst(o.status);
        }

        const footer = card.querySelector('.pos-card-footer');
        if (footer) {
            const flow = STATUS_FLOW[o.status];
            if (flow && flow.next) {
                const btnClass = o.status === 'pending' ? 'new' : (o.status === 'confirmed' ? 'prep' : (o.status === 'preparing' ? 'dispatch' : 'complete'));
                footer.innerHTML = `
                    <span class="pos-card-price">PKR ${Number(o.total).toLocaleString()}</span>
                    <button type="button" class="pos-card-advance-btn ${btnClass}"
                            onclick="event.stopPropagation(); triggerCardAdvance(${o.id}, '${o.status}', this)">
                        <span>${flow.btnText}</span>
                        <span>→</span>
                    </button>
                `;
            } else {
                footer.innerHTML = `
                    <span class="pos-card-price">PKR ${Number(o.total).toLocaleString()}</span>
                    <span class="pos-card-advance-btn done">${flow?.btnText || 'Delivered'}</span>
                `;
            }
        }
        applyGridFilters();
    }

    // ── Dispatch Modal ──
    function openDispatchModal(orderId, orderCode, customerName, address) {
        document.getElementById('dispatchSubtext').textContent = 'Order #' + orderCode + ' • ' + (customerName || 'Customer');
        document.getElementById('dispatchAddress').textContent = address || 'Dine-in / Pickup / WhatsApp Address';
        document.getElementById('dispatchForm').action = `/dashboard/${RESTAURANT_ID}/orders/${orderId}/status`;

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
            customFields.style.display = 'block';
        }

        document.getElementById('dispatchModal').classList.add('open');
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
        document.getElementById('dispatchModal').classList.remove('open');
    }

    async function ajaxSubmitDispatch(e) {
        if (e) e.preventDefault();
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
            showToast('🛵 Order dispatched to rider!');

            const urlMatch = form.action.match(/\/orders\/(\d+)\/status/);
            const orderId = urlMatch ? parseInt(urlMatch[1]) : ACTIVE_DRAWER_ID;

            if (orderId && currentOrdersMap[orderId]) {
                currentOrdersMap[orderId].status = 'out_for_delivery';
                currentOrdersMap[orderId].status_label = data.status_label || 'Out for Delivery';
                currentOrdersMap[orderId].rider_name = dataObj.rider_name || '';
                currentOrdersMap[orderId].rider_phone = dataObj.rider_phone || '';
                updateCardInDom(currentOrdersMap[orderId]);

                if (ACTIVE_DRAWER_ID === orderId) {
                    openOrderDrawer(orderId);
                }
            }

            pollLiveFeed();

        } catch (err) {
            showToast('❌ ' + err.message, 'error');
        } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.style.opacity = '1'; }
        }
        return false;
    }

    // ── Cancel Order Modal ──
    function confirmCancelOrder(orderId, trackingCode) {
        _cancelOrderId = orderId;
        document.getElementById('cancelOrderCode').textContent = '#' + trackingCode;
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelOrderModal').classList.add('open');
    }

    function closeCancelModal() {
        document.getElementById('cancelOrderModal').classList.remove('open');
        _cancelOrderId = null;
    }

    async function executeCancelOrder() {
        if (!_cancelOrderId) return;
        const reason = (document.getElementById('cancelReason').value || '').trim();
        const url = `/dashboard/${RESTAURANT_ID}/orders/${_cancelOrderId}/status`;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ status: 'cancelled', notes: reason || undefined }),
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Cancel failed');

            const cancelledId = _cancelOrderId;
            closeCancelModal();
            showToast('❌ Order cancelled');

            if (currentOrdersMap[cancelledId]) {
                currentOrdersMap[cancelledId].status = 'cancelled';
                currentOrdersMap[cancelledId].status_label = 'Cancelled';
                updateCardInDom(currentOrdersMap[cancelledId]);
                if (ACTIVE_DRAWER_ID === cancelledId) {
                    openOrderDrawer(cancelledId);
                }
            }

            pollLiveFeed();

        } catch (err) {
            showToast('❌ ' + err.message, 'error');
        }
    }

    // ── Toast System ──
    function showToast(msg, type = 'success') {
        const t = document.getElementById('live-toast');
        if (!t) return;
        t.style.background = type === 'success' ? '#181818' : (type === 'info' ? '#2563eb' : '#dc2626');
        t.style.color = '#ffffff';
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.classList.remove('show'); }, 3500);
    }

    // ── Live Feed Arrival Card Renderer ──
    function generateCardHtml(o) {
        const badgeClass = o.status === 'out_for_delivery' ? 'out_for_delivery' : (o.status || 'pending');
        const advanceBtnClass = o.status === 'pending' ? 'new' : (o.status === 'confirmed' ? 'prep' : (o.status === 'preparing' ? 'dispatch' : 'complete'));
        const flow = STATUS_FLOW[o.status] || { btnText: 'View' };

        const itemsHtml = (o.items || []).slice(0, 3).map(it => `
            <div class="pos-card-item-line">
                <span class="pos-card-item-qty">${it.quantity}x</span>
                <span class="pos-card-item-name">${escHtml(it.name)}</span>
            </div>
        `).join('');

        const moreCount = (o.items || []).length > 3 ? `+ ${o.items.length - 3} more dishes...` : '';

        return `
            <div class="pos-order-card" 
                 data-order-id="${o.id}"
                 data-status="${o.status}"
                 data-tracking="${escHtml(o.tracking_code).toLowerCase()}"
                 data-customer="${escHtml(o.customer_name || '').toLowerCase()}"
                 data-phone="${escHtml(o.customer_phone || '')}"
                 onclick="openOrderDrawer(${o.id})">

                <div class="pos-card-head">
                    <div>
                        <span class="pos-card-code">#${escHtml(o.tracking_code)}</span>
                        <span class="pos-card-time">${escHtml(o.created_at_time || '')} • ${escHtml(o.created_at_humans || '')}</span>
                    </div>
                    <span class="pos-status-badge ${badgeClass}">
                        ${escHtml(o.status_label || flow.label || o.status)}
                    </span>
                </div>

                <div class="pos-card-cust-line">
                    <span>👤 ${escHtml(o.customer_name || 'Guest Customer')}</span>
                    <span style="font-size: 10.5px; color: var(--pos-text-muted); font-weight: 600;">
                        ${o.payment_method === 'online' ? '💳 Paid' : '💵 COD'} • 🛵
                    </span>
                </div>

                <div class="pos-card-items-box">
                    ${itemsHtml}
                    ${moreCount ? `<span style="font-size: 10px; font-weight: 700; color: var(--pos-text-muted);">${moreCount}</span>` : ''}
                </div>

                <div class="pos-card-footer">
                    <span class="pos-card-price">PKR ${Number(o.total).toLocaleString()}</span>

                    ${!['delivered', 'cancelled'].includes(o.status) ? `
                        <button type="button" class="pos-card-advance-btn ${advanceBtnClass}"
                                onclick="event.stopPropagation(); triggerCardAdvance(${o.id}, '${o.status}', this)">
                            <span>${flow.btnText}</span>
                            <span>→</span>
                        </button>
                    ` : `
                        <span class="pos-card-advance-btn done">${flow.btnText}</span>
                    `}
                </div>
            </div>
        `;
    }

    // ── Live Feed Polling Engine (Every 5s) ──
    async function pollLiveFeed() {
        try {
            const res = await fetch(LIVE_FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            // Update KPI cards
            const kpiRev = document.getElementById('kpi-revenue');
            const kpiTodayCount = document.getElementById('kpi-today-count');
            const kpiSub = document.getElementById('kpi-sales-sub');
            const kpiNeedsAttention = document.getElementById('kpi-needs-attention');
            const kpiAttnSub = document.getElementById('kpi-attn-sub');
            const btnLiveCount = document.getElementById('btn-live-count');

            if (kpiRev && data.revenue !== undefined) kpiRev.textContent = 'PKR ' + Number(data.revenue).toLocaleString();
            if (kpiTodayCount && data.today_count !== undefined) kpiTodayCount.textContent = data.today_count;
            if (kpiSub && data.today_count !== undefined) kpiSub.textContent = data.today_count + ' orders received today';
            if (btnLiveCount && data.active_count !== undefined) btnLiveCount.textContent = data.active_count;

            if (kpiNeedsAttention && data.pending_count !== undefined) {
                kpiNeedsAttention.textContent = data.pending_count;
                const attnCard = kpiNeedsAttention.closest('.op-metric-card');
                if (attnCard) {
                    attnCard.classList.toggle('alert-card', data.pending_count > 0);
                }
                if (kpiAttnSub) {
                    kpiAttnSub.textContent = data.pending_count > 0 ? 'Unconfirmed orders pending kitchen' : 'All live orders in progress';
                }

                // Update attention banner
                const banner = document.getElementById('attentionBanner');
                const bannerCount = document.getElementById('attnBannerCount');
                if (banner && bannerCount) {
                    bannerCount.textContent = data.pending_count;
                    banner.style.display = data.pending_count > 0 ? 'flex' : 'none';
                }
            }

            const orders = data.orders || [];
            let hasBrandNew = false;

            orders.forEach(o => {
                if (!currentOrdersMap[o.id]) {
                    hasBrandNew = true;
                }
                currentOrdersMap[o.id] = o;
            });

            // Update Pipeline counts
            const stepAll = document.getElementById('step-count-all');
            const stepPending = document.getElementById('step-count-pending');
            if (stepAll && data.today_count !== undefined) stepAll.textContent = data.today_count;
            if (stepPending && data.pending_count !== undefined) stepPending.textContent = data.pending_count;

            if (hasBrandNew) {
                playPosChime();
                showToast('🔔 New order arrived on WhatsApp!');
            }

            const grid = document.getElementById('posCardsGrid');
            orders.forEach(o => {
                const existing = grid.querySelector(`.pos-order-card[data-order-id="${o.id}"]`);
                if (!existing) {
                    const temp = document.createElement('div');
                    temp.innerHTML = generateCardHtml(o);
                    grid.prepend(temp.firstElementChild);
                } else {
                    updateCardInDom(o);
                }
            });

            applyGridFilters();

        } catch (_) {}
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escJs(s) {
        return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
    }

    @if($selectedOrder)
    document.addEventListener('DOMContentLoaded', () => {
        openOrderDrawer({{ $selectedOrder->id }});
    });
    @endif

    setInterval(pollLiveFeed, 5000);
</script>

@endsection