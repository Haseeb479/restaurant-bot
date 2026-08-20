<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Restaurant Platform</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; min-height: 100vh; color: #111; }

        /* NAV */
        nav { background: #0e0e10; height: 54px; padding: 0 1.75rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 20px; height: 20px; }
        .wm-sq { border-radius: 2px; }
        .wm-sq:nth-child(1),.wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2),.wm-sq:nth-child(3) { background: rgba(255,255,255,0.2); }
        .brand-text { font-size: 13px; font-weight: 600; color: #fff; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-badge { font-size: 11px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5); border-radius: 99px; padding: 3px 10px; border: 0.5px solid rgba(255,255,255,0.1); }
        .logout-btn { font-size: 12px; color: rgba(255,255,255,0.45); background: none; border: 0.5px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 5px 14px; cursor: pointer; }
        .logout-btn:hover { color: #fff; border-color: rgba(255,255,255,0.3); }

        /* LAYOUT */
        .body { max-width: 1220px; margin: 1.75rem auto; padding: 0 1.25rem; }

        /* FLASH */
        .flash { border-radius: 8px; padding: 10px 16px; font-size: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
        .flash-success { background: #eaf4ee; border: 0.5px solid #c0dd97; color: #27500A; }
        .flash-warning { background: #fff7ed; border: 0.5px solid #fed7aa; color: #9a3412; }
        .flash-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

        /* SECTION */
        .section-head { display: flex; align-items: center; justify-content: space-between; margin: 1.75rem 0 1rem; flex-wrap: wrap; gap: 8px; }
        .section-title { font-size: 12px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.07em; }
        .add-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; background: #0e0e10; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; cursor: pointer; text-decoration: none; }
        .add-btn:hover { background: #2a2a2e; }

        /* PLATFORM STAT CARDS */
        .platform-stats { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 1.75rem; }
        .pstat { background: #0e0e10; border-radius: 12px; padding: 1.1rem 1.25rem; }
        .pstat-num { font-size: 22px; font-weight: 600; color: #fff; letter-spacing: -0.03em; margin-bottom: 3px; }
        .pstat-label { font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.06em; line-height: 1.4; }
        .pstat.highlight { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 0.5px solid rgba(255,255,255,0.1); }

        /* CARDS */
        .card { background: #fff; border-radius: 14px; border: 0.5px solid #e8e8e4; overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 0.5px solid #f0efe9; }
        .card-header h3 { font-size: 13px; font-weight: 700; color: #111; }
        .card-header .count-badge { font-size: 11px; background: #f3f4f6; color: #666; border-radius: 99px; padding: 2px 8px; font-weight: 600; }

        /* RESTAURANT TABLE */
        .r-table { width: 100%; border-collapse: collapse; }
        .r-table th { text-align: left; font-size: 10px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; background: #fafafa; }
        .r-table td { padding: 13px 16px; border-top: 0.5px solid #f5f5f2; font-size: 12px; vertical-align: middle; }
        .r-table tr:hover td { background: #fdfcfc; }

        .r-name { font-size: 13px; font-weight: 600; color: #111; }
        .r-meta { font-size: 11px; color: #aaa; margin-top: 2px; }

        /* STATUS PILLS */
        .pill { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; border-radius: 99px; padding: 3px 9px; white-space: nowrap; }
        .pill .dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

        .s-active    { background: #eaf4ee; color: #27500A; }
        .s-inactive  { background: #f5f5f2; color: #999; }
        .s-expired   { background: #fef3c7; color: #92400e; }

        .plan-trial  { background: #faeeda; color: #633806; }
        .plan-basic  { background: #e0f2fe; color: #0369a1; }
        .plan-pro    { background: #EEEDFE; color: #3C3489; }

        .bot-connected    { background: #d1fae5; color: #065f46; }
        .bot-qr           { background: #fef3c7; color: #92400e; }
        .bot-expired      { background: #fee2e2; color: #991b1b; }
        .bot-disconnected { background: #f3f4f6; color: #6b7280; }

        /* SWITCHES */
        .switch { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #d1d5db; border-radius: 999px; transition: 0.2s; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.2s; }
        input:checked + .slider { background: #0e0e10; }
        input:checked + .slider:before { transform: translateX(16px); }

        /* ACTIONS */
        .action-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .btn-sm { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; border: 0.5px solid #e8e8e4; background: #f5f5f2; color: #444; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-sm:hover { background: #ececea; }
        .btn-danger { background: #fee2e2; border-color: #fca5a5; color: #991b1b; }
        .btn-danger:hover { background: #fecaca; }

        /* SYSTEM HEALTH */
        .health-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 16px; }
        .health-card { border: 0.5px solid #e8e8e4; border-radius: 10px; padding: 12px 14px; }
        .health-name { font-size: 12px; font-weight: 700; color: #111; margin-bottom: 4px; }
        .health-phone { font-size: 10px; color: #aaa; margin-bottom: 8px; }
        .health-error { font-size: 11px; color: #991b1b; background: #fef2f2; border: 0.5px solid #fca5a5; border-radius: 6px; padding: 6px 8px; margin-top: 6px; line-height: 1.4; word-break: break-all; }
        .health-last-seen { font-size: 10px; color: #6b7280; margin-top: 4px; }

        /* RANKING TABLE */
        .rank-num { font-size: 16px; font-weight: 800; color: #d1d5db; width: 30px; }
        .rank-bar { height: 6px; background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-top: 4px; }
        .rank-fill { height: 100%; background: #0e0e10; border-radius: 99px; }

        /* ATTENTION BANNER */
        .attention-banner { background: #fff7ed; border: 0.5px solid #fed7aa; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; }

        /* DATA ISOLATION BADGE */
        .isolation-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; background: #ecfdf5; color: #065f46; border: 0.5px solid #6ee7b7; border-radius: 99px; padding: 3px 10px; }

        @media(max-width: 1100px) { .platform-stats { grid-template-columns: repeat(4, 1fr); } }
        @media(max-width: 800px) { .platform-stats { grid-template-columns: repeat(2, 1fr); } .health-grid { grid-template-columns: 1fr 1fr; } }
        @media(max-width: 500px) { .platform-stats { grid-template-columns: 1fr 1fr; } .health-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <div class="wm-icon">
            <div class="wm-sq"></div><div class="wm-sq"></div>
            <div class="wm-sq"></div><div class="wm-sq"></div>
        </div>
        <span class="brand-text">Restaurant Platform Admin</span>
    </div>
    <div class="nav-right">
        <span class="isolation-badge">🔒 Data Isolated Per Tenant</span>
        <span class="nav-badge">Super Admin</span>
        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="logout-btn">Sign out</button>
        </form>
    </div>
</nav>

<div class="body">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="flash flash-success"><div class="flash-dot"></div>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-warning"><div class="flash-dot"></div>{{ session('error') }}</div>
    @endif

    {{-- ATTENTION BANNER --}}
    @if($needsAttention > 0)
    <div class="attention-banner">
        ⚠️ <strong>{{ $needsAttention }} restaurant(s)</strong> need WhatsApp bot reconnection. Their customers cannot receive messages.
    </div>
    @endif

    {{-- PLATFORM ANALYTICS --}}
    <div class="section-head" style="margin-top:0;">
        <span class="section-title">Platform Overview</span>
        <span style="font-size: 11px; color: #aaa;">Live as of {{ now()->format('H:i, d M Y') }}</span>
    </div>

    <div class="platform-stats">
        <div class="pstat">
            <div class="pstat-num">{{ $activeRestaurants }}</div>
            <div class="pstat-label">Active Restaurants</div>
        </div>
        <div class="pstat">
            <div class="pstat-num">{{ $totalOrders }}</div>
            <div class="pstat-label">Orders Today</div>
        </div>
        <div class="pstat highlight">
            <div class="pstat-num">Rs {{ number_format($totalRevenue) }}</div>
            <div class="pstat-label">Revenue Today</div>
        </div>
        <div class="pstat">
            <div class="pstat-num">{{ $monthOrders }}</div>
            <div class="pstat-label">Orders This Month</div>
        </div>
        <div class="pstat highlight">
            <div class="pstat-num">Rs {{ number_format($monthRevenue) }}</div>
            <div class="pstat-label">Revenue This Month</div>
        </div>
        <div class="pstat">
            <div class="pstat-num" style="color: #4ade80;">{{ $botConnected }}</div>
            <div class="pstat-label">Bots Connected</div>
        </div>
        <div class="pstat">
            <div class="pstat-num" style="color: {{ $needsAttention > 0 ? '#f87171' : '#4ade80' }};">{{ $needsAttention }}</div>
            <div class="pstat-label">Needs Reconnect</div>
        </div>
    </div>

    {{-- SYSTEM HEALTH --}}
    @php
        $problemRestaurants = $restaurants->where('is_active', true)
            ->filter(fn($r) => $r->bot_status !== 'connected' || $r->last_error);
    @endphp
    @if($problemRestaurants->count() > 0)
    <div class="card">
        <div class="card-header">
            <h3>🔴 System Health — Restaurants Needing Attention</h3>
            <span class="count-badge">{{ $problemRestaurants->count() }} issues</span>
        </div>
        <div class="health-grid">
            @foreach($problemRestaurants as $hr)
            <div class="health-card">
                <div class="health-name">{{ $hr->name }}</div>
                <div class="health-phone">📱 {{ $hr->whatsapp_number }}</div>
                <span class="pill {{ $hr->bot_status_class }}">
                    <span class="dot"></span>{{ $hr->bot_status_label }}
                </span>
                @if($hr->bot_last_seen_at)
                    <div class="health-last-seen">Last seen: {{ $hr->bot_last_seen_at->diffForHumans() }}</div>
                @else
                    <div class="health-last-seen">Never connected</div>
                @endif
                @if($hr->last_error)
                    <div class="health-error">⚠️ {{ Str::limit($hr->last_error, 120) }}</div>
                    @if($hr->last_error_at)
                        <div class="health-last-seen" style="margin-top:4px;">Error at: {{ $hr->last_error_at->diffForHumans() }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.clear-error', $hr->id) }}" style="margin-top:6px;">
                        @csrf
                        <button type="submit" class="btn-sm">✕ Clear Error</button>
                    </form>
                @endif
                <div style="margin-top:8px; display:flex; gap:6px;">
                    <a href="{{ route('dashboard.connect-whatsapp', $hr->id) }}" target="_blank" class="btn-sm">🔗 Reconnect Bot</a>
                    <a href="{{ route('dashboard.orders', $hr->id) }}" target="_blank" class="btn-sm">📋 View Orders</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- RESTAURANT MANAGEMENT TABLE --}}
    <div class="section-head">
        <span class="section-title">Restaurant Management</span>
        <a href="{{ route('admin.create-restaurant') }}" class="add-btn">
            <svg width="11" height="11" viewBox="0 0 12 12" fill="none"><path d="M6 1v10M1 6h10" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg>
            Add Restaurant
        </a>
    </div>

    <div class="card">
        @if($restaurants->count() === 0)
            <div style="padding:2.5rem; text-align:center; color:#bbb; font-size:13px;">No restaurants yet. Add your first one above.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="r-table">
                <thead>
                    <tr>
                        <th style="width:24%;">Restaurant</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>WhatsApp Bot</th>
                        <th>Today's Activity</th>
                        <th>Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($restaurants->sortByDesc('today_orders_count') as $r)
                    <tr style="{{ !$r->is_active ? 'opacity:0.6;' : '' }}">
                        <td>
                            <div class="r-name">{{ $r->name }}</div>
                            <div class="r-meta">
                                📱 {{ $r->whatsapp_number }}
                                @if($r->city) · {{ $r->city }} @endif
                            </div>
                            @if(!$r->is_active && $r->deactivated_at)
                                <div style="font-size:10px; color:#ef4444; margin-top:3px;">
                                    Deactivated {{ $r->deactivated_at->diffForHumans() }}
                                    @if($r->deactivated_reason) — {{ $r->deactivated_reason }} @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="pill plan-{{ $r->plan }}">{{ ucfirst($r->plan) }}</span>
                            @if($r->plan_expires_at)
                                <div style="font-size:10px; color:#aaa; margin-top:3px;">
                                    {{ $r->plan_expires_at->isFuture() ? 'Exp. ' . $r->plan_expires_at->format('d M Y') : '⚠️ Expired' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="pill {{ $r->display_status_class }}">
                                <span class="dot"></span>{{ $r->display_status }}
                            </span>
                        </td>
                        <td>
                            <span class="pill {{ $r->bot_status_class }}">
                                <span class="dot"></span>
                                {{ match($r->bot_status) {
                                    'connected'    => 'Connected',
                                    'qr_pending'   => 'Scan QR',
                                    'qr_expired'   => 'QR Expired',
                                    default        => 'Offline',
                                } }}
                            </span>
                            @if($r->bot_last_seen_at)
                                <div style="font-size:10px; color:#aaa; margin-top:2px;">{{ $r->bot_last_seen_at->diffForHumans() }}</div>
                            @else
                                <div style="font-size:10px; color:#e5e7eb; margin-top:2px;">Never connected</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:13px;">{{ $r->today_orders_count }} orders</div>
                            <div style="font-size:11px; color:#aaa;">
                                Rs {{ number_format($r->orders->sum('total'), 0) }} revenue
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px; font-weight:600;">{{ $r->month_orders_count }}</div>
                            <div style="font-size:10px; color:#aaa;">orders</div>
                        </td>
                        <td>
                            <div class="action-row">
                                <a href="/dashboard/{{ $r->id }}/orders" target="_blank" class="btn-sm">📋 Orders</a>
                                <a href="{{ route('dashboard.connect-whatsapp', $r->id) }}" target="_blank" class="btn-sm">🔗 Bot</a>
                                <form method="POST" action="{{ route('admin.toggle-restaurant', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <label class="switch" title="{{ $r->is_active ? 'Deactivate' : 'Reactivate' }} {{ $r->name }}">
                                        <input type="checkbox" onchange="this.form.submit()" {{ $r->is_active ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </form>
                            </div>
                            {{-- Extend Plan --}}
                            <form method="POST" action="{{ route('admin.extend-plan', $r->id) }}" style="display:flex; gap:4px; margin-top:6px;">
                                @csrf
                                <select name="months" style="font-size:10px; padding:2px 4px; border:0.5px solid #e8e8e4; border-radius:4px; background:#f9f9f9;">
                                    <option value="1">+1 mo</option>
                                    <option value="3">+3 mo</option>
                                    <option value="6">+6 mo</option>
                                    <option value="12">+1 yr</option>
                                </select>
                                <button type="submit" class="btn-sm" style="font-size:10px; padding:2px 8px;">Extend Plan</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ACTIVITY RANKING TABLE --}}
    <div class="section-head">
        <span class="section-title">Activity Ranking (This Month)</span>
    </div>
    @php
        $ranked = $restaurants->sortByDesc('month_orders_count')->values();
        $maxOrders = $ranked->first()?->month_orders_count ?? 1;
    @endphp
    <div class="card">
        <div style="overflow-x:auto;">
            <table class="r-table">
                <thead>
                    <tr>
                        <th style="width:40px;">Rank</th>
                        <th>Restaurant</th>
                        <th>Monthly Orders</th>
                        <th>Activity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranked as $idx => $r)
                    <tr>
                        <td class="rank-num">#{{ $idx + 1 }}</td>
                        <td>
                            <div class="r-name">{{ $r->name }}</div>
                            <div class="r-meta">{{ $r->city }}</div>
                        </td>
                        <td>
                            <strong style="font-size:15px;">{{ $r->month_orders_count }}</strong>
                            <span style="font-size:11px; color:#aaa;"> orders</span>
                        </td>
                        <td style="min-width: 130px;">
                            <div class="rank-bar">
                                <div class="rank-fill" style="width: {{ $maxOrders > 0 ? round(($r->month_orders_count / $maxOrders) * 100) : 0 }}%"></div>
                            </div>
                        </td>
                        <td>
                            <span class="pill {{ $r->bot_status_class }}">
                                <span class="dot"></span>
                                {{ $r->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color:#aaa; padding:2rem;">No data yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ADMIN QUICK LINKS --}}
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:2rem;">
        <a href="{{ route('admin.orders') }}" class="btn-sm" style="font-size:12px; padding:8px 16px;">📦 View All Orders</a>
        <a href="{{ route('admin.create-restaurant') }}" class="btn-sm" style="font-size:12px; padding:8px 16px;">➕ Add Restaurant</a>
    </div>

</div>
</body>
</html>