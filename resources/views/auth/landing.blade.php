<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Bot — WhatsApp Ordering Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 85% 30%, rgba(99, 102, 241, 0.18) 0%, transparent 50%),
                        radial-gradient(circle at 15% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 45%),
                        #080d1a;
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }

        /* Top Navbar */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28px 48px;
            max-width: 1380px;
            margin: 0 auto;
            width: 100%;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
        }
        .brand-icon {
            width: 44px;
            height: 44px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .brand-text h2 {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .brand-text p {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Top-Right Toggle Pill */
        .auth-switch {
            display: flex;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 9999px;
            padding: 4px;
            gap: 4px;
        }
        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 9999px;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .tab-btn:hover {
            color: #f1f5f9;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.45);
        }

        /* Main Container */
        .main-container {
            max-width: 1380px;
            margin: 0 auto;
            padding: 20px 48px 40px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 60px;
            align-items: center;
            flex-grow: 1;
        }

        /* Left Hero Content */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(51, 65, 85, 0.8);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 24px;
        }
        .hero-badge span.dot {
            font-size: 14px;
        }

        .hero-title {
            font-size: 52px;
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -1.2px;
            margin-bottom: 20px;
        }
        .hero-title .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.6;
            color: #94a3b8;
            max-width: 520px;
            margin-bottom: 44px;
        }

        /* 3 Feature Cards */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .feature-card {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(51, 65, 85, 0.55);
            border-radius: 18px;
            padding: 22px 18px;
            backdrop-filter: blur(12px);
            transition: transform 0.2s, border-color 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.5);
        }
        .feat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .feat-icon.purple { background: rgba(124, 58, 237, 0.2); }
        .feat-icon.blue { background: rgba(59, 130, 246, 0.2); }
        .feat-icon.green { background: rgba(16, 185, 129, 0.2); }

        .feat-title {
            font-size: 14px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 4px;
        }
        .feat-sub {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Right Form Card */
        .auth-card-wrapper {
            position: relative;
        }
        .auth-card {
            background: rgba(13, 19, 36, 0.75);
            border: 1px solid rgba(124, 58, 237, 0.35);
            border-radius: 28px;
            padding: 40px 36px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5),
                        0 0 40px rgba(124, 58, 237, 0.12);
            backdrop-filter: blur(20px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .auth-avatar {
            width: 60px;
            height: 60px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.35) 100%);
            border: 1px solid rgba(139, 92, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.35);
        }
        .auth-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -0.3px;
        }
        .auth-header p {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-lead-icon {
            position: absolute;
            left: 14px;
            font-size: 16px;
            color: #64748b;
            pointer-events: none;
        }
        .input-box input,
        .input-box select {
            width: 100%;
            background: rgba(8, 13, 26, 0.7);
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 14px;
            padding: 13px 44px 13px 42px;
            color: #f8fafc;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }
        .input-box select {
            padding-right: 20px;
            cursor: pointer;
        }
        .input-box input::placeholder {
            color: #475569;
        }
        .input-box input:focus,
        .input-box select:focus {
            border-color: #7c3aed;
            background: rgba(13, 19, 36, 0.9);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.25);
        }

        .pw-eye-btn {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #64748b;
            font-size: 18px;
            cursor: pointer;
            padding: 2px;
            line-height: 1;
            transition: color 0.15s;
        }
        .pw-eye-btn:hover {
            color: #cbd5e1;
        }

        .btn-submit-glow {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }
        .btn-submit-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.6);
        }

        .form-divider {
            display: flex;
            align-items: center;
            margin: 24px 0 20px;
            color: #475569;
            font-size: 12px;
        }
        .form-divider::before, .form-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
        }
        .form-divider span {
            padding: 0 12px;
        }

        .secure-badge-box {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(51, 65, 85, 0.6);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .secure-badge-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(99, 102, 241, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #818cf8;
            flex-shrink: 0;
        }
        .secure-badge-text h4 {
            font-size: 13px;
            font-weight: 700;
            color: #e2e8f0;
        }
        .secure-badge-text p {
            font-size: 11px;
            color: #64748b;
            margin-top: 1px;
        }

        .alert-error {
            background: rgba(153, 27, 27, 0.35);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 24px 48px;
            font-size: 12px;
            color: #475569;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 20px 24px;
            }
            .navbar { padding: 20px 24px; }
            .hero-title { font-size: 40px; }
            .feature-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .feature-grid { grid-template-columns: 1fr; }
            .hero-title { font-size: 32px; }
            .navbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            .auth-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <!-- Navigation Header -->
    <header class="navbar">
        <div class="brand">
            <div class="brand-icon">??</div>
            <div class="brand-text">
                <h2>Restaurant Bot</h2>
                <p>WhatsApp Ordering Platform</p>
            </div>
        </div>

        <!-- Top Right Switcher Pill -->
        <div class="auth-switch">
            <button type="button" class="tab-btn active" id="tabAdmin" onclick="switchLogin('admin')">
                <span>??</span>
                <span>Admin Login</span>
            </button>
            <button type="button" class="tab-btn" id="tabOwner" onclick="switchLogin('owner')">
                <span>??</span>
                <span>Owner Login</span>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">

        <!-- Left Hero Column -->
        <section class="hero-col">
            <div class="hero-badge">
                <span class="dot">??</span>
                <span>Smart WhatsApp Ordering</span>
            </div>

            <h1 class="hero-title">
                Restaurant Bot
                <span class="gradient-text">Platform</span>
            </h1>

            <p class="hero-desc">
                Powering restaurants with smart WhatsApp ordering, automation and real-time management.
            </p>

            <div class="feature-grid">
                <!-- Card 1 -->
                <div class="feature-card">
                    <div class="feat-icon purple">??</div>
                    <h3 class="feat-title">Smart Automation</h3>
                    <p class="feat-sub">24/7 WhatsApp ordering</p>
                </div>
                <!-- Card 2 -->
                <div class="feature-card">
                    <div class="feat-icon blue">???</div>
                    <h3 class="feat-title">Easy Restaurant Setup</h3>
                    <p class="feat-sub">Get your menu online fast</p>
                </div>
                <!-- Card 3 -->
                <div class="feature-card">
                    <div class="feat-icon green">??</div>
                    <h3 class="feat-title">Real-time Insights</h3>
                    <p class="feat-sub">Track orders & growth</p>
                </div>
            </div>
        </section>

        <!-- Right Login Card -->
        <section class="auth-card-wrapper">
            <div class="auth-card">

                <!-- 1. ADMIN FORM -->
                <div id="formAdmin">
                    <div class="auth-header">
                        <div class="auth-avatar">??</div>
                        <h3>Admin Login</h3>
                        <p>Access platform management and controls</p>
                    </div>

                    @if(session("admin_error"))
                        <div class="alert-error">
                            <span>??</span>
                            <span>{{ session("admin_error") }}</span>
                        </div>
                    @endif
                    @if($errors->hasBag("admin"))
                        <div class="alert-error">
                            <span>??</span>
                            <div>
                                @foreach($errors->getBag("admin")->all() as $e)
                                    <div>{{ $e }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route("admin.login") }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Admin Master Password</label>
                            <div class="input-box">
                                <span class="input-lead-icon">??</span>
                                <input type="password" id="admin_password" name="password" required placeholder="Enter admin master password" autofocus>
                                <button type="button" class="pw-eye-btn" onclick="togglePw('admin_password', this)" tabindex="-1">??</button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit-glow">
                            <span>Sign In as Admin</span>
                            <span>?</span>
                        </button>
                    </form>
                </div>

                <!-- 2. OWNER FORM -->
                <div id="formOwner" style="display: none;">
                    <div class="auth-header">
                        <div class="auth-avatar" style="box-shadow: 0 0 25px rgba(16, 185, 129, 0.35); background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.35) 100%); border-color: rgba(16, 185, 129, 0.4);">??</div>
                        <h3>Owner Login</h3>
                        <p>Sign in to manage your restaurant dashboard</p>
                    </div>

                    @if($errors->hasBag("owner"))
                        <div class="alert-error">
                            <span>??</span>
                            <div>
                                @foreach($errors->getBag("owner")->all() as $e)
                                    <div>{{ $e }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route("landing.owner-login") }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Select Your Restaurant</label>
                            <div class="input-box">
                                <span class="input-lead-icon">???</span>
                                <select name="restaurant_id" required>
                                    <option value="" disabled selected>— Choose your restaurant —</option>
                                    @foreach($restaurants as $r)
                                        <option value="{{ $r->id }}" {{ old("restaurant_id") == $r->id ? "selected" : "" }}>
                                            {{ $r->name }}{{ $r->city ? " · " . $r->city : "" }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Restaurant Owner Password</label>
                            <div class="input-box">
                                <span class="input-lead-icon">??</span>
                                <input type="password" id="owner_password" name="password" required placeholder="Enter owner password">
                                <button type="button" class="pw-eye-btn" onclick="togglePw('owner_password', this)" tabindex="-1">??</button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit-glow" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);">
                            <span>Sign In to Dashboard</span>
                            <span>?</span>
                        </button>
                    </form>
                </div>

                <div class="form-divider">
                    <span>or</span>
                </div>

                <div class="secure-badge-box">
                    <div class="secure-badge-icon">???</div>
                    <div class="secure-badge-text">
                        <h4>Secure & Reliable</h4>
                        <p>Multi-Tenant WhatsApp Bot Platform</p>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        © 2026 Restaurant Bot Platform. All rights reserved.
    </footer>

    <script>
        function switchLogin(type) {
            const tabAdmin  = document.getElementById("tabAdmin");
            const tabOwner  = document.getElementById("tabOwner");
            const formAdmin = document.getElementById("formAdmin");
            const formOwner = document.getElementById("formOwner");

            if (type === "admin") {
                tabAdmin.classList.add("active");
                tabOwner.classList.remove("active");
                formAdmin.style.display = "block";
                formOwner.style.display = "none";
                document.getElementById("admin_password")?.focus();
            } else {
                tabOwner.classList.add("active");
                tabAdmin.classList.remove("active");
                formOwner.style.display = "block";
                formAdmin.style.display = "none";
                document.getElementById("owner_password")?.focus();
            }
        }

        function togglePw(id, btn) {
            const inp = document.getElementById(id);
            if (!inp) return;
            if (inp.type === "password") {
                inp.type = "text";
                btn.textContent = "??";
            } else {
                inp.type = "password";
                btn.textContent = "??";
            }
        }

        // Auto-switch to owner tab if owner errors occurred
        @if($errors->hasBag("owner"))
            switchLogin("owner");
        @endif
    </script>

</body>
</html>
