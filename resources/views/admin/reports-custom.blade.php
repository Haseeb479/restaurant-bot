@extends('layouts.admin')
@section('title', 'SaaS Usage & Telemetry Reports')
@section('header_title', 'SaaS Usage & Telemetry Reports')
@section('header_subtitle', 'Tenant throughput reports, bot conversation volumes, and platform metrics across all stores')

@section('content')

<!-- Privacy Protection Notice -->
<div class="panel-card" style="border-left: 4px solid var(--brand-primary); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; background: rgba(79, 70, 229, 0.04);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 22px;">📊</span>
        <div>
            <div style="font-weight: 800; font-size: 13.5px; color: var(--text-heading);">Platform Business & Bot Usage Analytics</div>
            <div style="font-size: 12px; color: var(--text-muted);">This report generates multi-tenant SaaS metrics: bot message traffic, order conversion volumes, AI key mode, and plan status. Customer food dishes and contact details are excluded.</div>
        </div>
    </div>
    <span class="badge badge-green" style="font-size: 11px; padding: 4px 10px;">Zero Customer PII ✓</span>
</div>

<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Report Filter & Generator</h3>
            <p>Filter tenant activity by date range, subscription plan, and bot connection status</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.reports.export-csv', request()->all()) }}" class="btn btn-success" style="font-weight: 700; padding: 7px 14px; gap: 6px;">
                <span>📥</span><span>Export SaaS Usage CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.reports.custom') }}" style="display: grid; grid-template-columns: 1fr 1fr 1.3fr 1fr 1fr auto; gap: 10px; margin-bottom: 20px;">
        <div>
            <label class="form-label" style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Start Date</label>
            <input type="date" name="start_date" class="form-input" value="{{ $startDate->format('Y-m-d') }}">
        </div>

        <div>
            <label class="form-label" style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">End Date</label>
            <input type="date" name="end_date" class="form-input" value="{{ $endDate->format('Y-m-d') }}">
        </div>

        <div>
            <label class="form-label" style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Restaurant</label>
            <select name="restaurant_id" class="form-select">
                <option value="">All Restaurants</option>
                @foreach($allRestaurants as $r)
                    <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Subscription Plan</label>
            <select name="plan" class="form-select">
                <option value="">All Plans</option>
                <option value="free" {{ request('plan') === 'free' ? 'selected' : '' }}>Free Trial</option>
                <option value="starter" {{ request('plan') === 'starter' ? 'selected' : '' }}>Starter</option>
                <option value="growth" {{ request('plan') === 'growth' ? 'selected' : '' }}>Growth</option>
                <option value="enterprise" {{ request('plan') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
            </select>
        </div>

        <div>
            <label class="form-label" style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bot Health</label>
            <select name="bot_status" class="form-select">
                <option value="">All Statuses</option>
                <option value="connected" {{ request('bot_status') === 'connected' ? 'selected' : '' }}>Connected</option>
                <option value="disconnected" {{ request('bot_status') === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
            </select>
        </div>

        <div style="display: flex; align-items: flex-end; gap: 6px;">
            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px;">Generate</button>
            <a href="{{ route('admin.reports.custom') }}" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center; padding: 0 12px;" title="Reset">✕</a>
        </div>
    </form>

    <!-- Filtered Summary Banner -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px; background: var(--bg-canvas); border: 1px solid var(--border-card); border-radius: 12px; padding: 16px 20px;">
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Tenants In Report</div>
            <div style="font-size: 22px; font-weight: 800; color: var(--text-heading); margin-top: 2px;">{{ number_format($totalFilteredTenants) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Active customer-facing stores</div>
        </div>
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Bot Orders Processed</div>
            <div style="font-size: 22px; font-weight: 800; color: #10b981; margin-top: 2px;">{{ number_format($totalPeriodOrders) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">In selected period ({{ $startDate->format('M d') }} - {{ $endDate->format('M d') }})</div>
        </div>
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Bot Conversations</div>
            <div style="font-size: 22px; font-weight: 800; color: #6366f1; margin-top: 2px;">{{ number_format($totalPeriodConversations) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Total user chats handled by bot</div>
        </div>
    </div>

    <!-- Tenants Result Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant Restaurant</th>
                    <th>WhatsApp Bot</th>
                    <th>Plan & Status</th>
                    <th>Bot Health</th>
                    <th>AI Engine</th>
                    <th style="text-align: center;">Orders (Period)</th>
                    <th style="text-align: center;">Chats (Period)</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $r)
                    <tr>
                        <td>
                            <div style="font-weight: 800; font-size: 13px; color: var(--text-heading);">{{ $r->name }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $r->city ?: 'Pakistan' }} • ID #{{ $r->id }}</div>
                        </td>
                        <td>
                            <code style="font-size: 12px;">{{ $r->whatsapp_number }}</code>
                        </td>
                        <td>
                            <span class="badge badge-purple" style="font-weight: 700;">{{ strtoupper($r->plan) }}</span>
                            <div style="font-size: 10.5px; color: var(--text-muted); margin-top: 3px;">
                                @if($r->plan_expires_at)
                                    Exp: {{ $r->plan_expires_at->format('d M Y') }}
                                @else
                                    Active / Lifetime
                                @endif
                            </div>
                        </td>
                        <td>
                            @if(($r->bot_status ?? 'disconnected') === 'connected')
                                <span class="badge badge-green" style="gap: 4px;">
                                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#10b981;"></span> Online
                                </span>
                            @else
                                <span class="badge badge-red" style="gap: 4px;">
                                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#ef4444;"></span> Offline
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 700; font-size: 12px;">{{ strtoupper($r->getAiProvider()) }}</div>
                            <div style="font-size: 10.5px; color: var(--text-muted);">
                                @if($r->hasCustomAiKey())
                                    <span style="color: #10b981; font-weight: 700;">🔑 Dedicated BYOK</span>
                                @else
                                    <span style="color: #64748b;">⚡ Platform Key</span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 800; font-size: 13.5px; color: var(--text-heading);">
                            {{ number_format($r->period_orders_count ?? 0) }}
                        </td>
                        <td style="text-align: center; font-weight: 800; font-size: 13.5px; color: #6366f1;">
                            {{ number_format($r->period_conversations_count ?? 0) }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.restaurant.analytics', $r->id) }}" class="btn btn-secondary btn-sm" style="font-weight: 700; border-radius: 8px;">
                                Diagnostics →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            No tenant restaurants match the chosen filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 18px;">
        {{ $tenants->links() }}
    </div>
</div>
@endsection
