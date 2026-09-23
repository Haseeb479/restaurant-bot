<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ $restaurant->name ?? ($r->name ?? 'Restaurant Owner') }}</title>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Immediate theme initializer & Client-side Auth Guard -->
    <script>
        (function() {
            const t = localStorage.getItem('owner_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);

            @if(session('admin_logged_in') !== true)
            // On fresh server render (which passed server-side authCheck), mark session active
            sessionStorage.setItem('owner_authenticated_session', 'active');

            // On Back/Forward Cache restore, verify session was not cleared by visiting login page
            window.addEventListener('pageshow', function (event) {
                if (sessionStorage.getItem('owner_authenticated_session') !== 'active') {
                    document.documentElement.style.display = 'none';
                    window.location.replace('{{ route("landing.owner-login-page") }}');
                }
            });
            @endif
        })();
    </script>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #ffffff;
            --sidebar-hover: #f5f5f4;
            --sidebar-active: #181818;
            --sidebar-text: #525252;
            --sidebar-text-active: #ffffff;
            --main-bg: #f7f7f5;
            --card-bg: #ffffff;
            --border-color: #e8e8e8;
            --text-main: #181818;
            --text-muted: #737373;
            --input-bg: #ffffff;
            --header-bg: #ffffff;
        }

        /* ── Foodio Obsidian Midnight — Dark Mode Tokens ── */
        [data-theme="dark"] {
            --sidebar-bg: #0b0f19;
            --sidebar-hover: rgba(255, 255, 255, 0.05);
            --sidebar-active: #4f46e5;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --main-bg: #0b0f19;
            --card-bg: #131b2e;
            --card-elevated: #162035;
            --hover-bg: #1a233a;
            --border-color: rgba(255, 255, 255, 0.07);
            --border-subtle: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-faint: #64748b;
            --input-bg: #0e1424;
            --header-bg: #0b0f19;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body, button, input, select, textarea, optgroup, table, th, td, h1, h2, h3, h4, h5, h6, .btn, .badge {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: var(--main-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            font-size: 13px;
            line-height: 1.5;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* ── Luxury Pill Theme Toggle ── */
        .theme-toggle-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px 4px 5px;
            border-radius: 9999px;
            background: #151d2e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
            user-select: none;
        }
        .theme-toggle-pill:hover {
            background: #192338;
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }
        .theme-pill-icon {
            font-size: 11px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .theme-pill-icon.moon.active {
            background: #4f46e5;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.6);
            color: #ffffff;
        }
        .theme-pill-icon.sun.active {
            background: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            color: #ffffff;
        }
        [data-theme="light"] .theme-toggle-pill {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #0f172a;
        }
        [data-theme="light"] .theme-pill-icon.moon {
            background: transparent;
            box-shadow: none;
            opacity: 0.35;
        }
        [data-theme="dark"] .theme-pill-icon.sun {
            background: transparent;
            box-shadow: none;
            opacity: 0.35;
        }

        /* ═══════════════════════════════════════════════════
           FOODIO OBSIDIAN MIDNIGHT — Dark Mode Overrides
           ═══════════════════════════════════════════════════ */

        /* HEADER */
        [data-theme="dark"] header {
            background: #0b0f19 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
            box-shadow: 0 1px 0 rgba(255,255,255,0.03), 0 4px 20px rgba(0,0,0,0.3) !important;
        }
        [data-theme="dark"] .header-title h1,
        [data-theme="dark"] .user-name {
            color: #f8fafc !important;
        }
        [data-theme="dark"] .header-title p {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .date-pill {
            background: #151d2e !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .status-online-pill {
            background: rgba(34, 197, 94, 0.1) !important;
            border-color: rgba(34, 197, 94, 0.25) !important;
            color: #22c55e !important;
        }
        [data-theme="dark"] .status-dot {
            background: #22c55e !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2) !important;
        }
        [data-theme="dark"] #notif-bell-wrap {
            background: #151d2e !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .avatar {
            background: #4f46e5 !important;
            color: #ffffff !important;
        }

        /* SIDEBAR */
        [data-theme="dark"] aside {
            background: #0b0f19 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        }
        [data-theme="dark"] .nav-item {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .nav-item:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }
        [data-theme="dark"] .nav-item.active {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35) !important;
        }

        /* CARDS & SURFACES */
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .panel-card,
        [data-theme="dark"] .card,
        [data-theme="dark"] .dashboard-card,
        [data-theme="dark"] .content-card,
        [data-theme="dark"] .order-details-card,
        [data-theme="dark"] .modal-content,
        [data-theme="dark"] .modal-container,
        [data-theme="dark"] .modal-card,
        [data-theme="dark"] .orders-col,
        [data-theme="dark"] .table-card,
        [data-theme="dark"] .bg-white {
            background-color: #111827 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
            color: #F8FAFC !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4) !important;
        }
        [data-theme="dark"] .card-panel,
        [data-theme="dark"] .status-banner,
        [data-theme="dark"] .metric-card,
        [data-theme="dark"] .menu-card,
        [data-theme="dark"] .menu-item-box,
        [data-theme="dark"] .menu-item-card,
        [data-theme="dark"] .deal-card,
        [data-theme="dark"] .settings-group,
        [data-theme="dark"] .settings-block,
        [data-theme="dark"] .settings-section,
        [data-theme="dark"] .settings-card,
        [data-theme="dark"] .customer-card,
        [data-theme="dark"] .customer-row,
        [data-theme="dark"] .kpi-card,
        [data-theme="dark"] .chart-card,
        [data-theme="dark"] .metric-box,
        [data-theme="dark"] .qr-box-container {
            background: #111827 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
            color: #F8FAFC !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.35) !important;
        }

        /* PANEL HEADERS */
        [data-theme="dark"] .panel-header,
        [data-theme="dark"] .card-panel-header {
            border-bottom-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .panel-title h3,
        [data-theme="dark"] .card-panel-header h3,
        [data-theme="dark"] .stat-val,
        [data-theme="dark"] .order-detail-title h3,
        [data-theme="dark"] .status-banner-left h3,
        [data-theme="dark"] .step-text h4,
        [data-theme="dark"] .metric-value {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .panel-title p,
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .stat-footer,
        [data-theme="dark"] .stat-sub,
        [data-theme="dark"] .info-col-label,
        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .card-panel-header p,
        [data-theme="dark"] .status-banner-left p,
        [data-theme="dark"] .step-text p,
        [data-theme="dark"] .metric-title,
        [data-theme="dark"] .form-hint,
        [data-theme="dark"] .settings-desc,
        [data-theme="dark"] .setting-desc,
        [data-theme="dark"] .donut-legend-item {
            color: #94A3B8 !important;
        }
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3,
        [data-theme="dark"] h4, [data-theme="dark"] strong {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .metric-footer {
            color: #94A3B8 !important;
            border-top-color: rgba(255, 255, 255, 0.07) !important;
        }

        /* KPI / STAT ICON CONTAINERS */
        [data-theme="dark"] .stat-icon-wrap.purple { background: rgba(124, 92, 252, 0.15) !important; color: #9B85FF !important; }
        [data-theme="dark"] .stat-icon-wrap.green  { background: rgba(34, 197, 94, 0.12) !important;  color: #22C55E !important; }
        [data-theme="dark"] .stat-icon-wrap.blue   { background: rgba(56, 189, 248, 0.12) !important;  color: #38BDF8 !important; }
        [data-theme="dark"] .stat-icon-wrap.orange { background: rgba(245, 158, 11, 0.12) !important;  color: #F59E0B !important; }
        [data-theme="dark"] .stat-icon-wrap.teal   { background: rgba(34, 197, 94, 0.12) !important;   color: #22C55E !important; }
        [data-theme="dark"] .stat-growth  { background: rgba(34, 197, 94, 0.12) !important; color: #22C55E !important; }
        [data-theme="dark"] .stat-link    { color: #9B85FF !important; }
        [data-theme="dark"] .badge-count  { background: rgba(124, 92, 252, 0.15) !important; color: #9B85FF !important; }

        /* METRIC ICON BOXES */
        [data-theme="dark"] .metric-icon-box.blue   { background: rgba(56, 189, 248, 0.12) !important;  color: #38BDF8 !important; }
        [data-theme="dark"] .metric-icon-box.green  { background: rgba(34, 197, 94, 0.12) !important;   color: #22C55E !important; }
        [data-theme="dark"] .metric-icon-box.purple { background: rgba(124, 92, 252, 0.15) !important;  color: #9B85FF !important; }
        [data-theme="dark"] .metric-icon-box.orange { background: rgba(245, 158, 11, 0.12) !important;  color: #F59E0B !important; }
        [data-theme="dark"] .metric-icon-box.red    { background: rgba(239, 68, 68, 0.12) !important;   color: #EF4444 !important; }
        [data-theme="dark"] .sub-badge.green  { background: rgba(34, 197, 94, 0.12) !important;  color: #22C55E !important; }
        [data-theme="dark"] .sub-badge.blue   { background: rgba(56, 189, 248, 0.12) !important; color: #38BDF8 !important; }
        [data-theme="dark"] .sub-badge.orange { background: rgba(245, 158, 11, 0.12) !important; color: #F59E0B !important; }
        [data-theme="dark"] .sub-badge.red    { background: rgba(239, 68, 68, 0.12) !important;  color: #EF4444 !important; }

        /* ROUTE MAP */
        [data-theme="dark"] .route-map-preview {
            background: linear-gradient(135deg, #0D1220 0%, #111827 50%, #0A0E18 100%) !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .map-distance-badge {
            background: #0A0E18 !important;
            color: #F8FAFC !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        [data-theme="dark"] .route-line-svg path { stroke: #7C5CFC !important; }

        /* CUSTOMER & ORDER DETAILS */
        [data-theme="dark"] .customer-info-box {
            background: #0D1220 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .info-col-label { color: #9B85FF !important; }
        [data-theme="dark"] .info-col-val   { color: #F8FAFC !important; }
        [data-theme="dark"] .info-col-sub   { color: #94A3B8 !important; }

        /* ASSIGNED RIDER CARD */
        [data-theme="dark"] .assigned-rider-box {
            background: #0D1220 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .rider-name-status h4 span { color: #F8FAFC !important; }
        [data-theme="dark"] .rider-phone-sub { color: #94A3B8 !important; }
        [data-theme="dark"] .rider-avatar {
            background: #151D2D !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        [data-theme="dark"] .btn-call-rider {
            background: #151D2D !important;
            border-color: rgba(34, 197, 94, 0.3) !important;
            color: #22C55E !important;
        }

        /* ORDER ITEMS & TOTALS */
        [data-theme="dark"] .order-item-qty-name  { color: #CBD5E1 !important; }
        [data-theme="dark"] .order-item-qty-badge { color: #9B85FF !important; }
        [data-theme="dark"] .order-item-price     { color: #F8FAFC !important; }
        [data-theme="dark"] .order-bill-divider   { background: rgba(255, 255, 255, 0.07) !important; }
        [data-theme="dark"] .order-total-row      { color: #F8FAFC !important; }
        [data-theme="dark"] .order-total-row span:last-child { color: #9B85FF !important; }

        /* LIVE ORDERS LIST */
        [data-theme="dark"] .live-order-item {
            background: #111827 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
            transition: all 0.15s ease !important;
        }
        [data-theme="dark"] .live-order-item:hover {
            background: #151D2D !important;
            border-color: rgba(124, 92, 252, 0.3) !important;
        }
        [data-theme="dark"] .live-order-item.active {
            background: #151D2D !important;
            border-color: rgba(124, 92, 252, 0.5) !important;
            box-shadow: 0 0 0 1px rgba(124, 92, 252, 0.35), inset 0 0 20px rgba(124, 92, 252, 0.06) !important;
        }
        [data-theme="dark"] .order-code-text     { color: #F8FAFC !important; }
        [data-theme="dark"] .order-time-text,
        [data-theme="dark"] .order-customer-text { color: #94A3B8 !important; }
        [data-theme="dark"] .order-price-bold    { color: #9B85FF !important; }
        [data-theme="dark"] .wa-avatar-box {
            background: rgba(34, 197, 94, 0.12) !important;
            color: #22C55E !important;
        }

        /* RIDERS COLUMN */
        [data-theme="dark"] .rider-item-card {
            background: #0D1220 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .rider-pic { background: #151D2D !important; }
        [data-theme="dark"] .rider-meta-left > div > div:first-child { color: #F8FAFC !important; }
        [data-theme="dark"] .rider-meta-left > div > div:last-child  { color: #94A3B8 !important; }
        [data-theme="dark"] .rider-tag.delivery { background: rgba(34, 197, 94, 0.12) !important;  color: #22C55E !important; }
        [data-theme="dark"] .rider-tag.offline  { background: rgba(100, 116, 139, 0.12) !important; color: #94A3B8 !important; }

        /* STATUS PILLS */
        [data-theme="dark"] .status-pill.pending          { background: rgba(245, 158, 11, 0.15) !important; color: #F59E0B !important; }
        [data-theme="dark"] .status-pill.confirmed        { background: rgba(34, 197, 94, 0.12) !important;  color: #22C55E !important; }
        [data-theme="dark"] .status-pill.preparing        { background: rgba(124, 92, 252, 0.15) !important; color: #9B85FF !important; }
        [data-theme="dark"] .status-pill.out_for_delivery { background: rgba(56, 189, 248, 0.15) !important; color: #38BDF8 !important; }
        [data-theme="dark"] .status-pill.delivered        { background: rgba(100, 116, 139, 0.12) !important; color: #94A3B8 !important; }
        [data-theme="dark"] .status-pill.cancelled        { background: rgba(239, 68, 68, 0.12) !important;  color: #EF4444 !important; }

        /* ORDER STATUS BADGES */
        [data-theme="dark"] .badge-status.pending          { background: rgba(245, 158, 11, 0.15) !important; color: #F59E0B !important; }
        [data-theme="dark"] .badge-status.confirmed        { background: rgba(56, 189, 248, 0.15) !important;  color: #38BDF8 !important; }
        [data-theme="dark"] .badge-status.preparing        { background: rgba(124, 92, 252, 0.15) !important; color: #9B85FF !important; }
        [data-theme="dark"] .badge-status.out_for_delivery { background: rgba(56, 189, 248, 0.15) !important;  color: #38BDF8 !important; }
        [data-theme="dark"] .badge-status.delivered        { background: rgba(34, 197, 94, 0.12) !important;   color: #22C55E !important; }
        [data-theme="dark"] .badge-status.cancelled        { background: rgba(239, 68, 68, 0.12) !important;   color: #EF4444 !important; }

        /* FORMS, INPUTS, SELECTS */
        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] .form-input,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #0A0E18 !important;
            color: #F8FAFC !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus,
        [data-theme="dark"] textarea:focus {
            border-color: rgba(124, 92, 252, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.12) !important;
        }
        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder,
        [data-theme="dark"] .form-input::placeholder {
            color: #64748B !important;
        }
        [data-theme="dark"] .form-label,
        [data-theme="dark"] label {
            color: #CBD5E1 !important;
        }

        /* TABLES */
        [data-theme="dark"] .data-table,
        [data-theme="dark"] .custom-table {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .data-table thead th,
        [data-theme="dark"] .custom-table thead th,
        [data-theme="dark"] table thead th,
        [data-theme="dark"] table th {
            background-color: #0D1220 !important;
            color: #64748B !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .data-table tbody td,
        [data-theme="dark"] .custom-table tbody td,
        [data-theme="dark"] table tbody td,
        [data-theme="dark"] table td {
            border-color: rgba(255, 255, 255, 0.05) !important;
            color: #CBD5E1 !important;
        }
        [data-theme="dark"] .data-table tbody td strong,
        [data-theme="dark"] .custom-table tbody td strong,
        [data-theme="dark"] table tbody td strong {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .data-table tbody tr:hover td,
        [data-theme="dark"] .custom-table tbody tr:hover td,
        [data-theme="dark"] table tbody tr:hover td,
        [data-theme="dark"] table tr:hover {
            background-color: #192338 !important;
        }

        /* CODE BLOCKS */
        [data-theme="dark"] code {
            background: #0A0E18 !important;
            color: #9B85FF !important;
            padding: 2px 6px;
            border-radius: 6px;
            border: 1px solid rgba(124, 92, 252, 0.2) !important;
        }

        /* BUTTONS */
        [data-theme="dark"] .btn-primary {
            background: #7C5CFC !important;
            border-color: #7C5CFC !important;
            color: #fff !important;
            box-shadow: 0 2px 12px rgba(124, 92, 252, 0.3) !important;
        }
        [data-theme="dark"] .btn-primary:hover {
            background: #6d4ef0 !important;
            box-shadow: 0 4px 20px rgba(124, 92, 252, 0.45) !important;
        }
        [data-theme="dark"] .btn-sub-action,
        [data-theme="dark"] .btn-action-secondary,
        [data-theme="dark"] .btn-secondary,
        [data-theme="dark"] .btn-light,
        [data-theme="dark"] .btn-outline {
            background: #151D2D !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .btn-sub-action:hover,
        [data-theme="dark"] .btn-action-secondary:hover,
        [data-theme="dark"] .btn-secondary:hover {
            background: #192338 !important;
            border-color: rgba(124, 92, 252, 0.35) !important;
        }

        /* MODALS & DIALOGS */
        [data-theme="dark"] #dispatchModal > div,
        [data-theme="dark"] .modal-box {
            background: #111827 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #F8FAFC !important;
            box-shadow: 0 24px 64px rgba(0,0,0,0.65) !important;
        }
        [data-theme="dark"] div[id^="modal-"] > div {
            background: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #F8FAFC !important;
            box-shadow: 0 24px 64px rgba(0,0,0,0.65) !important;
        }
        [data-theme="dark"] .modal-close       { color: #94A3B8 !important; }
        [data-theme="dark"] .modal-close:hover { color: #F8FAFC !important; }

        /* CATEGORY PILLS */
        [data-theme="dark"] .cat-pill {
            background: #151D2D !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #94A3B8 !important;
        }
        [data-theme="dark"] .cat-pill.active-pill,
        [data-theme="dark"] .cat-pill.active {
            background: #7C5CFC !important;
            color: #ffffff !important;
            border-color: #7C5CFC !important;
            box-shadow: 0 2px 12px rgba(124, 92, 252, 0.3) !important;
        }
        [data-theme="dark"] .item-disabled { background: #0D1220 !important; }
        [data-theme="dark"] .toggle-off    { background: #151D2D !important; color: #64748B !important; }

        /* DONUT CHART */
        [data-theme="dark"] .donut-circle-wrap svg path:first-child { stroke: rgba(255,255,255,0.06) !important; }
        [data-theme="dark"] .donut-center-num { color: #F8FAFC !important; }
        [data-theme="dark"] .legend-name      { color: #CBD5E1 !important; }

        /* QR BOX & GUIDE STEPS */
        [data-theme="dark"] .qr-box {
            background: #0D1220 !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        [data-theme="dark"] .step-num {
            background: #151D2D !important;
            color: #F8FAFC !important;
        }
        [data-theme="dark"] div[style*="background: #ecfdf5"],
        [data-theme="dark"] div[style*="background:#ecfdf5"],
        [data-theme="dark"] div[style*="background: #f0fdf4"],
        [data-theme="dark"] div[style*="background:#f0fdf4"] {
            background: rgba(34, 197, 94, 0.1) !important;
            border-color: rgba(34, 197, 94, 0.25) !important;
            color: #22C55E !important;
        }

        /* SETTINGS INFO ROWS */
        [data-theme="dark"] .info-row,
        [data-theme="dark"] .detail-row   { border-color: rgba(255, 255, 255, 0.07) !important; }
        [data-theme="dark"] .info-label   { color: #94A3B8 !important; }
        [data-theme="dark"] .info-value   { color: #F8FAFC !important; }

        /* FLASH ALERTS */
        [data-theme="dark"] .flash-success-banner {
            background: rgba(34, 197, 94, 0.1) !important;
            border-color: rgba(34, 197, 94, 0.3) !important;
            color: #22C55E !important;
        }
        [data-theme="dark"] .bot-alert-banner {
            background: rgba(245, 158, 11, 0.1) !important;
            border-color: rgba(245, 158, 11, 0.25) !important;
            color: #F59E0B !important;
        }
        [data-theme="dark"] .bot-alert-banner a {
            background: #F59E0B !important;
            color: #080B14 !important;
        }

        /* PAGINATION */
        [data-theme="dark"] .pagination a,
        [data-theme="dark"] .pagination span {
            background: #151D2D !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #94A3B8 !important;
        }
        [data-theme="dark"] .pagination .active span {
            background: #7C5CFC !important;
            color: #ffffff !important;
            border-color: #7C5CFC !important;
            box-shadow: 0 2px 12px rgba(124, 92, 252, 0.3) !important;
        }

        /* SIDEBAR */
        aside {
            width: 230px;
            background: var(--sidebar-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }

        .brand-header {
            padding: 20px 18px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-avatar {
            width: 36px;
            height: 36px;
            background: #181818;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .brand-info h2 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .brand-info span {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .nav-section {
            padding: 16px 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 9px;
            transition: all 0.15s ease;
        }

        .nav-item:hover {
            color: var(--text-main);
            background: var(--sidebar-hover);
        }

        .nav-item.active {
            background: #181818;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .nav-item .icon {
            font-size: 15px;
            width: 18px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nav-item .badge-pill {
            margin-left: auto;
            font-size: 11px;
            background: #f97316;
            padding: 2px 7px;
            border-radius: 99px;
            color: #fff;
            font-weight: 700;
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            margin-left: 230px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--main-bg);
            transition: margin-left 0.25s ease;
        }

        /* TOPBAR */
        header {
            height: 68px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 90;
            transition: background 0.2s, border-color 0.2s;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .mobile-menu-toggle {
            display: none;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--main-bg);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 12px;
            color: #64748b;
            margin-top: 1px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-close-btn {
            display: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 14px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 95;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        /* MOBILE BOTTOM NAVIGATION BAR */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid var(--border-color);
            z-index: 85;
            padding: 4px 8px;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] .mobile-bottom-nav {
            background: rgba(10, 14, 24, 0.96);
            border-top-color: rgba(255, 255, 255, 0.07);
            box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.5);
        }

        .mob-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 12px;
            transition: all 0.15s ease;
            position: relative;
            min-width: 54px;
        }

        .mob-nav-item .mob-icon {
            font-size: 18px;
            line-height: 1;
        }

        .mob-nav-item.active {
            color: #4f46e5;
            background: rgba(79, 70, 229, 0.08);
        }

        [data-theme="dark"] .mob-nav-item.active {
            color: #9B85FF;
            background: rgba(124, 92, 252, 0.12);
        }

        .mob-badge {
            position: absolute;
            top: 2px;
            right: 8px;
            background: #ef4444;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 99px;
            min-width: 15px;
            text-align: center;
            border: 1.5px solid #fff;
        }
        [data-theme="dark"] .mob-badge {
            border-color: #080B14;
        }

        .status-online-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
            color: #16a34a;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 12px;
            border-left: 1px solid var(--border-color);
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .logout-link {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            border-radius: 6px;
        }
        .logout-link:hover { color: #dc2626; }

        /* CONTENT */
        main {
            padding: 24px 32px 60px;
            flex: 1;
        }

        /* ── GLOBAL UI SYSTEM COMPONENTS ── */
        .panel-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .panel-title h3 {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .panel-title p {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        /* DATA TABLES */
        .data-table, .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th, .custom-table th {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 14px;
            border-bottom: 1.5px solid #e2e8f0;
            background: #f8fafc;
        }

        .data-table td, .custom-table td {
            font-size: 13px;
            padding: 13px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .data-table tr:hover td, .custom-table tr:hover td {
            background: #fafafa;
        }

        /* METRIC CARDS */
        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .metric-title {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .metric-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #64748b;
        }

        .metric-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .metric-icon-box.green  { background: #f0fdf4; color: #16a34a; }
        .metric-icon-box.blue   { background: #eff6ff; color: #2563eb; }
        .metric-icon-box.orange { background: #fff7ed; color: #ea580c; }
        .metric-icon-box.red    { background: #fef2f2; color: #dc2626; }
        .metric-icon-box.purple { background: #faf5ff; color: #7e22ce; }

        .sub-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 99px;
        }
        .sub-badge.green  { background: #dcfce7; color: #166534; }
        .sub-badge.blue   { background: #dbeafe; color: #1e40af; }
        .sub-badge.orange { background: #ffedd5; color: #9a3412; }
        .sub-badge.red    { background: #fee2e2; color: #991b1b; }

        /* BUTTONS & PILLS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary { background: #4f46e5; color: #fff; border-color: #4f46e5; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25); }
        .btn-primary:hover { background: #4338ca; }
        .btn-success { background: #16a34a; color: #fff; border-color: #16a34a; }
        .btn-success:hover { background: #15803d; }
        .btn-secondary { background: #f8fafc; color: #334155; border-color: var(--border-color); }
        .btn-secondary:hover { background: #f1f5f9; }

        .badge-status {
            display: inline-flex;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .badge-status.pending   { background: #fef3c7; color: #b45309; }
        .badge-status.confirmed { background: #e0e7ff; color: #4338ca; }
        .badge-status.preparing { background: #fef9c3; color: #854d0e; }
        .badge-status.out_for_delivery { background: #dbeafe; color: #1e40af; }
        .badge-status.delivered { background: #dcfce7; color: #15803d; }
        .badge-status.cancelled { background: #fee2e2; color: #b91c1c; }

        /* SWITCH TOGGLE */
        .switch { position: relative; display: inline-block; width: 34px; height: 18px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; border-radius: 999px; transition: 0.2s; }
        .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.2s; }
        input:checked + .slider { background: #16a34a; }
        input:checked + .slider:before { transform: translateX(16px); }

        /* ── RESPONSIVE MOBILE & TABLET BREAKPOINTS ── */
        @media (max-width: 1024px) {
            aside {
                width: 280px;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
                z-index: 1000;
            }
            aside.mobile-open {
                transform: translateX(0);
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            }
            .sidebar-backdrop.active {
                display: block;
                opacity: 1;
            }
            .sidebar-close-btn {
                display: flex;
            }
            .mobile-menu-toggle {
                display: inline-flex;
            }
            .main-wrapper {
                margin-left: 0 !important;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            header {
                height: 60px;
                padding: 0 16px;
            }
            .header-title h1 {
                font-size: 15px;
            }
            .header-title p {
                display: none;
            }
            main {
                padding: 16px 14px 85px !important;
            }
            .mobile-bottom-nav {
                display: flex;
            }
            .user-profile {
                padding-left: 6px;
                border-left: none;
            }
            .user-info {
                display: none;
            }
            .date-pill {
                display: none !important;
            }
            .status-online-pill span:last-child {
                display: none;
            }
            .status-online-pill {
                padding: 6px;
            }
            .theme-toggle-btn #themeText {
                display: none;
            }
            .theme-toggle-btn {
                padding: 6px 9px;
            }
            .panel-card, .card, .dashboard-card {
                padding: 16px 14px;
                border-radius: 16px;
                margin-bottom: 16px;
            }
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .data-table th, .data-table td {
                padding: 10px 10px;
                font-size: 12px;
            }
            .metric-grid, .stats-row {
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            header {
                padding: 0 12px;
            }
            .header-actions {
                gap: 8px;
            }
            .avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            .stat-val, .metric-value {
                font-size: 20px;
            }
            .btn {
                padding: 7px 12px;
                font-size: 11.5px;
            }
        }
    </style>
</head>
<body>

@php
    $currentRest = $restaurant ?? ($r ?? null);
    $restId = $currentRest?->id ?? 1;
@endphp

<!-- SIDEBAR BACKDROP FOR MOBILE -->
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleOwnerSidebar()"></div>

<!-- SIDEBAR -->
<aside id="ownerSidebar">
    <div style="padding: 18px 16px 14px; border-bottom: 1px solid var(--border-color);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 9px; background: #181818; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff;">
                    ⚡
                </div>
                <div>
                    <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em; line-height: 1.1;">Foodio</h2>
                    <p style="font-size: 11px; color: var(--text-muted); font-weight: 500;">Restaurant POS</p>
                </div>
            </div>
            <button type="button" class="sidebar-close-btn" onclick="toggleOwnerSidebar()" title="Close menu">✕</button>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; background: var(--sidebar-hover); border: 1px solid var(--border-color); padding: 8px 10px; border-radius: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 26px; height: 26px; border-radius: 7px; background: #181818; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;">
                    {{ strtoupper(substr($currentRest->name ?? 'FD', 0, 2)) }}
                </div>
                <div style="overflow: hidden;">
                    <h4 style="font-size: 12px; font-weight: 700; color: var(--text-main); line-height: 1.2; text-overflow: ellipsis; white-space: nowrap;">{{ Str::limit($currentRest->name ?? 'My Restaurant', 14) }}</h4>
                    <span style="font-size: 10px; color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background: #16a34a;"></span>
                        {{ ($currentRest->is_open ?? true) ? 'Taking Orders' : 'Closed' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="nav-section">
        <a href="{{ route('dashboard.orders', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.orders') ? 'active' : '' }}">
            <span class="icon">⚡</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('dashboard.live-orders', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.live-orders*') ? 'active' : '' }}">
            <span class="icon">🛍️</span>
            <span>Live Orders</span>
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; margin-left: 2px;"></span>
            @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                <span class="badge-pill" style="margin-left: auto;">{{ $liveOrdersCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard.history', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.history*') ? 'active' : '' }}">
            <span class="icon">📋</span>
            <span>Order History</span>
        </a>

        <a href="{{ route('dashboard.menu', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
            <span class="icon">🍔</span>
            <span>Menu & Items</span>
        </a>

        <a href="{{ route('dashboard.riders', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.riders*') ? 'active' : '' }}">
            <span class="icon">🚴</span>
            <span>Riders</span>
        </a>

        <a href="{{ route('dashboard.customers', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.customers*') ? 'active' : '' }}">
            <span class="icon">👥</span>
            <span>Customers</span>
        </a>

        <a href="{{ route('dashboard.reports', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.reports*') ? 'active' : '' }}">
            <span class="icon">📊</span>
            <span>Reports</span>
        </a>

        <a href="{{ route('dashboard.settings', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}">
            <span class="icon">⚙️</span>
            <span>Settings</span>
        </a>

        <a href="{{ route('dashboard.connect-whatsapp', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.connect-whatsapp*') ? 'active' : '' }}">
            <span class="icon">🤖</span>
            <span>WhatsApp Bot</span>
        </a>

        <!-- Bot Status Box -->
        <div style="background: var(--sidebar-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; margin: 12px 2px 6px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                <span style="font-size: 11px; font-weight: 700; color: var(--text-main);">WhatsApp Bot</span>
                <span style="display: flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; color: #16a34a; background: #ecfdf5; padding: 1px 6px; border-radius: 99px;">
                    <span style="width: 4px; height: 4px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                    Online
                </span>
            </div>
            <a href="{{ route('dashboard.connect-whatsapp', $restId) }}" style="display: block; width: 100%; text-align: center; padding: 4px 8px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; font-size: 10px; font-weight: 600; color: var(--text-muted); text-decoration: none; margin-top: 6px;">
                Manage Bot ↗
            </a>
        </div>

        <form method="POST" action="{{ route('dashboard.logout', $restId) }}" onsubmit="sessionStorage.removeItem('owner_authenticated_session')" style="margin-top: 4px;">
            @csrf
            <button type="submit" class="nav-item" style="width: 100%; background: none; border: none; cursor: pointer; text-align: left; color: #dc2626;">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <header>
        <div class="header-left">
            <button type="button" class="mobile-menu-toggle" onclick="toggleOwnerSidebar()" aria-label="Open Menu">☰</button>
            <div class="header-title">
                <h1>@yield('header_title', 'Dashboard')</h1>
                <p>@yield('header_subtitle', 'Welcome back, ' . ($currentRest->name ?? 'Owner') . '! 👋')</p>
            </div>
        </div>

        <div class="header-actions">
            <!-- Dark / Light Mode Toggle Pill (Screenshot Style) -->
            <button type="button" class="theme-toggle-pill" id="themeToggleBtn" onclick="toggleOwnerTheme()" title="Toggle Dark/Light Mode">
                <span class="theme-pill-icon sun" id="themeSunIcon">☀️</span>
                <span class="theme-pill-icon moon active" id="themeMoonIcon">🌙</span>
                <span class="theme-pill-text" id="themeText">Dark</span>
            </button>

            <div class="status-online-pill" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                <span>Online</span>
            </div>

            <div id="notif-bell-wrap" title="Pending orders awaiting action" style="position: relative; width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer;" onclick="window.location.href='{{ route('dashboard.orders', $restId) }}'">
                🔔
                <span id="notif-badge" style="position: absolute; top: -4px; right: -4px; min-width: 17px; height: 17px; border-radius: 99px; background: #ef4444; color: #fff; font-size: 9px; font-weight: 800; display: none; align-items: center; justify-content: center; padding: 0 3px; border: 2px solid #f8fafc;">0</span>
            </div>

            <div class="user-profile">
                <div class="avatar">{{ strtoupper(substr($currentRest->name ?? 'TB', 0, 2)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ $currentRest->name ?? 'Restaurant Owner' }} ▾</div>
                </div>
            </div>

            <div class="date-pill" style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 12px; font-weight: 700; color: #334155;">
                <span>📅</span>
                <span>Today, {{ now()->format('M d') }}</span>
                <span style="font-size: 10px; color: #94a3b8;">▾</span>
            </div>
        </div>
    </header>

    <main>
        @php
            $currentRest = $restaurant ?? ($r ?? null);
        @endphp
        @if($currentRest && ($currentRest->bot_status === 'disconnected' || $currentRest->bot_status === 'qr_pending'))
            <div class="bot-alert-banner" style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">⚠️</span>
                    <span>WhatsApp Bot is <strong>{{ $currentRest->bot_status === 'qr_pending' ? 'Awaiting QR Scan' : 'Offline / Disconnected' }}</strong>. Your customers cannot place orders until connected!</span>
                </div>
                <a href="{{ route('dashboard.connect-whatsapp', $currentRest->id) }}" style="padding: 6px 14px; background: #d97706; color: #fff; border-radius: 8px; font-size: 12px; font-weight: 800; text-decoration: none;">
                    Scan QR & Connect →
                </a>
            </div>
        @endif

        @if(session('success'))
            <div class="flash-success-banner" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="mobile-bottom-nav">
        <a href="{{ route('dashboard.orders', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.orders') && !request('view') ? 'active' : '' }}">
            <span class="mob-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('dashboard.orders', $restId) }}?view=live" class="mob-nav-item {{ request('view') === 'live' ? 'active' : '' }}">
            <span class="mob-icon">🛍️</span>
            <span>Live</span>
            @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                <span class="mob-badge">{{ $liveOrdersCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard.history', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.history*') ? 'active' : '' }}">
            <span class="mob-icon">📋</span>
            <span>Orders</span>
        </a>

        <a href="{{ route('dashboard.menu', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
            <span class="mob-icon">🍽️</span>
            <span>Menu</span>
        </a>

        <button type="button" class="mob-nav-item" onclick="toggleOwnerSidebar()" style="background: none; border: none; cursor: pointer;">
            <span class="mob-icon">⚙️</span>
            <span>More</span>
        </button>
    </nav>
</div>
<!-- Live Notification Bell Poller -->
@if(isset($restId))
<script>
(function() {
    const badge  = document.getElementById('notif-badge');
    const bell   = document.getElementById('notif-bell-wrap');
    if (!badge || !bell) return;

    let lastCount = null;

    function updateBell(pending) {
        if (pending > 0) {
            badge.textContent = pending > 99 ? '99+' : pending;
            badge.style.display = 'flex';
            // Pulse animation on new orders
            if (lastCount !== null && pending > lastCount) {
                bell.style.animation = 'bellShake 0.5s ease';
                setTimeout(() => bell.style.animation = '', 600);
            }
        } else {
            badge.style.display = 'none';
        }
        lastCount = pending;
    }

    // CSS for bell shake animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes bellShake {
            0%,100% { transform: rotate(0); }
            20%      { transform: rotate(-18deg); }
            40%      { transform: rotate(18deg); }
            60%      { transform: rotate(-12deg); }
            80%      { transform: rotate(8deg); }
        }
    `;
    document.head.appendChild(style);

    async function pollPendingOrders() {
        try {
            const res  = await fetch('/dashboard/{{ $restId }}/orders/live-feed', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && typeof data.pending_count !== 'undefined') {
                updateBell(data.pending_count);
            }
        } catch (e) { /* offline / retry next tick */ }
    }

    // Initial poll immediately, then every 8 seconds
    pollPendingOrders();
    setInterval(pollPendingOrders, 8000);
})();
</script>
@endif

<script>
// ── Owner Dashboard Dark Mode Toggle & Persistence ──────────────────
function initOwnerTheme() {
    const savedTheme = localStorage.getItem('owner_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateOwnerThemeButton(savedTheme);
}

function toggleOwnerTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('owner_theme', newTheme);
    updateOwnerThemeButton(newTheme);
}

function updateOwnerThemeButton(theme) {
    const sun = document.getElementById('themeSunIcon');
    const moon = document.getElementById('themeMoonIcon');
    const text = document.getElementById('themeText');
    if (theme === 'dark') {
        if (sun) sun.classList.remove('active');
        if (moon) moon.classList.add('active');
        if (text) text.textContent = 'Dark';
    } else {
        if (sun) sun.classList.add('active');
        if (moon) moon.classList.remove('active');
        if (text) text.textContent = 'Light';
    }
}

// ── Mobile Sidebar Drawer Toggle ────────────────────────────────────
function toggleOwnerSidebar() {
    const sb = document.getElementById('ownerSidebar');
    const bd = document.getElementById('sidebarBackdrop');
    if (sb) sb.classList.toggle('mobile-open');
    if (bd) bd.classList.toggle('active');
}

// Auto-close mobile sidebar when clicking a nav item on mobile
document.addEventListener('DOMContentLoaded', function() {
    initOwnerTheme();
    const navItems = document.querySelectorAll('#ownerSidebar .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                toggleOwnerSidebar();
            }
        });
    });
});
</script>

</body>
</html>