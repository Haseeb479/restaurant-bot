@extends('layouts.admin')
@section('title', 'Orders (All)')
@section('header_title', 'All Platform Orders')
@section('header_subtitle', 'Real-time feed and historical order archive across all tenant restaurants')

@section('content')

<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
        <div class="panel-title">
            <h3>Orders Directory ({{ $orders->total() }})</h3>
            <p>Search by tracking code, phone, or filter by restaurant</p>
        </div>

        <form method="GET" action="{{ route('admin.orders') }}" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tracking # / phone..." style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff; width: 180px;">

            <select name="restaurant_id" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff;">
                <option value="">All Restaurants</option>
                @foreach($restaurants as $r)
                    <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->name }}
                    </option>
                @endforeach
            </select>

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
            @if(request()->hasAny(['search', 'restaurant_id', 'status']))
                <a href="{{ route('admin.orders') }}" class="btn btn-secondary" style="padding: 8px 14px;">Clear</a>
            @endif
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Date & Time</th>
                    <th>Restaurant</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
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
                    <td><strong>{{ $o->restaurant->name ?? '—' }}</strong></td>
                    <td>
                        <div>{{ $o->customer_name ?: 'Guest' }}</div>
                        <div style="font-size: 11px; color: #64748b;">{{ $o->customer_phone }}</div>
                    </td>
                    <td>{{ $o->items->count() }} items</td>
                    <td><strong>PKR {{ number_format($o->total, 0) }}</strong></td>
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
                    <td>
                        <a href="/track/{{ $o->tracking_code }}" target="_blank" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">
                            Track ↗
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