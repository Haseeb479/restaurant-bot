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

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#114232">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/icon.svg">

    <!-- Google Fonts: Editorial Serif & Clean Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Service Worker Registration for Desktop App -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>

    <style>
        :root {
            --bg-canvas: #F2F4EE;
            --bg-card: #FFFFFF;
            --border-subtle: #EAECE4;
            --border-card: #EAECE4;

            /* Exact HEX Palette from Attached System (Vessa / AuraBistro Editorial) */
            --color-forest: #114232;
            --color-forest-hover: #0b3125;
            --color-forest-light: #E8EFEA;
            --color-ochre: #C89B53;
            --color-coral: #E07A5F;
            --color-green: #10B981;
            --color-dark: #121915;
            --color-gray: #717A74;
            --color-light-gray: #A3ACA5;

            --text-heading: #121915;
            --text-body: #374151;
            --text-muted: #717A74;
            --text-light: #9ca3af;

            --brand-primary: #114232;
            --brand-primary-hover: #0b3125;
            --brand-primary-light: #E8EFEA;
            --brand-accent: #C89B53;

            --sidebar-bg: #FFFFFF;
            --sidebar-border: #EAECE4;
            --sidebar-text: #717A74;
            --sidebar-text-hover: #121915;
            --sidebar-active-bg: #114232;
            --sidebar-active-text: #FFFFFF;
            --header-bg: transparent;

            --success-bg: #E8EFEA;
            --success-border: #CBE0D4;
            --success-text: #114232;
            --warning-bg: #FFF8E7;
            --warning-border: #F6E1B6;
            --warning-text: #C89B53;
            --danger-bg: #FDF2F0;
            --danger-border: #F8D3CC;
            --danger-text: #E07A5F;

            --radius-card: 26px;
            --radius-badge: 9999px;
            --radius-pill: 9999px;
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.02);
            --shadow-elevated: 0 14px 38px rgba(17, 66, 50, 0.06);

            /* Motion Design System Tokens */
            --ease-out-smooth: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-in-out-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --duration-instant: 150ms;
            --duration-fast: 200ms;
            --duration-normal: 260ms;
            --duration-deliberate: 380ms;
            --duration-chart: 650ms;
        }

        [data-theme="dark"] {
            --bg-canvas: #0C120F;
            --bg-card: #141B17;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-card: rgba(255, 255, 255, 0.09);
            --text-heading: #F4F6F5;
            --text-body: #C2CBC6;
            --text-muted: #839189;
            --text-light: #5E6C64;
            --sidebar-bg: #141B17;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #839189;
            --sidebar-text-hover: #FFFFFF;
            --sidebar-active-bg: #1E5C47;
            --sidebar-active-text: #FFFFFF;
            --header-bg: transparent;
            --brand-primary: #1E5C47;
            --brand-primary-hover: #164837;
            --brand-primary-light: rgba(30, 92, 71, 0.25);
            --brand-accent: #D7AB63;
            --color-forest: #1E5C47;
            --color-forest-hover: #164837;
            --color-forest-light: rgba(30, 92, 71, 0.25);
            --color-dark: #FFFFFF;
            --success-bg: rgba(30, 92, 71, 0.2);
            --success-border: rgba(30, 92, 71, 0.4);
            --success-text: #4ADE80;
            --warning-bg: rgba(200, 155, 83, 0.18);
            --warning-border: rgba(200, 155, 83, 0.35);
            --warning-text: #EAB308;
            --danger-bg: rgba(224, 122, 95, 0.2);
            --danger-border: rgba(224, 122, 95, 0.35);
            --danger-text: #F87171;
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.35);
            --shadow-elevated: 0 14px 38px rgba(0, 0, 0, 0.5);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body, button, input, select, textarea, table, th, td, h1, h2, h3, h4, h5, h6, .btn, .badge {
            font-family: 'Plus Jakarta Sans', 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
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
           SLIM FLOATING DOCK & TOPBAR (VESSA UI MATCH)
           ═══════════════════════════════════════════════════ */
        aside#ownerSidebar {
            width: 76px;
            background: var(--sidebar-bg);
            border: 1px solid var(--border-subtle);
            border-radius: 28px;
            height: calc(100vh - 36px);
            position: fixed;
            top: 18px;
            bottom: 18px;
            left: 18px;
            z-index: 120;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 16px 10px 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            box-sizing: border-box;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), background 0.2s ease;
        }

        .sidebar-brand-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 0;
            margin-bottom: 8px;
        }
        .sidebar-brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 18px;
            background: var(--color-forest);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(17, 66, 50, 0.25);
            flex-shrink: 0;
            transition: transform 0.2s ease;
            color: #ffffff;
        }
        .sidebar-brand-icon:hover {
            transform: scale(1.04);
        }
        .sidebar-brand-name {
            display: none !important;
        }

        .sidebar-nav-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            flex: 1;
            width: 100%;
            margin-top: 10px;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar-nav-stack::-webkit-scrollbar {
            display: none;
        }

        .sidebar-nav-item {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            box-sizing: border-box;
        }
        .sidebar-nav-item:hover {
            color: var(--sidebar-text-hover);
            background: rgba(17, 66, 50, 0.07);
            transform: translateY(-1px);
        }
        [data-theme="dark"] .sidebar-nav-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .sidebar-nav-item.active {
            color: #ffffff !important;
            background: var(--color-forest) !important;
            box-shadow: 0 4px 14px rgba(17, 66, 50, 0.28);
        }
        [data-theme="dark"] .sidebar-nav-item.active {
            background: var(--color-forest) !important;
            box-shadow: 0 4px 14px rgba(30, 92, 71, 0.4);
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
            display: none !important;
        }
        .sidebar-nav-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--color-coral);
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            min-width: 17px;
            height: 17px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--sidebar-bg);
            line-height: 1;
        }
        .sidebar-nav-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            border: 2px solid var(--sidebar-bg);
        }

        .sidebar-bottom-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding-top: 10px;
            border-top: 1px solid var(--border-subtle);
        }
        .sidebar-drawer-header {
            display: none;
        }

        /* ═══════════════════════════════════════════════════
           MAIN WRAPPER & TOPBAR (EXACT MATCH TO REFERENCE)
           ═══════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: 104px;
            padding-top: 0;
            flex: 1;
            min-height: 100vh;
            min-width: 0;
            max-width: calc(100vw - 104px);
            width: calc(100vw - 104px);
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
            padding: 10px 32px 60px 20px;
            box-sizing: border-box;
        }

        header.topbar {
            height: 74px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 32px 10px 20px;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-sizing: border-box;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* ── 1. Brand Logo Left ── */
        .vessa-brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .vessa-brand-text {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.03em;
            white-space: nowrap;
        }
        .vessa-layout-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .vessa-layout-btn:hover {
            color: var(--text-heading);
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        /* ── 2. Center Floating Pill Navigation Capsule ── */
        .vessa-segmented-dock {
            display: inline-flex;
            align-items: center;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 9999px;
            padding: 4px;
            gap: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }
        .vessa-dock-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 22px;
            border-radius: 9999px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.16s ease;
            white-space: nowrap;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .vessa-dock-pill:hover {
            color: var(--text-heading);
            background: rgba(17, 66, 50, 0.05);
        }
        [data-theme="dark"] .vessa-dock-pill:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        .vessa-dock-pill.active {
            background: var(--color-forest) !important;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(17, 66, 50, 0.25);
        }
        .vessa-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
        }

        /* ── 3. Right Action Controls ── */
        .vessa-right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Search Pill */
        .vessa-search-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 9999px;
            height: 42px;
            padding: 0 16px;
            color: var(--text-muted);
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: all 0.15s ease;
        }
        .vessa-search-pill:hover {
            border-color: #cbd5e1;
            color: var(--text-heading);
        }
        .vessa-search-placeholder {
            font-size: 13px;
            font-weight: 500;
        }
        .vessa-search-kbd {
            font-size: 10.5px;
            font-weight: 700;
            background: var(--bg-canvas);
            border: 1px solid var(--border-subtle);
            padding: 2px 6px;
            border-radius: 6px;
            color: var(--text-muted);
        }

        /* Circle Icon Buttons */
        .vessa-circle-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-heading);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        .vessa-circle-btn:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .vessa-bell-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-coral);
            position: absolute;
            top: 10px;
            right: 10px;
            box-shadow: 0 0 0 2px var(--bg-card);
        }

        /* Solid Forest Green "+ New" Button */
        .vessa-new-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--color-forest);
            color: #ffffff;
            height: 42px;
            padding: 0 20px;
            border-radius: 9999px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(17, 66, 50, 0.22);
            transition: all 0.18s ease;
            border: none;
            cursor: pointer;
        }
        .vessa-new-btn:hover {
            background: var(--color-forest-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(17, 66, 50, 0.3);
            color: #ffffff;
        }

        /* Owner Greeting & Avatar */
        .vessa-user-greeting-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 4px;
        }
        .vessa-greeting-line {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-heading);
            white-space: nowrap;
        }
        .vessa-avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--color-forest-light);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            color: var(--color-forest);
            border: 1.5px solid var(--border-subtle);
            overflow: visible;
        }
        .vessa-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .vessa-avatar-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            position: absolute;
            bottom: 0;
            right: 0;
            border: 2px solid var(--bg-card);
        }
        .vessa-avatar-status-dot.paused {
            background: #f59e0b;
        }

        @media (max-width: 1023px) {
            aside#ownerSidebar {
                transform: translateX(-120%);
                top: 0;
                bottom: 0;
                left: 0;
                height: 100vh;
                border-radius: 0 24px 24px 0;
                width: 80px;
            }
            aside#ownerSidebar.open {
                transform: translateX(0);
            }
            .sidebar-drawer-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin-bottom: 12px;
            }
            .main-wrapper {
                margin-left: 0 !important;
                max-width: 100vw !important;
                width: 100vw !important;
            }
            .main-wrapper main {
                padding: 12px 14px 80px;
            }
            header.topbar {
                padding: 10px 16px;
            }
            .mobile-menu-toggle {
                display: inline-flex !important;
                background: var(--bg-card);
                border: 1px solid var(--border-subtle);
                border-radius: 10px;
                padding: 8px 12px;
                font-size: 16px;
                cursor: pointer;
            }
            .vessa-search-pill, .vessa-greeting-line {
                display: none;
            }
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

        /* ═════════════════════════════════════════════════════════
           1. PAGE SWITCHING — PAGE TRANSITION ANIMATION (Fade + Slide)
           ═════════════════════════════════════════════════════════ */
        .foodio-page-container {
            animation: foodioFadeSlideIn var(--duration-normal) var(--ease-out-smooth) both;
            will-change: opacity, transform;
        }

        @keyframes foodioFadeSlideIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .foodio-page-container.foodio-page-exit {
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity 150ms var(--ease-out-smooth), transform 150ms var(--ease-out-smooth);
        }

        /* ═════════════════════════════════════════════════════════
           2. SIDEBAR NAVIGATION — SLIDING ACTIVE INDICATOR
           ═════════════════════════════════════════════════════════ */
        .sidebar-nav-stack {
            position: relative;
        }

        .sidebar-active-indicator {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--color-forest);
            box-shadow: 0 4px 14px rgba(17, 66, 50, 0.28);
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            transition: top var(--duration-normal) var(--ease-out-smooth),
                        transform var(--duration-normal) var(--ease-out-smooth),
                        opacity var(--duration-fast) var(--ease-out-smooth);
        }
        [data-theme="dark"] .sidebar-active-indicator {
            background: var(--color-forest);
            box-shadow: 0 4px 14px rgba(30, 92, 71, 0.45);
        }

        .sidebar-nav-item {
            z-index: 1;
            transition: transform var(--duration-fast) var(--ease-out-smooth),
                        color var(--duration-fast) var(--ease-out-smooth),
                        background var(--duration-fast) var(--ease-out-smooth) !important;
        }
        .sidebar-nav-item:hover {
            transform: scale(1.06) !important;
        }
        .sidebar-nav-item:active {
            transform: scale(0.92) !important;
            transition-duration: 80ms !important;
        }
        .sidebar-nav-item.active {
            transition: color var(--duration-fast) var(--ease-out-smooth) !important;
        }

        /* ═════════════════════════════════════════════════════════
           7. BUTTONS AND CONTROLS — MICRO-INTERACTIONS
           ═════════════════════════════════════════════════════════ */
        .btn, 
        .vessa-btn-forest, 
        .vessa-btn-outline, 
        .vessa-btn-primary, 
        .cat-pill, 
        .vessa-capsule-item, 
        .vessa-dropdown-pill, 
        .status-pill-toggle,
        .sidebar-brand-icon,
        .vessa-action-btn-circle {
            transition: background-color var(--duration-fast) var(--ease-out-smooth),
                        border-color var(--duration-fast) var(--ease-out-smooth),
                        color var(--duration-fast) var(--ease-out-smooth),
                        box-shadow var(--duration-fast) var(--ease-out-smooth),
                        transform var(--duration-fast) var(--ease-out-smooth) !important;
        }

        .btn:hover, 
        .vessa-btn-forest:hover, 
        .vessa-btn-outline:hover,
        .cat-pill:hover,
        .vessa-dropdown-pill:hover {
            transform: translateY(-1px);
        }

        .btn:active, 
        .vessa-btn-forest:active, 
        .vessa-btn-outline:active, 
        .cat-pill:active, 
        .vessa-capsule-item:active,
        .vessa-dropdown-pill:active,
        .status-pill-toggle:active {
            transform: scale(0.96) translateY(0) !important;
            transition-duration: 75ms !important;
        }

        .btn:focus-visible, 
        button:focus-visible, 
        a:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid var(--color-forest) !important;
            outline-offset: 2px !important;
        }

        /* ═════════════════════════════════════════════════════════
           9. LOADING & EMPTY STATES (Skeleton Shimmer & Fade)
           ═════════════════════════════════════════════════════════ */
        .foodio-skeleton {
            background: linear-gradient(90deg, var(--border-subtle) 25%, rgba(255,255,255,0.45) 50%, var(--border-subtle) 75%);
            background-size: 200% 100%;
            animation: foodioShimmer 1.5s infinite var(--ease-in-out-smooth);
            border-radius: 8px;
        }
        [data-theme="dark"] .foodio-skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.12) 50%, rgba(255,255,255,0.05) 75%);
            background-size: 200% 100%;
        }
        @keyframes foodioShimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .foodio-empty-state {
            animation: foodioFadeSlideIn var(--duration-deliberate) var(--ease-out-smooth) both;
        }

        /* ═════════════════════════════════════════════════════════
           10. ACCESSIBILITY — PREFERS-REDUCED-MOTION
           ═════════════════════════════════════════════════════════ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .sidebar-active-indicator {
                display: none !important;
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
        ->dineIn()
        ->whereIn('status', ['pending', 'confirmed', 'preparing', 'served'])
        ->whereDate('created_at', now()->today())
        ->count() : 0;

    $liveDeliveryOrdersCount = $currentRest ? $currentRest->orders()
        ->deliveryOrders()
        ->whereIn('status', ['pending', 'confirmed', 'preparing', 'out_for_delivery'])
        ->whereDate('created_at', now()->today())
        ->count() : 0;
@endphp

<!-- Mobile Backdrop -->
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleOwnerSidebar()"></div>

<!-- SLIM ICON SIDEBAR (VESSA / REFERENCE DOCK) -->
<aside id="ownerSidebar">
    <!-- Top Brand Logo -->
    <a href="{{ route('dashboard.orders', $restId) }}" class="sidebar-brand-badge" title="{{ $currentRest->name ?? 'Foodio' }} Restaurant POS">
        <div class="sidebar-brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2C8 2 4 6 4 11c0 5.25 8 11 8 11s8-5.75 8-11c0-5-4-9-8-9z"/>
                <circle cx="12" cy="11" r="3"/>
            </svg>
        </div>
    </a>

    <!-- Center Navigation Stack -->
    <div class="sidebar-nav-stack">
        <!-- 1. Dashboard / Overview -->
        <a href="{{ route('dashboard.orders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.orders') && !request('tab') ? 'active' : '' }}"
           title="Dashboard Overview">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>
            </span>
        </a>

        <!-- 2. Orders / Live Cart Queue -->
        <a href="{{ route('dashboard.live-orders', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.live-orders*') ? 'active' : '' }}"
           title="Orders & Live Cart">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                    <path d="M3 6h18"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </span>
            @if(isset($pendingCount) && $pendingCount > 0)
                <span class="sidebar-nav-badge">{{ $pendingCount }}</span>
            @elseif(isset($liveDeliveryOrdersCount) && $liveDeliveryOrdersCount > 0)
                <span class="sidebar-nav-badge">{{ $liveDeliveryOrdersCount }}</span>
            @endif
        </a>

        <!-- 3. Tables / Dine In Floor -->
        <a href="{{ route('dashboard.dine-in', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.dine-in*') ? 'active' : '' }}"
           title="Tables & Dine-in Floor">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="3"/>
                    <path d="M3 9h18"/>
                    <path d="M9 21V9"/>
                </svg>
            </span>
            @if($activeDineInCount > 0)
                <span class="sidebar-nav-dot" id="dineInNavBadge"></span>
            @endif
        </a>

        <!-- 4. Menu & Dish Catalog -->
        <a href="{{ route('dashboard.menu', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.menu*') ? 'active' : '' }}"
           title="Menu Catalog">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                    <path d="M7 2v20"/>
                    <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                </svg>
            </span>
        </a>

        <!-- 5. Analytics & Reports -->
        <a href="{{ route('dashboard.reports', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.reports*') ? 'active' : '' }}"
           title="Reports & Analytics">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" x2="18" y1="20" y2="10"/>
                    <line x1="12" x2="12" y1="20" y2="4"/>
                    <line x1="6" x2="6" y1="20" y2="14"/>
                </svg>
            </span>
        </a>

        <!-- 6. Customers (CRM) -->
        <a href="{{ route('dashboard.customers', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.customers*') ? 'active' : '' }}"
           title="Customers CRM">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
        </a>

        <!-- 7. Settings / Configuration -->
        <a href="{{ route('dashboard.settings', $restId) }}" 
           class="sidebar-nav-item {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}"
           title="Settings">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </span>
            <span class="sidebar-nav-dot" style="background: #10b981;"></span>
        </a>
    </div>

    <!-- Bottom Stack: History & Logout -->
    <div class="sidebar-bottom-stack">
        <!-- History / Daily Closing -->
        <a href="{{ route('dashboard.daily-closing', $restId) }}" class="sidebar-nav-item {{ request()->routeIs('dashboard.daily-closing*') ? 'active' : '' }}" title="Order History & Daily Closing">
            <span class="sidebar-nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </span>
        </a>

        <!-- Store Status Toggle / Power -->
        <button type="button" class="sidebar-nav-item" onclick="quickToggleRestaurantStatus()" title="Toggle Restaurant {{ $isOpen ? 'Online' : 'Paused' }}" style="background: transparent; border: none; cursor: pointer;">
            <span class="sidebar-nav-icon" style="color: {{ $isOpen ? '#10b981' : '#f59e0b' }};">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"/>
                    <line x1="12" x2="12" y1="2" y2="12"/>
                </svg>
            </span>
        </button>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <!-- TOPBAR (REFERENCE UI EXACT MATCH) -->
    <header class="topbar">
        <!-- 1. LEFT: Brand Wordmark & Grid Layout Icon -->
        <div style="display: flex; align-items: center; gap: 14px;">
            <button type="button" class="mobile-menu-toggle" onclick="toggleOwnerSidebar()" aria-label="Open menu" style="display: none;">
                ☰
            </button>
            <a href="{{ route('dashboard.orders', $restId) }}" class="vessa-brand-wrap" title="{{ $currentRest->name ?? 'Foodio' }}">
                <span class="vessa-brand-text">{{ strtolower($currentRest->name ?? 'foodio') }}</span>
            </a>
            <button type="button" class="vessa-layout-btn" title="Toggle Bento Grid View" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="7" x="3" y="3" rx="1.5"/>
                    <rect width="7" height="7" x="14" y="3" rx="1.5"/>
                    <rect width="7" height="7" x="14" y="14" rx="1.5"/>
                    <rect width="7" height="7" x="3" y="14" rx="1.5"/>
                </svg>
            </button>
        </div>

        <!-- 2. CENTER: Segmented Floating Capsule Dock -->
        <nav class="vessa-segmented-dock">
            <a href="{{ route('dashboard.orders', $restId) }}" class="vessa-dock-pill {{ request()->routeIs('dashboard.orders') && !request('tab') ? 'active' : '' }}" onclick="switchVessaTab('overview', event)">
                Overview
            </a>
            <a href="{{ route('dashboard.orders', $restId) }}?tab=activity" class="vessa-dock-pill {{ request('tab') === 'activity' || request()->routeIs('dashboard.live-orders*') ? 'active' : '' }}" onclick="switchVessaTab('activity', event)">
                Activity
            </a>
            <a href="{{ route('dashboard.orders', $restId) }}?tab=alerts" class="vessa-dock-pill {{ request('tab') === 'alerts' ? 'active' : '' }}" onclick="switchVessaTab('alerts', event)">
                <span class="vessa-pulse-dot"></span>
                Alerts
            </a>
        </nav>

        <!-- 3. RIGHT: Search, Bell, Theme, +New Button & Owner Profile -->
        <div class="vessa-right-actions">
            <!-- Search Pill -->
            <div class="vessa-search-pill" onclick="document.getElementById('globalOrderSearch')?.focus(); const s = document.getElementById('activityWorkspaceSection'); if(s) s.scrollIntoView({behavior:'smooth'});">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <span class="vessa-search-placeholder">Search</span>
                <kbd class="vessa-search-kbd">⌘K</kbd>
            </div>

            <!-- Notification Bell with Coral Alert Dot -->
            <a href="{{ route('dashboard.live-orders', $restId) }}" class="vessa-circle-btn" title="Live Alerts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="vessa-bell-dot" id="tastyBellDot" style="display: {{ (isset($pendingCount) && $pendingCount > 0) || (isset($liveDeliveryOrdersCount) && $liveDeliveryOrdersCount > 0) ? 'block' : 'none' }};"></span>
            </a>

            <!-- Dark / Light Theme Toggle -->
            <button type="button" class="vessa-circle-btn" onclick="toggleOwnerTheme()" title="Toggle Theme" aria-label="Toggle Theme">
                <span id="themeToggleIcon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                </span>
            </button>

            <!-- Solid Forest Green + New Order Button -->
            <a href="{{ route('dashboard.dine-in', $restId) }}" class="vessa-new-btn" title="Create New Walk-in / Order">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <span>New</span>
            </a>

            <!-- Owner Profile & Avatar -->
            <div class="vessa-user-greeting-wrap">
                <span class="vessa-greeting-line">{{ $timeGreeting }}, {{ $ownerFirstName }}</span>
                <a href="{{ route('dashboard.settings', $restId) }}" class="vessa-avatar-circle" title="{{ $ownerName }} (Owner)">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="{{ $ownerFirstName }}" class="vessa-avatar-img" onerror="this.onerror=null;this.parentElement.innerHTML='{{ substr($ownerFirstName, 0, 1) }}';">
                    <span class="vessa-avatar-status-dot {{ $isOpen ? '' : 'paused' }}" id="sidebarStatusDot" title="Restaurant {{ $isOpen ? 'Online' : 'Paused' }}"></span>
                </a>
            </div>
        </div>
    </header>

    <main class="foodio-page-container">
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
        icon.innerHTML = t === 'dark' 
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>';
    }
}

// ── Vessa Topbar Tab Switcher ──────────────────────────
function switchVessaTab(tab, e) {
    if (window.location.pathname.includes('/orders') && !window.location.search.includes('view=')) {
        if (e) e.preventDefault();
        document.querySelectorAll('.vessa-dock-pill').forEach(p => p.classList.remove('active'));
        if (e && e.currentTarget) e.currentTarget.classList.add('active');
        
        if (tab === 'overview') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (tab === 'activity') {
            const el = document.getElementById('activityWorkspaceSection');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        } else if (tab === 'alerts') {
            const el = document.getElementById('liveNotifCard') || document.getElementById('activityWorkspaceSection');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }
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

// ── Global Real-Time Navigation Badges & Notification Poller ──
(function() {
    const orderBadge   = document.getElementById('tastyOrderBadge');
    const tablesBadge  = document.getElementById('tastyTablesBadge');
    const bellDot      = document.getElementById('tastyBellDot');
    const sideBadge    = document.getElementById('sidebarLiveBadge');
    const sideDineDot  = document.getElementById('dineInNavBadge');
    const badge        = document.getElementById('notif-badge');

    async function pollLiveCounters() {
        try {
            const res = await fetch("{{ route('dashboard.orders.live-feed', $restId) }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.success) return;

            // 1. Live WhatsApp Delivery / Takeaway count
            if (typeof data.live_delivery_count !== 'undefined') {
                const delCount = parseInt(data.live_delivery_count, 10);
                if (orderBadge) {
                    orderBadge.textContent = delCount;
                    orderBadge.style.display = delCount > 0 ? 'inline-block' : 'none';
                }
                if (sideBadge) {
                    sideBadge.textContent = delCount;
                    sideBadge.style.display = delCount > 0 ? 'inline-block' : 'none';
                }
            }

            // 2. Active Dine-In Table Sessions count
            if (typeof data.active_dine_in_count !== 'undefined') {
                const dineCount = parseInt(data.active_dine_in_count, 10);
                if (tablesBadge) {
                    tablesBadge.textContent = dineCount;
                    tablesBadge.style.display = dineCount > 0 ? 'inline-block' : 'none';
                }
                if (sideDineDot) {
                    sideDineDot.style.display = dineCount > 0 ? 'inline-block' : 'none';
                }
            }

            // 3. Bell Dot (Coral dot alert for incoming delivery orders)
            if (bellDot) {
                const pend = parseInt(data.pending_count || 0, 10);
                const deliv = parseInt(data.live_delivery_count || 0, 10);
                bellDot.style.display = (pend > 0 || deliv > 0) ? 'block' : 'none';
            }

            // 4. Mobile / Drawer notification badge
            if (badge && typeof data.pending_count !== 'undefined') {
                const pending = parseInt(data.pending_count, 10);
                badge.style.display = pending > 0 ? 'block' : 'none';
            }
        } catch (e) {}
    }

    // Run immediately and every 3 seconds for instant real-time sync across pages
    pollLiveCounters();
    setInterval(pollLiveCounters, 3000);
})();

document.addEventListener('DOMContentLoaded', () => {
    initOwnerTheme();

    // ── 1. Page Switching Route Transition Animation (Fade + Slide) ──
    const pageContainer = document.querySelector('.foodio-page-container');
    if (pageContainer) {
        document.querySelectorAll('aside#ownerSidebar a[href], header.topbar a[href], .mobile-bottom-nav a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.hasAttribute('download')) return;
            if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

            link.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (link.href === window.location.href) return;

                e.preventDefault();
                pageContainer.classList.add('foodio-page-exit');
                setTimeout(() => {
                    window.location.href = href;
                }, 130);
            });
        });
    }

    // ── 2. Sidebar Navigation Sliding Active Indicator ──
    const navStack = document.querySelector('.sidebar-nav-stack');
    if (navStack) {
        const activeItem = navStack.querySelector('.sidebar-nav-item.active');
        const indicator = document.createElement('div');
        indicator.className = 'sidebar-active-indicator';
        navStack.insertBefore(indicator, navStack.firstChild);

        function updateIndicatorPos(targetEl, animate = true) {
            if (!targetEl) {
                indicator.style.opacity = '0';
                return;
            }
            if (!animate) {
                indicator.style.transition = 'none';
            } else {
                indicator.style.transition = 'top var(--duration-normal) var(--ease-out-smooth), opacity var(--duration-fast) var(--ease-out-smooth)';
            }
            indicator.style.top = targetEl.offsetTop + 'px';
            indicator.style.opacity = '1';
        }

        if (activeItem) {
            updateIndicatorPos(activeItem, false);
            // Once positioned, remove individual active background to avoid double rendering
            activeItem.style.background = 'transparent';
        }

        navStack.querySelectorAll('.sidebar-nav-item').forEach(item => {
            item.addEventListener('mouseenter', () => updateIndicatorPos(item, true));
            item.addEventListener('focus', () => updateIndicatorPos(item, true));
        });

        navStack.addEventListener('mouseleave', () => {
            const current = navStack.querySelector('.sidebar-nav-item.active') || activeItem;
            updateIndicatorPos(current, true);
        });
    }

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

// ── Desktop App Install Trigger ──
let deferredDesktopPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredDesktopPrompt = e;
    const btn = document.getElementById('headerInstallDesktopBtn');
    if (btn) btn.style.display = 'inline-flex';
});

function triggerDesktopAppInstall() {
    if (deferredDesktopPrompt) {
        deferredDesktopPrompt.prompt();
        deferredDesktopPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                const btn = document.getElementById('headerInstallDesktopBtn');
                if (btn) btn.style.display = 'none';
            }
            deferredDesktopPrompt = null;
        });
    } else {
        alert("To install as a Desktop App:\nClick the Install icon in the right side of your browser address bar (top right) or press Menu (⋮) -> 'Install Foodio POS'.");
    }
}
</script>

</body>
</html>