@extends('layouts.dashboard')
@section('title', 'Orders History')
@section('header_title', 'Orders Archive & History')
@section('header_subtitle', 'Search, filter, and view full historical records of past orders')

@section('content')

<div class="panel-card">
    <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
        <div class="panel-title">
            <h3>Orders History ({{ $orders->total() }})</h3>
            <p>Full record of incoming WhatsApp customer orders</p>
        </div>

        <form method="GET" action="{{ route('dashboard.history', $r->id) }}" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tracking # / phone..." style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff; width: 200px;">

            <select name="status" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff;">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="preparing" {{ request('status') == 'preparing' ? 'selected' : '' }}>Preparing</option>
                <option value="out_for_delivery" {{ request('status') == 'out_for_delivery' ? 'selected' : '' }}>Dispatched</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('dashboard.history', $r->id) }}" class="btn btn-secondary" style="padding: 8px 14px;">Clear</a>
            @endif
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Items</th>
                    <th>Total Bill</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><code>#{{ $o->tracking_code }}</code></td>
                    <td>
                        <div>{{ $o->created_at->format('d M Y') }}</div>
                        <div style="font-size: 11px; color: #64748b;">{{ $o->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        <strong>{{ $o->customer_name ?: 'Customer' }}</strong>
                        <div style="font-size: 11px; color: #64748b;">{{ $o->customer_phone }}</div>
                    </td>
                    <td>
                        <span style="font-size: 12px; color: #475569;">{{ Str::limit($o->delivery_address ?: 'Standard Delivery', 30) }}</span>
                    </td>
                    <td>{{ $o->items->count() }} items</td>
                    <td><strong>PKR {{ number_format($o->total, 0) }}</strong></td>
                    <td>
                        <span class="badge-status {{ $o->status }}">
                            {{ ucfirst(str_replace('_', ' ', $o->status)) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('dashboard.print-bill', [$r->id, $o->id]) }}" target="_blank" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px; background:#0f172a; color:#fff; border-color:#0f172a;">
                            🖨️ Print Bill
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                        No orders match the selected filters.
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
