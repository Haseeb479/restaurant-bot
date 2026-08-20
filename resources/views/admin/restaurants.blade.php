@extends('layouts.admin')
@section('title', 'Restaurants')
@section('header_title', 'Restaurants Management')
@section('header_subtitle', 'Manage registered restaurants, bot status, and subscription plans')

@section('content')

<div class="panel-card" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div class="panel-title">
            <h3>All Registered Restaurants ({{ $restaurants->count() }})</h3>
            <p>Live tenant directory and bot connection controls</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="restSearch" placeholder="🔍 Search restaurants..." onkeyup="filterRestaurants()" style="padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; width: 220px;">
            <a href="{{ route('admin.create-restaurant') }}" class="btn btn-primary">
                + Add Restaurant
            </a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table" id="restTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Restaurant</th>
                    <th>City & Phone</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Bot Connection</th>
                    <th>Menu Items</th>
                    <th>Month Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restaurants as $r)
                <tr class="rest-row">
                    <td><strong>{{ $r->id }}</strong></td>
                    <td>
                        <div style="font-weight: 800; color: #0f172a;">{{ $r->name }}</div>
                        <div style="font-size: 11px; color: #64748b;">{{ $r->whatsapp_number }}</div>
                    </td>
                    <td>
                        <div>{{ $r->city ?: 'Pakistan' }}</div>
                        <div style="font-size: 11px; color: #64748b;">👤 {{ $r->owner_phone }}</div>
                    </td>
                    <td>
                        <span class="badge badge-blue" style="text-transform: uppercase;">
                            {{ $r->plan }}
                        </span>
                    </td>
                    <td>
                        @if($r->is_active)
                            <span class="badge badge-green">● Active</span>
                        @else
                            <span class="badge badge-red">○ Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($r->bot_status === 'connected')
                            <span class="badge badge-green">🟢 Connected</span>
                        @elseif($r->bot_status === 'qr_pending')
                            <span class="badge badge-yellow">🟡 Scan QR</span>
                        @else
                            <span class="badge badge-red">🔴 Disconnected</span>
                        @endif
                    </td>
                    <td>{{ $r->menu_items_count }} items</td>
                    <td><strong>{{ $r->month_orders_count }}</strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <form method="POST" action="{{ route('admin.toggle-restaurant', $r->id) }}" style="display: inline;">
                                @csrf
                                <label class="switch" title="Activate / Deactivate">
                                    <input type="checkbox" onchange="this.form.submit()" {{ $r->is_active ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </form>

                            <a href="/dashboard/{{ $r->id }}/orders" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px;">
                                Open Dashboard ↗
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #94a3b8; padding: 2rem;">
                        No restaurants registered yet. Click <strong>+ Add Restaurant</strong> to register your first tenant.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function filterRestaurants() {
    const input = document.getElementById('restSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.rest-row');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
</script>

@endsection
