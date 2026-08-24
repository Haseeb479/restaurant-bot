@extends('layouts.admin')
@section('title', 'Add Restaurant')
@section('header_title', 'Register New Restaurant')
@section('header_subtitle', 'Onboard a new restaurant tenant and configure WhatsApp bot credentials')

@section('content')

<div style="max-width: 860px;">

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px;">
            <strong style="display: block; margin-bottom: 4px;">⚠️ Please check the following errors:</strong>
            <ul style="padding-left: 18px; margin: 0;">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel-card">
        <form method="POST" action="{{ route('admin.store-restaurant') }}">
            @csrf

            <!-- STEP 1: RESTAURANT BASIC DETAILS -->
            <div style="margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9;">
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">1. Business Profile</h3>
                <p style="font-size: 12px; color: #64748b;">Basic restaurant identity and location details</p>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Restaurant Name <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Tasty Bites, Pizza Crust, Biryani Express" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        City
                    </label>
                    <input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Lahore, Karachi, Bahawalpur" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Physical Address
                    </label>
                    <input type="text" name="address" value="{{ old('address') }}" placeholder="e.g. Shop 4, Commercial Market" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                </div>
            </div>

            <!-- STEP 2: WHATSAPP BOT & OWNER CREDENTIALS -->
            <div style="margin: 28px 0 20px; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">2. WhatsApp Bot & Owner Login</h3>
                <p style="font-size: 12px; color: #64748b;">Phone numbers for bot engine and owner portal credentials</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        WhatsApp Bot Number <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" required placeholder="e.g. 03293647476" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                    <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">The number that will scan the QR code to take orders</span>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Owner Contact Phone <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="owner_phone" value="{{ old('owner_phone') }}" required placeholder="e.g. 03001234567" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                    <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">Contact number to receive owner alerts</span>
                </div>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Owner Dashboard Password <span style="color: #ef4444;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="password" id="owner_password" name="owner_password" value="{{ old('owner_password') }}" required placeholder="••••••" style="width: 100%; padding: 10px 44px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc; box-sizing: border-box;">
                    <button type="button" onclick="togglePw('owner_password', this)" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:17px;color:#94a3b8;padding:2px;" aria-label="Show/hide password">👁</button>
                </div>
                <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">Password the owner uses to sign into /dashboard/{id}/login (min 6 characters)</span>
            </div>

            <!-- STEP 3: SUBSCRIPTION PLAN & DELIVERY RULES -->
            <div style="margin: 28px 0 20px; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">3. SaaS Plan & Delivery Rules</h3>
                <p style="font-size: 12px; color: #64748b;">Subscription package and automated order pricing</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Subscription Package Plan <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="plan" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                        <option value="trial" {{ old('plan') === 'trial' ? 'selected' : '' }}>Trial — 14 Days Free</option>
                        <option value="basic" {{ old('plan', 'basic') === 'basic' ? 'selected' : '' }}>Basic — 3,000 PKR / mo</option>
                        <option value="pro" {{ old('plan') === 'pro' ? 'selected' : '' }}>Pro — 7,000 PKR / mo (Sheets sync included)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Delivery Fee (PKR)
                    </label>
                    <input type="number" name="delivery_charge" value="{{ old('delivery_charge', 0) }}" min="0" placeholder="0 = Free" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    WhatsApp Greeting Message
                </label>
                <input type="text" name="greeting_message" value="{{ old('greeting_message', 'Welcome! How can I help you today? Send menu to view food items.') }}" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc;">
            </div>

            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 13px; font-weight: 700;">
                    ✓ Register Restaurant & Proceed to QR Code
                </button>
                <a href="{{ route('admin.restaurants') }}" class="btn btn-secondary" style="padding: 12px 20px; font-size: 13px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>


@push('scripts')
<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.textContent = '🙈';
    } else {
        inp.type = 'password';
        btn.textContent = '👁';
    }
}
</script>
@endpush

@endsection