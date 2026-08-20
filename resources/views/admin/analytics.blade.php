@extends('layouts.admin')
@section('title', 'Analytics')
@section('header_title', 'Platform Analytics & Revenue')
@section('header_subtitle', 'SaaS package MRR, platform-wide order trends, and restaurant performance')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- TOP SUMMARY CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Monthly SaaS Revenue</span>
            <div class="metric-icon-box green">💰</div>
        </div>
        <div class="metric-value">PKR {{ number_format($mrr, 0) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● Active MRR</span>
            <span>Based on Basic/Pro packages</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Orders This Month</span>
            <div class="metric-icon-box blue">📦</div>
        </div>
        <div class="metric-value">{{ number_format($totalOrdersMonth) }}</div>
        <div class="metric-footer">
            <span class="sub-badge blue">● Today: {{ number_format($totalOrdersToday) }}</span>
            <span>Platform-wide</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Subscription Plans</span>
            <div class="metric-icon-box orange">📊</div>
        </div>
        <div class="metric-value">{{ $proCount }} Pro / {{ $basicCount }} Basic</div>
        <div class="metric-footer">
            <span class="sub-badge orange">{{ $trialCount }} Trial</span>
            <span>Active restaurants</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">WhatsApp Chats</span>
            <div class="metric-icon-box purple">💬</div>
        </div>
        <div class="metric-value">{{ number_format($totalConversations) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● AI Bot Active</span>
            <span>Total customer interactions</span>
        </div>
    </div>
</div>

<!-- 14-DAY ORDERS TREND CHART -->
<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>14-Day Order Volume Growth</h3>
            <p>Total daily WhatsApp orders processed platform-wide</p>
        </div>
    </div>
    <div style="height: 280px;">
        <canvas id="growthChart"></canvas>
    </div>
</div>

<!-- RESTAURANTS PERFORMANCE BREAKDOWN -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Restaurant Activity Breakdown</h3>
            <p>Comparison of customer chats, orders, and food catalog size per restaurant</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Plan</th>
                    <th>Today Orders</th>
                    <th>Month Orders</th>
                    <th>Customer Conversations</th>
                    <th>Menu Items</th>
                </tr>
            </thead>
            <tbody>
                @foreach($restaurants as $r)
                <tr>
                    <td><strong>{{ $r->name }}</strong> ({{ $r->city ?: 'Pakistan' }})</td>
                    <td><span class="badge badge-blue" style="text-transform: uppercase;">{{ $r->plan }}</span></td>
                    <td><strong>{{ $r->today_orders }}</strong></td>
                    <td><strong>{{ $r->month_orders }}</strong></td>
                    <td>{{ $r->conversations_count }} chats</td>
                    <td>{{ $r->menu_items_count }} items</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Orders Processed',
                data: {!! json_encode($chartData) !!},
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.08)',
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5'
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
