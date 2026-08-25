@extends('layouts.admin')
@section('title', 'Support Tickets Helpdesk')
@section('header_title', 'Support Helpdesk')
@section('header_subtitle', 'Manage customer & restaurant owner queries, priority issues, and status tracking')

@section('content')
<!-- Tickets Overview Counters -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Open Tickets</span>
            <div class="metric-icon red">🚨</div>
        </div>
        <div class="metric-value">{{ $openCount }}</div>
        <div class="metric-footer">Awaiting first response</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">In Progress</span>
            <div class="metric-icon orange">⏳</div>
        </div>
        <div class="metric-value">{{ $progressCount }}</div>
        <div class="metric-footer">Actively being handled</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Resolved Tickets</span>
            <div class="metric-icon green">✓</div>
        </div>
        <div class="metric-value">{{ $resolvedCount }}</div>
        <div class="metric-footer">Successfully solved</div>
    </div>
</div>

<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Support Queue</h3>
            <p>Filter by status and priority</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.support') }}" style="display: flex; gap: 10px; margin-bottom: 18px;">
        <select name="status" class="form-select" style="max-width: 180px;">
            <option value="">All Statuses</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>

        <select name="priority" class="form-select" style="max-width: 180px;">
            <option value="">All Priorities</option>
            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
        </select>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.support') }}" class="btn btn-secondary" title="Reset">✕</a>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Subject</th>
                    <th>Restaurant / Contact</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $t)
                    <tr>
                        <td><code>{{ $t->ticket_id }}</code></td>
                        <td>
                            <strong>{{ $t->subject }}</strong>
                            <div style="font-size: 11px; color: var(--text-secondary);">{{ Str::limit($t->description, 50) }}</div>
                        </td>
                        <td>
                            <div>{{ $t->restaurant->name ?? ($t->contact_name ?: 'General') }}</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">{{ $t->contact_phone }}</div>
                        </td>
                        <td>
                            @if($t->priority === 'urgent')
                                <span class="badge badge-red">🚨 Urgent</span>
                            @elseif($t->priority === 'high')
                                <span class="badge badge-yellow">High</span>
                            @else
                                <span class="badge badge-gray">{{ ucfirst($t->priority) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($t->status === 'open')
                                <span class="badge badge-red">Open</span>
                            @elseif($t->status === 'in_progress')
                                <span class="badge badge-yellow">In Progress</span>
                            @elseif($t->status === 'resolved')
                                <span class="badge badge-green">Resolved</span>
                            @else
                                <span class="badge badge-gray">Closed</span>
                            @endif
                        </td>
                        <td style="font-size: 11.5px; color: var(--text-secondary);">{{ $t->created_at->diffForHumans() }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.support.detail', $t->id) }}" class="btn btn-primary btn-sm">View & Reply →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-secondary);">
                            No support tickets matching current filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px;">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
