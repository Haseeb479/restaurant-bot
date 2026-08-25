@extends('layouts.admin')
@section('title', 'Restaurants Directory')
@section('header_title', 'Restaurants Management')
@section('header_subtitle', 'View, manage, edit credentials and monitor restaurant status')

@section('content')
<div class="panel-card">
    <div class="panel-header">
        <div class="panel-title">
            <h3>Registered Restaurants</h3>
            <p>Filter by status, search by phone or name, configure details</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.restaurants.pending') }}" class="btn btn-secondary">
                <span>⏳</span><span>Pending Approvals</span>
            </a>
            <a href="{{ route('admin.create-restaurant') }}" class="btn btn-primary">
                <span>➕</span><span>Register New</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <form method="GET" action="{{ route('admin.restaurants') }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; margin-bottom: 18px;">
        <input type="text" name="search" class="form-input" placeholder="Search by name, phone, WhatsApp number, city..." value="{{ request('search') }}">
        
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended / Inactive</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <select name="plan" class="form-select">
            <option value="">All Plans</option>
            @foreach($plans as $p)
                <option value="{{ $p->slug }}" {{ request('plan') === $p->slug ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>

        <div style="display: flex; gap: 6px;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary" title="Reset">✕</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>WhatsApp / Bot</th>
                    <th>City & Pricing</th>
                    <th>Plan & Status</th>
                    <th>Activity</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restaurants as $r)
                    <tr>
                        <td>
                            <div style="font-weight: 700; font-size: 13.5px;">{{ $r->name }}</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">Owner: {{ $r->owner_phone }}</div>
                        </td>
                        <td>
                            <div><code>{{ $r->whatsapp_number }}</code></div>
                            <div style="margin-top: 3px;">
                                @if($r->bot_status === 'connected')
                                    <span class="badge badge-green">🟢 Connected</span>
                                @elseif($r->bot_status === 'qr_pending')
                                    <span class="badge badge-yellow">🟡 QR Ready</span>
                                @else
                                    <span class="badge badge-gray">⚪ Disconnected</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div>{{ $r->city ?: 'N/A' }}</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">Deliv: Rs. {{ number_format($r->delivery_charge) }} | Min: Rs. {{ number_format($r->minimum_order) }}</div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="badge badge-blue">{{ strtoupper($r->plan ?: 'starter') }}</span>
                                @if($r->status === 'pending')
                                    <span class="badge badge-yellow">Pending</span>
                                @elseif($r->status === 'rejected')
                                    <span class="badge badge-red">Rejected</span>
                                @elseif($r->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Suspended</span>
                                @endif
                            </div>
                            <div style="font-size: 10.5px; color: var(--text-secondary); margin-top: 3px;">
                                {{ $r->plan_expires_at ? 'Exp: ' . $r->plan_expires_at->format('d M Y') : 'Lifetime / Trial' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 12px;"><strong>{{ $r->orders_count }}</strong> orders ({{ $r->month_orders_count }} this mo)</div>
                            <div style="font-size: 11px; color: var(--text-secondary);">{{ $r->menu_items_count }} items • {{ $r->conversations_count }} chats</div>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 5px;">
                                <a href="{{ route('admin.restaurant.edit', $r->id) }}" class="btn btn-secondary btn-sm" title="Edit & Bot Settings">✏️ Edit</a>
                                <a href="{{ route('admin.restaurant.analytics', $r->id) }}" class="btn btn-secondary btn-sm" title="Analytics">📊</a>
                                
                                <form method="POST" action="{{ route('admin.toggle-restaurant', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm" type="submit" title="{{ $r->is_active ? 'Suspend Restaurant' : 'Reactivate Restaurant' }}">
                                        {{ $r->is_active ? '⏸️' : '▶️' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.restaurant.delete', $r->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete {{ addslashes($r->name) }} and all associated data?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit" title="Delete">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-secondary);">
                            No restaurants found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 18px;">
        {{ $restaurants->links() }}
    </div>
</div>
@endsection
