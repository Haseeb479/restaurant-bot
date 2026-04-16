<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Restaurant — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; min-height: 100vh; }
        nav { background: #0e0e10; height: 54px; padding: 0 1.75rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 22px; height: 22px; }
        .wm-sq { border-radius: 2px; }
        .wm-sq:nth-child(1),.wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2),.wm-sq:nth-child(3) { background: rgba(255,255,255,0.25); }
        .brand-text { font-size: 13px; font-weight: 500; color: #fff; letter-spacing: -0.01em; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .back-btn { font-size: 12px; color: rgba(255,255,255,0.45); background: none; border: 0.5px solid rgba(255,255,255,0.12); border-radius: 7px; padding: 5px 14px; cursor: pointer; text-decoration: none; }
        .back-btn:hover { color: #fff; border-color: rgba(255,255,255,0.3); }
        .body { max-width: 700px; margin: 1.75rem auto; padding: 0 1.25rem; }
        .success-bar { background: #eaf4ee; border: 0.5px solid #c0dd97; border-radius: 8px; padding: 10px 16px; font-size: 12px; color: #27500A; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
        .s-bar-dot { width: 6px; height: 6px; border-radius: 50%; background: #3B6D11; flex-shrink: 0; }
        h1 { font-size: 22px; font-weight: 600; margin-bottom: 0.5rem; color: #111; }
        p.sub { color: #888; font-size: 14px; margin-bottom: 1.5rem; }
        .card { background: #fff; border-radius: 12px; border: 0.5px solid #e8e8e4; padding: 1.5rem; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 1.5rem 0 1rem; padding-top: 1.5rem; border-top: 1px solid #f3f4f6; }
        .section-title:first-child { margin-top: 0; padding-top: 0; border-top: none; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 5px; }
        .form-label .req { color: #dc2626; }
        .form-control { width: 100%; padding: 9px 12px; border: 1px solid #e8e8e4; border-radius: 10px; font-size: 14px; background: #f8f8f6; color: #111; outline: none; }
        .form-control:focus { border-color: #aaa; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
        .form-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; line-height: 1.5; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn { display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; gap: 6px; }
        .btn-dark { background: #0e0e10; color: white; }
        .btn-dark:hover { background: #2a2a2e; }
        .btn-outline { background: white; border: 1px solid #e8e8e4; color: #374151; }
        .btn-outline:hover { background: #f9fafb; }
        .error { background: #fff0f0; border: 1px solid #f5c1c1; border-radius: 8px; padding: 12px 16px; margin-bottom: 1.5rem; font-size: 14px; color: #a32d2d; }
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; font-size: 13px; color: #1e40af; line-height: 1.6; }
        @media (max-width: 600px) { .grid2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <div class="wm-icon">
            <div class="wm-sq"></div><div class="wm-sq"></div>
            <div class="wm-sq"></div><div class="wm-sq"></div>
        </div>
        <span class="brand-text">Restaurant admin</span>
    </div>
    <div class="nav-right">
        <a href="{{ route('admin.dashboard') }}" class="back-btn">← Back to Dashboard</a>
    </div>
</nav>

<div class="body">
    @if(session('success'))
        <div class="success-bar">
            <div class="s-bar-dot"></div>
            {{ session('success') }}
        </div>
    @endif

    <h1>Add New Restaurant</h1>
    <p class="sub">Fill in the restaurant details. They'll be live on WhatsApp immediately.</p>

    <div class="info-box">
        <strong>📋 Before you start:</strong> You need the restaurant's WhatsApp Business API credentials from Meta.
        Go to <strong>business.facebook.com</strong> → WhatsApp → API Setup to get the
        <strong>Phone Number ID</strong> and <strong>Access Token</strong>.
    </div>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="card">
        <form method="POST" action="/admin/restaurant">
            @csrf

            <div class="section-title">Restaurant Info</div>

            <div class="form-group">
                <label class="form-label">Restaurant Name <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                       placeholder="e.g. Pizza Palace Bahawalpur" required>
            </div>

            <div class="grid2">
                <div class="form-group">
                    <label class="form-label">City <span class="req">*</span></label>
                    <input type="text" name="city" class="form-control" value="{{ old('city','Bahawalpur') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address') }}"
                           placeholder="Shop 5, Satellite Town">
                </div>
            </div>

            <div class="grid2">
                <div class="form-group">
                    <label class="form-label">Delivery Charge (Rs.)</label>
                    <input type="number" name="delivery_charge" class="form-control" value="{{ old('delivery_charge',0) }}" min="0" step="1">
                    <div class="form-hint">Set 0 for free delivery</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order (Rs.)</label>
                    <input type="number" name="minimum_order" class="form-control" value="{{ old('minimum_order',0) }}" min="0" step="1">
                    <div class="form-hint">Set 0 for no minimum</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Greeting Message</label>
                <input type="text" name="greeting_message" class="form-control"
                       value="{{ old('greeting_message','Assalam o Alaikum! Welcome!') }}">
                <div class="form-hint">First message shown to customers when they text the bot</div>
            </div>

            <div class="section-title">Owner Info</div>

            <div class="grid2">
                <div class="form-group">
                    <label class="form-label">Owner WhatsApp Number <span class="req">*</span></label>
                    <input type="text" name="owner_phone" class="form-control" value="{{ old('owner_phone') }}"
                           placeholder="+923001234567" required>
                    <div class="form-hint">Owner gets new order alerts here</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Dashboard Password <span class="req">*</span></label>
                    <input type="text" name="owner_password" class="form-control" value="{{ old('owner_password') }}"
                           placeholder="Give owner a password" required minlength="4">
                    <div class="form-hint">Owner uses this to login to their dashboard</div>
                </div>
            </div>

            <div class="section-title">WhatsApp API Credentials (from Meta)</div>

            <div class="form-group">
                <label class="form-label">WhatsApp Number (with country code) <span class="req">*</span></label>
                <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number') }}"
                       placeholder="+923001234567" required>
                <div class="form-hint">The phone number registered on WhatsApp Business API</div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number ID <span class="req">*</span></label>
                <input type="text" name="wa_phone_id" class="form-control" value="{{ old('wa_phone_id') }}"
                       placeholder="1234567890123456" required>
                <div class="form-hint">Found in Meta Business Manager → WhatsApp → API Setup</div>
            </div>

            <div class="section-title">Plan</div>

            <div class="grid2">
                <div class="form-group">
                    <label class="form-label">Plan <span class="req">*</span></label>
                    <select name="plan" class="form-control" required>
                        <option value="trial"  {{ old('plan')=='trial' ?'selected':'' }}>Trial (no expiry)</option>
                        <option value="basic"  {{ old('plan')=='basic' ?'selected':'' }}>Basic</option>
                        <option value="pro"    {{ old('plan')=='pro'   ?'selected':'' }}>Pro</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #f3f4f6;">
                <button type="submit" class="btn btn-dark">Create Restaurant →</button>
                <a href="/admin" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>