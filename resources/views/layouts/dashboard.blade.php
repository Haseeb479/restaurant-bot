<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ $restaurant->name ?? ($r->name ?? 'Restaurant Owner') }}</title>

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
            <div class="status-online-pill" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                <span>Online</span>
            </div>

            <div style="position: relative; width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer;">
                🔔
                <span style="position: absolute; top: -3px; right: -3px; width: 16px; height: 16px; border-radius: 50%; background: #ef4444; color: #fff; font-size: 9px; font-weight: 800; display: flex; align-items: center; justify-content: center;">3</span>
            </div>

            <div class="user-profile">
                <div class="avatar">{{ strtoupper(substr($currentRest->name ?? 'TB', 0, 2)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ $currentRest->name ?? 'Restaurant Owner' }} ▾</div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 12px; font-weight: 700; color: #334155;">
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

</body>
</html>