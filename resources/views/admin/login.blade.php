<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login — WhatsApp Bot Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            color: #0f172a;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            max-width: 860px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }

        .left-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            padding: 3.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            position: relative;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #22c55e;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.4);
        }

        .logo-title h2 { font-size: 16px; font-weight: 800; }
        .logo-title span { font-size: 11px; color: #94a3b8; font-weight: 600; }

        .banner-quote {
            margin: 2.5rem 0;
        }
        .banner-quote h1 {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .banner-quote p {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
        }

        .stats-strip {
            display: flex;
            gap: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        .stat-item .num { font-size: 20px; font-weight: 800; color: #fff; }
        .stat-item .lbl { font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; }

        .right-form {
            padding: 3.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-header h2 { font-size: 22px; font-weight: 800; color: #0f172a; }
        .form-header p  { font-size: 13px; color: #64748b; margin-top: 4px; margin-bottom: 24px; }

        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }

        .input-wrap {
            position: relative;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 44px 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            background: #f8fafc;
            color: #0f172a;
            font-family: inherit;
            box-sizing: border-box;
        }
        .input-wrap input:focus {
            border-color: #4f46e5;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 4px;
            display: flex;
            align-items: center;
            font-size: 18px;
            line-height: 1;
        }
        .toggle-pw:hover { color: #4f46e5; }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #4f46e5;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            transition: all 0.15s ease;
            margin-top: 8px;
        }
        .btn-submit:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        .error-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #64748b;
            margin-top: 24px;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .login-container { grid-template-columns: 1fr; }
            .left-banner { display: none; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Left Hero Banner -->
    <div class="left-banner">
        <div class="logo-box">
            <div class="logo-icon">💬</div>
            <div class="logo-title">
                <h2>WhatsApp Bot</h2>
                <span>Super Admin Platform</span>
            </div>
        </div>

        <div class="banner-quote">
            <h1>Master Platform Administration</h1>
            <p>Manage all restaurant bots, monitor real-time WhatsApp connections, and oversee subscriptions platform-wide.</p>
        </div>

        <div class="stats-strip">
            <div class="stat-item">
                <div class="num">100%</div>
                <div class="lbl">Automated</div>
            </div>
            <div class="stat-item">
                <div class="num">24/7</div>
                <div class="lbl">Uptime Monitor</div>
            </div>
            <div class="stat-item">
                <div class="num">Zero</div>
                <div class="lbl">Data Leaks</div>
            </div>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="right-form">
        <div class="form-header">
            <h2>Super Admin Access</h2>
            <p>Enter your developer/platform password to continue.</p>
        </div>

        @if($errors->any())
            <div class="error-alert">
                @foreach($errors->all() as $e)
                    <div>⚠️ {{ $e }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Admin Master Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" required placeholder="••••••••" autofocus>
                    <button type="button" class="toggle-pw" onclick="togglePw('password', this)" tabindex="-1" aria-label="Show/hide password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Sign In to Super Admin →</button>
        </form>

        <div class="security-badge">
            <span>🔒</span>
            <span>Secured Multi-Tenant Platform Architecture</span>
        </div>
    </div>
</div>

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
</body>
</html>