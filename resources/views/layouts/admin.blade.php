<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin') — WhatsApp Ordering Platform</title>

    <!-- Plus Jakarta Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255, 255, 255, 0.07);
            --sidebar-active: #4f46e5;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --input-bg: #ffffff;
            --table-header-bg: #f8fafc;
            --table-hover-bg: #fafafa;
        }

        [data-theme="dark"] {
            --sidebar-bg: #0b0f19;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --sidebar-active: #6366f1;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --bg-page: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --input-bg: #0f172a;
            --table-header-bg: #182234;
            --table-hover-bg: #26334d;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            font-size: 13px;
            line-height: 1.5;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* SIDEBAR */
        aside {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1e293b;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
            transition: transform 0.25s ease;
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
            gap: 10px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .brand-info h2 {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .brand-info span {
            font-size: 10px;
            color: #10b981;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .nav-section {
            padding: 14px 12px 30px;
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
            color: #475569;
            padding: 14px 12px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .nav-item .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover {
            color: #fff;
            background: var(--sidebar-hover);
        }

        .nav-item.active {
            background: var(--sidebar-active);
            color: var(--sidebar-text-active);
            box-shadow: 0 3px 10px rgba(79, 70, 229, 0.3);
        }

        .nav-item .icon {
            font-size: 15px;
            width: 18px;
            text-align: center;
        }

        .nav-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 99px;
            background: #ef4444;
            color: #fff;
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s ease;
        }

        /* TOPBAR */
        header {
            height: 64px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: 1px solid var(--border-color);
            padding: 6px 10px;
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 16px;
        }

        .header-title h1 {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 1px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .theme-toggle-btn {
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            padding: 7px 11px;
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.15s;
        }
        .theme-toggle-btn:hover {
            border-color: #6366f1;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 12px;
            border-left: 1px solid var(--border-color);
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #fff;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .user-email {
            font-size: 10.5px;
            color: var(--text-secondary);
        }

        .logout-link {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 16px;
            padding: 4px 6px;
            border-radius: 6px;
            transition: 0.15s;
        }
        .logout-link:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

        /* CONTENT */
        main {
            padding: 24px 28px 60px;
            flex: 1;
        }

        /* GLOBAL COMPONENTS */
        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 22px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-title h3 {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .panel-title p {
            font-size: 11.5px;
            color: var(--text-secondary);
            margin-top: 1px;
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
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 10px 12px;
            border-bottom: 1.5px solid var(--border-color);
            background: var(--table-header-bg);
        }

        .data-table td {
            font-size: 12.5px;
            padding: 12px 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: var(--table-hover-bg);
        }

        /* METRIC CARDS */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .metric-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .metric-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .metric-footer {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        .metric-icon.green  { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .metric-icon.blue   { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .metric-icon.orange { background: rgba(249, 115, 22, 0.12); color: #f97316; }
        .metric-icon.purple { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
        .metric-icon.red    { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: #4f46e5;
            color: #ffffff;
        }
        .btn-primary:hover { background: #4338ca; }

        .btn-secondary {
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .btn-secondary:hover { background: var(--border-color); }

        .btn-success {
            background: #10b981;
            color: #ffffff;
        }
        .btn-success:hover { background: #059669; }

        .btn-danger {
            background: #ef4444;
            color: #ffffff;
        }
        .btn-danger:hover { background: #dc2626; }

        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 6px;
        }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2.5px 8px;
            border-radius: 99px;
            white-space: nowrap;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .badge-blue   { background: #e0e7ff; color: #4338ca; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* FORM ELEMENTS */
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 9px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 12.5px;
            outline: none;
            transition: 0.15s;
            font-family: inherit;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        /* MOBILE RESPONSIVENESS */
        @media (max-width: 1024px) {
            aside {
                transform: translateX(-100%);
            }
            aside.mobile-open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: inline-flex;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside id="adminSidebar">
    <div class="brand-header">
        <div class="brand-box">
            <div class="brand-icon">🤖</div>
            <div class="brand-info">
                <h2>RestaurantBot</h2>
                <span>Super Admin</span>
            </div>
        </div>
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
    <!-- TOPBAR -->
    <header>
        <div class="header-left">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
            <div class="header-title">
                <h1>@yield('header_title', 'Super Admin')</h1>
                <p>@yield('header_subtitle', 'Master Control Center')</p>
            </div>
        </div>

        <div class="header-actions">
            <!-- Theme toggle -->
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeBtn" title="Toggle Light/Dark Mode">
                <span id="themeIcon">🌙</span>
                <span id="themeText">Dark</span>
            </button>

            <!-- Super Admin Profile -->
            <div class="user-profile">
                <div class="avatar">SA</div>
                <div class="user-meta">
                    <span class="user-name">Super Admin</span>
                    <span class="user-email">Administrator</span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin-left: 6px;">
                    @csrf
                    <button type="submit" class="logout-link" title="Sign Out">🚪</button>
                </form>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main>
        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid #10b981; color: #10b981; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; color: #ef4444; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 13px;">
                @foreach($errors->all() as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
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

function toggleSidebar() {
    const sb = document.getElementById('adminSidebar');
    if (sb) sb.classList.toggle('mobile-open');
}

document.addEventListener('DOMContentLoaded', initTheme);
</script>

@stack('scripts')
</body>
</html>
