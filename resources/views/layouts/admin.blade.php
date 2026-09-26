<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin') — WhatsApp Ordering Platform</title>

    <!-- Immediate theme initializer to avoid flash of wrong theme -->
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

            /* Sidebar */
            --sidebar-bg: #0e1424;
            --sidebar-hover: rgba(255, 255, 255, 0.07);
            --sidebar-active: #4f46e5;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --sidebar-border: rgba(255, 255, 255, 0.07);

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

            /* Legacy variable aliases for subpage compatibility */
            --bg-page: var(--bg-canvas);
            --card-bg: var(--bg-card);
            --border-color: var(--border-card);
            --text-primary: var(--text-heading);
            --text-secondary: var(--text-muted);
            --input-bg: #ffffff;
            --table-header-bg: #f8fafc;
            --table-hover-bg: #f8fafc;
        }

        /* ── Foodio Obsidian Midnight — Admin Dark Mode Tokens ── */
        [data-theme="dark"] {
            /* Surface & Canvas */
            --bg-canvas: #0b0f19;
            --bg-card: #131b2e;
            --border-subtle: rgba(255, 255, 255, 0.06);
            --border-card: rgba(255, 255, 255, 0.08);
            --text-heading: #f8fafc;
            --text-body: #cbd5e1;
            --text-muted: #94a3b8;
            --text-light: #64748b;

            /* Sidebar */
            --sidebar-bg: #0b0f19;
            --sidebar-hover: rgba(99, 102, 241, 0.09);
            --sidebar-active: #4f46e5;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --sidebar-border: rgba(255, 255, 255, 0.06);

            /* Header & Brand */
            --header-bg: rgba(11, 15, 25, 0.82);
            --brand-primary: #6366f1;
            --brand-primary-light: rgba(99, 102, 241, 0.15);
            --brand-accent: #f8fafc;

            /* Status Semantics */
            --success-bg: rgba(16, 185, 129, 0.12);
            --success-border: rgba(16, 185, 129, 0.25);
            --success-text: #34d399;
            --warning-bg: rgba(245, 158, 11, 0.12);
            --warning-border: rgba(245, 158, 11, 0.25);
            --warning-text: #fbbf24;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --danger-border: rgba(239, 68, 68, 0.25);
            --danger-text: #f87171;

            /* Radii & Shadows */
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.25);
            --shadow-elevated: 0 12px 30px rgba(0, 0, 0, 0.4);

            /* Legacy variable aliases */
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
           SIDEBAR STYLING
           ═══════════════════════════════════════════════════ */
        aside#adminSidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--sidebar-border);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
            transition: transform 0.25s ease, background-color 0.2s ease;
        }

        aside#adminSidebar::-webkit-scrollbar {
            width: 5px;
        }
        aside#adminSidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 4px;
        }

        .brand-header {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--sidebar-border);
            background: rgba(255, 255, 255, 0.015);
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            transition: transform 0.2s ease;
        }
        .brand-box:hover .brand-icon {
            transform: scale(1.05);
        }

        .brand-info h2 {
            font-size: 15.5px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-info span {
            font-size: 10px;
            color: #818cf8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: inline-block;
            margin-top: 1px;
        }

        .nav-section {
            padding: 14px 12px 36px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
        }

        .nav-category {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 16px 12px 5px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8.5px 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.15s ease;
            position: relative;
        }

        .nav-item .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover {
            color: #ffffff;
            background: var(--sidebar-hover);
            transform: translateX(2px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(79, 70, 229, 0.16));
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2);
            font-weight: 700;
        }

        .nav-item .icon {
            font-size: 15px;
            width: 20px;
            text-align: center;
            line-height: 1;
        }

        .nav-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            background: #ef4444;
            color: #fff;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
        }

        /* ═══════════════════════════════════════════════════
           MAIN WRAPPER & FIXED FROSTED GLASS HEADER
           ═══════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: 260px;
            padding-top: 80px;
            flex: 1;
            min-height: 100vh;
            min-width: 0;
            max-width: calc(100vw - 260px);
            width: calc(100% - 260px);
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            transition: margin-left 0.25s ease;
            overflow-x: hidden;
        }

        header.topbar {
            height: 80px;
            background: var(--header-bg);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--border-card);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: fixed;
            top: 0;
            left: 260px;
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
            transition: all 0.15s ease;
        }
        .mobile-menu-btn:hover {
            background: var(--border-subtle);
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Live Date Pill */
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

        /* Theme Toggle Pill */
        .theme-toggle-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border-card);
            border-radius: 9999px;
            padding: 5px 12px 5px 6px;
            color: var(--text-heading);
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
            user-select: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        .theme-toggle-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        [data-theme="dark"] .theme-toggle-pill {
            background: rgba(19, 27, 46, 0.85);
            border-color: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
        }
        .theme-pill-icon {
            font-size: 11px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .theme-pill-icon.moon.active {
            background: #4f46e5;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.5);
            color: #ffffff;
        }
        .theme-pill-icon.sun.active {
            background: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            color: #ffffff;
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
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 3px 10px rgba(79, 70, 229, 0.25);
        }
        [data-theme="dark"] .avatar {
            background: linear-gradient(135deg, #6366f1, #38bdf8);
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .user-name {
            font-size: 12.5px;
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
            padding: 28px 32px 64px;
            flex: 1;
        }

        /* ═══════════════════════════════════════════════════
           GLOBAL COMPONENT STYLES (MATCH OWNER SYSTEM)
           ═══════════════════════════════════════════════════ */
        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 22px 24px;
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

        /* METRIC CARDS */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 20px 22px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-elevated);
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .metric-title {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 4px;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .metric-footer {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .metric-icon.green  { background: var(--success-bg); color: var(--success-text); }
        .metric-icon.blue   { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .metric-icon.orange { background: var(--warning-bg); color: var(--warning-text); }
        .metric-icon.purple { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
        .metric-icon.red    { background: var(--danger-bg); color: var(--danger-text); }

        /* GRID LAYOUTS */
        .admin-grid-2-1 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 22px;
        }
        .admin-grid-half {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 1024px) {
            .admin-grid-2-1, .admin-grid-half {
                grid-template-columns: 1fr;
                gap: 18px;
            }
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
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        [data-theme="dark"] .btn-primary {
            background: #6366f1;
        }
        [data-theme="dark"] .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-secondary {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            color: var(--text-heading);
        }
        .btn-secondary:hover {
            background: var(--border-subtle);
            border-color: var(--text-muted);
        }

        .btn-success {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
        }
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 5px 10px;
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

        /* ── MOBILE RESPONSIVENESS & BOTTOM NAV ── */
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
            background: rgba(11, 15, 25, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 95;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

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

        .mob-nav-item .mob-icon {
            font-size: 18px;
            line-height: 1;
        }

        .mob-nav-item.active {
            color: var(--brand-primary);
            background: var(--brand-primary-light);
        }

        .mob-badge {
            position: absolute;
            top: 2px;
            right: 6px;
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
            border-color: #0b0f19;
        }

        @media (max-width: 1024px) {
            aside#adminSidebar {
                transform: translateX(-100%);
                z-index: 1000;
                width: 280px;
                box-shadow: none;
            }
            aside#adminSidebar.mobile-open {
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
            .date-pill {
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
            .panel-card {
                padding: 18px 16px;
                border-radius: 14px;
                margin-bottom: 16px;
            }
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .metric-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .data-table th, .data-table td {
                padding: 10px 10px;
                font-size: 11.5px;
            }
        }

        @media (max-width: 480px) {
            .metric-grid {
                grid-template-columns: 1fr;
            }
            header.topbar {
                padding: 0 12px;
            }
            .header-actions {
                gap: 8px;
            }
            .theme-toggle-pill #themeText {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR BACKDROP FOR MOBILE -->
<div id="adminSidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside id="adminSidebar">
    <div class="brand-header">
        <div class="brand-box">
            <div class="brand-icon">🤖</div>
            <div class="brand-info">
                <h2>Foodio</h2>
                <span>Super Admin</span>
            </div>
        </div>
        <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" title="Close menu">✕</button>
    </div>

    <div class="nav-section">
        <div class="nav-category">Main Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📊</span><span>Dashboard</span></div>
        </a>

        <div class="nav-category">1. Restaurants</div>
        <a href="{{ route('admin.restaurants') }}" class="nav-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🏪</span><span>All Restaurants</span></div>
        </a>
        <a href="{{ route('admin.restaurants.pending') }}" class="nav-item {{ request()->routeIs('admin.restaurants.pending') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">⏳</span><span>Pending Approval</span></div>
            @php $pCount = \App\Models\Restaurant::where('status', 'pending')->count(); @endphp
            @if($pCount > 0)
                <span class="nav-badge">{{ $pCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.password-resets') }}" class="nav-item {{ request()->routeIs('admin.password-resets') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🔑</span><span>Password Resets</span></div>
            @php $rCount = \App\Models\PasswordResetRequest::where('status', 'pending')->count(); @endphp
            @if($rCount > 0)
                <span class="nav-badge" style="background: #f59e0b;">{{ $rCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.create-restaurant') }}" class="nav-item {{ request()->routeIs('admin.create-restaurant') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">➕</span><span>Add Restaurant</span></div>
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">👥</span><span>Owner Accounts</span></div>
        </a>

        <div class="nav-category">2. Bot & Menus</div>
        <a href="{{ route('admin.bot-settings') }}" class="nav-item {{ request()->routeIs('admin.bot-settings') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">⚙️</span><span>AI & Feature Flags</span></div>
        </a>
        <a href="{{ route('admin.bot-templates') }}" class="nav-item {{ request()->routeIs('admin.bot-templates') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">💬</span><span>Message Templates</span></div>
        </a>
        <a href="{{ route('admin.bot-commands') }}" class="nav-item {{ request()->routeIs('admin.bot-commands') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">⌨️</span><span>Bot Commands</span></div>
        </a>
        <a href="{{ route('admin.menu-templates') }}" class="nav-item {{ request()->routeIs('admin.menu-templates') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📋</span><span>Global Menu Templates</span></div>
        </a>

        <div class="nav-category">3. Analytics & Orders</div>
        <a href="{{ route('admin.analytics') }}" class="nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📈</span><span>Platform Analytics</span></div>
        </a>
        <a href="{{ route('admin.reports.custom') }}" class="nav-item {{ request()->routeIs('admin.reports.custom') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📑</span><span>Custom Reports & CSV</span></div>
        </a>
        <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📦</span><span>Live Orders Feed</span></div>
        </a>

        <div class="nav-category">4. Billing & Payments</div>
        <a href="{{ route('admin.billing') }}" class="nav-item {{ request()->routeIs('admin.billing') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">💳</span><span>Plans & Invoices</span></div>
        </a>

        <div class="nav-category">5. Support & Moderation</div>
        <a href="{{ route('admin.support') }}" class="nav-item {{ request()->routeIs('admin.support*') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🎫</span><span>Support Tickets</span></div>
            @php $tCount = \App\Models\SupportTicket::whereIn('status', ['open', 'in_progress'])->count(); @endphp
            @if($tCount > 0)
                <span class="nav-badge">{{ $tCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.moderation') }}" class="nav-item {{ request()->routeIs('admin.moderation') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🚫</span><span>Spam & Blacklist</span></div>
        </a>
        <a href="{{ route('admin.announcements') }}" class="nav-item {{ request()->routeIs('admin.announcements') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📢</span><span>Announcements</span></div>
        </a>
        <a href="{{ route('admin.feedback') }}" class="nav-item {{ request()->routeIs('admin.feedback') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">⭐</span><span>Feedback & Reviews</span></div>
        </a>

        <div class="nav-category">6. Platform & Security</div>
        <a href="{{ route('admin.system-health') }}" class="nav-item {{ request()->routeIs('admin.system-health') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🛡️</span><span>System Health</span></div>
        </a>
        <a href="{{ route('admin.api-keys') }}" class="nav-item {{ request()->routeIs('admin.api-keys') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🔑</span><span>API Keys</span></div>
        </a>
        <a href="{{ route('admin.email-templates') }}" class="nav-item {{ request()->routeIs('admin.email-templates') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">✉️</span><span>Email Templates</span></div>
        </a>
        <a href="{{ route('admin.policies') }}" class="nav-item {{ request()->routeIs('admin.policies') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📜</span><span>Platform Policies</span></div>
        </a>
        <a href="{{ route('admin.audit-logs') }}" class="nav-item {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">📝</span><span>Audit Trail</span></div>
        </a>
        <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <div class="nav-left"><span class="icon">🔒</span><span>Settings & 2FA</span></div>
        </a>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <!-- FIXED FROSTED GLASS TOPBAR -->
    <header class="topbar">
        <div class="header-left">
            <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open navigation menu">☰</button>
            <div class="header-title">
                <h1>@yield('header_title', 'Super Admin')</h1>
                <p>@yield('header_subtitle', 'Master Control Center')</p>
            </div>
        </div>

        <div class="header-actions">
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

            <!-- Theme toggle pill -->
            <button type="button" class="theme-toggle-pill" onclick="toggleTheme()" id="themeBtn" title="Toggle Light/Dark Mode">
                <span class="theme-pill-icon sun" id="themeSunIcon">☀️</span>
                <span class="theme-pill-icon moon active" id="themeMoonIcon">🌙</span>
                <span class="theme-pill-text" id="themeText">Dark</span>
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
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <span class="mob-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.restaurants') }}" class="mob-nav-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">
            <span class="mob-icon">🏪</span>
            <span>Stores</span>
        </a>

        <a href="{{ route('admin.restaurants.pending') }}" class="mob-nav-item {{ request()->routeIs('admin.restaurants.pending') ? 'active' : '' }}">
            <span class="mob-icon">⏳</span>
            <span>Pending</span>
            @php $pCount = \App\Models\Restaurant::where('status', 'pending')->count(); @endphp
            @if($pCount > 0)
                <span class="mob-badge">{{ $pCount }}</span>
            @endif
        </a>

        <a href="{{ route('admin.analytics') }}" class="mob-nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
            <span class="mob-icon">📈</span>
            <span>Stats</span>
        </a>

        <button type="button" class="mob-nav-item" onclick="toggleSidebar()" style="background: none; border: none; cursor: pointer;">
            <span class="mob-icon">⚙️</span>
            <span>More</span>
        </button>
    </nav>
</div>

<script>
// Dark Mode Toggle with LocalStorage
function initTheme() {
    const savedTheme = localStorage.getItem('sa_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeButton(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('sa_theme', newTheme);
    updateThemeButton(newTheme);
}

function updateThemeButton(theme) {
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

function toggleSidebar() {
    const sb = document.getElementById('adminSidebar');
    const bd = document.getElementById('adminSidebarBackdrop');
    if (sb) sb.classList.toggle('mobile-open');
    if (bd) bd.classList.toggle('active');
}

// Auto-close mobile sidebar when clicking a nav item on mobile
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    const navItems = document.querySelectorAll('#adminSidebar .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                toggleSidebar();
            }
        });
    });
});
</script>

@stack('scripts')
</body>
</html>
