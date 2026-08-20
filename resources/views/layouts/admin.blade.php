<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — WhatsApp Bot Super Admin</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
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
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            overflow-y: auto;
        }

        .brand-header {
            padding: 20px 20px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: #22c55e;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);
        }

        .brand-info h2 {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .brand-info span {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
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

        .nav-item .badge {
            margin-left: auto;
            font-size: 11px;
            background: rgba(255, 255, 255, 0.15);
            padding: 2px 7px;
            border-radius: 99px;
            color: #fff;
        }

        .nav-sub-list {
            padding-left: 36px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 2px;
            margin-bottom: 6px;
        }

        .nav-sub-item {
            font-size: 12px;
            color: #64748b;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 6px;
            transition: 0.15s;
        }

        .nav-sub-item:hover, .nav-sub-item.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
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
            font-size: 20px;
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

        .date-picker-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
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

        .user-email {
            font-size: 11px;
            color: #64748b;
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
            padding: 28px 32px 60px;
            flex: 1;
        }

        /* PILLS & BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
            white-space: nowrap;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .badge-blue   { background: #e0e7ff; color: #4338ca; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* SWITCH TOGGLE */
        .switch { position: relative; display: inline-block; width: 36px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; border-radius: 999px; transition: 0.2s; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.2s; }
        input:checked + .slider { background: #4f46e5; }
        input:checked + .slider:before { transform: translateX(16px); }

        @media (max-width: 1024px) {
            aside { width: 70px; }
            aside .brand-info, aside .nav-item span, aside .nav-sub-list, aside .badge { display: none; }
            .main-wrapper { margin-left: 70px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside>
    <div class="brand-header">
        <div class="brand-box">
            <div class="brand-icon">💬</div>
            <div class="brand-info">
                <h2>WhatsApp Bot</h2>
                <span>Super Admin</span>
            </div>
        </div>
    </div>

    <div class="nav-section">
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.restaurants') }}" class="nav-item {{ request()->routeIs('admin.restaurants') || request()->routeIs('admin.create-restaurant') ? 'active' : '' }}">
            <span class="icon">🏪</span>
            <span>Restaurants</span>
        </a>
        <div class="nav-sub-list">
            <a href="{{ route('admin.restaurants') }}" class="nav-sub-item {{ request()->routeIs('admin.restaurants') ? 'active' : '' }}">All Restaurants</a>
            <a href="{{ route('admin.create-restaurant') }}" class="nav-sub-item {{ request()->routeIs('admin.create-restaurant') ? 'active' : '' }}">Add Restaurant</a>
            <a href="{{ route('admin.users') }}" class="nav-sub-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">Owners</a>
        </div>

        <a href="{{ route('admin.analytics') }}" class="nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
            <span class="icon">📈</span>
            <span>Analytics</span>
        </a>

        <a href="{{ route('admin.system-health') }}" class="nav-item {{ request()->routeIs('admin.system-health') ? 'active' : '' }}">
            <span class="icon">🛡️</span>
            <span>System Health</span>
        </a>

        <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
            <span class="icon">📦</span>
            <span>Orders (All)</span>
        </a>

        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <span class="icon">👥</span>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.logs') }}" class="nav-item {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
            <span class="icon">📋</span>
            <span>Logs</span>
        </a>

        <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <span class="icon">⚙️</span>
            <span>Settings</span>
        </a>

        <a href="{{ route('admin.system-health') }}" class="nav-item">
            <span class="icon">❓</span>
            <span>Support</span>
        </a>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-wrapper">
    <header>
        <div class="header-title">
            <h1>@yield('header_title', 'Dashboard')</h1>
            <p>@yield('header_subtitle', 'Platform Overview')</p>
        </div>

        <div class="header-actions">
            <div class="date-picker-btn">
                <span>📅</span>
                <span>{{ now()->subDays(6)->format('M d') }} – {{ now()->format('M d, Y') }} ▾</span>
            </div>

            <div class="user-profile">
                <div class="avatar">SA</div>
                <div class="user-info">
                    <div class="user-name">Super Admin</div>
                    <div class="user-email">superadmin@platform.com</div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" style="display:inline; margin-left:8px;">
                    @csrf
                    <button type="submit" class="logout-link" title="Sign out">🚪</button>
                </form>
            </div>
        </div>
    </header>

    <main>
        @if(session('success'))
            <div style="background: #eaf4ee; border: 1px solid #c0dd97; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

</body>
</html>
