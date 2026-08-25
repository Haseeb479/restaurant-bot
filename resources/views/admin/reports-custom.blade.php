@extends('layouts.admin')
@section('title', 'Custom Reports Generator')
@section('header_title', 'Custom Reports & Data Exports')
@section('header_subtitle', 'Filter orders by custom date range, specific restaurant, and download CSV reports')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Report Filter & Generator</h3>
            <p>Generate financial and operational transaction reports</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.reports.export-csv', request()->all()) }}" class="btn btn-success">
                <span>📥</span><span>Export Filtered CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.reports.custom') }}" style="display: grid; grid-template-columns: 1fr 1fr 1.5fr 1fr auto; gap: 10px; margin-bottom: 20px;">
        <div>
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-input" value="{{ $startDate->format('Y-m-d') }}">
        </div>

        <div>
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-input" value="{{ $endDate->format('Y-m-d') }}">
        </div>

        <div>
            <label class="form-label">Restaurant</label>
            <select name="restaurant_id" class="form-select">
                <option value="">All Restaurants</option>
                @foreach($restaurants as $r)
                    <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Order Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div style="display: flex; align-items: flex-end; gap: 6px;">
            <button type="submit" class="btn btn-primary" style="height: 38px;">Generate</button>
            <a href="{{ route('admin.reports.custom') }}" class="btn btn-secondary" style="height: 38px;" title="Reset">✕</a>
        </div>
    </form>

    <!-- Filtered Summary Banner -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 18px;">
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Total Orders in Selection</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--text-primary);">{{ number_format($totalFilteredOrders) }}</div>
        </div>
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Total GMV Revenue</div>
            <div style="font-size: 20px; font-weight: 800; color: #10b981;">Rs. {{ number_format($totalFilteredRevenue) }}</div>
        </div>
    </div>

    <!-- Orders Result Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tracking Code</th>
                    <th>Restaurant</th>
                    <th>Customer Name</th>
                    <th>Customer Phone</th>
                    <th>Total (PKR)</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td><code>{{ $o->tracking_code }}</code></td>
                        <td><strong>{{ $o->restaurant->name ?? 'N/A' }}</strong></td>
                        <td>{{ $o->customer_name }}</td>
                        <td>{{ $o->customer_phone }}</td>
                        <td style="font-weight: 700;">Rs. {{ number_format($o->total) }}</td>
                        <td>
                            @if($o->status === 'delivered')
                                <span class="badge badge-green">Delivered</span>
                            @elseif($o->status === 'cancelled')
                                <span class="badge badge-red">Cancelled</span>
                            @else
                                <span class="badge badge-yellow">{{ ucfirst($o->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 11.5px; color: var(--text-secondary);">
                            {{ $o->created_at->format('d M Y, h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-secondary);">
                            No orders found matching the chosen date range and filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 18px;">
        {{ $orders->links() }}
    </div>
</div>
@endsection
