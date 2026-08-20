@extends('layouts.admin')
@section('title', 'System Health')
@section('header_title', 'System Health & Bot Monitoring')
@section('header_subtitle', 'Live WhatsApp socket connectivity, QR token status, and error logs')

@section('content')

<!-- STATUS SUMMARY ROW -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Connected Bots</span>
            <div class="metric-icon-box green">🟢</div>
        </div>
        <div class="metric-value">{{ $onlineBots->count() }} / {{ $restaurants->count() }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● Active Sockets</span>
            <span>Online and listening</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Bots Needing Attention</span>
            <div class="metric-icon-box red">⚠️</div>
        </div>
        <div class="metric-value">{{ $issuesBots->count() }}</div>
        <div class="metric-footer">
            <span class="sub-badge red">● QR or Disconnected</span>
            <span>Requires reconnection</span>
        </div>
    </div>
</div>

<!-- SYSTEM HEALTH TABLE -->
<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>WhatsApp Bot Connection Health</h3>
            <p>Real-time socket status for each restaurant's Baileys engine</p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>WhatsApp Phone</th>
                    <th>Bot Status</th>
                    <th>QR Session</th>
                    <th>Last Seen</th>
                    <th>Recent Error</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($restaurants as $r)
                <tr>
                    <td><strong>{{ $r->name }}</strong></td>
                    <td><code>{{ $r->whatsapp_number }}</code></td>
                    <td>
                        @if($r->bot_status === 'connected')
                            <span class="badge badge-green">🟢 Connected</span>
                        @elseif($r->bot_status === 'qr_pending')
                            <span class="badge badge-yellow">🟡 QR Pending</span>
                        @else
                            <span class="badge badge-red">🔴 Disconnected</span>
                        @endif
                    </td>
                    <td>
                        @if($r->bot_status === 'connected')
                            <span class="badge badge-green">Valid & Active</span>
                        @else
                            <span class="badge badge-yellow">Scan Required</span>
                        @endif
                    </td>
                    <td>{{ $r->bot_last_seen_at ? $r->bot_last_seen_at->diffForHumans() : 'Recently' }}</td>
                    <td>
                        @if($r->last_error)
                            <span style="color: #dc2626; font-size: 12px; font-weight: 600;">⚠️ {{ Str::limit($r->last_error, 35) }}</span>
                        @else
                            <span style="color: #16a34a; font-size: 12px;">✓ Healthy</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            @if($r->last_error)
                            <form method="POST" action="{{ route('admin.clear-error', $r->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">
                                    Clear Error
                                </button>
                            </form>
                            @endif
                            <a href="/dashboard/{{ $r->id }}/connect-whatsapp" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;">
                                QR Scanner ↗
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
