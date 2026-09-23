@extends('layouts.dashboard')
@section('title', 'POS Terminal & Orders')
@section('header_title', 'POS Terminal')
@section('header_subtitle', 'Live Order & Kitchen Flow • ' . ($restaurant->name ?? 'Restaurant'))

@section('content')

<style>
    /* ═════════════════════════════════════════════════════════════
       FOODIO MINIMAL POS SYSTEM — APPLE & SQUARE VIBE
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
    }

    .pos-terminal-layout {
        display: flex;
        flex-direction: column;
        gap: 20px;
        color: var(--pos-text);
        max-width: 1600px;
        margin: 0 auto;
    }

    /* ── ZONE 1: TOP GREETING & MINIMAL METRIC STRIP ── */
    .pos-top-zone {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        padding-bottom: 4px;
    }

    .pos-greeting-block {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pos-greeting-title {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.025em;
        line-height: 1.2;
        color: var(--pos-text);
    }

    .pos-greeting-subtitle {
        font-size: 13px;
        color: var(--pos-text-muted);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pos-store-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #15803d;
        font-size: 11px;
        font-weight: 700;
    }
    [data-theme="dark"] .pos-store-status-pill {
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
    }

    .pos-status-pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #16a34a;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
        animation: posPulse 2s infinite;
    }

    @keyframes posPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    /* Minimal 3-Card Metric Strip */
    .pos-metrics-strip {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .pos-metric-card {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 12px 18px;
        min-width: 175px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        gap: 2px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .pos-metric-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .pos-metric-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--pos-text-muted);
    }

    .pos-metric-val {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--pos-text);
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }

    .pos-metric-sub {
        font-size: 11px;
        color: var(--pos-text-faint);
        font-weight: 500;
    }

    /* ── ZONE 2: PIPELINE FILTER BAR & REAL-TIME SEARCH ── */
    .pos-controls-bar {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 16px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .pos-filter-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
        padding: 2px 0;
        scrollbar-width: none;
    }
    .pos-filter-tabs::-webkit-scrollbar { display: none; }

    .pos-tab-pill {
        border: 1px solid var(--pos-border);
        background: transparent;
        color: var(--pos-text-muted);
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        white-space: nowrap;
        font-family: inherit;
    }

    .pos-tab-pill:hover {
        background: var(--pos-canvas);
        color: var(--pos-text);
    }

    .pos-tab-pill.active {
        background: var(--pos-accent);
        color: #ffffff;
        border-color: var(--pos-accent);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    [data-theme="dark"] .pos-tab-pill.active {
        background: #f8fafc;
        color: #0b0f19;
        border-color: #f8fafc;
    }

    .pos-tab-count {
        font-size: 10.5px;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.07);
    }
    .pos-tab-pill.active .pos-tab-count {
        background: rgba(255, 255, 255, 0.25);
    }
    [data-theme="dark"] .pos-tab-pill.active .pos-tab-count {
        background: rgba(0, 0, 0, 0.15);
    }

    .pos-actions-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        justify-content: flex-end;
        min-width: 260px;
    }

    .pos-search-wrap {
        position: relative;
        flex: 1;
        max-width: 320px;
    }

    .pos-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13px;
        color: var(--pos-text-muted);
        pointer-events: none;
    }

    .pos-search-input {
        width: 100%;
        padding: 7px 12px 7px 34px;
        border-radius: 999px;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        font-size: 12.5px;
        font-family: inherit;
        color: var(--pos-text);
        outline: none;
        transition: all 0.15s ease;
    }
    .pos-search-input:focus {
        border-color: var(--pos-accent);
        background: var(--pos-card);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }

    .pos-audio-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .pos-audio-toggle:hover {
        color: var(--pos-text);
        border-color: var(--pos-text-muted);
    }
    .pos-audio-toggle.active {
        color: #15803d;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    [data-theme="dark"] .pos-audio-toggle.active {
        color: #4ade80;
        background: rgba(34, 197, 94, 0.15);
        border-color: rgba(34, 197, 94, 0.3);
    }

    /* ── ZONE 3: POS ORDER CARDS GRID ── */
    .pos-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 16px;
        align-items: stretch;
    }

    .pos-order-card {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .pos-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }
    [data-theme="dark"] .pos-order-card:hover {
        border-color: rgba(255, 255, 255, 0.2);
    }
    .pos-order-card.selected {
        border-color: #181818;
        box-shadow: 0 0 0 2px #181818, 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    [data-theme="dark"] .pos-order-card.selected {
        border-color: #f8fafc;
        box-shadow: 0 0 0 2px #f8fafc;
    }

    /* Card Top Bar */
    .pos-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .pos-card-id-block {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .pos-card-code {
        font-size: 16px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--pos-text);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .pos-card-time {
        font-size: 11px;
        color: var(--pos-text-muted);
        font-weight: 600;
    }

    .pos-status-badge {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 3px 9px;
        border-radius: 999px;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .pos-status-badge.pending {
        background: var(--st-new-bg);
        color: var(--st-new-text);
        border-color: var(--st-new-border);
    }
    .pos-status-badge.confirmed {
        background: var(--st-new-bg);
        color: var(--st-new-text);
        border-color: var(--st-new-border);
    }
    .pos-status-badge.preparing {
        background: var(--st-prep-bg);
        color: var(--st-prep-text);
        border-color: var(--st-prep-border);
    }
    .pos-status-badge.out_for_delivery {
        background: var(--st-ready-bg);
        color: var(--st-ready-text);
        border-color: var(--st-ready-border);
    }
    .pos-status-badge.delivered {
        background: var(--st-deliv-bg);
        color: var(--st-deliv-text);
        border-color: var(--st-deliv-border);
    }
    .pos-status-badge.cancelled {
        background: var(--st-cancel-bg);
        color: var(--st-cancel-text);
        border-color: var(--st-cancel-border);
    }

    /* Customer & Meta */
    .pos-card-customer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 12.5px;
    }

    .pos-card-customer-name {
        font-weight: 700;
        color: var(--pos-text);
        display: flex;
        align-items: center;
        gap: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pos-card-customer-type {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        border: 1px solid var(--pos-border);
        white-space: nowrap;
    }

    /* Items Preview */
    .pos-card-items-box {
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border-subtle);
        border-radius: 10px;
        padding: 9px 12px;
        margin-bottom: 14px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .pos-card-item-line {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--pos-text);
        line-height: 1.3;
    }

    .pos-card-item-qty {
        font-size: 11px;
        font-weight: 800;
        color: var(--pos-text-muted);
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 4px;
        padding: 0 4px;
    }

    .pos-card-item-name {
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pos-card-more-items {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--pos-text-muted);
        margin-top: 2px;
    }

    /* Card Footer & 1-Tap Advance */
    .pos-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid var(--pos-border-subtle);
        gap: 8px;
    }

    .pos-card-total-box {
        display: flex;
        flex-direction: column;
    }

    .pos-card-total-label {
        font-size: 9.5px;
        font-weight: 700;
        color: var(--pos-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pos-card-total-price {
        font-size: 16px;
        font-weight: 800;
        color: var(--pos-text);
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
    }

    .pos-card-advance-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 13px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        font-family: inherit;
    }

    .pos-card-advance-btn.new {
        background: #181818;
        color: #ffffff;
    }
    .pos-card-advance-btn.new:hover { background: #333333; }

    .pos-card-advance-btn.prep {
        background: #2563eb;
        color: #ffffff;
    }
    .pos-card-advance-btn.prep:hover { background: #1d4ed8; }

    .pos-card-advance-btn.dispatch {
        background: #4f46e5;
        color: #ffffff;
    }
    .pos-card-advance-btn.dispatch:hover { background: #4338ca; }

    .pos-card-advance-btn.complete {
        background: #16a34a;
        color: #ffffff;
    }
    .pos-card-advance-btn.complete:hover { background: #15803d; }

    .pos-card-advance-btn.done {
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        border-color: var(--pos-border);
        cursor: default;
    }

    /* Empty State */
    .pos-empty-grid {
        grid-column: 1 / -1;
        background: var(--pos-card);
        border: 1px dashed var(--pos-border);
        border-radius: 18px;
        padding: 60px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .pos-empty-icon { font-size: 44px; margin-bottom: 4px; }
    .pos-empty-title { font-size: 16px; font-weight: 800; color: var(--pos-text); }
    .pos-empty-desc { font-size: 12.5px; color: var(--pos-text-muted); max-width: 380px; line-height: 1.5; }

    /* ── ZONE 4: SLIDE-OVER POS ORDER DRAWER ── */
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
    .pos-drawer-backdrop.open {
        opacity: 1;
        pointer-events: auto;
    }

    .pos-drawer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
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
    .pos-drawer.open {
        transform: translateX(0);
    }

    /* Drawer Header */
    .pos-drawer-head {
        padding: 18px 20px;
        border-bottom: 1px solid var(--pos-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--pos-card);
    }

    .pos-drawer-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pos-drawer-tracking-code {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.02em;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: var(--pos-text);
    }

    .pos-drawer-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .pos-drawer-close-btn:hover {
        color: var(--pos-text);
        background: #e5e5e5;
    }

    /* Drawer Body (Scrollable Receipt) */
    .pos-drawer-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* Customer & WhatsApp Card */
    .pos-drawer-section {
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .pos-drawer-section-label {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--pos-text-muted);
    }

    .pos-customer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .pos-customer-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow: hidden;
    }

    .pos-customer-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--pos-text);
    }

    .pos-customer-phone {
        font-size: 12px;
        color: var(--pos-text-muted);
        font-variant-numeric: tabular-nums;
    }

    .pos-drawer-wa-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 8px;
        background: #16a34a;
        color: #ffffff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        transition: background 0.15s ease;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
    }
    .pos-drawer-wa-btn:hover { background: #15803d; }

    /* Address & GPS */
    .pos-address-box {
        font-size: 12.5px;
        color: var(--pos-text);
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-gps-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #0284c7;
        font-weight: 700;
        text-decoration: none;
    }
    .pos-gps-badge:hover { text-decoration: underline; }

    /* Receipt Ticket Area */
    .pos-receipt-ticket {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 12px;
        padding: 14px;
        position: relative;
    }

    .pos-receipt-ticket::before {
        content: "";
        position: absolute;
        top: -1px;
        left: 0;
        right: 0;
        height: 3px;
        background: repeating-linear-gradient(90deg, var(--pos-border) 0, var(--pos-border) 6px, transparent 6px, transparent 10px);
    }

    .pos-ticket-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--pos-border);
        margin-bottom: 12px;
        font-size: 11px;
        color: var(--pos-text-muted);
        font-weight: 600;
    }

    .pos-receipt-items {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    .pos-receipt-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        font-size: 13px;
    }

    .pos-receipt-item-title {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        color: var(--pos-text);
        font-weight: 600;
        line-height: 1.3;
    }

    .pos-receipt-item-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 1px 5px;
        border-radius: 4px;
        background: var(--pos-canvas);
        color: var(--pos-text-muted);
        border: 1px solid var(--pos-border);
    }

    .pos-receipt-item-price {
        font-weight: 700;
        color: var(--pos-text);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .pos-receipt-totals {
        border-top: 1px dashed var(--pos-border);
        padding-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-totals-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: var(--pos-text-muted);
        font-weight: 500;
    }

    .pos-grand-total-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        margin-top: 4px;
        border-top: 1.5px solid var(--pos-border);
        font-size: 16px;
        font-weight: 800;
        color: var(--pos-text);
    }

    /* Rider Box */
    .pos-rider-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        background: var(--pos-canvas);
        border: 1px solid var(--pos-border);
        border-radius: 10px;
    }

    .pos-rider-meta {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pos-rider-avatar {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .pos-rider-details h4 {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--pos-text);
    }

    .pos-rider-details p {
        font-size: 11px;
        color: var(--pos-text-muted);
    }

    /* Drawer Footer (Sticky Actions) */
    .pos-drawer-foot {
        padding: 16px 20px;
        border-top: 1px solid var(--pos-border);
        background: var(--pos-card);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .pos-drawer-primary-btn {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: -0.01em;
        cursor: pointer;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.15s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        font-family: inherit;
    }

    .pos-drawer-sec-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pos-drawer-sec-btn {
        flex: 1;
        padding: 9px;
        border-radius: 10px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        color: var(--pos-text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.15s ease;
        font-family: inherit;
    }
    .pos-drawer-sec-btn:hover {
        background: #eaeaea;
    }
    .pos-drawer-sec-btn.danger {
        color: #dc2626;
        border-color: #fecaca;
        background: #fef2f2;
    }
    .pos-drawer-sec-btn.danger:hover {
        background: #fee2e2;
    }

    /* ── MODALS (DISPATCH & CANCEL) ── */
    .pos-modal-backdrop {
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
    .pos-modal-backdrop.open {
        display: flex;
    }

    .pos-modal-window {
        background: var(--pos-card);
        border: 1px solid var(--pos-border);
        border-radius: 18px;
        width: 480px;
        max-width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        animation: posModalFade 0.2s ease;
    }

    @keyframes posModalFade {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }

    .pos-modal-header {
        padding: 18px 22px;
        border-bottom: 1px solid var(--pos-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pos-modal-header h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--pos-text);
    }

    .pos-modal-body {
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .pos-modal-foot {
        padding: 14px 22px;
        border-top: 1px solid var(--pos-border);
        background: var(--pos-canvas);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* Toast Notification */
    #live-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 10000;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
        transition: opacity 0.25s ease, transform 0.25s ease;
        opacity: 0;
        pointer-events: none;
        transform: translateY(8px);
    }
    #live-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 900px) {
        .pos-cards-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
        .pos-drawer {
            width: 100vw;
        }
    }
    @media (max-width: 600px) {
        .pos-greeting-title { font-size: 20px; }
        .pos-metric-card { min-width: 100%; }
        .pos-cards-grid { grid-template-columns: 1fr; }
        .pos-actions-right { width: 100%; min-width: 100%; }
    }
</style>

<div class="pos-terminal-layout">

    <!-- ── ZONE 1: TOP GREETING & MINIMAL KPI STRIP ── -->
    <div class="pos-top-zone">
        <div class="pos-greeting-block">
            <h1 class="pos-greeting-title">
                Good {{ now()->format('A') === 'AM' ? 'morning' : (now()->format('H') < 17 ? 'afternoon' : 'evening') }}, 
                {{ $restaurant->name ?? 'Chef' }} 👋
            </h1>
            <div class="pos-greeting-subtitle">
                <span>{{ now()->format('l, F j') }}</span>
                <span>•</span>
                <span class="pos-store-status-pill">
                    <span class="pos-status-pulse-dot"></span>
                    {{ ($restaurant->is_open ?? true) ? 'Terminal Online' : 'Closed' }}
                </span>
                <span>•</span>
                <span>🚴 {{ $activeRidersCount ?? 0 }} active fleet</span>
            </div>
        </div>

        <div class="pos-metrics-strip">
            <!-- 1. Today's Sales -->
            <div class="pos-metric-card">
                <span class="pos-metric-label">Today's Sales</span>
                <span class="pos-metric-val" id="kpi-revenue">PKR {{ number_format($todayRevenue) }}</span>
                <span class="pos-metric-sub" id="kpi-sales-sub">{{ $totalOrdersToday }} orders processed</span>
            </div>

            <!-- 2. Active Orders in Kitchen -->
            <div class="pos-metric-card">
                <span class="pos-metric-label">Active Kitchen</span>
                <span class="pos-metric-val" id="kpi-live-orders">{{ $liveOrdersCount }}</span>
                <span class="pos-metric-sub">Live in preparation queue</span>
            </div>

            <!-- 3. Average Order Value -->
            <div class="pos-metric-card">
                <span class="pos-metric-label">Avg Ticket (AOV)</span>
                <span class="pos-metric-val" id="kpi-aov">
                    PKR {{ number_format($totalOrdersToday > 0 ? round($todayRevenue / $totalOrdersToday) : 0) }}
                </span>
                <span class="pos-metric-sub">Across today's orders</span>
            </div>
        </div>
    </div>

    <!-- ── ZONE 2: PIPELINE FILTER BAR & REAL-TIME SEARCH ── -->
    <div class="pos-controls-bar">
        <div class="pos-filter-tabs">
            <button type="button" class="pos-tab-pill active" onclick="setFilter('all', this)">
                <span>All Orders</span>
                <span class="pos-tab-count" id="pill-count-all">{{ $orders->total() ?? count($orders) }}</span>
            </button>
            <button type="button" class="pos-tab-pill" onclick="setFilter('new', this)">
                <span>New</span>
                <span class="pos-tab-count" id="pill-count-new">{{ ($statusCounts['pending'] ?? 0) + ($statusCounts['confirmed'] ?? 0) }}</span>
            </button>
            <button type="button" class="pos-tab-pill" onclick="setFilter('preparing', this)">
                <span>Preparing</span>
                <span class="pos-tab-count" id="pill-count-preparing">{{ $statusCounts['preparing'] ?? 0 }}</span>
            </button>
            <button type="button" class="pos-tab-pill" onclick="setFilter('ready', this)">
                <span>Delivery</span>
                <span class="pos-tab-count" id="pill-count-ready">{{ $statusCounts['out_for_delivery'] ?? 0 }}</span>
            </button>
            <button type="button" class="pos-tab-pill" onclick="setFilter('delivered', this)">
                <span>Delivered</span>
                <span class="pos-tab-count" id="pill-count-delivered">{{ $statusCounts['delivered'] ?? 0 }}</span>
            </button>
        </div>

        <div class="pos-actions-right">
            <div class="pos-search-wrap">
                <span class="pos-search-icon">🔍</span>
                <input type="text" id="posSearchInput" class="pos-search-input" placeholder="Search order #, customer, phone..." onkeyup="handleSearch(this.value)">
            </div>

            <button type="button" class="pos-audio-toggle active" id="audioToggleBtn" onclick="toggleAudioChime()" title="Toggle incoming order audio ringtone">
                <span id="audioIcon">🔊</span>
                <span id="audioText">Sound On</span>
            </button>
        </div>
    </div>

    <!-- ── ZONE 3: POS ORDER CARDS GRID ── -->
    <div class="pos-cards-grid" id="posCardsGrid">
        @forelse($orders as $order)
            @php
                $orderStatus = $order->status ?? 'pending';
                $badgeClass = match($orderStatus) {
                    'pending', 'confirmed' => 'pending',
                    'preparing'            => 'preparing',
                    'out_for_delivery'     => 'out_for_delivery',
                    'delivered'            => 'delivered',
                    'cancelled'            => 'cancelled',
                    default                => 'pending'
                };

                $advanceBtnClass = match($orderStatus) {
                    'pending'          => 'new',
                    'confirmed'        => 'prep',
                    'preparing'        => 'dispatch',
                    'out_for_delivery' => 'complete',
                    default            => 'done'
                };

                $advanceText = match($orderStatus) {
                    'pending'          => 'Confirm Order ✓',
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
                 data-status="{{ $orderStatus }}"
                 data-tracking="{{ strtolower($order->tracking_code) }}"
                 data-customer="{{ strtolower($order->customer_name ?? '') }}"
                 data-phone="{{ $order->customer_phone ?? '' }}"
                 onclick="openOrderDrawer({{ $order->id }})">

                <!-- Card Header -->
                <div class="pos-card-head">
                    <div class="pos-card-id-block">
                        <span class="pos-card-code">#{{ $order->tracking_code }}</span>
                        <span class="pos-card-time">{{ $order->created_at->format('h:i A') }} • {{ $order->created_at->diffForHumans(null, true, true) }}</span>
                    </div>
                    <span class="pos-status-badge {{ $badgeClass }}">
                        {{ $order->status_label ?? ucfirst(str_replace('_', ' ', $orderStatus)) }}
                    </span>
                </div>

                <!-- Customer Info -->
                <div class="pos-card-customer-row">
                    <div class="pos-card-customer-name">
                        <span>👤</span>
                        <span>{{ $order->customer_name ?: 'Guest Customer' }}</span>
                    </div>
                    <span class="pos-card-customer-type">
                        {{ $order->payment_method === 'online' ? '💳 Paid' : '💵 COD' }} • 🛵
                    </span>
                </div>

                <!-- Items Preview -->
                <div class="pos-card-items-box">
                    @php $itemsPreview = $order->items->take(3); @endphp
                    @foreach($itemsPreview as $item)
                        <div class="pos-card-item-line">
                            <span class="pos-card-item-qty">{{ $item->quantity }}x</span>
                            <span class="pos-card-item-name">{{ $item->name ?? ($item->item_name ?? 'Dish') }}</span>
                        </div>
                    @endforeach
                    @if($order->items->count() > 3)
                        <span class="pos-card-more-items">+ {{ $order->items->count() - 3 }} more items...</span>
                    @endif
                </div>

                <!-- Card Footer -->
                <div class="pos-card-footer">
                    <div class="pos-card-total-box">
                        <span class="pos-card-total-label">Total Bill</span>
                        <span class="pos-card-total-price">PKR {{ number_format($order->total) }}</span>
                    </div>

                    @if(!in_array($orderStatus, ['delivered', 'cancelled']))
                        <button type="button" 
                                class="pos-card-advance-btn {{ $advanceBtnClass }}"
                                onclick="event.stopPropagation(); triggerCardAdvance({{ $order->id }}, '{{ $orderStatus }}', this)">
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
                <div class="pos-empty-icon">🍽️</div>
                <div class="pos-empty-title">No Orders In Queue</div>
                <p class="pos-empty-desc">New orders placed by customers on WhatsApp will automatically ring and appear here instantly.</p>
            </div>
        @endforelse
    </div>

</div>

<!-- ── ZONE 4: SLIDE-OVER POS ORDER DRAWER ── -->
<div class="pos-drawer-backdrop" id="posDrawerBackdrop" onclick="closeOrderDrawer()"></div>

<div class="pos-drawer" id="posDrawer">
    <!-- Drawer Head -->
    <div class="pos-drawer-head">
        <div class="pos-drawer-title-group">
            <span class="pos-drawer-tracking-code" id="drawerTrackingCode">#ORDER</span>
            <span class="pos-status-badge pending" id="drawerStatusBadge">Pending</span>
        </div>
        <button type="button" class="pos-drawer-close-btn" onclick="closeOrderDrawer()" title="Close Drawer (Esc)">✕</button>
    </div>

    <!-- Drawer Body -->
    <div class="pos-drawer-body" id="drawerBody">
        <!-- Rendered dynamically by renderDrawerContent() -->
    </div>

    <!-- Drawer Footer -->
    <div class="pos-drawer-foot" id="drawerFoot">
        <!-- Rendered dynamically -->
    </div>
</div>

<!-- ── DISPATCH MODAL ── -->
<div class="pos-modal-backdrop" id="dispatchModal">
    <div class="pos-modal-window">
        <div class="pos-modal-header" style="background: #181818; color: #fff;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">🛵</span>
                <div>
                    <h3 style="color: #fff; font-size: 15px;">Dispatch Order to Rider</h3>
                    <p style="font-size: 11.5px; color: #a3a3a3; margin-top: 1px;" id="dispatchSubtext">Order #</p>
                </div>
            </div>
            <button type="button" onclick="closeDispatchModal()" style="background: none; border: none; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
        </div>

        <form id="dispatchForm" method="POST" action="" onsubmit="return ajaxSubmitDispatch(event);">
            @csrf
            <input type="hidden" name="status" value="out_for_delivery">

            <div class="pos-modal-body">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px;">Select Fleet Rider *</label>
                    <select id="riderSelect" class="form-control" style="width: 100%; padding: 9px 12px; border-radius: 10px; border: 1px solid var(--pos-border); font-size: 13px;" onchange="handleRiderSelect(this)">
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
                            <label style="display: block; font-size: 11px; font-weight: 600; color: var(--pos-text-muted); margin-bottom: 4px;">Rider Name *</label>
                            <input type="text" id="inputRiderName" name="rider_name" class="form-control" placeholder="e.g. Ali Khan" style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12.5px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 600; color: var(--pos-text-muted); margin-bottom: 4px;">Rider Phone Number</label>
                            <input type="text" id="inputRiderPhone" name="rider_phone" class="form-control" placeholder="e.g. 03001234567" style="width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--pos-border); font-size: 12.5px;">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px;">ETA (Minutes)</label>
                        <input type="number" name="estimated_minutes" class="form-control" value="25" min="5" max="180" style="width: 100%; padding: 9px 12px; border-radius: 10px; border: 1px solid var(--pos-border); font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px;">Rider Instructions</label>
                        <input type="text" name="rider_notes" class="form-control" placeholder="e.g. Ring bell twice" style="width: 100%; padding: 9px 12px; border-radius: 10px; border: 1px solid var(--pos-border); font-size: 13px;">
                    </div>
                </div>

                <div style="background: var(--pos-canvas); border: 1px solid var(--pos-border); border-radius: 10px; padding: 10px 12px; font-size: 12px;">
                    <span style="font-weight: 700;">📍 Delivery Destination:</span>
                    <span id="dispatchAddress" style="color: var(--pos-text-muted); margin-left: 4px;"></span>
                </div>
            </div>

            <div class="pos-modal-foot">
                <button type="button" onclick="closeDispatchModal()" class="pos-drawer-sec-btn" style="flex: none; width: 100px;">Cancel</button>
                <button type="submit" class="pos-card-advance-btn dispatch" style="padding: 10px 20px; font-size: 13px;">Confirm & Dispatch 🛵</button>
            </div>
        </form>
    </div>
</div>

<!-- ── CANCEL ORDER MODAL ── -->
<div class="pos-modal-backdrop" id="cancelOrderModal">
    <div class="pos-modal-window" style="max-width: 400px; text-align: center;">
        <div class="pos-modal-body" style="padding: 28px 24px 20px;">
            <div style="font-size: 40px; margin-bottom: 6px;">❌</div>
            <h3 style="font-size: 17px; font-weight: 800; color: var(--pos-text); margin-bottom: 6px;">Cancel Order?</h3>
            <p style="font-size: 12.5px; color: var(--pos-text-muted); line-height: 1.5; margin-bottom: 14px;">
                Order <strong id="cancelOrderCode" style="color: var(--pos-text);"></strong> will be marked as Cancelled and the customer will be notified via WhatsApp.
            </p>
            <textarea id="cancelReason" rows="2" placeholder="Optional cancellation reason..." style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--pos-border); background: var(--pos-canvas); font-size: 12.5px; resize: none; font-family: inherit; margin-bottom: 8px;"></textarea>
        </div>
        <div class="pos-modal-foot" style="justify-content: stretch; gap: 10px;">
            <button type="button" onclick="closeCancelModal()" class="pos-drawer-sec-btn" style="flex: 1;">Keep Order</button>
            <button type="button" onclick="executeCancelOrder()" class="pos-card-advance-btn" style="flex: 1; justify-content: center; background: #dc2626; color: #fff;">Yes, Cancel</button>
        </div>
    </div>
</div>

<!-- ── TOAST CONTAINER ── -->
<div id="live-toast"></div>

<!-- ── JAVASCRIPT ENGINE ── -->
<script>
    const RESTAURANT_ID     = '{{ $restaurant->id }}';
    const LIVE_FEED_URL     = '/dashboard/' + RESTAURANT_ID + '/orders/live-feed';
    const CSRF_TOKEN        = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let CURRENT_FILTER      = 'all';
    let SEARCH_QUERY        = '';
    let ACTIVE_DRAWER_ID    = null;
    let AUDIO_ENABLED       = true;
    let _cancelOrderId      = null;

    // Local Map of all orders for zero-latency drawer lookups
    const currentOrdersMap  = {};

    // Initial server-side orders injection
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

    // Status transition definitions
    const STATUS_FLOW = {
        pending: {
            label: 'New Order',
            next: 'confirmed',
            btnText: 'Confirm Order ✓',
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

    // ── Web Audio API POS Chime (Synthesizes crisp 2-tone kitchen bell) ──
    function playPosChime() {
        if (!AUDIO_ENABLED) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            gain1.gain.setValueAtTime(0.2, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.35);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.12); // A5
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
            text.textContent = 'Sound On';
            playPosChime();
            showToast('🔊 Order sound alerts active');
        } else {
            btn.classList.remove('active');
            icon.textContent = '🔇';
            text.textContent = 'Sound Muted';
            showToast('🔇 Order sound alerts muted');
        }
    }

    // ── Filter and Search Logic ──
    function setFilter(status, pillElement) {
        CURRENT_FILTER = status;
        document.querySelectorAll('.pos-tab-pill').forEach(p => p.classList.remove('active'));
        if (pillElement) pillElement.classList.add('active');
        applyGridFilters();
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

            let matchesTab = true;
            if (CURRENT_FILTER === 'new') {
                matchesTab = (st === 'pending' || st === 'confirmed');
            } else if (CURRENT_FILTER === 'preparing') {
                matchesTab = (st === 'preparing');
            } else if (CURRENT_FILTER === 'ready') {
                matchesTab = (st === 'out_for_delivery');
            } else if (CURRENT_FILTER === 'delivered') {
                matchesTab = (st === 'delivered');
            }

            let matchesSearch = true;
            if (SEARCH_QUERY) {
                matchesSearch = tracking.includes(SEARCH_QUERY) || 
                                customer.includes(SEARCH_QUERY) || 
                                phone.includes(SEARCH_QUERY);
            }

            if (matchesTab && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Toggle empty grid state
        let emptyState = document.getElementById('posEmptyGrid');
        if (!emptyState && visibleCount === 0) {
            const grid = document.getElementById('posCardsGrid');
            emptyState = document.createElement('div');
            emptyState.id = 'posEmptyGrid';
            emptyState.className = 'pos-empty-grid';
            emptyState.innerHTML = `
                <div class="pos-empty-icon">🍽️</div>
                <div class="pos-empty-title">No orders match "${CURRENT_FILTER}"</div>
                <p class="pos-empty-desc">There are currently no orders under this filter or search query.</p>
            `;
            grid.appendChild(emptyState);
        } else if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    }

    // ── Slide-Over POS Order Drawer ──
    function openOrderDrawer(orderId) {
        ACTIVE_DRAWER_ID = orderId;
        const o = currentOrdersMap[orderId];
        if (!o) return;

        // Highlight selected card
        document.querySelectorAll('.pos-order-card').forEach(c => {
            c.classList.toggle('selected', parseInt(c.dataset.orderId) === orderId);
        });

        // Set Header
        document.getElementById('drawerTrackingCode').textContent = '#' + o.tracking_code;
        const badge = document.getElementById('drawerStatusBadge');
        badge.className = 'pos-status-badge ' + (o.status === 'out_for_delivery' ? 'out_for_delivery' : (o.status || 'pending'));
        badge.textContent = o.status_label || (STATUS_FLOW[o.status]?.label || o.status);

        // Render Body
        renderDrawerContent(o);

        // Open Drawer
        document.getElementById('posDrawerBackdrop').classList.add('open');
        document.getElementById('posDrawer').classList.add('open');
    }

    function closeOrderDrawer() {
        document.getElementById('posDrawerBackdrop').classList.remove('open');
        document.getElementById('posDrawer').classList.remove('open');
        document.querySelectorAll('.pos-order-card').forEach(c => c.classList.remove('selected'));
        ACTIVE_DRAWER_ID = null;
    }

    // Keyboard ESC listener
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

        // Items HTML
        const itemsHtml = (o.items || []).map(it => `
            <div class="pos-receipt-row">
                <div class="pos-receipt-item-title">
                    <span class="pos-receipt-item-badge">${it.quantity}x</span>
                    <span>${escHtml(it.name)}</span>
                </div>
                <span class="pos-receipt-item-price">PKR ${Number(it.subtotal).toLocaleString()}</span>
            </div>
        `).join('');

        const subtotal = (o.items || []).reduce((acc, it) => acc + (it.subtotal || 0), 0);
        const deliveryFee = o.delivery_fee || 0;

        // Rider HTML
        let riderHtml = '';
        if (o.rider_name || o.rider_phone) {
            riderHtml = `
                <div class="pos-rider-box">
                    <div class="pos-rider-meta">
                        <div class="pos-rider-avatar">🚴</div>
                        <div class="pos-rider-details">
                            <h4>${escHtml(o.rider_name || 'Assigned Rider')}</h4>
                            <p>${escHtml(o.rider_phone || 'Fleet Delivery')}</p>
                        </div>
                    </div>
                    ${o.rider_phone ? `<a href="tel:${escHtml(o.rider_phone)}" class="pos-drawer-wa-btn" style="background:#0284c7;">📞 Call</a>` : ''}
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
            <!-- Customer Card -->
            <div class="pos-drawer-section">
                <span class="pos-drawer-section-label">Customer & Contact</span>
                <div class="pos-customer-row">
                    <div class="pos-customer-details">
                        <span class="pos-customer-name">${escHtml(o.customer_name || 'Guest Customer')}</span>
                        <span class="pos-customer-phone">📞 ${escHtml(o.customer_phone || 'Not provided')}</span>
                    </div>
                    ${cleanPhone ? `
                        <a href="https://wa.me/${cleanPhone}" target="_blank" class="pos-drawer-wa-btn">
                            <span>💬</span>
                            <span>WhatsApp</span>
                        </a>
                    ` : ''}
                </div>
                <div class="pos-address-box">
                    <div>📍 <strong>Address:</strong> ${escHtml(o.delivery_address || 'Pickup / Counter Order')}</div>
                    ${o.delivery_lat && o.delivery_lng ? `
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(o.delivery_lat)},${encodeURIComponent(o.delivery_lng)}" 
                           target="_blank" class="pos-gps-badge">
                            📍 Verified GPS Location (Open Maps ↗)
                        </a>
                    ` : ''}
                </div>
            </div>

            <!-- Receipt Ticket -->
            <div class="pos-receipt-ticket">
                <div class="pos-ticket-head">
                    <span>PLACED AT ${escHtml(o.created_at_time || '')}</span>
                    <span>${o.payment_method === 'online' ? '💳 PREPAID' : '💵 CASH ON DELIVERY'}</span>
                </div>

                <div class="pos-receipt-items">
                    ${itemsHtml || '<div style="color:var(--pos-text-muted);font-size:12px;">No items listed</div>'}
                </div>

                <div class="pos-receipt-totals">
                    <div class="pos-totals-row">
                        <span>Items Subtotal</span>
                        <span>PKR ${Number(subtotal).toLocaleString()}</span>
                    </div>
                    <div class="pos-totals-row">
                        <span>Delivery Fee</span>
                        <span>PKR ${Number(deliveryFee).toLocaleString()}</span>
                    </div>
                    <div class="pos-grand-total-row">
                        <span>Total Amount</span>
                        <span>PKR ${Number(o.total).toLocaleString()}</span>
                    </div>
                </div>
            </div>

            <!-- Rider Status Box -->
            ${riderHtml}
        `;

        // Drawer Footer Actions
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

            // Extract order ID from URL
            const urlMatch = url.match(/\/orders\/(\d+)\/status/);
            const orderId = urlMatch ? parseInt(urlMatch[1]) : ACTIVE_DRAWER_ID;

            if (orderId && currentOrdersMap[orderId]) {
                currentOrdersMap[orderId].status = data.status || status;
                currentOrdersMap[orderId].status_label = data.status_label || (STATUS_FLOW[data.status || status]?.label);

                // Update card element in DOM
                updateCardInDom(currentOrdersMap[orderId]);

                // Update drawer if currently open for this order
                if (ACTIVE_DRAWER_ID === orderId) {
                    openOrderDrawer(orderId);
                }
            }

            // Refresh metrics and counts
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

    // ── Update Card in DOM ──
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
                    <div class="pos-card-total-box">
                        <span class="pos-card-total-label">Total Bill</span>
                        <span class="pos-card-total-price">PKR ${Number(o.total).toLocaleString()}</span>
                    </div>
                    <button type="button" class="pos-card-advance-btn ${btnClass}"
                            onclick="event.stopPropagation(); triggerCardAdvance(${o.id}, '${o.status}', this)">
                        <span>${flow.btnText}</span>
                        <span>→</span>
                    </button>
                `;
            } else {
                footer.innerHTML = `
                    <div class="pos-card-total-box">
                        <span class="pos-card-total-label">Total Bill</span>
                        <span class="pos-card-total-price">PKR ${Number(o.total).toLocaleString()}</span>
                    </div>
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
            showToast('❌ Order cancelled successfully');

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
        t.style.background = type === 'success' ? '#181818' : '#dc2626';
        t.style.color = '#ffffff';
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.classList.remove('show'); }, 3500);
    }

    // ── Card HTML Generator for Live Arrivals ──
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

        const moreCount = (o.items || []).length > 3 ? `+ ${o.items.length - 3} more items...` : '';

        return `
            <div class="pos-order-card" 
                 data-order-id="${o.id}"
                 data-status="${o.status}"
                 data-tracking="${escHtml(o.tracking_code).toLowerCase()}"
                 data-customer="${escHtml(o.customer_name || '').toLowerCase()}"
                 data-phone="${escHtml(o.customer_phone || '')}"
                 onclick="openOrderDrawer(${o.id})">

                <div class="pos-card-head">
                    <div class="pos-card-id-block">
                        <span class="pos-card-code">#${escHtml(o.tracking_code)}</span>
                        <span class="pos-card-time">${escHtml(o.created_at_time || '')} • ${escHtml(o.created_at_humans || '')}</span>
                    </div>
                    <span class="pos-status-badge ${badgeClass}">
                        ${escHtml(o.status_label || flow.label || o.status)}
                    </span>
                </div>

                <div class="pos-card-customer-row">
                    <div class="pos-card-customer-name">
                        <span>👤</span>
                        <span>${escHtml(o.customer_name || 'Guest Customer')}</span>
                    </div>
                    <span class="pos-card-customer-type">
                        ${o.payment_method === 'online' ? '💳 Paid' : '💵 COD'} • 🛵
                    </span>
                </div>

                <div class="pos-card-items-box">
                    ${itemsHtml}
                    ${moreCount ? `<span class="pos-card-more-items">${moreCount}</span>` : ''}
                </div>

                <div class="pos-card-footer">
                    <div class="pos-card-total-box">
                        <span class="pos-card-total-label">Total Bill</span>
                        <span class="pos-card-total-price">PKR ${Number(o.total).toLocaleString()}</span>
                    </div>

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

            // Update Metric Cards
            const kpiRev = document.getElementById('kpi-revenue');
            const kpiLive = document.getElementById('kpi-live-orders');
            const kpiSub = document.getElementById('kpi-sales-sub');
            if (kpiRev && data.revenue !== undefined) kpiRev.textContent = 'PKR ' + Number(data.revenue).toLocaleString();
            if (kpiLive && data.active_count !== undefined) kpiLive.textContent = data.active_count;
            if (kpiSub && data.today_count !== undefined) kpiSub.textContent = data.today_count + ' orders processed';

            const orders = data.orders || [];
            let hasBrandNew = false;

            // Check for new incoming orders
            orders.forEach(o => {
                if (!currentOrdersMap[o.id]) {
                    hasBrandNew = true;
                }
                currentOrdersMap[o.id] = o;
            });

            // Update Tab Counts
            const countAll = document.getElementById('pill-count-all');
            const countNew = document.getElementById('pill-count-new');
            if (countAll && data.today_count !== undefined) countAll.textContent = data.today_count;
            if (countNew && data.pending_count !== undefined) countNew.textContent = data.pending_count;

            if (hasBrandNew) {
                playPosChime();
                showToast('🔔 New WhatsApp order arrived!');
            }

            // Sync grid items
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

    // Utilities
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

    // Auto-open selected order if passed in query
    @if($selectedOrder)
    document.addEventListener('DOMContentLoaded', () => {
        openOrderDrawer({{ $selectedOrder->id }});
    });
    @endif

    // Initial poll & periodic 5s loop
    setInterval(pollLiveFeed, 5000);
</script>

@endsection