@extends('layouts.admin')
@section('title', 'Logs')
@section('header_title', 'System Logs & Security Audit')
@section('header_subtitle', 'Audit logs, bot error stacktraces, and platform tenant activity')

@section('content')

<!-- RECENT ERRORS LOG -->
<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>⚠️ Recent Bot Exceptions & Failure Logs</h3>
            <p>Captured communication errors and disconnected session events</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Error Details</th>
                    <th>Occurred At</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restaurants as $r)
                <tr>
                    <td><strong>{{ $r->name }}</strong> ({{ $r->whatsapp_number }})</td>
                    <td><code style="color: #dc2626; font-size: 12px;">{{ $r->last_error ?: 'Socket disconnected unexpectedly' }}</code></td>
                    <td>{{ $r->last_error_at ? $r->last_error_at->diffForHumans() : 'Recently' }}</td>
                    <td><span class="badge badge-red">Failed</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.clear-error', $r->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">
                                Clear Log
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #16a34a; padding: 2rem;">
                        ✓ All systems healthy! No error logs recorded.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- AUDIT LOG (RECENT ORDERS DISPATCHED) -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>📋 Platform Activity Audit Stream</h3>
            <p>Latest 20 orders processed across all tenant restaurants</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Restaurant</th>
                    <th>Tracking #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOrders as $o)
                <tr>
                    <td>{{ $o->created_at->format('M d, H:i:s') }}</td>
                    <td><strong>{{ $o->restaurant->name ?? '—' }}</strong></td>
                    <td><code>#{{ $o->tracking_code }}</code></td>
                    <td>{{ $o->customer_phone }}</td>
                    <td><strong>PKR {{ number_format($o->total, 0) }}</strong></td>
                    <td><span class="badge badge-green">{{ ucfirst($o->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
