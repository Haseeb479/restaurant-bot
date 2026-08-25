@extends('layouts.admin')
@section('title', 'System Health & Maintenance')
@section('header_title', 'System Health & Infrastructure')
@section('header_subtitle', 'Live server telemetry, bot daemon health, database maintenance, and backup triggers')

@section('content')
<!-- Telemetry Metrics -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">PHP Runtime</span>
            <div class="metric-icon blue">🐘</div>
        </div>
        <div class="metric-value" style="font-size: 18px;">PHP {{ $phpVersion }}</div>
        <div class="metric-footer">Laravel {{ $laravelVersion }}</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Memory Allocation</span>
            <div class="metric-icon green">⚡</div>
        </div>
        <div class="metric-value" style="font-size: 18px;">{{ $memoryUsage }}</div>
        <div class="metric-footer">Current process memory</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Bot Daemon Status</span>
            <div class="metric-icon green">🤖</div>
        </div>
        <div class="metric-value" style="font-size: 18px;">{{ $onlineBots->count() }} Online</div>
        <div class="metric-footer">{{ $issuesBots->count() }} bots need attention</div>
    </div>
</div>

<!-- Database Maintenance & System Actions -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Database & Platform Maintenance</h3>
            <p>Perform optimizations, purge old logs, and trigger backup snapshots</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
        <!-- Vacuum / Optimize DB -->
        <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 6px;">🧹 Optimize Database</h4>
            <p style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 14px;">Reclaims unused space, re-indexes tables, and optimizes database search queries.</p>
            <form method="POST" action="{{ route('admin.system.optimize') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">Optimize Tables</button>
            </form>
        </div>

        <!-- Clean Logs -->
        <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 6px;">🗑️ Purge Old Logs</h4>
            <p style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 14px;">Removes expired audit logs and temporary session entries older than 60 days.</p>
            <form method="POST" action="{{ route('admin.system.clean-logs') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">Clean Old Logs</button>
            </form>
        </div>

        <!-- Create DB Backup Snapshot -->
        <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 6px;">💾 Database Snapshot</h4>
            <p style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 14px;">Generates a point-in-time state backup recorded in platform audit records.</p>
            <form method="POST" action="{{ route('admin.system.backup') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">Create Snapshot Backup</button>
            </form>
        </div>
    </div>
</div>

<!-- Bot Fleet Connectivity Status Table -->
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>WhatsApp Bot Fleet Status</h3>
            <p>Real-time connectivity and error diagnostics for all restaurants</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Bot Number</th>
                    <th>Connection State</th>
                    <th>Last Error / Diagnostic</th>
                    <th>Last Ping</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($restaurants as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong></td>
                        <td><code>{{ $r->whatsapp_number }}</code></td>
                        <td>
                            @if($r->bot_status === 'connected')
                                <span class="badge badge-green">🟢 Online</span>
                            @elseif($r->bot_status === 'qr_pending')
                                <span class="badge badge-yellow">🟡 QR Ready</span>
                            @else
                                <span class="badge badge-gray">⚪ Disconnected</span>
                            @endif
                        </td>
                        <td>
                            @if($r->last_error)
                                <span style="color: #ef4444; font-size: 11.5px;">⚠️ {{ Str::limit($r->last_error, 40) }}</span>
                            @else
                                <span style="color: #10b981; font-size: 11.5px;">✓ Healthy</span>
                            @endif
                        </td>
                        <td style="font-size: 11.5px; color: var(--text-secondary);">
                            {{ $r->bot_last_seen_at ? $r->bot_last_seen_at->diffForHumans() : 'Never' }}
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('admin.restaurant.reset-bot', $r->id) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" title="Clear Error / Reconnect">🔄 Reset</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
