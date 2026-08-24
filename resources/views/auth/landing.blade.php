<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Bot — Login</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .page-wrap { width: 100%; max-width: 900px; }
        .hero { text-align: center; margin-bottom: 40px; }
        .hero-icon { font-size: 44px; margin-bottom: 12px; }
        .hero h1 { font-size: 28px; font-weight: 800; color: #f8fafc; letter-spacing: -0.5px; }
        .hero p { font-size: 14px; color: #94a3b8; margin-top: 6px; }
        .cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 36px 32px; transition: border-color 0.2s; }
        .card:hover { border-color: #4f46e5; }
        .card-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; }
        .admin-icon { background: rgba(79,70,229,0.15); }
        .owner-icon { background: rgba(16,185,129,0.15); }
        .card h2 { font-size: 18px; font-weight: 700; color: #f1f5f9; margin-bottom: 4px; }
        .card .sub { font-size: 12px; color: #64748b; margin-bottom: 24px; }
        .divider { height: 1px; background: #334155; margin-bottom: 24px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap input, .input-wrap select { width: 100%; padding: 11px 42px 11px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f1f5f9; font-size: 13px; font-family: inherit; outline: none; transition: border-color 0.15s, box-shadow 0.15s; appearance: none; -webkit-appearance: none; }
        .input-wrap select { padding-right: 14px; cursor: pointer; }
        .input-wrap input::placeholder { color: #475569; }
        .input-wrap input:focus, .input-wrap select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.2); }
        .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #475569; cursor: pointer; font-size: 17px; padding: 2px; line-height: 1; }
        .toggle-pw:hover { color: #94a3b8; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s; margin-top: 4px; }
        .btn-admin { background: #4f46e5; color: #fff; box-shadow: 0 4px 14px rgba(79,70,229,0.35); }
        .btn-admin:hover { background: #4338ca; transform: translateY(-1px); }
        .btn-owner { background: #059669; color: #fff; box-shadow: 0 4px 14px rgba(5,150,105,0.35); }
        .btn-owner:hover { background: #047857; transform: translateY(-1px); }
        .alert { background: #450a0a; border: 1px solid #7f1d1d; color: #fca5a5; padding: 10px 14px; border-radius: 10px; font-size: 12px; margin-bottom: 16px; }
        .badge { text-align: center; margin-top: 32px; font-size: 11px; color: #334155; }
        @media (max-width: 640px) { .cards { grid-template-columns: 1fr; } .card { padding: 28px 22px; } }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="hero">
        <div class="hero-icon">???</div>
        <h1>Restaurant Bot Platform</h1>
        <p>WhatsApp Ordering System — Choose your login type below</p>
    </div>
    <div class="cards">
        {{-- Admin Login --}}
        <div class="card">
            <div class="card-icon admin-icon">??</div>
            <h2>Super Admin</h2>
            <p class="sub">Platform-wide management, restaurant setup and monitoring</p>
            <div class="divider"></div>
            @if(session("admin_error"))
                <div class="alert">?? {{ session("admin_error") }}</div>
            @endif
            @if($errors->hasBag("admin"))
                <div class="alert">
                    @foreach($errors->getBag("admin")->all() as $e)
                        <div>?? {{ $e }}</div>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route("admin.login") }}">
                @csrf
                <div class="field">
                    <label>Admin Master Password</label>
                    <div class="input-wrap">
                        <input type="password" id="admin_pw" name="password" required placeholder="••••••••" autofocus>
                        <button type="button" class="toggle-pw" onclick="togglePw('admin_pw',this)" tabindex="-1">??</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-admin">Sign In as Admin ?</button>
            </form>
        </div>
        {{-- Owner Login --}}
        <div class="card">
            <div class="card-icon owner-icon">??</div>
            <h2>Restaurant Owner</h2>
            <p class="sub">Manage orders, menu, riders and WhatsApp bot connection</p>
            <div class="divider"></div>
            @if($errors->hasBag("owner"))
                <div class="alert">
                    @foreach($errors->getBag("owner")->all() as $e)
                        <div>?? {{ $e }}</div>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route("landing.owner-login") }}">
                @csrf
                <div class="field">
                    <label>Your Restaurant</label>
                    <div class="input-wrap">
                        <select name="restaurant_id" required>
                            <option value="" disabled selected>— Select your restaurant —</option>
                            @foreach($restaurants as $r)
                                <option value="{{ $r->id }}" {{ old("restaurant_id") == $r->id ? "selected" : "" }}>
                                    {{ $r->name }}{{ $r->city ? " · ".$r->city : "" }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Owner Password</label>
                    <div class="input-wrap">
                        <input type="password" id="owner_pw" name="password" required placeholder="••••••••">
                        <button type="button" class="toggle-pw" onclick="togglePw('owner_pw',this)" tabindex="-1">??</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-owner">Sign In as Owner ?</button>
            </form>
        </div>
    </div>
    <p class="badge">?? Secured Multi-Tenant WhatsApp Bot Platform</p>
</div>
<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === "password" ? "text" : "password";
    btn.textContent = inp.type === "password" ? "??" : "??";
}
</script>
</body>
</html>
