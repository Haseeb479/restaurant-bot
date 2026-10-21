<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Restaurant Terminal Sign In</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        whatsapp: {
                            DEFAULT: '#25D366',
                            dark: '#128C7E',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #F8F7F4;
            background-image: 
                radial-gradient(at 15% 10%, rgba(16, 185, 129, 0.05) 0px, transparent 40%),
                radial-gradient(at 85% 15%, rgba(209, 250, 229, 0.3) 0px, transparent 45%);
            color: #111827;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.06),
                0 2px 6px rgba(0, 0, 0, 0.02);
            border-radius: 32px;
        }

        .btn-primary-glass {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 8px 20px -3px rgba(15, 23, 42, 0.25);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-primary-glass:hover {
            background: #047857;
            box-shadow: 0 10px 25px -3px rgba(4, 120, 87, 0.35);
            transform: translateY(-1px);
        }

        .glass-pill-nav {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 9999px;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <!-- Top Minimal Navigation -->
    <header class="p-6 relative z-10 flex items-center justify-between max-w-5xl mx-auto w-full">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-sm">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12c0 3.84 2.16 7.18 5.34 8.86L6.5 22l3.41-1.07C10.59 21.08 11.28 21.14 12 21.14c5.52 0 10-4.48 10-9.14S17.52 2 12 2z"/>
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">Foodio</span>
            <span class="text-[10px] font-mono font-bold bg-slate-200/60 text-slate-700 px-2 py-0.5 rounded-full ml-1">TERMINAL</span>
        </a>

        <div class="flex items-center gap-4 text-xs font-semibold">
            <span class="text-slate-500 hidden sm:inline">New restaurant?</span>
            <a href="{{ route('onboarding.signup') }}" class="glass-pill-nav px-4 py-2 text-slate-900 hover:text-emerald-700 font-bold transition">
                Start Free Trial →
            </a>
        </div>
    </header>

    <!-- Main Glass Login Container -->
    <main class="flex-1 flex items-center justify-center p-4 relative z-10 my-4">
        <div class="w-full max-w-md glass-card p-8 sm:p-10 space-y-6">
            
            <!-- Header -->
            <div class="text-center space-y-1">
                <span class="inline-block px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold uppercase tracking-wider border border-emerald-200">
                    Terminal Authentication
                </span>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">Restaurant Sign In</h1>
                <p class="text-xs text-slate-500">Access your cloud POS, WhatsApp bot, and kitchen display</p>
            </div>

            <!-- Session Alerts -->
            @if(session('success'))
                <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->hasBag('owner') && $errors->owner->any())
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>{{ $errors->owner->first() }}</span>
                </div>
            @elseif($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Station Role Selector Pucks -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Station Role</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="selectStation('owner', this)" class="station-tab py-2 rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-800 text-xs font-bold transition flex items-center justify-center gap-1 shadow-sm">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        <span>Admin</span>
                    </button>
                    <button type="button" onclick="selectStation('cashier', this)" class="station-tab py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:border-slate-300 transition flex items-center justify-center gap-1">
                        <i data-lucide="tablet" class="w-3.5 h-3.5"></i>
                        <span>POS</span>
                    </button>
                    <button type="button" onclick="selectStation('kitchen', this)" class="station-tab py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:border-slate-300 transition flex items-center justify-center gap-1">
                        <i data-lucide="chef-hat" class="w-3.5 h-3.5"></i>
                        <span>Kitchen</span>
                    </button>
                </div>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('landing.owner-login') }}" class="space-y-4 pt-1" onsubmit="sessionStorage.setItem('owner_authenticated_session','active')">
                @csrf

                <!-- Restaurant Name or Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Restaurant Name or Phone</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="text"
                            name="restaurant_name"
                            value="{{ old('restaurant_name') }}"
                            placeholder="e.g. Copper Kettle Grill or 03001234567"
                            required
                            autocomplete="organization"
                            class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold text-slate-700">Terminal Password</label>
                        <a href="{{ route('owner.forgot-password') }}" class="text-[11px] text-emerald-700 hover:underline font-bold">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="password"
                            id="owner_password_field"
                            name="password"
                            placeholder="Enter password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-10 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
                            title="Show/hide password"
                        >
                            <i id="eye-icon" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Terminal -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 select-none">
                        <input type="checkbox" checked class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-0">
                        <span>Remember terminal</span>
                    </label>
                    <span class="text-emerald-700 font-mono text-[10px] font-bold flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> SSL Protected
                    </span>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="btn-primary-glass w-full py-3.5 rounded-full text-xs font-bold flex items-center justify-center gap-2 mt-2"
                >
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Access Foodio POS Register</span>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-200/80 text-center space-y-2 text-xs text-slate-500">
                <p>
                    Don't have an active restaurant?
                    <a href="{{ route('onboarding.signup') }}" class="text-emerald-700 font-bold hover:underline ml-1">
                        Register Your Restaurant →
                    </a>
                </p>
                <div class="pt-1 flex items-center justify-center gap-4 text-[11px] text-slate-400">
                    <a href="/" class="hover:text-slate-700 flex items-center gap-1 font-medium">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i> Home
                    </a>
                    <span>•</span>
                    <a href="{{ route('admin.force-logout') }}" class="hover:text-slate-700 font-medium">
                        Platform Superadmin
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Clean Footer -->
    <footer class="p-6 text-center text-xs text-slate-400 relative z-10">
        <p>Foodio — Pure hospitality. Zero commissions.</p>
    </footer>

    <script>
        lucide.createIcons();

        function togglePassword() {
            const input = document.getElementById('owner_password_field');
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        function selectStation(role, btn) {
            document.querySelectorAll('.station-tab').forEach(el => {
                el.className = 'station-tab py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:border-slate-300 transition flex items-center justify-center gap-1';
            });
            btn.className = 'station-tab py-2 rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-800 text-xs font-bold transition flex items-center justify-center gap-1 shadow-sm';
        }
    </script>
</body>
</html>
