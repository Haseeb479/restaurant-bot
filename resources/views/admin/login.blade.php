<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Login — Foodio Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-wa { background: linear-gradient(135deg, #059669 0%, #047857 50%, #064e3b 100%); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased flex flex-col">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <header class="bg-white border-b border-slate-200/80">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/30 group-hover:scale-105 transition">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.08L2 22l5.09-1.34C8.54 21.49 10.22 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm4.64 14.34c-.23.64-1.34 1.25-1.85 1.33-.48.08-1.08.11-3.23-.78-2.61-1.08-4.27-3.76-4.4-3.93-.13-.17-1.06-1.41-1.06-2.69s.67-1.9 1-2.17c.28-.27.61-.34.81-.34.2 0 .41 0 .59.01.19.01.44-.07.69.52.26.61.88 2.14.95 2.3.07.16.12.35.02.56-.1.21-.15.34-.3.51-.15.17-.32.38-.45.51-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.3.14.47.12.65-.08.17-.2.74-.86.94-1.15.2-.29.41-.24.68-.14.28.1.1.75 2.13.88 2.27.13.14.22.21.25.26.04.05.04.83-.19 1.47z"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Platform Administration</span>
                </div>
            </a>
            <a href="{{ route('landing') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 flex items-center gap-1 transition">
                ← Back to Home
            </a>
        </div>
    </header>

    <!-- ── Main Login Area ─────────────────────────────────────── -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">

        <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-0 bg-white rounded-3xl border border-slate-200/80 shadow-2xl shadow-slate-200/60 overflow-hidden">

            <!-- Left Green Banner -->
            <div class="gradient-wa p-10 flex flex-col justify-between text-white hidden md:flex">
                <div>
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center text-2xl mb-6">
                        🛡️
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight leading-tight mb-3">
                        Foodio Platform<br>Administration
                    </h2>
                    <p class="text-emerald-100 text-sm leading-relaxed">
                        Review pending restaurant applications, manage subscriptions, monitor WhatsApp bot connections, and oversee the entire Foodio platform.
                    </p>
                </div>

                <div class="mt-10 pt-6 border-t border-white/20 grid grid-cols-3 gap-4 text-center text-xs font-bold">
                    <div>
                        <div class="text-xl font-black text-white">100%</div>
                        <div class="text-emerald-200 mt-0.5">Automated</div>
                    </div>
                    <div>
                        <div class="text-xl font-black text-white">24/7</div>
                        <div class="text-emerald-200 mt-0.5">Monitoring</div>
                    </div>
                    <div>
                        <div class="text-xl font-black text-white">🔒</div>
                        <div class="text-emerald-200 mt-0.5">Secured</div>
                    </div>
                </div>
            </div>

            <!-- Right Login Form -->
            <div class="p-10 flex flex-col justify-center bg-white">

                <div class="mb-8">
                    <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-2xl mb-4">🔐</div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Superadmin Access</h1>
                    <p class="text-slate-500 text-xs mt-1.5">
                        Enter the platform master password to continue. This area is restricted to authorized administrators only.
                    </p>
                </div>

                @if(session('info'))
                    <div class="mb-6 p-3.5 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 text-xs font-semibold flex items-center gap-2">
                        🔒 {{ session('info') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold space-y-1">
                        @foreach($errors->all() as $e)
                            <div class="flex items-center gap-1.5">⚠️ {{ $e }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Admin Master Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="admin_password"
                                name="password"
                                required
                                autofocus
                                placeholder="••••••••••••"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition pr-12"
                            >
                            <button type="button" onclick="togglePw('admin_password', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600 transition p-1 text-lg">
                                👁
                            </button>
                        </div>
                    </div>

                    @if(\App\Models\Setting::get('admin_2fa_enabled', '0') === '1')
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">2FA Security PIN *</label>
                        <input
                            type="password"
                            name="two_fa_pin"
                            required
                            placeholder="Enter Security PIN"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                        >
                    </div>
                    @endif

                    <button
                        type="submit"
                        class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99] flex items-center justify-center gap-2"
                    >
                        <span>Sign In to Superadmin Panel</span>
                        <span>→</span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                    <p>Restaurant owner?
                        <a href="{{ route('landing.owner-login-page') }}" class="text-brand-600 font-bold hover:underline">
                            Sign in here →
                        </a>
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-1.5 text-slate-400">
                        <span>🔒</span>
                        <span>Secured multi-tenant platform architecture</span>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. Superadmin access is restricted and monitored.
    </footer>

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