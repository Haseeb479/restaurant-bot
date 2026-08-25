@extends('layouts.admin')
@section('title', 'Security Audit Trail')
@section('header_title', 'Security & Activity Audit Trail')
@section('header_subtitle', 'Comprehensive audit logs of all Super Admin administrative actions, credentials resets, and system changes')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Audit Records</h3>
            <p>Chronological record of administrative operations with IP and timestamp</p>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="{{ route('admin.audit-logs') }}" style="display: flex; gap: 10px; margin-bottom: 18px;">
        <input type="text" name="search" class="form-input" placeholder="Search by action, details, IP address..." value="{{ request('search') }}" style="max-width: 380px;">
        <button type="submit" class="btn btn-secondary">Search Logs</button>
        <a href="{{ route('admin.audit-logs') }}" class="btn btn-secondary" title="Reset">✕</a>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Actor</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td style="font-size: 11.5px; color: var(--text-secondary); white-space: nowrap;">
                            {{ $l->created_at->format('d M Y, H:i:s') }}
                            <div style="font-size: 10.5px;">{{ $l->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <code style="font-weight: 700; color: #4f46e5;">{{ $l->action }}</code>
                        </td>
                        <td><strong>{{ $l->actor_name }}</strong></td>
                        <td><span class="badge badge-gray">{{ $l->ip_address ?: '127.0.0.1' }}</span></td>
                        <td style="font-size: 12px;">{{ $l->details ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 25px;">No audit records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
