<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin') — WhatsApp Ordering Platform</title>

    <!-- Immediate theme initializer -->
    <script>
        (function() {
            const t = localStorage.getItem('sa_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Surface & Canvas */
            --bg-canvas: #f8fafc;
            --bg-card: #ffffff;
            --border-subtle: #f1f5f9;
            --border-card: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --text-light: #94a3b8;

            /* Slim Sidebar */
            --sidebar-bg: #ffffff;
            --sidebar-border: #f1f5f9;
            --sidebar-hover: #f1f5f9;
            --sidebar-active-bg: rgba(99, 102, 241, 0.12);
            --sidebar-active-color: #4f46e5;

            /* Header & Brand */
            --header-bg: rgba(248, 250, 252, 0.82);
            --brand-primary: #4f46e5;
            --brand-primary-light: #eef2ff;
            --brand-accent: #0f172a;

            /* Status Semantics */
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #059669;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            --warning-text: #b45309;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #dc2626;

            /* Radii & Shadows */
            --radius-card: 16px;
            --radius-badge: 9999px;
            --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            --shadow-elevated: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);

            /* Legacy variable aliases for subviews compatibility */
            --bg-page: var(--bg-canvas);
            --card-bg: var(--bg-card);
            --border-color: var(--border-card);
            --text-primary: var(--text-heading);
            --text-secondary: var(--text-muted);
            --input-bg: #ffffff;
            --table-header-bg: #f8fafc;
            --table-hover-bg: #f8fafc;
        }

        /* ── Obsidian Midnight Dark Mode ── */
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
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --sidebar-active-bg: rgba(99, 102, 241, 0.22);
            --sidebar-active-color: #818cf8;

            --header-bg: rgba(11, 15, 25, 0.82);
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

            --bg-page: var(--bg-canvas);
            --card-bg: var(--bg-card);
            --border-color: var(--border-card);
            --text-primary: var(--text-heading);
            --text-secondary: var(--text-muted);
            --input-bg: #0e1424;
            --table-header-bg: #0e1422;
            --table-hover-bg: #151e33;
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

        html {
            overflow-x: hidden;
            max-width: 100vw;
        }

        body {
            background-color: var(--bg-canvas);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            font-size: 13px;
            line-height: 1.5;
            transition: background-color 0.2s ease, color 0.2s ease;
            overflow-x: hidden;
            max-width: 100vw;
            width: 100%;
        }

        /* ═══════════════════════════════════════════════════
           SLIM ICON SIDEBAR (IMAGE CONCEPT MATCH)
           ═══════════════════════════════════════════════════ */
        aside#adminSidebar {
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
            padding: 18px 0 20px;
            transition: transform 0.25s ease, background-color 0.2s ease;
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
            margin-bottom: 20px;
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
            gap: 12px;
            flex: 1;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar-nav-stack::-webkit-scrollbar {
            display: none;
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
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .sidebar-icon-btn:hover {
            background: var(--sidebar-hover);
            color: var(--text-heading);
            transform: translateY(-1px);
        }
        .sidebar-icon-btn.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-color);
        }

        /* Tooltip on icon hover */
        .sidebar-icon-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 82px;
            background: #0f172a;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity 0.15s ease, transform 0.15s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            z-index: 200;
        }
        .sidebar-icon-btn:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .sidebar-dot-badge {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            border: 2px solid var(--sidebar-bg);
        }

        .sidebar-footer-status {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        .sidebar-online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.22);
        }

        /* ═══════════════════════════════════════════════════
           MAIN WRAPPER & FIXED FROSTED GLASS HEADER
           ═══════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: 76px;
            padding-top: 84px;
            flex: 1;
            min-height: 100vh;
            min-width: 0;
            max-width: calc(100vw - 76px);
            width: calc(100% - 76px);
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            transition: margin-left 0.25s ease;
            overflow-x: hidden;
        }

        header.topbar {
            height: 84px;
            background: var(--header-bg);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--border-card);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            position: fixed;
            top: 0;
            left: 76px;
            right: 0;
            z-index: 90;
            transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        [data-theme="dark"] header.topbar {
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.35);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: 1px solid var(--border-card);
            padding: 8px 12px;
            border-radius: 10px;
            color: var(--text-heading);
            cursor: pointer;
            font-size: 16px;
            align-items: center;
            justify-content: center;
        }

        .header-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-title p {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 3px;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Status Toggle Pill Button */
        .status-pill-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 7px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            user-select: none;
        }
        .online-indicator-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        }

        /* Date Pill */
        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border-card);
            padding: 7px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            user-select: none;
        }
        [data-theme="dark"] .date-pill {
            background: rgba(19, 27, 46, 0.85);
            border-color: rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
        }
        .date-pill svg {
            color: var(--brand-primary);
        }

        /* Header Circular Icon Button (Theme & Notifications) */
        .header-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border-card);
            color: var(--text-heading);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.18s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .header-icon-btn:hover {
            transform: translateY(-1px);
            background: var(--border-subtle);
        }
        [data-theme="dark"] .header-icon-btn {
            background: rgba(19, 27, 46, 0.85);
            border-color: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
        }

        /* Profile & Logout */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 14px;
            border-left: 1px solid var(--border-card);
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-heading);
        }

        .user-email {
            font-size: 11px;
            color: var(--text-muted);
        }

        .logout-link {
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .logout-link:hover {
            color: #ef4444;
            background: var(--danger-bg);
            border-color: var(--danger-border);
        }

        /* ═══════════════════════════════════════════════════
           MAIN CONTENT AREA
           ═══════════════════════════════════════════════════ */
        main {
            padding: 28px 36px 64px;
            flex: 1;
        }

        /* ═══════════════════════════════════════════════════
           GLOBAL COMPONENT STYLES
           ═══════════════════════════════════════════════════ */
        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 24px;
            box-shadow: var(--shadow-card);
            margin-bottom: 22px;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

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
            letter-spacing: -0.015em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-card);
            background: var(--table-header-bg);
        }

        .data-table td {
            font-size: 12.5px;
            padding: 13px 14px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-heading);
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: var(--table-hover-bg);
        }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: var(--brand-primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-heading);
        }
        .btn-secondary:hover {
            background: var(--border-subtle);
        }

        .btn-success {
            background: #10b981;
            color: #ffffff;
        }
        .btn-danger {
            background: #ef4444;
            color: #ffffff;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 11.5px;
            border-radius: 8px;
        }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: var(--radius-badge);
            white-space: nowrap;
        }
        .badge-green  { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .badge-red    { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }
        .badge-yellow { background: var(--warning-bg); color: var(--warning-text); border: 1px solid var(--warning-border); }
        .badge-blue   { background: var(--brand-primary-light); color: var(--brand-primary); border: 1px solid rgba(99, 102, 241, 0.25); }
        .badge-gray   { background: var(--border-subtle); color: var(--text-muted); border: 1px solid var(--border-card); }

        /* FORM ELEMENTS */
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-card);
            background: var(--input-bg);
            color: var(--text-heading);
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            font-family: inherit;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px var(--brand-primary-light);
        }

        /* ── FLASH ALERTS ── */
        .admin-alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-alert-error {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-weight: 600;
            font-size: 13px;
        }

        /* ═══════════════════════════════════════════════════
           ALL MODULES MEGA DRAWER / MODAL (PRESERVE ALL 24+ FEATURES)
           ═══════════════════════════════════════════════════ */
        .all-modules-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11, 15, 25, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .all-modules-backdrop.active {
            display: flex;
            opacity: 1;
        }
        .all-modules-modal {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            width: 860px;
            max-width: 92vw;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-elevated);
            overflow: hidden;
            animation: modalScale 0.2s ease;
        }
        @keyframes modalScale {
            from { transform: scale(0.96); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .all-modules-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-card);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .all-modules-search {
            margin: 14px 24px 0;
            position: relative;
        }
        .all-modules-search input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            border-radius: 12px;
            border: 1px solid var(--border-card);
            background: var(--bg-canvas);
            color: var(--text-heading);
            font-size: 13px;
            outline: none;
        }
        .all-modules-search input:focus {
            border-color: var(--brand-primary);
        }
        .all-modules-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 15px;
        }
        .all-modules-body {
            padding: 20px 24px 30px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .module-group-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .module-link-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 8px;
            color: var(--text-body);
            text-decoration: none;
            font-weight: 600;
            font-size: 12.5px;
            transition: all 0.15s ease;
        }
        .module-link-item:hover {
            background: var(--border-subtle);
            color: var(--brand-primary);
            transform: translateX(2px);
        }
        .module-link-item.active {
            background: var(--sidebar-active-bg);
            color: var(--brand-primary);
            font-weight: 700;
        }

        /* ── MOBILE RESPONSIVENESS & BOTTOM NAV ── */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--border-card);
            z-index: 85;
            padding: 4px 8px;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.04);
        }
        [data-theme="dark"] .mobile-bottom-nav {
            background: rgba(11, 15, 25, 0.94);
            border-top-color: var(--border-card);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.35);
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
            padding: 6px 10px;
            border-radius: 12px;
            transition: all 0.15s ease;
            position: relative;
            min-width: 52px;
        }
        .mob-nav-item.active {
            color: var(--brand-primary);
            background: var(--brand-primary-light);
        }

        @media (max-width: 1024px) {
            aside#adminSidebar {
                transform: translateX(-100%);
                z-index: 1000;
                width: 76px;
            }
            aside#adminSidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            }
            .main-wrapper {
                margin-left: 0;
                padding-top: 64px;
                max-width: 100vw;
                width: 100%;
            }
            header.topbar {
                left: 0;
                right: 0;
                height: 64px;
                padding: 0 20px;
            }
            .mobile-menu-btn {
                display: inline-flex;
            }
        }

        @media (max-width: 768px) {
            header.topbar {
                padding: 0 16px;
            }
            .header-title h1 {
                font-size: 15px;
            }
            .header-title p {
                display: none;
            }
            .date-pill, .status-pill-toggle {
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
            .user-meta {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- SLIM ICON SIDEBAR -->
<aside id="adminSidebar">
    <!-- Brand / Knife-Fork Badge -->
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand-badge" title="Foodio Platform">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
        </svg>
    </a>

    <!-- Nav Stack matching the exact visual icons in the concept image -->
    <div class="sidebar-nav-stack">
        <!-- 1. Dashboard (Home) -->
        <a href="{{ route('admin.dashboard') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
        </a>

        <!-- 2. All Restaurants (Store) -->
        <a href="{{ route('admin.restaurants') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}" data-tooltip="All Restaurants">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <rect x="7" y="13" width="10" height="9"></rect>
            </svg>
        </a>

        <!-- 3. Pending Approvals (Hourglass) -->
        @php $pCount = \App\Models\Restaurant::where('status', 'pending')->count(); @endphp
        <a href="{{ route('admin.restaurants.pending') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.restaurants.pending') ? 'active' : '' }}" data-tooltip="Pending Approvals ({{ $pCount }})">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 22h14"></path>
                <path d="M5 2h14"></path>
                <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path>
                <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path>
            </svg>
            @if($pCount > 0)
                <span class="sidebar-dot-badge"></span>
            @endif
        </a>

        <!-- 4. Password Resets (Key) -->
        @php $rCount = \App\Models\PasswordResetRequest::where('status', 'pending')->count(); @endphp
        <a href="{{ route('admin.password-resets') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.password-resets') ? 'active' : '' }}" data-tooltip="Password Resets ({{ $rCount }})">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 2l-2 2m-1.5 1.5L16 7l-1.5-1.5"></path>
                <circle cx="7.5" cy="15.5" r="5.5"></path>
                <path d="M16 7l-4.5 4.5"></path>
            </svg>
            @if($rCount > 0)
                <span class="sidebar-dot-badge" style="background: #f59e0b;"></span>
            @endif
        </a>

        <!-- 5. Add Restaurant (Plus) -->
        <a href="{{ route('admin.create-restaurant') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.create-restaurant') ? 'active' : '' }}" data-tooltip="Add Restaurant">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
        </a>

        <!-- 6. Owner Accounts (Users) -->
        <a href="{{ route('admin.users') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.users') ? 'active' : '' }}" data-tooltip="Owner Accounts">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </a>

        <!-- 7. Bot & AI Settings (Gear) -->
        <a href="{{ route('admin.bot-settings') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.bot-settings') ? 'active' : '' }}" data-tooltip="AI & Bot Settings">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </a>

        <!-- 8. Message Templates (Chat) -->
        <a href="{{ route('admin.bot-templates') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.bot-templates') ? 'active' : '' }}" data-tooltip="Message Templates">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </a>

        <!-- 9. Global Menu Templates (Document) -->
        <a href="{{ route('admin.menu-templates') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.menu-templates') ? 'active' : '' }}" data-tooltip="Global Menu Templates">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
        </a>

        <!-- 10. Platform Analytics (Chart) -->
        <a href="{{ route('admin.analytics') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.analytics') ? 'active' : '' }}" data-tooltip="Platform Analytics">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
        </a>

        <!-- 11. Audit Trail (Document check) -->
        <a href="{{ route('admin.audit-logs') }}" class="sidebar-icon-btn {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}" data-tooltip="Audit Trail">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
        </a>

        <!-- 12. All Modules & Features Mega Drawer Launcher -->
        <button type="button" class="sidebar-icon-btn" onclick="openAllModulesModal()" data-tooltip="All Modules (24+)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
        </button>
    </div>

    <!-- Bottom Status Indicator Dot -->
    <div class="sidebar-footer-status" title="Platform Status: Online">
        <div class="sidebar-online-dot"></div>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <!-- FIXED FROSTED GLASS TOPBAR -->
    <header class="topbar">
        <div class="header-left">
            <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open navigation menu">☰</button>
            <div class="header-title">
                @php
                    $hour = (int) date('H');
                    $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                @endphp
                <h1>@yield('header_title', $timeGreeting . ', Super Admin 👑')</h1>
                <p>@yield('header_subtitle', "Here's what's happening across the entire platform today.")</p>
            </div>
        </div>

        <div class="header-actions">
            <!-- Platform Online Status Pill -->
            <div class="status-pill-toggle" title="Platform Status: Operational">
                <span class="online-indicator-dot"></span>
                <span>Platform Online</span>
                <span style="font-size: 10px; opacity: 0.7;">▾</span>
            </div>

            <!-- Live Date Pill -->
            <div class="date-pill" id="headerDatePill" title="Today's Date">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>{{ date('D, M j, Y') }}</span>
            </div>

            <!-- Theme toggle circular button -->
            <button type="button" class="header-icon-btn" onclick="toggleTheme()" id="themeBtn" title="Toggle Light/Dark Theme">
                <span id="themeSunIcon">☀️</span>
            </button>

            <!-- Notifications / Modules Drawer button -->
            <button type="button" class="header-icon-btn" onclick="openAllModulesModal()" title="All Modules & Alerts">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </button>

            <!-- Super Admin Profile -->
            <div class="user-profile">
                <div class="avatar">SA</div>
                <div class="user-meta">
                    <span class="user-name">Super Admin</span>
                    <span class="user-email">Root Platform</span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin-left: 4px;">
                    @csrf
                    <button type="submit" class="logout-link" title="Sign Out">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main>
        @if(session('success'))
            <div class="admin-alert-success">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="admin-alert-error">
                @foreach($errors->all() as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    <!-- MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="mobile-bottom-nav">
        <a href="{{ route('admin.dashboard') }}" class="mob-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span style="font-size: 18px;">🏠</span>
            <span>Home</span>
        </a>

        <a href="{{ route('admin.restaurants') }}" class="mob-nav-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
            <span style="font-size: 18px;">🏪</span>
            <span>Stores</span>
        </a>

        <a href="{{ route('admin.restaurants.pending') }}" class="mob-nav-item {{ request()->routeIs('admin.restaurants.pending') ? 'active' : '' }}">
            <span style="font-size: 18px;">⏳</span>
            <span>Pending</span>
        </a>

        <a href="{{ route('admin.analytics') }}" class="mob-nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
            <span style="font-size: 18px;">📈</span>
            <span>Stats</span>
        </a>

        <button type="button" class="mob-nav-item" onclick="openAllModulesModal()" style="background: none; border: none; cursor: pointer;">
            <span style="font-size: 18px;">▦</span>
            <span>Modules</span>
        </button>
    </nav>
</div>

<!-- ALL MODULES MEGA DRAWER / MODAL -->
<div id="allModulesModal" class="all-modules-backdrop" onclick="if(event.target === this) closeAllModulesModal()">
    <div class="all-modules-modal">
        <div class="all-modules-header">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: var(--text-heading);">Platform Modules & Controls</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Direct access to all 24+ platform control modules</p>
            </div>
            <button type="button" onclick="closeAllModulesModal()" style="background: var(--border-subtle); border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 700; color: var(--text-heading);">✕</button>
        </div>

        <div class="all-modules-search">
            <span class="all-modules-search-icon">🔍</span>
            <input type="text" id="moduleSearchInput" placeholder="Quick find any module (e.g. Billing, Support, API, Bots)..." onkeyup="filterModules()">
        </div>

        <div class="all-modules-body" id="modulesGrid">
            <!-- 1. Restaurants -->
            <div class="module-group">
                <div class="module-group-title">1. Restaurants</div>
                <a href="{{ route('admin.restaurants') }}" class="module-link-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
                    <span>🏪 All Restaurants</span>
                </a>
                <a href="{{ route('admin.restaurants.pending') }}" class="module-link-item {{ request()->routeIs('admin.restaurants.pending') ? 'active' : '' }}">
                    <span>⏳ Pending Approvals</span>
                    @if($pCount > 0)<span class="badge badge-yellow">{{ $pCount }}</span>@endif
                </a>
                <a href="{{ route('admin.password-resets') }}" class="module-link-item {{ request()->routeIs('admin.password-resets') ? 'active' : '' }}">
                    <span>🔑 Password Resets</span>
                    @if($rCount > 0)<span class="badge badge-yellow">{{ $rCount }}</span>@endif
                </a>
                <a href="{{ route('admin.create-restaurant') }}" class="module-link-item {{ request()->routeIs('admin.create-restaurant') ? 'active' : '' }}">
                    <span>➕ Add Restaurant</span>
                </a>
                <a href="{{ route('admin.users') }}" class="module-link-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <span>👥 Owner Accounts</span>
                </a>
            </div>

            <!-- 2. Bot & Menus -->
            <div class="module-group">
                <div class="module-group-title">2. Bot & Menus</div>
                <a href="{{ route('admin.bot-settings') }}" class="module-link-item {{ request()->routeIs('admin.bot-settings') ? 'active' : '' }}">
                    <span>⚙️ AI & Feature Flags</span>
                </a>
                <a href="{{ route('admin.bot-templates') }}" class="module-link-item {{ request()->routeIs('admin.bot-templates') ? 'active' : '' }}">
                    <span>💬 Message Templates</span>
                </a>
                <a href="{{ route('admin.bot-commands') }}" class="module-link-item {{ request()->routeIs('admin.bot-commands') ? 'active' : '' }}">
                    <span>⌨️ Bot Commands</span>
                </a>
                <a href="{{ route('admin.menu-templates') }}" class="module-link-item {{ request()->routeIs('admin.menu-templates') ? 'active' : '' }}">
                    <span>📋 Global Menu Templates</span>
                </a>
            </div>

            <!-- 3. Analytics & Orders -->
            <div class="module-group">
                <div class="module-group-title">3. Analytics & Orders</div>
                <a href="{{ route('admin.analytics') }}" class="module-link-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                    <span>📈 Platform Analytics</span>
                </a>
                <a href="{{ route('admin.reports.custom') }}" class="module-link-item {{ request()->routeIs('admin.reports.custom') ? 'active' : '' }}">
                    <span>📑 Custom Reports & CSV</span>
                </a>
                <a href="{{ route('admin.orders') }}" class="module-link-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                    <span>📦 Live Orders Feed</span>
                </a>
            </div>

            <!-- 4. Billing & Payments -->
            <div class="module-group">
                <div class="module-group-title">4. Billing & Payments</div>
                <a href="{{ route('admin.billing') }}" class="module-link-item {{ request()->routeIs('admin.billing') ? 'active' : '' }}">
                    <span>💳 Plans & Invoices</span>
                </a>
            </div>

            <!-- 5. Support & Moderation -->
            <div class="module-group">
                <div class="module-group-title">5. Support & Moderation</div>
                @php $tCount = \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count(); @endphp
                <a href="{{ route('admin.support') }}" class="module-link-item {{ request()->routeIs('admin.support*') ? 'active' : '' }}">
                    <span>🎫 Support Tickets</span>
                    @if($tCount > 0)<span class="badge badge-yellow">{{ $tCount }}</span>@endif
                </a>
                <a href="{{ route('admin.moderation') }}" class="module-link-item {{ request()->routeIs('admin.moderation') ? 'active' : '' }}">
                    <span>🚫 Spam & Blacklist</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="module-link-item {{ request()->routeIs('admin.announcements') ? 'active' : '' }}">
                    <span>📢 Announcements</span>
                </a>
                <a href="{{ route('admin.feedback') }}" class="module-link-item {{ request()->routeIs('admin.feedback') ? 'active' : '' }}">
                    <span>⭐ Feedback & Reviews</span>
                </a>
            </div>

            <!-- 6. Platform & Security -->
            <div class="module-group">
                <div class="module-group-title">6. Platform & Security</div>
                <a href="{{ route('admin.system-health') }}" class="module-link-item {{ request()->routeIs('admin.system-health') ? 'active' : '' }}">
                    <span>🛡️ System Health</span>
                </a>
                <a href="{{ route('admin.api-keys') }}" class="module-link-item {{ request()->routeIs('admin.api-keys') ? 'active' : '' }}">
                    <span>🔑 API Keys</span>
                </a>
                <a href="{{ route('admin.email-templates') }}" class="module-link-item {{ request()->routeIs('admin.email-templates') ? 'active' : '' }}">
                    <span>✉️ Email Templates</span>
                </a>
                <a href="{{ route('admin.policies') }}" class="module-link-item {{ request()->routeIs('admin.policies') ? 'active' : '' }}">
                    <span>📜 Platform Policies</span>
                </a>
                <a href="{{ route('admin.audit-logs') }}" class="module-link-item {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                    <span>📝 Audit Trail</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="module-link-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <span>🔒 Settings & 2FA</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Theme Toggle
function initTheme() {
    const savedTheme = localStorage.getItem('sa_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('sa_theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeSunIcon');
    if (icon) {
        icon.textContent = theme === 'dark' ? '🌙' : '☀️';
    }
}

function toggleSidebar() {
    const sb = document.getElementById('adminSidebar');
    if (sb) sb.classList.toggle('mobile-open');
}

// All Modules Modal
function openAllModulesModal() {
    const m = document.getElementById('allModulesModal');
    if (m) {
        m.classList.add('active');
        const inp = document.getElementById('moduleSearchInput');
        if (inp) {
            inp.value = '';
            filterModules();
            setTimeout(() => inp.focus(), 100);
        }
    }
}

function closeAllModulesModal() {
    const m = document.getElementById('allModulesModal');
    if (m) m.classList.remove('active');
}

function filterModules() {
    const query = (document.getElementById('moduleSearchInput')?.value || '').toLowerCase();
    const items = document.querySelectorAll('#modulesGrid .module-link-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? 'flex' : 'none';
    });
}

// Hotkey: Ctrl+K / Cmd+K to open All Modules
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openAllModulesModal();
    }
    if (e.key === 'Escape') {
        closeAllModulesModal();
    }
});

document.addEventListener('DOMContentLoaded', initTheme);
</script>

@stack('scripts')
</body>
</html>
