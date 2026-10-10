<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Restaurant Dashboard') — {{ $restaurant->name ?? ($r->name ?? 'Foodio') }}</title>

    <!-- Immediate theme initializer -->
    <script>
        (function() {
            const t = localStorage.getItem('owner_theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <!-- Google Font: Urbanist (Typography Requirement) -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600;1,700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-canvas: #f4f5f7;
            --bg-card: #ffffff;
            --border-subtle: #ebeef2;
            --border-card: #ebeef2;

            /* Exact HEX Palette from Attached System */
            --color-coral: #FD6941;
            --color-green: #36D161;
            --color-yellow: #F2AC11;
            --color-dark: #070A08;
            --color-gray: #888E89;
            --color-peach: #F08D45;

            --text-heading: #070A08;
            --text-body: #374151;
            --text-muted: #888E89;
            --text-light: #9ca3af;

            --brand-primary: #FD6941;
            --brand-primary-hover: #e5532b;
            --brand-primary-light: #fff2ed;
            --brand-accent: #FD6941;

            --sidebar-bg: #070A08;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #888E89;
            --sidebar-text-hover: #ffffff;
            --sidebar-active-bg: #FD6941;
            --sidebar-active-text: #ffffff;
            --header-bg: #ffffff;

            --success-bg: #ebfbf0;
            --success-border: #bbf7d0;
            --success-text: #36D161;
            --warning-bg: #fff8e7;
            --warning-border: #fef08a;
            --warning-text: #F2AC11;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #dc2626;

            --radius-card: 24px;
            --radius-badge: 9999px;
            --radius-pill: 9999px;
            --shadow-card: 0 2px 14px rgba(0, 0, 0, 0.03);
            --shadow-elevated: 0 10px 30px rgba(7, 10, 8, 0.07);
        }

        [data-theme="dark"] {
            --bg-canvas: #070A08;
            --bg-card: #111513;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-card: rgba(255, 255, 255, 0.1);
            --text-heading: #ffffff;
            --text-body: #d1d5db;
            --text-muted: #888E89;
            --text-light: #6b7280;
            --sidebar-bg: #070A08;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #888E89;
            --sidebar-text-hover: #ffffff;
            --sidebar-active-bg: #FD6941;
            --sidebar-active-text: #ffffff;
            --header-bg: #111513;
            --brand-primary: #FD6941;
            --brand-primary-hover: #e5532b;
            --brand-primary-light: rgba(253, 105, 65, 0.18);
            --brand-accent: #FD6941;
            --success-bg: rgba(54, 209, 97, 0.15);
            --success-border: rgba(54, 209, 97, 0.3);
            --success-text: #36D161;
            --warning-bg: rgba(242, 172, 17, 0.15);
            --warning-border: rgba(242, 172, 17, 0.3);
            --warning-text: #F2AC11;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --danger-border: rgba(239, 68, 68, 0.3);
            --danger-text: #f87171;
            --shadow-card: 0 2px 14px rgba(0, 0, 0, 0.4);
            --shadow-elevated: 0 12px 30px rgba(0, 0, 0, 0.5);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body, button, input, select, textarea, table, th, td, h1, h2, h3, h4, h5, h6, .btn, .badge {
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Attached Typography Specs */
        h1, .heading-32 {
            font-size: 32px !important;
            line-height: 1.2 !important;
            font-weight: 700 !important;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }
        h2, h3, .subheading-18 {
            font-size: 18px !important;
            line-height: 1.2 !important;
            font-weight: 600 !important;
            color: var(--text-heading);
        }
        p, .body-14 {
            font-size: 14px;
            line-height: 1.6;
            font-weight: 400;
            color: var(--text-body);
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
           PAGINATION & SVG SIZE GUARDS (PREVENTS OVERSIZED ARROWS)
           ═══════════════════════════════════════════════════ */
        nav[role="navigation"] svg,
        .pagination svg,
        svg.w-5, svg.h-5,
        .w-5, .h-5 {
            width: 15px !important;
            height: 15px !important;
            max-width: 15px !important;
            max-height: 15px !important;
            display: inline-block;
            vertical-align: middle;
        }

        /* ═══════════════════════════════════════════════════
           EMERALD FOREST SIDEBAR (FOODIO UI MATCH)
           ═══════════════════════════════════════════════════ */
        aside#ownerSidebar {
            width: 240px;
            background: linear-gradient(180deg, #074232 0%, #053326 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: space-between;
            padding: 24px 16px 20px;
            transition: transform 0.25s ease, background 0.2s ease;
            box-shadow: 4px 0 24px rgba(6, 59, 44, 0.08);
            box-sizing: border-box;
        }

        .sidebar-brand-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            padding: 0 8px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 14px;
        }
        .sidebar-brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }
        .sidebar-brand-name {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.03em;
        }

        .sidebar-nav-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: none;
            padding-right: 2px;
        }
        .sidebar-nav-stack::-webkit-scrollbar {
            display: none;
        }

        .sidebar-nav-item {
            width: 100%;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 0 14px;
            gap: 12px;
            color: #a3c4b8;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.18s ease;
            position: relative;
            box-sizing: border-box;
        }
        .sidebar-nav-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.07);
            transform: translateX(2px);
        }
        .sidebar-nav-item.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.14);
            font-weight: 700;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16), 0 4px 14px rgba(0, 0, 0, 0.18);
        }
        .sidebar-nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .sidebar-nav-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-nav-badge {
            background: #ea580c;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            line-height: 1;
        }
        .sidebar-nav-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
        }

        /* Bottom Branch Card in Sidebar */
        .sidebar-branch-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-top: 10px;
            box-sizing: border-box;
        }
        .sidebar-branch-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }
        .sidebar-branch-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .sidebar-branch-details {
            min-width: 0;
            flex: 1;
        }
        .sidebar-branch-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-branch-sub {
            font-size: 11px;
            color: #88a89b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }
        .sidebar-branch-status {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
            font-size: 10.5px;
            font-weight: 700;
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
           TASTYIGNITER TOPBAR & MAIN WRAPPER
           ═══════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: 0;
            padding-top: 0;
            flex: 1;
            min-height: 100vh;
            min-width: 0;
            max-width: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            box-sizing: border-box;
        }

        .main-wrapper main {
            flex: 1;
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 24px 32px 60px;
            box-sizing: border-box;
        }

        @media (min-width: 1024px) {
            aside#ownerSidebar {
                display: none !important;
            }
        }

        header.topbar {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-sizing: border-box;
        }

        [data-theme="dark"] header.topbar {
            background: #111513;
            border-bottom-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
        }

        /* ── 1. Brand Logo Left ── */
        .tasty-brand-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .tasty-flame-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .tasty-brand-text {
            font-size: 21px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            white-space: nowrap;
        }

        /* ── 2. Center Floating Pill Navigation Capsule ── */
        .tasty-nav-capsule {
            display: inline-flex;
            align-items: center;
            background: #f4f5f7;
            border: 1px solid #ebeef2;
            border-radius: 9999px;
            padding: 4px;
            gap: 2px;
        }
        [data-theme="dark"] .tasty-nav-capsule {
            background: #1a201c;
            border-color: rgba(255, 255, 255, 0.08);
        }
        .tasty-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: all 0.16s ease;
            white-space: nowrap;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .tasty-nav-item:hover {
            color: var(--color-dark);
            background: rgba(255, 255, 255, 0.6);
        }
        [data-theme="dark"] .tasty-nav-item {
            color: #9ca3af;
        }
        [data-theme="dark"] .tasty-nav-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }
        /* Active Capsule Nav (Solid Black in Mockup) */
        .tasty-nav-item.active {
            background: var(--color-dark) !important;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(7, 10, 8, 0.2);
        }
        [data-theme="dark"] .tasty-nav-item.active {
            background: #ffffff !important;
            color: #070A08 !important;
        }
        .tasty-nav-badge {
            background: var(--color-coral);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            line-height: 1;
        }

        /* Dropdown within Capsule */
        .tasty-nav-dropdown {
            position: relative;
            display: inline-block;
        }
        .tasty-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            min-width: 190px;
            padding: 8px;
            z-index: 200;
        }
        [data-theme="dark"] .tasty-dropdown-menu {
            background: #1a201c;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .tasty-dropdown-menu.show {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .tasty-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-heading);
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .tasty-dropdown-menu a:hover {
            background: #f4f5f7;
            color: var(--color-coral);
        }
        [data-theme="dark"] .tasty-dropdown-menu a:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* ── 3. Right Action Controls ── */
        .tasty-right-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tasty-circle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-dark);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        [data-theme="dark"] .tasty-circle-btn {
            background: #1a201c;
            border-color: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .tasty-circle-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }
        .tasty-bell-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-coral);
            position: absolute;
            top: 9px;
            right: 9px;
            box-shadow: 0 0 0 2px #ffffff;
        }
        [data-theme="dark"] .tasty-bell-dot {
            box-shadow: 0 0 0 2px #1a201c;
        }

        /* Admin Profile Pill */
        .tasty-admin-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 9999px;
            padding: 4px 12px 4px 4px;
            text-decoration: none;
            color: var(--color-dark);
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            position: relative;
        }
        [data-theme="dark"] .tasty-admin-pill {
            background: #1a201c;
            border-color: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .tasty-admin-pill:hover {
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .tasty-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f4f5f7;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }
        .tasty-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tasty-admin-name {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--color-dark);
        }
        [data-theme="dark"] .tasty-admin-name {
            color: #ffffff;
        }

        .header-greeting-title {
            font-size: 24px;
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
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-card);
            color: var(--text-body);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        [data-theme="dark"] .date-pill {
            background: rgba(19, 27, 46, 0.75);
            border-color: rgba(255, 255, 255, 0.1);
        }

        /* Theme Toggle Button */
        .theme-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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
        [data-theme="dark"] .theme-icon-btn {
            background: rgba(19, 27, 46, 0.75);
            border-color: rgba(255, 255, 255, 0.1);
        }

        /* Notification Bell */
        .notif-bell-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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
        [data-theme="dark"] .notif-bell-btn {
            background: rgba(19, 27, 46, 0.75);
            border-color: rgba(255, 255, 255, 0.1);
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

        /* User Profile Pill (Mockup Match) */
        .user-profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 4px 12px 4px 5px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1px solid var(--border-card);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.15s ease;
        }
        .user-profile-pill:hover {
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        [data-theme="dark"] .user-profile-pill {
            background: #131b2e;
            border-color: rgba(255, 255, 255, 0.1);
        }
        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #064e3b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
        }
        .user-profile-text {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
            text-align: left;
        }
        .user-profile-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-heading);
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-profile-role {
            font-size: 10.5px;
            color: var(--text-muted);
            font-weight: 500;
        }
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
            min-width: 0;
            box-sizing: border-box;
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

        .sidebar-btn-label {
            display: none;
        }
        .sidebar-drawer-header {
            display: none;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        /* Responsive Breakpoints & Universal Mobile Guards */
        @media (max-width: 1024px) {
            aside#ownerSidebar {
                transform: translateX(-100%);
                z-index: 1000;
                width: 260px;
                align-items: stretch;
                padding: 18px 16px 24px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.45);
            }
            aside#ownerSidebar.mobile-open {
                transform: translateX(0);
            }
            .sidebar-backdrop.active {
                display: block;
                opacity: 1;
            }
            .sidebar-drawer-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 14px;
                margin-bottom: 16px;
                border-bottom: 1px solid var(--border-subtle);
            }
            .sidebar-drawer-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: var(--text-heading);
                font-weight: 800;
                font-size: 15px;
            }
            .sidebar-drawer-close {
                width: 32px;
                height: 32px;
                border-radius: 8px;
                background: var(--border-subtle);
                border: none;
                color: var(--text-heading);
                font-size: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }
            .sidebar-brand-badge {
                display: none !important;
            }
            .sidebar-nav-stack {
                align-items: stretch;
                gap: 8px;
            }
            .sidebar-icon-btn {
                width: 100%;
                height: 44px;
                justify-content: flex-start;
                padding: 0 12px;
                gap: 12px;
                border-radius: 10px;
            }
            .sidebar-btn-label {
                display: inline-block;
                font-size: 13px;
                font-weight: 700;
                color: inherit;
            }
            .sidebar-icon-btn::after {
                display: none !important;
            }
            .sidebar-footer-status {
                justify-content: flex-start;
                padding: 10px 12px;
                width: 100%;
            }

            .mobile-menu-toggle {
                display: inline-flex;
            }
            .main-wrapper {
                margin-left: 0 !important;
                padding-top: 68px !important;
                max-width: 100vw !important;
                width: 100% !important;
                min-width: 0 !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }
            header.topbar {
                padding: 0 16px;
                height: 68px;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
            }
            main {
                padding: 14px 16px 84px !important;
                max-width: 100vw !important;
                width: 100% !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }
            .mobile-bottom-nav {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            header.topbar {
                height: 62px !important;
                padding: 0 12px !important;
            }
            .main-wrapper {
                padding-top: 62px !important;
            }
            main {
                padding: 12px 12px 84px !important;
            }
            .header-left {
                gap: 8px !important;
                min-width: 0 !important;
                flex: 1 1 auto !important;
                overflow: hidden !important;
            }
            .header-greeting-wrap {
                min-width: 0 !important;
                overflow: hidden !important;
            }
            .header-greeting-title {
                font-size: 14px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 140px !important;
                line-height: 1.2 !important;
            }
            .header-greeting-sub {
                display: none !important;
            }
            .header-right-actions {
                gap: 6px !important;
                flex-shrink: 0 !important;
            }
            .date-pill {
                display: none !important;
            }
            /* Compact Status Dot Badge */
            .status-pill-toggle {
                padding: 7px 10px !important;
                gap: 0 !important;
                min-width: unset !important;
            }
            #pillStatusLabel, .status-pill-toggle .pill-chevron {
                display: none !important;
            }
            .theme-icon-btn, .notif-bell-btn, .user-avatar-badge {
                width: 34px !important;
                height: 34px !important;
                font-size: 13px !important;
                flex-shrink: 0 !important;
            }

            /* Responsive Panels & Blowout Prevention */
            .panel-card, .card {
                padding: 16px 14px !important;
                border-radius: 14px !important;
                margin-bottom: 16px !important;
            }
            .panel-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }
            .panel-header > div:last-child {
                width: 100% !important;
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
            }
            .bot-alert-banner {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            .bot-alert-banner a.btn {
                width: 100% !important;
                justify-content: center !important;
            }
        }

        @media (max-width: 400px) {
            header.topbar {
                padding: 0 8px !important;
            }
            main {
                padding: 10px 8px 84px !important;
            }
            .header-left {
                gap: 6px !important;
            }
            .header-greeting-title {
                font-size: 13px !important;
                max-width: 100px !important;
            }
            .header-right-actions {
                gap: 4px !important;
            }
            .mobile-menu-toggle, .theme-icon-btn, .notif-bell-btn, .user-avatar-badge {
                width: 32px !important;
                height: 32px !important;
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

    $activeDineInCount = $currentRest ? $currentRest->orders()
        ->where(function ($q) {
            $q->where('order_type', 'dine_in')
              ->orWhereNotNull('table_number')
              ->orWhere('delivery_address', 'LIKE', 'Table %')
              ->orWhere('notes', 'LIKE', '%DINE-IN%');
        })
        ->whereIn('status', ['pending', 'confirmed', 'preparing', 'served'])
        ->whereDate('created_at', now()->today())
        ->count() : 0;
@endphp

<!-- Mobile Backdrop -->
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleOwnerSidebar()"></div>

<!-- SLIM ICON SIDEBAR -->
<aside id="ownerSidebar">
    <!-- Mobile Drawer Header -->
    <div class="sidebar-drawer-header">
        <a href="{{ route('dashboard.orders', $restId) }}" class="sidebar-drawer-brand">
            <span style="font-size: 20px;">🌿</span>
            <span style="color: #ffffff; font-weight: 800; font-size: 18px;">foodio</span>
        </a>
        <button type="button" class="sidebar-drawer-close" onclick="toggleOwnerSidebar()" aria-label="Close menu">✕</button>
    </div>

    <!-- Top Brand Logo -->
    <a href="{{ route('dashboard.orders', $restId) }}" class="sidebar-brand-badge" title="Foodio Restaurant POS">
        <div class="sidebar-brand-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 2v20"/>
                <path d="M6 2v6a3 3 0 0 0 3 3h0a3 3 0 0 0 3-3V2"/>
                <path d="M9 11v11"/>
                <path d="M18 7a3 3 0 0 1-3-3V2h6v2a3 3 0 0 1-3 3Z"/>
            </svg>
        </div>
        <span class="sidebar-brand-name">foodio</span>
    </a>

    <!-- Center Navigation Stack -->
    <div class="sidebar-nav-stack">
        <!-- 1. Dashboard -->
        <a href="{{ route('dashboard.orders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.orders') && !request('view') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Dashboard</span>
        </a>

        <!-- 2. Menu -->
        <a href="{{ route('dashboard.menu', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                    <path d="M7 2v20"/>
                    <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Menu</span>
        </a>

        <!-- 3. POS / Dine In -->
        <a href="{{ route('dashboard.dine-in', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.dine-in*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="5" rx="2"/>
                    <line x1="2" x2="22" y1="10" y2="10"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">POS</span>
            @if($activeDineInCount > 0)
                <span class="sidebar-nav-dot" id="dineInNavBadge"></span>
            @endif
        </a>

        <!-- 4. Orders -->
        <a href="{{ route('dashboard.orders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.orders*') && !request()->routeIs('dashboard.live-orders*') && !request()->routeIs('dashboard.dine-in*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" x2="8" y1="13" y2="13"/>
                    <line x1="16" x2="8" y1="17" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Orders</span>
            @if(isset($pendingCount) && $pendingCount > 0)
                <span class="sidebar-nav-badge">{{ $pendingCount }}</span>
            @endif
        </a>

        <!-- 5. Live Orders -->
        <a href="{{ route('dashboard.live-orders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.live-orders*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/>
                    <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/>
                    <circle cx="12" cy="12" r="2"/>
                    <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/>
                    <path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Live Orders</span>
            @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                <span class="sidebar-nav-badge">{{ $liveOrdersCount }}</span>
            @endif
        </a>

        <!-- 6. Reports -->
        <a href="{{ route('dashboard.reports', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.reports*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3v18h18"/>
                    <path d="m19 9-5 5-4-4-3 3"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Reports</span>
        </a>

        <!-- 7. Restaurant -->
        <a href="{{ route('dashboard.settings', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Restaurant</span>
        </a>

        <!-- 8. Staff / Riders -->
        <a href="{{ route('dashboard.riders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.riders*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Staff</span>
        </a>

        <!-- 9. Customers -->
        <a href="{{ route('dashboard.customers', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.customers*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="19" cy="11" r="3"/>
                    <path d="M23 21v-2a3 3 0 0 0-3-3"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Customers</span>
        </a>

        <!-- 10. Settings -->
        <a href="{{ route('dashboard.settings', $restId) }}" 
           class="sidebar-nav-item">
            <span class="sidebar-nav-icon">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </span>
            <span class="sidebar-nav-label">Settings</span>
        </a>
    </div>

    <!-- Bottom Branch Profile Card -->
    <div class="sidebar-branch-card" onclick="quickToggleRestaurantStatus()" title="Click to toggle Online/Paused">
        <div class="sidebar-branch-avatar">
            <span>👨‍🍳</span>
        </div>
        <div class="sidebar-branch-details">
            <div class="sidebar-branch-title">{{ $currentRest->name ?? 'Foodio Restaurant' }}</div>
            <div class="sidebar-branch-sub">{{ $currentRest->city ? 'Main Branch • ' . $currentRest->city : 'Main Branch' }}</div>
            <div class="sidebar-branch-status">
                <span class="sidebar-online-dot {{ $isOpen ? '' : 'paused' }}" id="sidebarStatusDot"></span>
                <span id="sidebarStatusText" style="color: {{ $isOpen ? '#34d399' : '#fbbf24' }};">{{ $isOpen ? 'Online' : 'Paused' }}</span>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <!-- TOPBAR (EXACT TASTYIGNITER MOCKUP MATCH) -->
    <header class="topbar">
        <!-- 1. LEFT: Brand Flame Logo & Name -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <button type="button" class="mobile-menu-toggle" onclick="toggleOwnerSidebar()" aria-label="Open menu" style="display: none;">
                ☰
            </button>
            <a href="{{ route('dashboard.live-orders', $restId) }}" class="tasty-brand-wrap" title="{{ $currentRest->name ?? 'TastyIgniter' }}">
                <span class="tasty-flame-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C10.5 5 7.5 7.5 7.5 11.5C7.5 14.5 9.5 16 11 16.5C10.5 15.5 10.5 14 11.5 13C12.5 14 13 15 13 16C15 15.5 16.5 13.8 16.5 11.5C16.5 6.5 12 2 12 2Z" fill="#FD6941"/>
                        <path d="M12 22C16.4183 22 20 18.4183 20 14C20 9.5 16 6 12 2C8 6 4 9.5 4 14C4 18.4183 7.58172 22 12 22Z" fill="#FD6941" fill-opacity="0.15" stroke="#FD6941" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="tasty-brand-text">{{ $currentRest->name ?? 'TastyIgniter' }}</span>
            </a>
        </div>

        <!-- 2. CENTER: Floating Pill Navigation Capsule (Mockup Identical) -->
        <nav class="tasty-nav-capsule">
            <a href="{{ route('dashboard.orders', $restId) }}" class="tasty-nav-item {{ request()->routeIs('dashboard.orders') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('dashboard.menu', $restId) }}" class="tasty-nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}">
                Menu
            </a>
            <a href="{{ route('dashboard.live-orders', $restId) }}" class="tasty-nav-item {{ request()->routeIs('dashboard.live-orders*') ? 'active' : '' }}">
                Order
                @if(isset($liveOrdersCount) && $liveOrdersCount > 0)
                    <span class="tasty-nav-badge">{{ $liveOrdersCount }}</span>
                @endif
            </a>
            <a href="{{ route('dashboard.dine-in', $restId) }}" class="tasty-nav-item {{ request()->routeIs('dashboard.dine-in*') ? 'active' : '' }}">
                Tables
            </a>
            <a href="{{ route('dashboard.dine-in', $restId) }}" class="tasty-nav-item">
                VIP Rooms
            </a>
            <a href="{{ route('dashboard.reports', $restId) }}" class="tasty-nav-item {{ request()->routeIs('dashboard.reports*') ? 'active' : '' }}">
                Sales
            </a>

            <!-- More Links Dropdown -->
            <div class="tasty-nav-dropdown">
                <button type="button" class="tasty-nav-item" onclick="toggleTastyDropdown(event)">
                    More <span style="font-size: 10px; margin-left: 2px;">▾</span>
                </button>
                <div class="tasty-dropdown-menu" id="tastyMoreDropdown">
                    <a href="{{ route('dashboard.connect-whatsapp', $restId) }}">🤖 WhatsApp Bot</a>
                    <a href="{{ route('dashboard.customers', $restId) }}">👥 Customers</a>
                    <a href="{{ route('dashboard.riders', $restId) }}">🚴 Fleet & Riders</a>
                    <a href="{{ route('dashboard.daily-closing', $restId) }}">📁 Daily Closing</a>
                    <a href="{{ route('dashboard.history', $restId) }}">📜 Order History</a>
                    <a href="{{ route('dashboard.settings', $restId) }}">⚙️ Settings</a>
                </div>
            </div>
        </nav>

        <!-- 3. RIGHT: Headset, Settings, Bell, Admin Pill -->
        <div class="tasty-right-actions">
            <!-- Headset Support Circle -->
            <a href="https://wa.me/923000000000" target="_blank" class="tasty-circle-btn" title="Contact Support">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                </svg>
            </a>

            <!-- Settings Gear Circle -->
            <a href="{{ route('dashboard.settings', $restId) }}" class="tasty-circle-btn" title="Settings">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </a>

            <!-- Bell Circle with Coral Alert Dot -->
            <a href="{{ route('dashboard.live-orders', $restId) }}" class="tasty-circle-btn" title="Live Orders">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="tasty-bell-dot" style="display: {{ (isset($pendingCount) && $pendingCount > 0) || (isset($liveOrdersCount) && $liveOrdersCount > 0) ? 'block' : 'none' }};"></span>
            </a>

            <!-- Admin Profile Pill (Mockup Match: Avatar + Name + Chevron) -->
            <a href="{{ route('dashboard.settings', $restId) }}" class="tasty-admin-pill" title="Admin Account">
                <div class="tasty-avatar-circle">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&auto=format&fit=crop&q=80" alt="Admin" class="tasty-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='👩‍💼';">
                </div>
                <span class="tasty-admin-name">Admin</span>
                <span style="font-size: 10px; color: var(--color-gray); margin-left: 2px;">▾</span>
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

        <a href="{{ route('dashboard.dine-in', $restId) }}" class="mob-nav-item {{ request()->routeIs('dashboard.dine-in*') ? 'active' : '' }}" style="position: relative;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21h18"/>
                <path d="M5 21v-7"/>
                <path d="M19 21v-7"/>
                <path d="M4 10h16"/>
                <path d="M6 10V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v5"/>
            </svg>
            <span>Dine In</span>
            @if($activeDineInCount > 0)
                <span style="position: absolute; top: 2px; right: calc(50% - 16px); width: 8px; height: 8px; background: #ea580c; border-radius: 50%; border: 1.5px solid #fff;"></span>
            @endif
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
function toggleTastyDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('tastyMoreDropdown');
    if (m) m.classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const m = document.getElementById('tastyMoreDropdown');
    if (m && !e.target.closest('.tasty-nav-dropdown')) {
        m.classList.remove('show');
    }
});

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

document.addEventListener('DOMContentLoaded', () => {
    initOwnerTheme();

    // Instant Navbar Link Hover Prefetcher (Zero delay / zero glitch)
    const navLinks = document.querySelectorAll('aside#ownerSidebar a[href], .mobile-bottom-nav a[href]');
    const prefetchedUrls = new Set();
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', () => {
            const url = link.href;
            if (!url || url.includes('javascript:') || prefetchedUrls.has(url)) return;
            prefetchedUrls.add(url);
            const prefetchEl = document.createElement('link');
            prefetchEl.rel = 'prefetch';
            prefetchEl.href = url;
            document.head.appendChild(prefetchEl);
        }, { passive: true });
    });
});
</script>

</body>
</html>