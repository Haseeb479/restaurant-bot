<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurant->name ?? 'Dashboard' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0efe9;
            min-height: 100vh;
        }

        /* ── NAV ── */
        nav {
            background: #0e0e10;
            height: 54px;
            padding: 0 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-left { display: flex; align-items: center; gap: 16px; }

        .brand {
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .nav-links { display: flex; align-items: center; gap: 4px; }

        .nav-link {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 6px;
            transition: 0.15s;
        }

        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.07); }
        .nav-link.active { color: #fff; background: rgba(255,255,255,0.1); }

        .nav-right { display: flex; align-items: center; gap: 10px; }

        .open-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 99px;
            font-weight: 500;
        }

        .badge-open  { background: rgba(34,197,94,0.15); color: #4ade80; }
        .badge-closed { background: rgba(239,68,68,0.15); color: #f87171; }

        .logout-btn {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            background: none;
            border: 0.5px solid rgba(255,255,255,0.12);
            border-radius: 7px;
            padding: 5px 14px;
            cursor: pointer;
        }

        .logout-btn:hover { color: #fff; border-color: rgba(255,255,255,0.3); }

        /* ── BODY ── */
        .body {
            max-width: 1100px;
            margin: 1.75rem auto;
            padding: 0 1.25rem;
        }

        /* ── PAGE HEADER ── */
        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 22px; font-weight: 600; color: #111; }
        .page-header p  { font-size: 13px; color: #888; margin-top: 4px; }

        /* ── SUCCESS BAR ── */
        .success-bar {
            background: #eaf4ee;
            border: 0.5px solid #c0dd97;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 12px;
            color: #27500A;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border: 1px solid #e8e8e4;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 18px;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 { font-size: 15px; font-weight: 600; }

        .card-body { padding: 18px; }

        /* ── FORM ── */
        .form-group  { margin-bottom: 1rem; }
        .form-label  { font-size: 12px; color: #666; display: block; margin-bottom: 6px; font-weight: 500; }
        .form-hint   { font-size: 11px; color: #aaa; margin-top: 4px; }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            background: #fafafa;
            border: 1px solid #e8e8e4;
            border-radius: 8px;
            transition: 0.15s;
        }

        .form-control:focus {
            border-color: #0e0e10;
            outline: none;
            background: #fff;
        }

        /* ── BUTTON ── */
        .btn {
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.15s;
        }

        .btn-primary   { background: #0e0e10; color: #fff; }
        .btn-primary:hover { background: #2a2a2e; }

        .btn-success   { background: #16a34a; color: #fff; }
        .btn-success:hover { opacity: 0.9; }

        .btn-danger    { background: transparent; color: #ef4444; }
        .btn-danger:hover { background: rgba(239,68,68,0.08); }

        /* ── GRID ── */
        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* ── STATUS BADGES ── */
        .status-badge {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending          { background:#f3f4f6; color:#374151; }
        .status-confirmed        { background:#ecfeff; color:#155e75; }
        .status-preparing        { background:#fff7ed; color:#9a3412; }
        .status-out_for_delivery { background:#f0fdf4; color:#166534; }
        .status-delivered        { background:#dcfce7; color:#166534; }
        .status-cancelled        { background:#fee2e2; color:#991b1b; }
        .status-new              { background:#eef2ff; color:#3730a3; }

        /* ── TOGGLE ── */
        .toggle { position: relative; display: inline-block; width: 40px; height: 22px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: #d1d5db; border-radius: 999px; transition: 0.2s;
        }
        .toggle-slider:before {
            position: absolute; content: "";
            height: 16px; width: 16px; left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: 0.2s;
        }
        .toggle input:checked + .toggle-slider { background: #0e0e10; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(18px); }

        /* ── OPEN INDICATOR ── */
        .open-indicator {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600;
            padding: 4px 12px; border-radius: 999px;
        }
        .open { background: #dcfce7; color: #166534; }
        .closed { background: #fee2e2; color: #991b1b; }
        .dot { width: 6px; height: 6px; border-radius: 50%; }
        .dot-green { background: #16a34a; }
        .dot-red   { background: #ef4444; }

        /* ── RESPONSIVE ── */
        @media(max-width: 768px) {
            .grid2 { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <span class="brand">🍽️ {{ $restaurant->name }}</span>
        <div class="nav-links">
            <a href="/dashboard/{{ $restaurant->id }}/orders"
               class="nav-link {{ request()->routeIs('dashboard.orders') ? 'active' : '' }}">
                Orders
            </a>
            <a href="/dashboard/{{ $restaurant->id }}/menu"
               class="nav-link {{ request()->routeIs('dashboard.menu') ? 'active' : '' }}">
                Menu
            </a>
            <a href="/dashboard/{{ $restaurant->id }}/settings"
               class="nav-link {{ request()->routeIs('dashboard.settings') ? 'active' : '' }}">
                Settings
            </a>
        </div>
    </div>

    <div class="nav-right">
        <span class="open-badge {{ $restaurant->is_open ? 'badge-open' : 'badge-closed' }}">
            {{ $restaurant->is_open ? '● Open' : '● Closed' }}
        </span>
        <form method="POST" action="/dashboard/{{ $restaurant->id }}/logout" style="display:inline;">
            @csrf
            <button type="submit" class="logout-btn">Sign out</button>
        </form>
    </div>
</nav>

<div class="body">
    @if(session('success'))
        <div class="success-bar">✓ {{ session('success') }}</div>
    @endif

    @yield('content')
</div>

</body>
</html>