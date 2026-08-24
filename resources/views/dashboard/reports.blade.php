@extends('layouts.dashboard')
@section('title', 'Reports')
@section('header_title', 'Daily Sales & Shift Reports')
@section('header_subtitle', 'Daily sales analytics, shift session breakdown, and CSV data export')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- TIMEFRAME SELECTOR & CSV EXPORT HEADER -->
<div class="panel-card" style="padding: 16px 20px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
            <span style="font-size: 12px; font-weight: 700; color: #64748b; margin-right: 4px;">Period:</span>
            <a href="?period=today" class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">Today</a>
            <a href="?period=session" class="btn {{ $period === 'session' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">Login Session</a>
            <a href="?period=yesterday" class="btn {{ $period === 'yesterday' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">Yesterday</a>
            <a href="?period=this_week" class="btn {{ $period === 'this_week' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">This Week</a>
            <a href="?period=this_month" class="btn {{ $period === 'this_month' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">This Month</a>
            <a href="?period=all" class="btn {{ $period === 'all' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px;">All-Time</a>
        </div>

        <div>
            <a href="{{ route('dashboard.export-sales-report-csv', [$r->id, 'period' => $period]) }}" class="btn btn-success" style="padding: 8px 16px; font-weight: 700;">
                📥 Export {{ $period === 'session' ? 'Shift Session' : 'Sales' }} to CSV
            </a>
        </div>
    </div>
</div>

<!-- TOP SUMMARY CARDS FOR SELECTED PERIOD -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Net Sales Revenue</span>
            <div class="metric-icon-box green">💰</div>
        </div>
        <div class="metric-value">PKR {{ number_format($netTotalRevenue, 0) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● {{ $periodLabel }}</span>
            <span>Net total collected</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Food Sales Subtotal</span>
            <div class="metric-icon-box blue">🍕</div>
        </div>
        <div class="metric-value">PKR {{ number_format($foodRevenue, 0) }}</div>
        <div class="metric-footer">
            <span class="sub-badge blue">+ PKR {{ number_format($deliveryFees, 0) }} delivery</span>
            <span>Menu items gross</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Orders Processed</span>
            <div class="metric-icon-box purple">📦</div>
        </div>
        <div class="metric-value">{{ number_format($orders->count()) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">{{ $delivered->count() }} Delivered</span>
            <span>Fulfillment rate: {{ $orders->count() > 0 ? round(($delivered->count() / $orders->count()) * 100) : 0 }}%</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Cancelled / Rejected</span>
            <div class="metric-icon-box red">❌</div>
        </div>
        <div class="metric-value">{{ number_format($cancelled->count()) }}</div>
        <div class="metric-footer">
            <span class="sub-badge red">{{ $cancelled->count() }} Orders</span>
            <span>Excluded from revenue</span>
        </div>
    </div>
</div>

<!-- 7-DAY REVENUE GROWTH CHART -->
<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>7-Day Sales Trend (PKR)</h3>
            <p>Daily sales volume processed through your WhatsApp bot</p>
        </div>
    </div>
    <div style="height: 260px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- PERIOD ORDERS BREAKDOWN TABLE -->
<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Sales Breakdown for {{ $periodLabel }} ({{ $orders->count() }} Orders)</h3>
            <p>Detailed orders registered during this reporting timeframe</p>
        </div>
        <a href="{{ route('dashboard.export-sales-report-csv', [$r->id, 'period' => $period]) }}" class="btn btn-secondary" style="font-size: 11px;">
            📥 Download CSV
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Time</th>
                    <th>Customer</th>
                    <th>Food Subtotal</th>
                    <th>Delivery</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><code>#{{ $o->tracking_code }}</code></td>
                    <td>{{ $o->created_at->format('h:i A') }}</td>
                    <td>
                        <strong>{{ $o->customer_name ?: 'Customer' }}</strong>
                        <div style="font-size: 11px; color: #64748b;">{{ $o->customer_phone }}</div>
                    </td>
                    <td>PKR {{ number_format($o->subtotal, 0) }}</td>
                    <td>PKR {{ number_format($o->delivery_charge, 0) }}</td>
                    <td><strong style="color: #4f46e5;">PKR {{ number_format($o->total, 0) }}</strong></td>
                    <td>{{ ucwords(str_replace('_', ' ', $o->payment_method ?: 'COD')) }}</td>
                    <td>
                        <span class="badge-status {{ $o->status }}">
                            {{ ucfirst(str_replace('_', ' ', $o->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                        No orders recorded during {{ $periodLabel }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- TOP SELLING FOOD ITEMS IN THIS PERIOD -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>🔥 Best Selling Menu Items ({{ $periodLabel }})</h3>
            <p>Top ordered items and item-level revenue ranking</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Times Ordered</th>
                    <th>Total Quantity Sold</th>
                    <th>Item Total Sales</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topItems as $item)
                <tr>
                    <td><strong>🍕 {{ $item->name }}</strong></td>
                    <td>{{ $item->order_count }} orders</td>
                    <td><strong>{{ $item->total_qty }} units</strong></td>
                    <td><strong style="color: #4f46e5;">PKR {{ number_format($item->total_revenue, 0) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                        No itemized sales recorded in this timeframe.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Daily Sales (PKR)',
                data: {!! json_encode($chartRevenue) !!},
                backgroundColor: '#4f46e5',
                borderRadius: 8,
                maxBarThickness: 45
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

@endsection
