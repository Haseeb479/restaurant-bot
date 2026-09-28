@extends('layouts.admin')
@section('title', 'Diagnostics for ' . $r->name)
@section('header_title', $r->name . ' — Tenant Diagnostics')
@section('header_subtitle', 'WhatsApp bot health, AI engine status, subscription usage, and throughput telemetry')

@section('content')

<!-- Privacy Protection Notice -->
<div class="panel-card" style="border-left: 4px solid var(--brand-primary); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; background: rgba(79, 70, 229, 0.04);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 22px;">🛡️</span>
        <div>
            <div style="font-weight: 800; font-size: 13.5px; color: var(--text-heading);">Tenant Infrastructure Diagnostics & Health Monitor</div>
            <div style="font-size: 12px; color: var(--text-muted);">Displaying platform-level bot connectivity, AI configuration, and transaction signals. Private kitchen recipes and customer contact records are strictly isolated.</div>
        </div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('admin.restaurant.edit', $r->id) }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">Edit Settings ✏️</a>
        <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">← All Stores</a>
    </div>
</div>

<div class="panel-card" style="margin-bottom: 20px;">
    <div class="metric-grid">
        <!-- 1. Bot Connectivity -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">WhatsApp Bot Health</span>
                <div class="metric-icon {{ ($r->bot_status ?? 'disconnected') === 'connected' ? 'green' : 'red' }}">🤖</div>
            </div>
            <div class="metric-value" style="font-size: 20px;">
                @if(($r->bot_status ?? 'disconnected') === 'connected')
                    <span style="color: #10b981;">Online</span>
                @else
                    <span style="color: #ef4444;">Offline</span>
                @endif
            </div>
            <div class="metric-footer"><code>{{ $r->whatsapp_number }}</code></div>
        </div>

        <!-- 2. AI Engine & BYOK Status -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">AI Engine</span>
                <div class="metric-icon purple">🧠</div>
            </div>
            <div class="metric-value" style="font-size: 19px;">{{ strtoupper($aiProvider) }}</div>
            <div class="metric-footer">
                @if($hasCustomKey)
                    <span style="color: #10b981; font-weight: 700;">🔑 Dedicated BYOK Key</span>
                @else
                    <span style="color: #64748b;">⚡ Platform Master Key</span>
                @endif
            </div>
        </div>

        <!-- 3. Bot Conversations -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Total Conversations</span>
                <div class="metric-icon blue">💬</div>
            </div>
            <div class="metric-value">{{ number_format($totalConversations) }}</div>
            <div class="metric-footer">User interaction sessions</div>
        </div>

        <!-- 4. Orders Throughput -->
        <div class="metric-card">
            <div class="metric-header">
                <span class="metric-title">Bot Order Conversion</span>
                <div class="metric-icon orange">📦</div>
            </div>
            <div class="metric-value">{{ number_format($totalOrders) }}</div>
            <div class="metric-footer">{{ $conversionRate }}% conversation-to-order rate</div>
        </div>
    </div>
</div>

<!-- 14-Day Bot Traffic & Activity Trend Chart -->
<div class="panel-card" style="margin-bottom: 22px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>14-Day Bot Traffic & Orders Trend</h3>
            <p>Daily customer chat interactions and order throughput handled by the AI bot</p>
        </div>
    </div>
    <div style="position: relative; height: 260px; width: 100%;">
        <canvas id="restChart"></canvas>
    </div>
</div>

<!-- Bottom Row: Bot Infrastructure Diagnostics & Recent Signals -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Infrastructure & System Health -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Bot Engine & Infrastructure Diagnostics</h3>
                <p>Technical telemetry and subscription profile</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-card);">
                <span style="color: var(--text-muted); font-weight: 600;">Subscription Tier</span>
                <span class="badge badge-purple" style="font-weight: 800;">{{ strtoupper($r->plan) }}</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-card);">
                <span style="color: var(--text-muted); font-weight: 600;">Plan Expiration</span>
                <span style="font-weight: 700; color: var(--text-heading);">
                    {{ $r->plan_expires_at ? $r->plan_expires_at->format('d M Y') : 'Lifetime Active' }}
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-card);">
                <span style="color: var(--text-muted); font-weight: 600;">AI Engine Model</span>
                <code style="font-size: 12px;">{{ $aiModel }}</code>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-card);">
                <span style="color: var(--text-muted); font-weight: 600;">Key Allocation Mode</span>
                <span style="font-weight: 700; color: {{ $hasCustomKey ? '#10b981' : '#6366f1' }};">
                    {{ $hasCustomKey ? 'Isolated Custom BYOK' : 'Shared Platform Key' }}
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-card);">
                <span style="color: var(--text-muted); font-weight: 600;">Last Bot Ping</span>
                <span style="font-weight: 600; color: var(--text-muted);">
                    {{ $r->last_seen_at ? $r->last_seen_at->diffForHumans() : 'No recent ping' }}
                </span>
            </div>

            @if($r->last_error)
            <div style="margin-top: 6px; padding: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 10px;">
                <div style="font-weight: 800; font-size: 12px; color: #dc2626; margin-bottom: 4px;">⚠️ Last Recorded Bot Exception:</div>
                <code style="font-size: 11.5px; color: #dc2626; word-break: break-all;">{{ $r->last_error }}</code>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 6px;">Logged: {{ $r->last_error_at ? $r->last_error_at->diffForHumans() : 'Recently' }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Bot Activity Feed (PII Protected) -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-header">
            <div class="panel-title">
                <h3>Recent Bot Order Signals</h3>
                <p>Latest transactions processed via WhatsApp (PII Masked)</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tracking #</th>
                        <th>Customer (Masked)</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td>
                                <code>{{ $order->tracking_code }}</code>
                                <div style="font-size: 10.5px; color: var(--text-muted);">{{ $order->created_at->format('d M, h:i A') }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ \App\Support\LogSanitizer::maskName($order->customer_name) }}</div>
                                <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">{{ \App\Support\LogSanitizer::maskPhone($order->customer_phone) }}</div>
                            </td>
                            <td><strong>PKR {{ number_format($order->total) }}</strong></td>
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
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 25px;">No recent bot orders found.</td>
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
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Bot Conversations',
                        data: {!! json_encode($chartConversations) !!},
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2.5,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders Processed',
                        data: {!! json_encode($chartOrders) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 2.5,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(150, 150, 150, 0.1)' }
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
