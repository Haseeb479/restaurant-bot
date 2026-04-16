<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0efe9; min-height: 100vh; }
        nav { background: #0e0e10; height: 54px; padding: 0 1.75rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .wm-icon { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; width: 22px; height: 22px; }
        .wm-sq { border-radius: 2px; }
        .wm-sq:nth-child(1), .wm-sq:nth-child(4) { background: #fff; }
        .wm-sq:nth-child(2), .wm-sq:nth-child(3) { background: rgba(255,255,255,0.25); }
        .brand-text { font-size: 13px; font-weight: 500; color: #fff; letter-spacing: -0.01em; }
        .body { max-width: 920px; margin: 1.75rem auto; padding: 0 1.25rem; }
        .hero { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 1.5rem; margin-bottom: 1.75rem; }
        .hero-card { background: #0e0e10; border-radius: 18px; color: #fff; padding: 2rem; display: flex; flex-direction: column; justify-content: space-between; }
        .hero-card h1 { font-size: 28px; margin-bottom: 0.75rem; }
        .hero-card p { color: rgba(255,255,255,0.72); line-height: 1.7; }
        .hero-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .stat { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 1rem; }
        .stat-label { font-size: 11px; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.5rem; }
        .stat-value { font-size: 20px; font-weight: 600; }
        .card { background: #fff; border-radius: 18px; border: 0.5px solid #e8e8e4; padding: 2rem; }
        .card h2 { font-size: 22px; margin-bottom: 0.5rem; }
        .card p { color: #666; margin-bottom: 1rem; }
        .alert { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; border-radius: 12px; padding: 14px 16px; margin-bottom: 1rem; }
        .error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; padding: 14px 16px; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 14px; border: 1px solid #e8e8e4; border-radius: 12px; font-size: 14px; background: #f8f8f6; color: #111; }
        .form-control:focus { border-color: #0e0e10; outline: none; box-shadow: 0 0 0 3px rgba(14,14,16,0.08); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 18px; background: #0e0e10; color: #fff; border-radius: 12px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; width: 100%; }
        .btn:hover { background: #2a2a2e; }
        .hint { font-size: 13px; color: #6b7280; margin-top: 0.5rem; }
        .note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem; color: #475569; font-size: 13px; margin-top: 1rem; }
        @media(max-width: 900px) { .hero { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <div class="wm-icon">
            <div class="wm-sq"></div><div class="wm-sq"></div>
            <div class="wm-sq"></div><div class="wm-sq"></div>
        </div>
        <span class="brand-text">Restaurant onboarding</span>
    </div>
</nav>

<div class="body">
    <div class="hero">
        <div class="hero-card">
            <div>
                <h1>Get your WhatsApp restaurant bot live.</h1>
                <p>Register your restaurant and connect your WhatsApp Business number. Customers can then order directly through WhatsApp.</p>
            </div>
            <div class="hero-stats">
                <div class="stat"><div class="stat-label">Instant setup</div><div class="stat-value">Live in minutes</div></div>
                <div class="stat"><div class="stat-label">No code</div><div class="stat-value">Just paste tokens</div></div>
                <div class="stat"><div class="stat-label">WhatsApp ready</div><div class="stat-value">Bot enabled</div></div>
            </div>
        </div>

        <div class="card">
            <h2>Restaurant registration</h2>
            <p>Fill in your details and WhatsApp API credentials to start using the bot.</p>

            @if(session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="error">{{ implode(' ', $errors->all()) }}</div>
            @endif

            <form method="POST" action="{{ route('restaurant.register') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Restaurant Name</label>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number</label>
                        <input class="form-control" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="+923XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">WhatsApp Phone ID</label>
                        <input class="form-control" name="wa_phone_id" value="{{ old('wa_phone_id') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Access Token</label>
                        <input class="form-control" name="wa_access_token" value="{{ old('wa_access_token') }}" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Owner Phone</label>
                        <input class="form-control" name="owner_phone" value="{{ old('owner_phone') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner Password</label>
                        <input class="form-control" type="password" name="owner_password" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input class="form-control" name="city" value="{{ old('city') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input class="form-control" name="address" value="{{ old('address') }}">
                    </div>
                </div>

                <button class="btn" type="submit">Register Restaurant</button>
            </form>

            <div class="note">
                After registration, your restaurant will be active and ready to receive orders via WhatsApp. Use the link shown after success to log in.
            </div>
        </div>
    </div>
</div>
</body>
</html>