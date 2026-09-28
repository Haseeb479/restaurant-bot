@extends('layouts.admin')
@section('title', 'Bot Activity & Order Telemetry')
@section('header_title', 'Bot Activity & Order Telemetry')
@section('header_subtitle', 'Real-time WhatsApp bot transaction flow, delivery states, and channel health across all tenants')

@section('content')

<!-- Privacy Protection Notice -->
<div class="panel-card" style="border-left: 4px solid var(--brand-primary); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; background: rgba(79, 70, 229, 0.04);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 22px;">🛡️</span>
        <div>
            <div style="font-weight: 800; font-size: 13.5px; color: var(--text-heading);">Tenant Privacy & Customer PII Protection Guard</div>
            <div style="font-size: 12px; color: var(--text-muted);">In compliance with multi-tenant privacy architecture, customer phone numbers and names are masked, and private culinary orders remain strictly isolated to tenant restaurants.</div>
        </div>
    </div>
    <span class="badge badge-green" style="font-size: 11px; padding: 4px 10px;">Zero Customer Snooping ✓</span>
</div>

<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
        <div class="panel-title">
            <h3>Bot Activity Feed ({{ $orders->total() }})</h3>
            <p>Filter bot-dispatched orders by tracking code, status, or restaurant</p>
        </div>

        <form method="GET" action="{{ route('admin.orders') }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tracking code..." style="padding: 8px 14px; border: 1px solid var(--border-card); border-radius: 10px; font-size: 13px; outline: none; background: var(--bg-card); color: var(--text-heading); width: 180px;">

            <select name="restaurant_id" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid var(--border-card); border-radius: 10px; font-size: 13px; outline: none; background: var(--bg-card); color: var(--text-heading);">
                <option value="">All Restaurants</option>
                @foreach($restaurants as $r)
                    <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid var(--border-card); border-radius: 10px; font-size: 13px; outline: none; background: var(--bg-card); color: var(--text-heading);">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="preparing" {{ request('status') == 'preparing' ? 'selected' : '' }}>Preparing</option>
                <option value="out_for_delivery" {{ request('status') == 'out_for_delivery' ? 'selected' : '' }}>Dispatched</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
            @if(request()->hasAny(['search', 'restaurant_id', 'status']))
                <a href="{{ route('admin.orders') }}" class="btn btn-secondary" style="padding: 8px 14px;">Clear</a>
            @endif
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order & Tracking #</th>
                    <th>Date & Time</th>
                    <th>Restaurant Tenant</th>
                    <th>Channel</th>
                    <th>Customer (Masked)</th>
                    <th>Order Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td>
                        <div style="font-weight: 800; font-size: 13px; color: var(--text-heading);">#{{ $o->id }}</div>
                        <div style="font-size: 11px; font-family: monospace; color: var(--brand-primary); margin-top: 1px;">{{ $o->tracking_code }}</div>
                    </td>
                    <td>
                        <div>{{ $o->created_at->format('d M Y') }}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">{{ $o->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        <strong>{{ $o->restaurant->name ?? '—' }}</strong>
                        <div style="font-size: 11px; color: var(--text-muted);">{{ $o->restaurant->city ?? 'Pakistan' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-purple" style="font-size: 11px; gap: 4px;">
                            <span>🤖</span> WhatsApp Bot
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ \App\Support\LogSanitizer::maskName($o->customer_name) }}</div>
                        <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">{{ \App\Support\LogSanitizer::maskPhone($o->customer_phone) }}</div>
                    </td>
                    <td>
                        <strong>PKR {{ number_format($o->total, 0) }}</strong>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($o->status) {
                                'delivered' => 'badge-green',
                                'cancelled' => 'badge-red',
                                'out_for_delivery' => 'badge-blue',
                                'preparing' => 'badge-yellow',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $o->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                        No bot activity or orders match the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $orders->links() }}
    </div>
</div>

@endsection