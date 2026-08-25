@extends('layouts.admin')
@section('title', 'Platform Analytics')
@section('header_title', 'Platform Analytics & Deep Dive')
@section('header_subtitle', 'Volume trends, revenue growth, and comparative restaurant benchmarks')

@section('content')
<!-- High Level KPI Metrics -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Today's Order GMV</span>
            <div class="metric-icon blue">💰</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($totalRevenueToday) }}</div>
        <div class="metric-footer">{{ $totalOrdersToday }} orders received today</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Month-To-Date GMV</span>
            <div class="metric-icon green">📈</div>
        </div>
        <div class="metric-value">Rs. {{ number_format($totalRevenueMonth) }}</div>
        <div class="metric-footer">{{ number_format($totalOrdersMonth) }} orders across all tenants</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Bot Conversations</span>
            <div class="metric-icon purple">💬</div>
        </div>
        <div class="metric-value">{{ number_format($totalConversations) }}</div>
        <div class="metric-footer">Total customer interaction sessions</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Export Reports</span>
            <div class="metric-icon orange">📑</div>
        </div>
        <div class="metric-value" style="font-size: 16px; margin: 6px 0;">CSV & Custom</div>
        <div class="metric-footer">
            <a href="{{ route('admin.reports.custom') }}" style="color: #4f46e5; text-decoration: none; font-weight: 700;">Open Custom Generator →</a>
        </div>
    </div>
</div>

<!-- 14-Day Growth & GMV Trends Chart -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>14-Day Platform Orders & Revenue Trend</h3>
            <p>Interactive multi-axis growth graph</p>
        </div>
    </div>
    <div style="position: relative; height: 280px; width: 100%;">
        <canvas id="platformAnalyticsChart"></canvas>
    </div>
</div>

<!-- Comparative Restaurant Performance Benchmark -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Comparative Performance Benchmark</h3>
            <p>Comparing tenant GMV, order completion rates, and sales contribution</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Restaurant</th>
                    <th>City</th>
                    <th>Total Orders</th>
                    <th>Total Revenue</th>
                    <th>Order Success Rate</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($benchmark as $idx => $b)
                    <tr>
                        <td>
                            @if($idx === 0)
                                🥇
                            @elseif($idx === 1)
                                🥈
                            @elseif($idx === 2)
                                🥉
                            @else
                                #{{ $idx + 1 }}
                            @endif
                        </td>
                        <td><strong>{{ $b['name'] }}</strong></td>
                        <td>{{ $b['city'] ?: 'N/A' }}</td>
                        <td>{{ number_format($b['orders']) }}</td>
                        <td style="font-weight: 800; color: #10b981;">Rs. {{ number_format($b['revenue']) }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; max-width: 100px; height: 6px; background: var(--border-color); border-radius: 99px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ $b['success_rate'] }}%; background: {{ $b['success_rate'] > 80 ? '#10b981' : '#f97316' }};"></div>
                                </div>
                                <span style="font-size: 11.5px; font-weight: 700;">{{ $b['success_rate'] }}%</span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.restaurant.analytics', $b['id']) }}" class="btn btn-secondary btn-sm">Deep Stats →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 20px;">No benchmark data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('platformAnalyticsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Total Orders',
                        data: {!! json_encode($chartData) !!},
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Gross Merchandise Value (PKR)',
                        data: {!! json_encode($chartRev) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
