@extends('layouts.dashboard')
@section('content')

<div class="page-header">
    <h1>Restaurant Settings</h1>
    <p>Changes apply instantly to your WhatsApp bot.</p>
</div>

<div class="grid2">
    <div>

        {{-- Open/Closed toggle --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <strong style="font-size:16px;">Restaurant Status</strong>
                        <p style="font-size:13px;color:#6b7280;margin-top:4px;">
                            When closed, bot tells customers you're unavailable.
                        </p>
                    </div>
                    <div style="text-align:right;">
                        <div class="open-indicator {{ $restaurant->is_open ? 'open' : 'closed' }}">
                            <span class="dot {{ $restaurant->is_open ? 'dot-green' : 'dot-red' }}"></span>
                            {{ $restaurant->is_open ? 'OPEN' : 'CLOSED' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Settings form --}}
        <div class="card">
            <div class="card-header"><h2>General Settings</h2></div>
            <div class="card-body">
                <form method="POST" action="/dashboard/{{ $restaurant->id }}/settings">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Restaurant Name</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $restaurant->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control"
                               value="{{ old('address', $restaurant->address) }}"
                               placeholder="Shop 5, Satellite Town, Bahawalpur">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Delivery Areas</label>
                        <input type="text" name="delivery_areas" class="form-control"
                               value="{{ old('delivery_areas', $restaurant->delivery_areas) }}"
                               placeholder="Satellite Town, Model Town, City Centre">
                        <div class="form-hint">Comma-separated list of areas you deliver to</div>
                    </div>

                    <div class="grid2">
                        <div class="form-group">
                            <label class="form-label">Delivery Charge (Rs.)</label>
                            <input type="number" name="delivery_charge" class="form-control"
                                   value="{{ old('delivery_charge', $restaurant->delivery_charge) }}"
                                   min="0" step="1" placeholder="0 = free delivery">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Minimum Order (Rs.)</label>
                            <input type="number" name="minimum_order" class="form-control"
                                   value="{{ old('minimum_order', $restaurant->minimum_order) }}"
                                   min="0" step="1" placeholder="0 = no minimum">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Greeting Message</label>
                        <input type="text" name="greeting_message" class="form-control"
                               value="{{ old('greeting_message', $restaurant->greeting_message) }}">
                        <div class="form-hint">First message customers see when they text you</div>
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="is_open" value="0">
                        <label class="form-label" style="display:flex;align-items:center;gap:10px;">
                            <label class="toggle">
                                <input type="checkbox" name="is_open" value="1"
                                       {{ $restaurant->is_open ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            Restaurant is Open for Orders
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

    </div>

    <div>
        {{-- Plan info --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header"><h2>Plan Info</h2></div>
            <div class="card-body">
                <table style="width:100%;font-size:14px;border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;width:140px;">Current Plan</td>
                        <td><strong style="text-transform:capitalize;">{{ $restaurant->plan }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">Expires</td>
                        <td>
                            @if($restaurant->plan_expires_at)
                                {{ $restaurant->plan_expires_at->format('d M Y') }}
                                @if($restaurant->plan_expires_at->isPast())
                                    <span class="status-badge status-cancelled" style="margin-left:6px;">Expired</span>
                                @elseif($restaurant->plan_expires_at->diffInDays() < 7)
                                    <span class="status-badge status-new" style="margin-left:6px;">Expiring soon</span>
                                @else
                                    <span class="status-badge status-confirmed" style="margin-left:6px;">Active</span>
                                @endif
                            @else
                                <span class="status-badge status-confirmed">Trial (No expiry)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">WA Number</td>
                        <td><code style="font-size:13px;background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ $restaurant->whatsapp_number }}</code></td>
                    </tr>
                </table>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f3f4f6;font-size:13px;color:#6b7280;">
                    To renew your plan, contact your service provider.
                </div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="card">
            <div class="card-header"><h2>All-time Stats</h2></div>
            <div class="card-body">
                @php
                    $allOrders  = $restaurant->orders;
                    $delivered  = $allOrders->where('status','delivered');
                @endphp
                <table style="width:100%;font-size:14px;border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">Total Orders</td>
                        <td><strong>{{ $allOrders->count() }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">Delivered</td>
                        <td><strong>{{ $delivered->count() }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">Total Revenue</td>
                        <td><strong>Rs. {{ number_format($delivered->sum('total'), 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;">Menu Items</td>
                        <td><strong>{{ $restaurant->menuItems()->count() }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection