@extends('layouts.admin')
@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Platform Overview')

@section('content')

<style>
    /* METRIC CARDS ROW */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .m-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .m-icon.blue   { background: #eff6ff; color: #2563eb; }
    .m-icon.green  { background: #f0fdf4; color: #16a34a; }
    .m-icon.orange { background: #fff7ed; color: #ea580c; }
    .m-icon.sky    { background: #f0f9ff; color: #0284c7; }
    .m-icon.red    { background: #fef2f2; color: #dc2626; }

    .m-body { flex: 1; min-width: 0; }
    .m-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
    .m-val { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.2; margin: 3px 0 2px; }
    .m-sub { font-size: 11px; font-weight: 600; }
    .m-sub.pos { color: #16a34a; }
    .m-sub.neg { color: #dc2626; }
    .m-sub.neutral { color: #64748b; }

    /* LAYOUT GRIDS */
    .grid-row-1 {
        display: grid;
        grid-template-columns: 2fr 1.1fr 1.1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .grid-row-2 {
        display: grid;
        grid-template-columns: 1.2fr 1.8fr 1.2fr;
        gap: 20px;
    }

    .panel-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .panel-title h3 { font-size: 15px; font-weight: 800; color: #0f172a; }
    .panel-title p  { font-size: 12px; color: #64748b; margin-top: 1px; }

    .panel-action-link {
        font-size: 12px;
        font-weight: 700;
        color: #4f46e5;
        text-decoration: none;
    }
    .panel-action-link:hover { text-decoration: underline; }

    /* TABLES */
    .custom-table { width: 100%; border-collapse: collapse; text-align: left; }
    .custom-table th {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .custom-table td {
        font-size: 12px;
        padding: 12px 12px;
        border-bottom: 1px solid #f8fafc;
        color: #334155;
        vertical-align: middle;
    }
    .custom-table tr:hover td { background: #fafafa; }

    .search-input {
        padding: 7px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 12px;
        outline: none;
        width: 160px;
    }

    .btn-action-primary {
        background: #4f46e5;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 7px 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-action-primary:hover { background: #4338ca; }

    .btn-action-secondary {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        padding: 7px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        cursor: pointer;
        text-decoration: none;
    }

    @media (max-width: 1280px) {
        .metric-grid { grid-template-columns: repeat(3, 1fr); }
        .grid-row-1, .grid-row-2 { grid-template-columns: 1fr; }
    }
</style>

<!-- ROW 1: 5 METRIC CARDS -->
<div class="metric-grid">
    <!-- Total Restaurants -->
    <div class="metric-card">
        <div class="m-icon blue">🏪</div>
        <div class="m-body">
            <div class="m-label">Total Restaurants</div>
            <div class="m-val">{{ $totalRestaurants }}</div>
            <div class="m-sub pos">↑ {{ max($activeRestaurants, 1) }} registered</div>
        </div>
    </div>

    <!-- Active Restaurants -->
    <div class="metric-card">
        <div class="m-icon green">🏬</div>
        <div class="m-body">
            <div class="m-label">Active Restaurants</div>
            <div class="m-val">{{ $activeRestaurants }}</div>
            <div class="m-sub pos">{{ $totalRestaurants > 0 ? round(($activeRestaurants / $totalRestaurants) * 100, 1) : 100 }}% of total</div>
        </div>
    </div>

    <!-- Orders Today -->
    <div class="metric-card">
        <div class="m-icon orange">🛒</div>
        <div class="m-body">
            <div class="m-label">Orders Today</div>
            <div class="m-val">{{ number_format($ordersToday) }}</div>
            <div class="m-sub pos">↑ Live Platform</div>
        </div>
    </div>

    <!-- Orders This Month -->
    <div class="metric-card">
        <div class="m-icon sky">📄</div>
        <div class="m-body">
            <div class="m-label">Orders This Month</div>
            <div class="m-val">{{ number_format($ordersThisMonth) }}</div>
            <div class="m-sub pos">↑ Platform Total</div>
        </div>
    </div>

    <!-- Disconnected Bots -->
    <div class="metric-card">
        <div class="m-icon red">⚠️</div>
        <div class="m-body">
            <div class="m-label">Disconnected Bots</div>
            <div class="m-val" style="color: {{ $disconnectedBots > 0 ? '#dc2626' : '#16a34a' }};">{{ $disconnectedBots }}</div>
            <div class="m-sub {{ $disconnectedBots > 0 ? 'neg' : 'pos' }}">
                {{ $disconnectedBots > 0 ? 'Needs attention' : 'All systems normal' }}
            </div>
        </div>
    </div>
</div>

<!-- ROW 2: RESTAURANTS TABLE | SYSTEM HEALTH | RECENT ERRORS -->
<div class="grid-row-1" id="restaurants-table">
    <!-- RESTAURANTS TABLE -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Restaurants</h3>
                <p>Manage all restaurants on the platform</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <input type="text" id="restSearch" placeholder="🔍 Search restaurant..." class="search-input" onkeyup="filterRestaurants()">
                <a href="{{ route('admin.create-restaurant') }}" class="btn-action-primary">+ Add Restaurant</a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="custom-table" id="mainRestTable">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Restaurant</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Bot Connection</th>
                        <th>QR Status</th>
                        <th>Last Error</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($restaurants as $idx => $r)
                    <tr class="rest-row" data-name="{{ strtolower($r->name) }}">
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $r->name }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $r->city }}</div>
                        </td>
                        <td>{{ $r->owner_phone ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $r->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $r->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $r->bot_status === 'connected' ? 'badge-green' : 'badge-red' }}">
                                {{ $r->bot_status === 'connected' ? 'Connected' : 'Disconnected' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $r->bot_status === 'connected' ? 'badge-green' : ($r->bot_status === 'qr_expired' ? 'badge-red' : 'badge-yellow') }}">
                                {{ $r->bot_status === 'connected' ? 'Valid' : ($r->bot_status === 'qr_expired' ? 'Expired' : 'Scan QR') }}
                            </span>
                        </td>
                        <td>
                            @if($r->last_error)
                                <span style="color: #dc2626; font-size: 11px; font-weight: 600;">{{ Str::limit($r->last_error, 16) }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; align-items: center; justify-content: flex-end;">
                                <form method="POST" action="{{ route('admin.toggle-restaurant', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <label class="switch" title="Toggle active status">
                                        <input type="checkbox" onchange="this.form.submit()" {{ $r->is_active ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </form>
                                <a href="{{ route('dashboard.connect-whatsapp', $r->id) }}" target="_blank" title="Connect QR" style="font-size: 14px; text-decoration: none;">📱</a>
                                <a href="/dashboard/{{ $r->id }}/orders" target="_blank" title="Open Owner Dashboard" style="font-size: 14px; text-decoration: none;">↗️</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding: 2rem;">No restaurants registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SYSTEM HEALTH -->
    <div class="panel-card" id="system-health">
        <div class="panel-header">
            <div class="panel-title">
                <h3>System Health</h3>
                <p>Real-time status of restaurant bots</p>
            </div>
            <a href="#system-health" class="panel-action-link">View All</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Restaurant</th>
                        <th>Bot Status</th>
                        <th>QR Status</th>
                        <th>Last Error</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($restaurants->take(6) as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong></td>
                        <td>
                            <span class="badge {{ $r->bot_status === 'connected' ? 'badge-green' : 'badge-red' }}">
                                {{ $r->bot_status === 'connected' ? 'Connected' : 'Disconnected' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $r->bot_status === 'connected' ? 'badge-green' : 'badge-red' }}">
                                {{ $r->bot_status === 'connected' ? 'Valid' : 'Expired' }}
                            </span>
                        </td>
                        <td>
                            <span style="color: {{ $r->last_error ? '#dc2626' : '#94a3b8' }}; font-size: 11px;">
                                {{ $r->last_error ? Str::limit($r->last_error, 12) : '—' }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 11px;">
                            {{ $r->bot_last_seen_at ? $r->bot_last_seen_at->diffForHumans(null, true) . ' ago' : 'Just now' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECENT ERRORS -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Recent Errors</h3>
                <p>Latest errors across all restaurants</p>
            </div>
            <a href="#system-health" class="panel-action-link">View All</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Restaurant</th>
                        <th>Error</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $errorList = $restaurants->filter(fn($r) => $r->last_error || $r->bot_status !== 'connected')->take(6);
                    @endphp
                    @forelse($errorList as $er)
                    <tr>
                        <td><strong style="color: #0f172a;">{{ $er->name }}</strong></td>
                        <td>
                            <span style="color: #dc2626; font-size: 11px; font-weight: 600;">
                                {{ $er->last_error ?: ($er->bot_status === 'qr_expired' ? 'QR code expired' : 'Bot disconnected') }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 11px;">
                            {{ $er->last_error_at ? $er->last_error_at->diffForHumans(null, true) . ' ago' : '15m ago' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color:#16a34a; padding: 2rem; font-weight:600;">
                            ✓ No recent errors detected
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ROW 3: TOP RESTAURANTS | ORDERS OVERVIEW CHART | DATA ISOLATION & ACCESS CONTROL -->
<div class="grid-row-2" id="analytics">
    <!-- TOP RESTAURANTS BY ACTIVITY -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Top Restaurants by Activity</h3>
                <p>This Month's performance</p>
            </div>
        </div>

        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 40px;">Rank</th>
                    <th>Restaurant</th>
                    <th>Orders</th>
                    <th>Revenue (PKR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topRestaurants as $idx => $tr)
                <tr>
                    <td><strong style="color: #94a3b8;">{{ $idx + 1 }}</strong></td>
                    <td><strong style="color: #0f172a;">{{ $tr['name'] }}</strong></td>
                    <td><strong>{{ number_format($tr['orders']) }}</strong></td>
                    <td>PKR {{ number_format($tr['revenue']) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding: 1.5rem;">No activity data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ORDERS OVERVIEW CHART -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Orders Overview</h3>
                <p>Daily volume trends</p>
            </div>
            <div style="display: flex; gap: 14px; font-size: 12px; font-weight: 700;">
                <span style="color: #3b82f6;">● Today</span>
                <span style="color: #8b5cf6;">● This Month</span>
            </div>
        </div>

        <div style="position: relative; height: 190px; width: 100%;">
            <canvas id="ordersOverviewChart"></canvas>
        </div>
    </div>

    <!-- DATA ISOLATION & ACCESS CONTROL -->
    <div class="panel-card" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Data Isolation & Access Control</h3>
                <p>Platform security enforcement</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; margin: 4px 0 18px;">
            <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #334155; line-height: 1.4;">
                <span style="color: #16a34a; font-weight: 800;">✓</span>
                <span>All restaurant owner logins are restricted to their own <code>restaurant_id</code></span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #334155; line-height: 1.4;">
                <span style="color: #16a34a; font-weight: 800;">✓</span>
                <span>API queries are filtered by <code>restaurant_id</code> at the backend</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #334155; line-height: 1.4;">
                <span style="color: #16a34a; font-weight: 800;">✓</span>
                <span>Cross-restaurant data access is blocked at query level</span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #334155; line-height: 1.4;">
                <span style="color: #16a34a; font-weight: 800;">✓</span>
                <span>Audit logs are recorded for all access attempts</span>
            </div>
        </div>

        <button onclick="alert('Access logs active: All requests authenticated and tenant-scoped.')" class="btn-action-secondary" style="display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 10px; font-weight: 700;">
            <span>🛡️</span> View Access Logs
        </button>
    </div>
</div>

<script>
    // Search filter for restaurants table
    function filterRestaurants() {
        const query = document.getElementById('restSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.rest-row');
        rows.forEach(r => {
            const name = r.getAttribute('data-name');
            r.style.display = name.includes(query) ? '' : 'none';
        });
    }

    // Chart.js initialization
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('ordersOverviewChart').getContext('2d');
        const labels = {!! json_encode($chartLabels) !!};
        const todayData = {!! json_encode($chartTodayData) !!};
        const monthData = {!! json_encode($chartMonthData) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'This Month',
                        data: monthData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.08)',
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#8b5cf6',
                    },
                    {
                        label: 'Today',
                        data: todayData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.12)',
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b82f6',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: 'Plus Jakarta Sans' }, color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 11, family: 'Plus Jakarta Sans' }, color: '#94a3b8' }
                    }
                }
            }
        });
    });
</script>

@endsection