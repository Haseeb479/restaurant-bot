@extends('layouts.admin')
@section('title', 'Super Admin Dashboard')
@section('header_title', 'Executive Dashboard')
@section('header_subtitle', 'Platform Overview & Core KPIs')

@section('content')
<!-- Top Metric Cards -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">SaaS Monthly Revenue</span>
            <div class="metric-icon green">💰</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($monthlySaasRevenue) }}</div>
        <div class="metric-footer">Platform Subscription MRR</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Today's Orders GMV</span>
            <div class="metric-icon blue">📦</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($revenueToday) }}</div>
        <div class="metric-footer">{{ $ordersToday }} orders placed today</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Month Orders GMV</span>
            <div class="metric-icon purple">📈</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($revenueThisMonth) }}</div>
        <div class="metric-footer">{{ number_format($ordersThisMonth) }} orders this month</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Active Restaurants</span>
            <div class="metric-icon green">🏪</div>
        </div>
        <div class="metric-value">{{ $activeRestaurants }} <span style="font-size: 14px; font-weight: 500; color: var(--text-secondary);">/ {{ $totalRestaurants }}</span></div>
        <div class="metric-footer">
            @if($pendingCount > 0)
                <span style="color: #ea580c; font-weight: 700;">{{ $pendingCount }} pending approval</span>
            @else
                <span>All accounts reviewed</span>
            @endif
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">WhatsApp Bots</span>
            <div class="metric-icon {{ $disconnectedBots > 0 ? 'orange' : 'green' }}">🤖</div>
        </div>
        <div class="metric-value">{{ $botConnected }} <span style="font-size: 14px; font-weight: 500; color: var(--text-secondary);">online</span></div>
        <div class="metric-footer">
            @if($disconnectedBots > 0)
                <span style="color: #ea580c; font-weight: 700;">{{ $disconnectedBots }} disconnected</span>
            @else
                <span style="color: #10b981; font-weight: 700;">All active bots linked</span>
            @endif
        </div>
    </div>
</div>

@if($pendingCount > 0)
<!-- Pending Approval Alert Queue -->
<div class="panel-card" style="border-left: 4px solid #f97316;">
    <div class="panel-header">
        <div class="panel-title">
            <h3 style="display: flex; align-items: center; gap: 8px;">
                <span>⏳ Pending Restaurant Approvals</span>
                <span class="badge badge-yellow">{{ $pendingCount }} Action Required</span>
            </h3>
            <p>New restaurants registered awaiting super admin review and activation.</p>
        </div>
        <a href="{{ route('admin.restaurants.pending') }}" class="btn btn-primary btn-sm">View Approval Queue →</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>WhatsApp Number</th>
                    <th>Owner Phone</th>
                    <th>City</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingQueue as $p)
                    <tr>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td><code>{{ $p->whatsapp_number }}</code></td>
                        <td>{{ $p->owner_phone }}</td>
                        <td>{{ $p->city ?: 'N/A' }}</td>
                        <td>{{ $p->created_at->diffForHumans() }}</td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <form method="POST" action="{{ route('admin.restaurant.approve', $p->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-success btn-sm" type="submit">Approve ✓</button>
                                </form>
                                <button class="btn btn-danger btn-sm" onclick="rejectPrompt('{{ $p->id }}', '{{ addslashes($p->name) }}')">Reject ✕</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Middle Section: Orders & Revenue Trends + Top Performers -->
<div class="admin-grid-2-1">
    <!-- 7-Day Trend Chart -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>7-Day Platform Order Trends</h3>
                <p>Daily order volume and Gross Merchandise Value (PKR)</p>
            </div>
            <a href="{{ route('admin.analytics') }}" class="btn btn-secondary btn-sm">Full Analytics →</a>
        </div>
        
        <!-- Canvas for Chart -->
        <div style="position: relative; height: 260px; width: 100%;">
            <canvas id="dashboardTrendChart"></canvas>
        </div>
    </div>

    <!-- Top Performing Restaurants -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Top Restaurants</h3>
                <p>This month's highest order volume</p>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @forelse($topRestaurants as $tr)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 8px;">
                    <div>
                        <div style="font-weight: 700; font-size: 13px;">{{ $tr['name'] }}</div>
                        <div style="font-size: 11px; color: var(--text-secondary);">{{ $tr['city'] ?: 'Pakistan' }} • {{ $tr['orders'] }} orders</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 800; color: #10b981; font-size: 13px;">Rs. {{ number_format($tr['revenue']) }}</div>
                        <a href="{{ route('admin.restaurant.analytics', $tr['id']) }}" style="font-size: 10.5px; color: #4f46e5; text-decoration: none;">View Stats →</a>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-secondary); padding: 20px;">No order data yet.</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Bottom Section: Quick Links & Recent Admin Activity Audit Trail -->
<div class="admin-grid-half">
    <!-- Recent Activity Audit Logs -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Recent Admin Actions</h3>
                <p>Security & audit logging trail</p>
            </div>
            <a href="{{ route('admin.audit-logs') }}" class="btn btn-secondary btn-sm">All Logs →</a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @forelse($recentAuditLogs as $log)
                <div style="padding: 8px 12px; border-left: 3px solid #4f46e5; background: var(--bg-page); border-radius: 4px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 700;">
                        <span>{{ $log->action }}</span>
                        <span style="font-size: 10.5px; color: var(--text-secondary); font-weight: 500;">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 11.5px; margin-top: 2px;">{{ $log->details ?: 'Action executed by Super Admin' }}</div>
                </div>
            @empty
                <div style="color: var(--text-secondary); text-align: center; padding: 15px;">No activity logs recorded yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Platform Controls & Shortcuts -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Quick Management Hub</h3>
                <p>Fast access to core administration tools</p>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <a href="{{ route('admin.create-restaurant') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>➕</span><span>Register Restaurant</span>
            </a>
            <a href="{{ route('admin.bot-settings') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>🤖</span><span>AI Model Config</span>
            </a>
            <a href="{{ route('admin.billing') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>💳</span><span>Invoices & Plans</span>
            </a>
            <a href="{{ route('admin.menu-templates') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>📋</span><span>Global Menu Cloner</span>
            </a>
            <a href="{{ route('admin.support') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>🎫</span><span>Support Desk ({{ $openTicketsCount }})</span>
            </a>
            <a href="{{ route('admin.system-health') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 12px;">
                <span>🛡️</span><span>System Health & DB</span>
            </a>
        </div>
    </div>
</div>

<!-- Modal for Rejection Reason -->
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--card-bg); border-radius:12px; padding:24px; width:440px; max-width:90%; border:1px solid var(--border-color);">
        <h3 style="margin-bottom:8px;">Reject Restaurant Application</h3>
        <p style="font-size:12px; color:var(--text-secondary); margin-bottom:16px;">Specify the reason for rejection for <strong id="rejectRestName"></strong>.</p>
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-textarea" rows="3" required placeholder="e.g. Invalid phone number, unreachable owner, duplicate registration."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function rejectPrompt(id, name) {
    document.getElementById('rejectRestName').textContent = name;
    document.getElementById('rejectForm').action = '/admin/restaurant/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dashboardTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Orders Count',
                        data: {!! json_encode($chartOrdersData) !!},
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (PKR)',
                        data: {!! json_encode($chartRevenueData) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: 'rgba(150, 150, 150, 0.1)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection