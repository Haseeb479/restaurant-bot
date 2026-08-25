@extends('layouts.admin')
@section('title', 'Analytics for ' . $r->name)
@section('header_title', $r->name . ' — Analytics')
@section('header_subtitle', 'Performance metrics, top dishes, and 14-day sales trend')

@section('content')
<div class="panel-card" style="margin-bottom: 20px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>{{ $r->name }} Performance Summary</h3>
            <p>WhatsApp: <code>{{ $r->whatsapp_number }}</code> • Plan: <strong>{{ strtoupper($r->plan) }}</strong> • Status: <strong>{{ $r->is_active ? 'Active' : 'Inactive' }}</strong></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.restaurant.edit', $r->id) }}" class="btn btn-secondary btn-sm">Edit Restaurant ✏️</a>
            <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary btn-sm">← All Restaurants</a>
        </div>
    </div>

    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Total Orders</span>
                <div class="metric-icon blue">📦</div>
            </div>
            <div class="metric-value">{{ number_format($totalOrders) }}</div>
            <div class="metric-footer">{{ $cancelledOrders }} cancelled</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Total Revenue (GMV)</span>
                <div class="metric-icon green">💰</div>
            </div>
            <div class="metric-value">Rs. {{ number_format($totalRevenue) }}</div>
            <div class="metric-footer">Net completed orders</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Average Order Value</span>
                <div class="metric-icon purple">🎯</div>
            </div>
            <div class="metric-value">Rs. {{ number_format($avgOrderValue, 1) }}</div>
            <div class="metric-footer">Per successful order</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Customer Base</span>
                <div class="metric-icon orange">👥</div>
            </div>
            <div class="metric-value">{{ $r->customers_count }}</div>
            <div class="metric-footer">Registered customer profiles</div>
        </div>
    </div>
</div>

<!-- 14-Day Sales & Volume Trend Chart -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>14-Day Sales & Order Volume Trend</h3>
            <p>Daily order counts and daily revenue</p>
        </div>
    </div>
    <div style="position: relative; height: 260px; width: 100%;">
        <canvas id="restChart"></canvas>
    </div>
</div>

<!-- Bottom Row: Top Menu Items & Recent Orders -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Top Selling Dishes -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Top Selling Items</h3>
                <p>Most frequently ordered dishes</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Qty Sold</th>
                        <th style="text-align: right;">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topItems as $item)
                        <tr>
                            <td><strong>{{ $item->item_name }}</strong></td>
                            <td><span class="badge badge-blue">{{ $item->total_qty }}x</span></td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">Rs. {{ number_format($item->total_sales) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 15px;">No sales data recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="panel-card">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Recent Orders</h3>
                <p>Latest customer requests</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td><code>{{ $order->tracking_code }}</code></td>
                            <td>
                                <div>{{ $order->customer_name }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary);">{{ $order->customer_phone }}</div>
                            </td>
                            <td><strong>Rs. {{ number_format($order->total) }}</strong></td>
                            <td>
                                @if($order->status === 'delivered')
                                    <span class="badge badge-green">Delivered</span>
                                @elseif($order->status === 'cancelled')
                                    <span class="badge badge-red">Cancelled</span>
                                @else
                                    <span class="badge badge-yellow">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 15px;">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('restChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        type: 'line',
                        label: 'Orders Count',
                        data: {!! json_encode($chartOrders) !!},
                        borderColor: '#4f46e5',
                        backgroundColor: 'transparent',
                        tension: 0.35,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Revenue (PKR)',
                        data: {!! json_encode($chartRevenue) !!},
                        backgroundColor: 'rgba(16, 185, 129, 0.65)',
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: 'rgba(150, 150, 150, 0.1)' }
                    },
                    y1: {
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
