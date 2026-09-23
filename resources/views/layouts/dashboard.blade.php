<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Restaurant Dashboard') — {{ $restaurant->name ?? ($r->name ?? 'Foodio') }}</title>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Immediate theme initializer & Client-side Auth Guard -->
    <script>
        (function() {
            const t = localStorage.getItem('owner_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);

            @if(session('admin_logged_in') !== true)
            sessionStorage.setItem('owner_authenticated_session', 'active');
            window.addEventListener('pageshow', function (event) {
                if (sessionStorage.getItem('owner_authenticated_session') !== 'active') {
                    document.documentElement.style.display = 'none';
                    window.location.replace('{{ route("landing.owner-login-page") }}');
                }
            });
            @endif
        })();
    </script>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-canvas: #f8fafc;
            --bg-card: #ffffff;
            --border-subtle: #f1f5f9;
            --border-card: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --sidebar-bg: #ffffff;
            --sidebar-border: #f1f5f9;
            --header-bg: #f8fafc;
            --brand-primary: #4f46e5;
            --brand-primary-light: #eef2ff;
            --brand-accent: #0f172a;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #059669;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            --warning-text: #b45309;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #dc2626;
            --radius-card: 16px;
            --radius-badge: 9999px;
            --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            --shadow-elevated: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        [data-theme="dark"] {
            --bg-canvas: #0b0f19;
            --bg-card: #131b2e;
            --border-subtle: rgba(255, 255, 255, 0.06);
            --border-card: rgba(255, 255, 255, 0.08);
            --text-heading: #f8fafc;
            --text-body: #cbd5e1;
            --text-muted: #94a3b8;
            --text-light: #64748b;
            --sidebar-bg: #0e1424;
            --sidebar-border: rgba(255, 255, 255, 0.06);
            --header-bg: #0b0f19;
            --brand-primary: #6366f1;
            --brand-primary-light: rgba(99, 102, 241, 0.15);
            --brand-accent: #f8fafc;
            --success-bg: rgba(16, 185, 129, 0.12);
            --success-border: rgba(16, 185, 129, 0.25);
            --success-text: #34d399;
            --warning-bg: rgba(245, 158, 11, 0.12);
            --warning-border: rgba(245, 158, 11, 0.25);
            --warning-text: #fbbf24;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --danger-border: rgba(239, 68, 68, 0.25);
            --danger-text: #f87171;
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.25);
            --shadow-elevated: 0 12px 30px rgba(0, 0, 0, 0.4);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body, button, input, select, textarea, table, th, td, h1, h2, h3, h4, h5, h6, .btn, .badge {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-canvas);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            font-size: 13px;
            line-height: 1.5;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* ═══════════════════════════════════════════════════
           SLIM ICON SIDEBAR (CONCEPT UI MATCH)
           ═══════════════════════════════════════════════════ */
        aside#ownerSidebar {
            width: 76px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0 24px;
            transition: transform 0.25s ease, background 0.2s ease;
        }

        .sidebar-brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #18181b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease;
            text-decoration: none;
            margin-bottom: 28px;
        }
        .sidebar-brand-badge:hover {
            transform: scale(1.05);
        }
        [data-theme="dark"] .sidebar-brand-badge {
            background: #27272a;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            flex: 1;
            width: 100%;
        }

        .sidebar-icon-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.18s ease;
            position: relative;
        }
        .sidebar-icon-btn:hover {
            background: var(--border-subtle);
            color: var(--text-heading);
            transform: translateY(-1px);
        }
        .sidebar-icon-btn.active {
            background: var(--brand-primary-light);
            color: var(--brand-primary);
        }
        [data-theme="dark"] .sidebar-icon-btn.active {
            background: rgba(99, 102, 241, 0.18);
            color: #818cf8;
        }

        /* Tooltip on icon hover */
        .sidebar-icon-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 82px;
            background: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity 0.15s ease, transform 0.15s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 200;
        }
        .sidebar-icon-btn:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .sidebar-icon-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            border: 2px solid var(--sidebar-bg);
        }

        .sidebar-footer-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 20px;
            transition: background 0.15s ease;
            text-decoration: none;
        }
        .sidebar-footer-status:hover {
            background: var(--border-subtle);
        }
        .sidebar-online-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .sidebar-online-dot.paused {
            background: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        /* ═══════════════════════════════════════════════════
           MAIN WRAPPER & TOP HEADER
           ═══════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: 76px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            transition: margin-left 0.25s ease;
        }

        header.topbar {
            height: 84px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            position: sticky;
            top: 0;
            z-index: 90;
            transition: background 0.2s;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-greeting-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-greeting-sub {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 2px;
        }

        .header-right-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Status Toggle Pill Button */
        .status-pill-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            position: relative;
        }
        .status-pill-toggle:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .status-pill-toggle.paused {
            background: var(--warning-bg);
            border-color: var(--warning-border);
            color: var(--warning-text);
        }

        /* Date Pill */
        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-body);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        /* Theme Toggle Button */
        .theme-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 15px;
        }
        .theme-icon-btn:hover {
            color: var(--text-heading);
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        /* Notification Bell */
        .notif-bell-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .notif-bell-btn:hover {
            color: var(--text-heading);
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .notif-dot-badge {
            position: absolute;
            top: 7px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            border: 2px solid var(--bg-card);
        }

        /* Avatar Circle */
        .user-avatar-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #18181b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: -0.02em;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        [data-theme="dark"] .user-avatar-badge {
            background: #27272a;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Main Content Container */
        main {
            padding: 8px 36px 60px;
            flex: 1;
            max-width: 1720px;
            width: 100%;
        }

        /* Bot Alert Notice */
        .bot-alert-banner {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            color: var(--warning-text);
            padding: 12px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .flash-success-banner {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 12px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Common Generic Card & Table styles for subpages */
        .panel-card, .card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 22px;
            box-shadow: var(--shadow-card);
            margin-bottom: 20px;
        }

        .data-table, .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .data-table th, .custom-table th {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 14px;
            border-bottom: 1.5px solid var(--border-subtle);
            background: var(--bg-canvas);
        }
        .data-table td, .custom-table td {
            font-size: 13px;
            padding: 14px 14px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-body);
            vertical-align: middle;
        }
        .data-table tr:hover td, .custom-table tr:hover td {
            background: var(--border-subtle);
        }

        /* Panel Headers */
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .panel-title h3 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-heading);
        }
        .panel-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Metric Cards for CRM / Reports */
        .metric-card-box {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 20px;
            box-shadow: var(--shadow-card);
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
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .metric-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: var(--text-muted);
        }
        .metric-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .metric-icon-box.green  { background: #ecfdf5; color: #059669; }
        .metric-icon-box.blue   { background: #f0f9ff; color: #0284c7; }
        .metric-icon-box.purple { background: #faf5ff; color: #9333ea; }
        .metric-icon-box.orange { background: #fff7ed; color: #ea580c; }

        .sub-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
        }
        .sub-badge.green  { background: #dcfce7; color: #166534; }
        .sub-badge.blue   { background: #dbeafe; color: #1e40af; }
        .sub-badge.purple { background: #f3e8ff; color: #6b21a8; }
        .sub-badge.orange { background: #ffedd5; color: #9a3412; }

        /* Badge Status */
        .badge-status {
            display: inline-flex;
            align-items: center;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .badge-status.pending   { background: #fef3c7; color: #b45309; }
        .badge-status.confirmed { background: #e0e7ff; color: #4338ca; }
        .badge-status.preparing { background: #fef9c3; color: #854d0e; }
        .badge-status.out_for_delivery { background: #dbeafe; color: #1e40af; }
        .badge-status.delivered { background: #dcfce7; color: #15803d; }
        .badge-status.cancelled { background: #fee2e2; color: #b91c1c; }

        /* Category Filter Pills in Menu */
        .cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .cat-pill:hover {
            border-color: #cbd5e1;
            color: var(--text-heading);
        }
        .cat-pill.active-pill {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }
        .cat-pill .pill-count {
            opacity: 0.8;
            font-size: 11px;
        }

        /* Buttons & Pills */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: var(--brand-primary);
            color: #fff;
            border-color: var(--brand-primary);
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }
        .btn-primary:hover {
            filter: brightness(0.95);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-body);
            border-color: var(--border-card);
        }
        .btn-secondary:hover {
            background: var(--border-subtle);
            color: var(--text-heading);
        }
        .btn-success {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        }
        .btn-success:hover {
            filter: brightness(0.95);
        }

        /* Mobile Bottom Nav */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: var(--bg-card);
            border-top: 1px solid var(--border-card);
            z-index: 85;
            padding: 4px 8px;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
        }
        .mob-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 10px;
        }
        .mob-nav-item.active {
            color: var(--brand-primary);
            background: var(--brand-primary-light);
        }

        .mobile-menu-toggle {
            display: none;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-heading);
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 95;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            aside#ownerSidebar {
                transform: translateX(-100%);
                z-index: 1000;
                width: 76px;
            }
            aside#ownerSidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            }
            .sidebar-backdrop.active {
                display: block;
                opacity: 1;
            }
            .mobile-menu-toggle {
                display: inline-flex;
            }
            .main-wrapper {
                margin-left: 0 !important;
            }
            header.topbar {
                padding: 0 20px;
                height: 72px;
            }
            main {
                padding: 12px 18px 80px;
            }
            .mobile-bottom-nav {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .date-pill {
                display: none !important;
            }
            .header-greeting-title {
                font-size: 17px;
            }
            .header-greeting-sub {
                display: none;
            }
            .status-pill-toggle span:last-child {
                display: none;
            }
        }
    </style>
</head>
<body>

@php
    $currentRest = $restaurant ?? ($r ?? null);
    $restId = $currentRest?->id ?? 1;
    $ownerName = $currentRest->owner_name ?? ($currentRest->name ?? 'Haseeb');
    $ownerFirstName = explode(' ', trim($ownerName))[0] ?: 'Owner';
    $hour = now()->hour;
    $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $isOpen = (bool) ($currentRest->is_open ?? true);
@endphp

<!-- Mobile Backdrop -->
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleOwnerSidebar()"></div>

<!-- SLIM ICON SIDEBAR -->
<aside id="ownerSidebar">
    <!-- Top Cutlery Brand Badge -->
    <a href="{{ route('dashboard.orders', $restId) }}" class="sidebar-brand-badge" title="Foodio Restaurant POS">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 2v20"/>
            <path d="M6 2v6a3 3 0 0 0 3 3h0a3 3 0 0 0 3-3V2"/>
            <path d="M9 11v11"/>
            <path d="M18 7a3 3 0 0 1-3-3V2h6v2a3 3 0 0 1-3 3Z"/>
        </svg>
    </a>

    <!-- Center Icon Stack -->
    <div class="sidebar-nav-stack">
        <!-- 1. Dashboard (Home) -->
        <a href="{{ route('dashboard.orders', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.orders') && !request('view') ? 'active' : '' }}" 
           data-tooltip="Dashboard">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>

        <!-- 2. Orders (Checklist / Live) -->
        <a href="{{ route('dashboard.live-orders', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.live-orders*') || request('view') === 'live' ? 'active' : '' }}" 
           data-tooltip="Live Orders">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <path d="m9 14 2 2 4-4"/>
            </svg>
            @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                <span class="sidebar-icon-badge"></span>
            @endif
        </a>

        <!-- 3. Menu -->
        <a href="{{ route('dashboard.menu', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}" 
           data-tooltip="Menu & Items">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                <path d="M7 2v20"/>
                <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
            </svg>
        </a>

        <!-- 4. Customers -->
        <a href="{{ route('dashboard.customers', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.customers*') ? 'active' : '' }}" 
           data-tooltip="Customers">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </a>

        <!-- 5. Riders / Fleet -->
        <a href="{{ route('dashboard.riders', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.riders*') ? 'active' : '' }}" 
           data-tooltip="Riders & Fleet">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                <path d="M15 18H9"/>
                <path d="M19 18h2a1 1 0 0 0 1-1v-5l-3-4h-5v10h1"/>
                <circle cx="7" cy="18" r="2"/>
                <circle cx="17" cy="18" r="2"/>
            </svg>
        </a>

        <!-- 6. Reports -->
        <a href="{{ route('dashboard.reports', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.reports*') ? 'active' : '' }}" 
           data-tooltip="Reports & Analytics">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18"/>
                <path d="m19 9-5 5-4-4-3 3"/>
            </svg>
        </a>

        <!-- 7. Settings -->
        <a href="{{ route('dashboard.settings', $restId) }}" 
           class="sidebar-icon-btn {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}" 
           data-tooltip="Settings">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </a>
    </div>

    <!-- Bottom Status Dot + Online -->
    <a href="javascript:void(0)" onclick="quickToggleRestaurantStatus()" class="sidebar-footer-status" title="Click to toggle Online/Paused">
        <span class="sidebar-online-dot {{ $isOpen ? '' : 'paused' }}" id="sidebarStatusDot"></span>
        <span id="sidebarStatusText">{{ $isOpen ? 'Online' : 'Paused' }}</span>
    </a>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <!-- TOPBAR (MATCHES SCREENSHOT) -->
    <header class="topbar">
        <div class="header-left">
            <button type="button" class="mobile-menu-toggle" onclick="toggleOwnerSidebar()" aria-label="Open menu">
                ☰
            </button>
            <div>
                <h1 class="header-greeting-title">
                    @hasSection('header_title')
                        @yield('header_title')
                    @else
                        {{ $timeGreeting }}, {{ $ownerFirstName }} 👋
                    @endif
                </h1>
                <p class="header-greeting-sub">
                    @yield('header_subtitle', "Here's what's happening at your restaurant today.")
                </p>
            </div>
        </div>

        <div class="header-right-actions">
            <!-- Restaurant Online / Paused Toggle Pill -->
            <button type="button" class="status-pill-toggle {{ $isOpen ? '' : 'paused' }}" id="statusPillBtn" onclick="quickToggleRestaurantStatus()" title="Toggle Taking Orders">
                <span class="sidebar-online-dot {{ $isOpen ? '' : 'paused' }}" id="pillStatusDot"></span>
                <span id="pillStatusLabel">{{ $isOpen ? 'Restaurant Online' : 'Store Paused' }}</span>
                <span style="font-size: 10px; opacity: 0.6;">▾</span>
            </button>

            <!-- Live Date Pill -->
            <div class="date-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" x2="16" y1="2" y2="6"/>
                    <line x1="8" x2="8" y1="2" y2="6"/>
                    <line x1="3" x2="21" y1="10" y2="10"/>
                </svg>
                <span>{{ now()->format('D, M j, Y') }}</span>
            </div>

            <!-- Dark / Light Theme Toggle -->
            <button type="button" class="theme-icon-btn" id="themeToggleBtn" onclick="toggleOwnerTheme()" title="Toggle Dark/Light Mode">
                <span id="themeToggleIcon">☀️</span>
            </button>

            <!-- Notifications Bell -->
            <a href="{{ route('dashboard.orders', $restId) }}" class="notif-bell-btn" id="notif-bell-wrap" title="Pending orders & alerts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                </svg>
                <span class="notif-dot-badge" id="notif-badge" style="display: {{ isset($pendingCount) && $pendingCount > 0 ? 'block' : 'none' }};"></span>
            </a>

            <!-- User Initials Avatar -->
            <a href="{{ route('dashboard.settings', $restId) }}" class="user-avatar-badge" title="{{ $currentRest->name ?? 'Profile' }}">
                {{ strtoupper(substr($ownerFirstName, 0, 1)) }}{{ strtoupper(substr($currentRest->name ?? 'T', 0, 1)) }}
            </a>
        </div>
    </header>

    <main>
        @if($currentRest && ($currentRest->bot_status === 'disconnected' || $currentRest->bot_status === 'qr_pending'))
            <div class="bot-alert-banner">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">⚠️</span>
                    <span>WhatsApp Bot is <strong>{{ $currentRest->bot_status === 'qr_pending' ? 'Awaiting QR Scan' : 'Offline / Disconnected' }}</strong>. Your customers cannot place orders until connected!</span>
                </div>
                <a href="{{ route('dashboard.connect-whatsapp', $currentRest->id) }}" class="btn" style="background: #d97706; color: #fff;">
                    Scan QR & Connect →
                </a>
            </div>
        @endif

        @if(session('success'))
            <div class="flash-success-banner">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- MOBILE BOTTOM NAVIGATION -->
    <nav class="mobile-bottom-nav">
        <a href="{{ route('dashboard.orders', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.orders') && !request('view') ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            <span>Home</span>
        </a>

        <a href="{{ route('dashboard.live-orders', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.live-orders*') || request('view') === 'live' ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            </svg>
            <span>Live</span>
        </a>

        <a href="{{ route('dashboard.menu', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                <path d="M7 2v20"/>
            </svg>
            <span>Menu</span>
        </a>

        <a href="{{ route('dashboard.riders', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.riders*') ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="7" cy="18" r="2"/>
                <circle cx="17" cy="18" r="2"/>
                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
            </svg>
            <span>Riders</span>
        </a>

        <button type="button" class="mob-nav-item" onclick="toggleOwnerSidebar()" style="background: none; border: none; cursor: pointer;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="19" cy="12" r="1"/>
                <circle cx="5" cy="12" r="1"/>
            </svg>
            <span>More</span>
        </button>
    </nav>
</div>

<!-- SCRIPTS: Status Toggle, Theme & Live Bell Polling -->
<script>
// ── Theme Manager ──────────────────────────────────────
function initOwnerTheme() {
    const saved = localStorage.getItem('owner_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
}

function toggleOwnerTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('owner_theme', next);
    updateThemeIcon(next);
}

function updateThemeIcon(t) {
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        icon.textContent = t === 'dark' ? '🌙' : '☀️';
    }
}

// ── Mobile Sidebar Drawer ──────────────────────────────
function toggleOwnerSidebar() {
    const sb = document.getElementById('ownerSidebar');
    const bd = document.getElementById('sidebarBackdrop');
    if (sb) sb.classList.toggle('mobile-open');
    if (bd) bd.classList.toggle('active');
}

// ── Quick Toggle Restaurant Online / Paused ────────────
async function quickToggleRestaurantStatus() {
    try {
        const res = await fetch("{{ route('dashboard.toggle-open', $restId) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            const isOpen = data.is_open;
            
            // Update sidebar indicator
            const sideDot = document.getElementById('sidebarStatusDot');
            const sideTxt = document.getElementById('sidebarStatusText');
            if (sideDot) sideDot.className = 'sidebar-online-dot ' + (isOpen ? '' : 'paused');
            if (sideTxt) sideTxt.textContent = isOpen ? 'Online' : 'Paused';

            // Update topbar pill
            const pillBtn = document.getElementById('statusPillBtn');
            const pillDot = document.getElementById('pillStatusDot');
            const pillLbl = document.getElementById('pillStatusLabel');
            if (pillBtn) pillBtn.className = 'status-pill-toggle ' + (isOpen ? '' : 'paused');
            if (pillDot) pillDot.className = 'sidebar-online-dot ' + (isOpen ? '' : 'paused');
            if (pillLbl) pillLbl.textContent = isOpen ? 'Restaurant Online' : 'Store Paused';
        }
    } catch (e) {
        console.error("Failed to toggle restaurant status", e);
    }
}

// ── Notification Bell Poller ───────────────────────────
(function() {
    const badge = document.getElementById('notif-badge');
    const bell  = document.getElementById('notif-bell-wrap');
    if (!badge || !bell) return;

    let lastPending = null;

    async function pollPending() {
        try {
            const res = await fetch("{{ route('dashboard.orders.live-feed', $restId) }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && typeof data.pending_count !== 'undefined') {
                const pending = parseInt(data.pending_count, 10);
                badge.style.display = pending > 0 ? 'block' : 'none';
                lastPending = pending;
            }
        } catch (e) {}
    }

    pollPending();
    setInterval(pollPending, 8000);
})();

document.addEventListener('DOMContentLoaded', initOwnerTheme);
</script>

</body>
</html>