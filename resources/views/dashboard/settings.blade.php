@extends('layouts.dashboard')
@section('title', 'Settings')
@section('header_title', 'Restaurant Settings')
@section('header_subtitle', 'Configure business information, delivery rules, and WhatsApp bot preferences')

@section('content')

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .card-panel {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        margin-bottom: 20px;
    }

    .card-panel-header {
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-panel-header h3 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .card-panel-header p {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 13px;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        font-family: inherit;
        transition: all 0.15s ease;
    }
    .form-control:focus {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .form-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .status-banner {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .status-banner-left h3 {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }
    .status-banner-left p {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-table td {
        padding: 10px 0;
        font-size: 13px;
        border-bottom: 1px solid #f8fafc;
    }
    .info-table .lbl { color: #64748b; font-weight: 500; width: 40%; }
    .info-table .val { color: #0f172a; font-weight: 700; text-align: right; }

    @media (max-width: 1024px) {
        .settings-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- RESTAURANT STATUS BANNER -->
<form method="POST" action="{{ route('dashboard.update-settings', $restaurant->id) }}">
    @csrf

    <div class="status-banner">
        <div class="status-banner-left">
            <h3>🏪 Restaurant Ordering Status</h3>
            <p>When set to Closed, the WhatsApp bot politely informs customers that your kitchen is currently closed.</p>
        </div>

        <div style="display: flex; align-items: center; gap: 14px;">
            <span class="badge-status {{ $restaurant->is_open ? 'delivered' : 'cancelled' }}" style="font-size: 12px; padding: 5px 12px;">
                ● {{ $restaurant->is_open ? 'OPEN FOR ORDERS' : 'CLOSED' }}
            </span>
            <label class="switch" style="width: 44px; height: 24px;" title="Toggle Open/Closed">
                <input type="checkbox" name="is_open" value="1" onchange="this.form.submit()" {{ $restaurant->is_open ? 'checked' : '' }}>
                <span class="slider" style="border-radius: 999px;"></span>
            </label>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('dashboard.update-settings', $restaurant->id) }}">
    @csrf
    <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? '1' : '0' }}">

    <div class="settings-grid">
        <!-- LEFT COLUMN: SETTINGS FORM -->
        <div>
            <!-- GENERAL SETTINGS -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>General Business Information</h3>
                        <p>Basic details shown to customers during WhatsApp order placement</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Restaurant Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $restaurant->name) }}" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">WhatsApp Bot Number *</label>
                        <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $restaurant->whatsapp_number) }}" required>
                        <div class="form-hint">Number used to connect your AI WhatsApp bot</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Owner Contact Number</label>
                        <input type="text" name="owner_phone" class="form-control" value="{{ old('owner_phone', $restaurant->owner_phone) }}">
                        <div class="form-hint">Receives new order notifications and alerts</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $restaurant->city) }}" placeholder="e.g. Lahore, Karachi, Bahawalpur">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Restaurant Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $restaurant->address) }}" placeholder="e.g. Main Boulevard, Phase 5">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Opening Hours</label>
                    <input type="text" name="hours" class="form-control" value="{{ old('hours', $restaurant->hours) }}" placeholder="e.g. 10 AM – 11 PM">
                    <div class="form-hint">Shown to customers when you're closed, and used by the bot to answer "what time do you open?"</div>
                </div>
            </div>

            <!-- DELIVERY RULES -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Delivery & Pricing Rules</h3>
                        <p>Automated price calculations applied by the bot</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Areas</label>
                    <input type="text" name="delivery_areas" class="form-control" value="{{ old('delivery_areas', $restaurant->delivery_areas) }}" placeholder="e.g. Phase 1, Phase 2, Model Town, Cantt">
                    <div class="form-hint">Comma-separated list of sectors / neighborhoods you deliver to</div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Delivery Fee (PKR)</label>
                        <input type="number" name="delivery_charge" class="form-control" value="{{ old('delivery_charge', $restaurant->delivery_charge) }}" min="0" step="1">
                        <div class="form-hint">Auto-added to customer cart total (0 = Free Delivery)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Minimum Order Amount (PKR)</label>
                        <input type="number" name="minimum_order" class="form-control" value="{{ old('minimum_order', $restaurant->minimum_order) }}" min="0" step="1">
                        <div class="form-hint">Bot will enforce this minimum before accepting order</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Greeting Message</label>
                    <input type="text" name="greeting_message" class="form-control" value="{{ old('greeting_message', $restaurant->greeting_message) }}" placeholder="Welcome to Pizza Palace! How can I help you today?">
                    <div class="form-hint">First welcome message sent to new WhatsApp customers</div>
                </div>
            </div>

            <!-- GOOGLE SHEET WEBHOOK -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Google Sheets Webhook Sync</h3>
                        <p>Push live orders to your custom Google Sheet automatically</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Google Apps Script Webhook URL</label>
                    <input type="url" name="google_sheet_webhook" class="form-control" value="{{ old('google_sheet_webhook', $restaurant->google_sheet_webhook) }}" placeholder="https://script.google.com/macros/s/.../exec">
                    <div class="form-hint">Leave blank if not using Google Sheets sync</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 10px;">
                💾 Save All Settings
            </button>
        </div>

        <!-- RIGHT COLUMN: PLAN & STATS -->
        <div>
            <!-- SUBSCRIPTION INFO -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Subscription Package</h3>
                        <p>Your WhatsApp bot plan</p>
                    </div>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="lbl">Current Plan</td>
                        <td class="val">
                            <span class="badge-status confirmed" style="font-size: 11px;">
                                {{ ucfirst($restaurant->plan) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl">Plan Status</td>
                        <td class="val">
                            @if($restaurant->plan_expires_at)
                                {{ $restaurant->plan_expires_at->isFuture() ? 'Active' : 'Expired' }}
                            @else
                                Active (Trial)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="lbl">Expires On</td>
                        <td class="val">
                            {{ $restaurant->plan_expires_at ? $restaurant->plan_expires_at->format('d M Y') : 'No Expiry' }}
                        </td>
                    </tr>
                </table>

                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #64748b; line-height: 1.4;">
                    Need to extend your plan or add features? Contact your platform provider.
                </div>
            </div>

            <!-- ALL-TIME STATS -->
            <div class="card-panel">
                <div class="card-panel-header">
                    <div>
                        <h3>Restaurant Performance</h3>
                        <p>All-time bot orders overview</p>
                    </div>
                </div>

                @php
                    $allOrders = $restaurant->orders;
                    $deliveredOrders = $allOrders->where('status', 'delivered');
                    $activeRevenue = $allOrders->where('status', '!=', 'cancelled')->sum('total');
                @endphp

                <table class="info-table">
                    <tr>
                        <td class="lbl">Total Orders</td>
                        <td class="val">{{ number_format($allOrders->count()) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Delivered Orders</td>
                        <td class="val" style="color: #16a34a;">{{ number_format($deliveredOrders->count()) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Total Revenue</td>
                        <td class="val" style="color: #4f46e5;">PKR {{ number_format($activeRevenue, 0) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Menu Food Items</td>
                        <td class="val">{{ $restaurant->menuItems()->count() }} items</td>
                    </tr>
                </table>
            </div>

            <!-- BOT CONNECTION SHORTCUT -->
            <div class="card-panel" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #bbf7d0;">
                <h4 style="font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 4px;">📱 WhatsApp Bot Status</h4>
                <p style="font-size: 12px; color: #15803d; line-height: 1.4; margin-bottom: 14px;">
                    Scan the QR code anytime to connect or re-link your WhatsApp number.
                </p>
                <a href="{{ route('dashboard.connect-whatsapp', $restaurant->id) }}" class="btn btn-success" style="width: 100%; justify-content: center;">
                    Open QR Connection Screen →
                </a>
            </div>
        </div>
    </div>
</form>

@endsection