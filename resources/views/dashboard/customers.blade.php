@extends('layouts.dashboard')
@section('title', 'Customers')
@section('header_title', 'Customers Directory & CRM')
@section('header_subtitle', 'Persistent customer database, WhatsApp deal broadcasts, and ordering profiles')

@section('content')

<!-- TOP METRIC CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Total Customers</span>
            <div class="metric-icon-box blue">👥</div>
        </div>
        <div class="metric-value">{{ number_format($totalCustomers) }}</div>
        <div class="metric-footer">
            <span class="sub-badge blue">● WhatsApp CRM</span>
            <span>Unique phone profiles</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">VIP Customers</span>
            <div class="metric-icon-box green">⭐</div>
        </div>
        <div class="metric-value">{{ number_format($vipCount) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● 5+ Orders</span>
            <span>High-value regulars</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-title">Marketing Opt-In</span>
            <div class="metric-icon-box purple">📢</div>
        </div>
        <div class="metric-value">{{ number_format($marketingOptInCount) }}</div>
        <div class="metric-footer">
            <span class="sub-badge green">● Ready for Deals</span>
            <span>Receive WhatsApp offers</span>
        </div>
    </div>
</div>

<div class="panel-card">
    <div class="panel-header" style="flex-wrap: wrap; gap: 14px;">
        <div class="panel-title">
            <h3>Customer Directory ({{ $customers->total() }})</h3>
            <p>Stored profiles ready for automated future promotions and deals</p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <form method="GET" action="{{ route('dashboard.customers', $r->id) }}" style="display: flex; gap: 8px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Name / Phone / Area..." style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff; width: 180px;">

                <select name="tag" onchange="this.form.submit()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #fff;">
                    <option value="">All Tiers</option>
                    <option value="VIP" {{ request('tag') === 'VIP' ? 'selected' : '' }}>⭐ VIP Tier</option>
                    <option value="Frequent" {{ request('tag') === 'Frequent' ? 'selected' : '' }}>🔥 Frequent Tier</option>
                    <option value="New" {{ request('tag') === 'New' ? 'selected' : '' }}>🌱 New Tier</option>
                </select>

                <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
            </form>

            <button onclick="document.getElementById('modal-broadcast-deal').style.display='flex'" class="btn btn-success" style="padding: 8px 16px;">
                📢 Send WhatsApp Deal / Offer
            </button>

            <a href="{{ route('dashboard.export-customers-csv', $r->id) }}" class="btn btn-secondary" style="padding: 8px 14px;">
                📥 Export CSV
            </a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Tier</th>
                    <th>Delivery Area</th>
                    <th>Orders Count</th>
                    <th>Total Spent</th>
                    <th>Last Order</th>
                    <th>Direct Chat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td>
                        <strong style="font-size: 13px; color: #0f172a;">{{ $c->name ?: 'Customer' }}</strong>
                        <div style="font-size: 11px; color: #64748b;">📱 {{ $c->phone }}</div>
                    </td>
                    <td>
                        @if($c->tag === 'VIP')
                            <span class="badge-status delivered" style="font-size: 11px;">⭐ VIP Customer</span>
                        @elseif($c->tag === 'Frequent')
                            <span class="badge-status confirmed" style="font-size: 11px;">🔥 Frequent</span>
                        @else
                            <span class="badge-status pending" style="font-size: 11px;">🌱 New Customer</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size: 12px; color: #475569;">{{ Str::limit($c->address ?: 'Recorded in chat', 35) }}</span>
                    </td>
                    <td>
                        <strong>{{ $c->total_orders }}</strong> {{ Str::plural('order', $c->total_orders) }}
                    </td>
                    <td><strong style="color: #4f46e5;">PKR {{ number_format($c->total_spent, 0) }}</strong></td>
                    <td>
                        {{ $c->last_order_at ? $c->last_order_at->diffForHumans() : 'Recently' }}
                    </td>
                    <td>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->phone) }}" target="_blank" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px;">
                            💬 Open Chat ↗
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                        No customer profiles match the filter. Incoming bot orders will automatically register customer profiles here.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $customers->links() }}
    </div>
</div>

<!-- SEND BROADCAST DEAL / OFFER MODAL -->
<div id="modal-broadcast-deal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; border-radius: 20px; max-width: 520px; width: 100%; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a;">📢 Send WhatsApp Deal / Offer</h3>
                <p style="font-size: 12px; color: #64748b; margin-top: 2px;">Your bot will send this personalized deal message to opted-in customers</p>
            </div>
            <button onclick="document.getElementById('modal-broadcast-deal').style.display='none'" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94a3b8;">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.broadcast-deal', $r->id) }}">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Target Customer Audience <span style="color: #dc2626;">*</span>
                </label>
                <select name="target" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                    <option value="all">All Opted-In Customers ({{ $marketingOptInCount }} contacts)</option>
                    <option value="vip">VIP Customers Only ({{ $vipCount }} contacts)</option>
                    <option value="frequent">VIP + Frequent Regulars</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Promotional Deal / Offer Text <span style="color: #dc2626;">*</span>
                </label>
                <textarea name="message" rows="4" required placeholder="e.g. 🍕 Weekend Special: Get 20% OFF on all Pizzas! Order today and enjoy free delivery on orders above Rs. 1,000!" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc; resize: vertical; font-family: inherit;"></textarea>
                <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">Bot will automatically attach your menu ordering prompt to the message.</span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('modal-broadcast-deal').style.display='none'" class="btn btn-secondary" style="padding: 10px 18px;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-success" style="padding: 10px 22px;">
                    🚀 Dispatch via WhatsApp Bot
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
