<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ $restaurant->name ?? ($r->name ?? 'Restaurant Owner') }}</title>

    <!-- Immediate theme initializer (no flicker) -->
    <script>
        (function() {
            const t = localStorage.getItem('owner_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --sidebar-active: #4f46e5;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: #ffffff;
            --header-bg: #ffffff;
        }

        [data-theme="dark"] {
            --sidebar-bg: #0b0f19;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --sidebar-active: #6366f1;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --main-bg: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --header-bg: #1e293b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--main-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            font-size: 13px;
            line-height: 1.5;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* ── Dark Mode Element Overrides ────────────────── */
        [data-theme="dark"] header {
            background: var(--header-bg) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }
        [data-theme="dark"] .header-title h1,
        [data-theme="dark"] .user-name {
            color: #f8fafc !important;
        }
        [data-theme="dark"] .header-title p {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .date-pill {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
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
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }
        [data-theme="dark"] .stat-val,
        [data-theme="dark"] .panel-title,
        [data-theme="dark"] .order-detail-title h3,
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, [data-theme="dark"] h4,
        [data-theme="dark"] strong {
            color: #f8fafc !important;
        }
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .stat-footer,
        [data-theme="dark"] .info-col-label,
        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .stat-sub {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .panel-header {
            border-bottom-color: #334155 !important;
        }
        [data-theme="dark"] .stat-icon-wrap.purple { background: rgba(124, 58, 237, 0.2) !important; color: #a78bfa !important; }
        [data-theme="dark"] .stat-icon-wrap.green  { background: rgba(22, 163, 74, 0.2) !important; color: #4ade80 !important; }
        [data-theme="dark"] .stat-icon-wrap.blue   { background: rgba(2, 132, 199, 0.2) !important; color: #38bdf8 !important; }
        [data-theme="dark"] .stat-icon-wrap.orange { background: rgba(234, 88, 12, 0.2) !important; color: #fb923c !important; }
        [data-theme="dark"] .stat-icon-wrap.teal   { background: rgba(13, 148, 136, 0.2) !important; color: #2dd4bf !important; }
        [data-theme="dark"] .stat-growth { background: rgba(22, 163, 74, 0.15) !important; color: #4ade80 !important; }
        [data-theme="dark"] .stat-link { color: #818cf8 !important; }
        [data-theme="dark"] .badge-count { background: rgba(124, 58, 237, 0.2) !important; color: #a78bfa !important; }

        /* Route map graphic */
        [data-theme="dark"] .route-map-preview {
            background: linear-gradient(135deg, #131d2e 0%, #1a2436 50%, #151d2c 100%) !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .map-distance-badge {
            background: #0f172a !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .route-line-svg path {
            stroke: #6366f1 !important;
        }

        /* Customer & Order details */
        [data-theme="dark"] .customer-info-box {
            background: #131d2e !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .info-col-label { color: #818cf8 !important; }
        [data-theme="dark"] .info-col-val { color: #f8fafc !important; }
        [data-theme="dark"] .info-col-sub { color: #94a3b8 !important; }

        /* Assigned Rider Card */
        [data-theme="dark"] .assigned-rider-box {
            background: #131d2e !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .rider-name-status h4 span { color: #f8fafc !important; }
        [data-theme="dark"] .rider-phone-sub { color: #94a3b8 !important; }
        [data-theme="dark"] .rider-avatar {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
        }
        [data-theme="dark"] .btn-call-rider {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #10b981 !important;
        }

        /* Order items & totals */
        [data-theme="dark"] .order-item-qty-name { color: #cbd5e1 !important; }
        [data-theme="dark"] .order-item-qty-badge { color: #818cf8 !important; }
        [data-theme="dark"] .order-item-price { color: #f8fafc !important; }
        [data-theme="dark"] .order-bill-divider { background: #334155 !important; }
        [data-theme="dark"] .order-total-row { color: #f8fafc !important; }
        [data-theme="dark"] .order-total-row span:last-child { color: #818cf8 !important; }

        /* Live Orders List */
        [data-theme="dark"] .live-order-item {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .live-order-item:hover {
            background: #26354a !important;
            border-color: #6366f1 !important;
        }
        [data-theme="dark"] .live-order-item.active {
            background: #1a2536 !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 1px #6366f1, inset 0 0 0 1px #6366f1 !important;
        }
        [data-theme="dark"] .order-code-text { color: #f8fafc !important; }
        [data-theme="dark"] .order-time-text,
        [data-theme="dark"] .order-customer-text { color: #94a3b8 !important; }
        [data-theme="dark"] .order-price-bold { color: #818cf8 !important; }
        [data-theme="dark"] .wa-avatar-box {
            background: rgba(22, 163, 74, 0.2) !important;
            color: #4ade80 !important;
        }

        /* Riders Column */
        [data-theme="dark"] .rider-item-card {
            background: #131d2e !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .rider-pic { background: #1e293b !important; }
        [data-theme="dark"] .rider-meta-left > div > div:first-child { color: #f8fafc !important; }
        [data-theme="dark"] .rider-meta-left > div > div:last-child { color: #94a3b8 !important; }
        [data-theme="dark"] .rider-tag.delivery { background: rgba(22, 163, 74, 0.2) !important; color: #4ade80 !important; }
        [data-theme="dark"] .rider-tag.offline { background: rgba(100, 116, 139, 0.2) !important; color: #94a3b8 !important; }

        /* Status Pills */
        [data-theme="dark"] .status-pill.pending   { background: rgba(245, 158, 11, 0.2) !important; color: #fbbf24 !important; }
        [data-theme="dark"] .status-pill.confirmed { background: rgba(22, 163, 74, 0.2) !important; color: #4ade80 !important; }
        [data-theme="dark"] .status-pill.preparing { background: rgba(124, 58, 237, 0.2) !important; color: #a78bfa !important; }
        [data-theme="dark"] .status-pill.out_for_delivery { background: rgba(2, 132, 199, 0.2) !important; color: #38bdf8 !important; }
        [data-theme="dark"] .status-pill.delivered { background: rgba(100, 116, 139, 0.2) !important; color: #cbd5e1 !important; }
        [data-theme="dark"] .status-pill.cancelled { background: rgba(239, 68, 68, 0.2) !important; color: #f87171 !important; }

        /* Forms, inputs, tables, modals */
        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] .form-input,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder,
        [data-theme="dark"] .form-input::placeholder {
            color: #64748b !important;
        }
        [data-theme="dark"] .form-label,
        [data-theme="dark"] label {
            color: #cbd5e1 !important;
        }
        [data-theme="dark"] table th {
            background-color: #182234 !important;
            color: #94a3b8 !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] table td {
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] table tr:hover {
            background-color: #26334d !important;
        }
        [data-theme="dark"] .btn-sub-action,
        [data-theme="dark"] .btn-action-secondary,
        [data-theme="dark"] .btn-secondary,
        [data-theme="dark"] .btn-light,
        [data-theme="dark"] .btn-outline {
            background: #182234 !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .btn-sub-action:hover,
        [data-theme="dark"] .btn-action-secondary:hover {
            background: #26354a !important;
            border-color: #6366f1 !important;
        }
        [data-theme="dark"] #notif-bell-wrap,
        [data-theme="dark"] .theme-toggle-btn {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .theme-toggle-btn:hover {
            background: #2d3d54 !important;
        }
        [data-theme="dark"] #dispatchModal > div,
        [data-theme="dark"] .modal-box,
        [data-theme="dark"] .modal-content {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .modal-close {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .modal-close:hover {
            color: #f8fafc !important;
        }

        /* Other Pages (Menu, Settings, Customers, Reports) */
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
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        [data-theme="dark"] .cat-pill {
            background: #182234 !important;
            border-color: #334155 !important;
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .cat-pill.active-pill,
        [data-theme="dark"] .cat-pill.active {
            background: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #4f46e5 !important;
        }
        [data-theme="dark"] .item-disabled {
            background: #131d2e !important;
        }
        [data-theme="dark"] .toggle-off {
            background: #334155 !important;
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .donut-circle-wrap svg path:first-child {
            stroke: #334155 !important;
        }
        [data-theme="dark"] .donut-center-num {
            color: #f8fafc !important;
        }
        [data-theme="dark"] .donut-legend-item,
        [data-theme="dark"] .settings-desc,
        [data-theme="dark"] .setting-desc {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .legend-name {
            color: #cbd5e1 !important;
        }

        /* SIDEBAR */
        aside {
            width: 250px;
            background: var(--sidebar-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            border-right: 1px solid #1e293b;
            overflow-y: auto;
        }

        .brand-header {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-avatar {
            width: 38px;
            height: 38px;
            background: #4f46e5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .brand-info h2 {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .brand-info span {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
        }

        .nav-section {
            padding: 16px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.15s ease;
        }

        .nav-item:hover {
            color: #fff;
            background: var(--sidebar-hover);
        }

        .nav-item.active {
            background: var(--sidebar-active);
            color: var(--sidebar-text-active);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .nav-item .icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .nav-item .badge-pill {
            margin-left: auto;
            font-size: 11px;
            background: #ef4444;
            padding: 2px 7px;
            border-radius: 99px;
            color: #fff;
            font-weight: 700;
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            gap: 16px;
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

        @media (max-width: 1024px) {
            aside { width: 70px; }
            aside .brand-info, aside .nav-item span, aside .badge-pill { display: none; }
            .main-wrapper { margin-left: 70px; }
        }
    </style>
</head>
<body>

@php
    $currentRest = $restaurant ?? ($r ?? null);
    $restId = $currentRest?->id ?? 1;
@endphp

<!-- SIDEBAR -->
<aside>
    <div style="padding: 22px 20px 14px; border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🤖
            </div>
            <div>
                <h2 style="font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -0.3px;">RestoBot</h2>
                <p style="font-size: 11px; color: #94a3b8;">WhatsApp Ordering System</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(51, 65, 85, 0.6); padding: 8px 12px; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 28px; height: 28px; border-radius: 8px; background: #6366f1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800;">
                    {{ strtoupper(substr($currentRest->name ?? 'RB', 0, 2)) }}
                </div>
                <div>
                    <h4 style="font-size: 12px; font-weight: 700; color: #f1f5f9; line-height: 1.1;">{{ Str::limit($currentRest->name ?? 'My Restaurant', 13) }}</h4>
                    <span style="font-size: 10px; color: #64748b;">Restaurant Owner</span>
                </div>
            </div>
            <span style="color: #64748b; font-size: 10px;">▾</span>
        </div>
    </div>

    <div class="nav-section">
        <a href="{{ route('dashboard.orders', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.orders') && !request('view') ? 'active' : '' }}">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('dashboard.orders', $restId) }}" class="nav-item {{ request('view') === 'live' ? 'active' : '' }}">
            <span class="icon">🛍️</span>
            <span>Live Orders</span>
            @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                <span class="badge-pill" style="background: #6366f1;">{{ $liveOrdersCount }}</span>
            @endif
        </a>

        <a href="{{ route('dashboard.history', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.history*') ? 'active' : '' }}">
            <span class="icon">📋</span>
            <span>Orders History</span>
        </a>

        <a href="{{ route('dashboard.menu', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
            <span class="icon">🍽️</span>
            <span>Menu Management</span>
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
            <span class="icon">📈</span>
            <span>Reports</span>
        </a>

        <a href="{{ route('dashboard.history', $restId) }}" class="nav-item">
            <span class="icon">💬</span>
            <span>WhatsApp Logs</span>
        </a>

        <a href="{{ route('dashboard.settings', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}">
            <span class="icon">⚙️</span>
            <span>Settings</span>
        </a>

        <a href="{{ route('dashboard.connect-whatsapp', $restId) }}" class="nav-item {{ request()->routeIs('dashboard.connect-whatsapp*') ? 'active' : '' }}">
            <span class="icon">🤖</span>
            <span>Bot Settings</span>
        </a>

        <!-- Bot Status Box -->
        <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(51, 65, 85, 0.7); border-radius: 14px; padding: 14px; margin: 16px 4px 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 700; color: #cbd5e1;">Bot Status</span>
                <span style="display: flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; color: #10b981; background: rgba(16,185,129,0.15); padding: 2px 6px; border-radius: 99px;">
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    Online
                </span>
            </div>
            <p style="font-size: 10px; color: #64748b; margin-bottom: 10px;">Everything is working fine</p>
            <a href="{{ route('dashboard.connect-whatsapp', $restId) }}" style="display: block; width: 100%; text-align: center; padding: 6px 10px; background: rgba(51,65,85,0.5); border: 1px solid rgba(71,85,105,0.5); border-radius: 8px; font-size: 10px; font-weight: 700; color: #cbd5e1; text-decoration: none;">
                View Bot Activity ↗
            </a>
        </div>

        <form method="POST" action="{{ route('dashboard.logout', $restId) }}" style="margin-top: 4px;">
            @csrf
            <button type="submit" class="nav-item" style="width: 100%; background: none; border: none; cursor: pointer; text-align: left; color: #ef4444;">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <header>
        <div class="header-title">
            <h1>@yield('header_title', 'Dashboard')</h1>
            <p>@yield('header_subtitle', 'Welcome back, ' . ($currentRest->name ?? 'Owner') . '! 👋')</p>
        </div>

        <div class="header-actions">
            <!-- Dark / Light Mode Toggle -->
            <button type="button" class="theme-toggle-btn" onclick="toggleOwnerTheme()" title="Toggle Dark/Light Theme" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 7px 12px; border-radius: 10px; color: var(--text-main); cursor: pointer; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                <span id="themeIcon">🌙</span>
                <span id="themeText" style="font-size: 11.5px;">Dark</span>
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
        @if(session('success'))
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
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
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');
    if (theme === 'dark') {
        if (icon) icon.textContent = '☀️';
        if (text) text.textContent = 'Light';
    } else {
        if (icon) icon.textContent = '🌙';
        if (text) text.textContent = 'Dark';
    }
}

document.addEventListener('DOMContentLoaded', initOwnerTheme);
</script>

</body>
</html>