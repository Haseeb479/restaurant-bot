<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Restaurant Operator Portal Login</title>
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
        .glass-dark {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glow-emerald {
            box-shadow: 0 0 35px -5px rgba(16, 185, 129, 0.25);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white relative overflow-x-hidden">

    <!-- Ambient Glow Backgrounds -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[650px] h-[400px] bg-emerald-500/10 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-[450px] h-[400px] bg-teal-500/10 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- Top Minimal Navigation -->
    <header class="p-6 relative z-10 flex items-center justify-between max-w-7xl mx-auto w-full">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                <i data-lucide="utensils" class="w-4 h-4 text-slate-950 stroke-[2.5]"></i>
            </div>
            <span class="text-lg font-extrabold text-white tracking-tight">Foodio <span class="text-[10px] bg-emerald-500/20 text-emerald-300 font-mono px-2 py-0.5 rounded-full border border-emerald-500/30">PORTAL</span></span>
        </a>

        <div class="flex items-center gap-4 text-xs font-semibold">
            <span class="text-slate-400 hidden sm:inline">New to Foodio?</span>
            <a href="{{ route('onboarding.signup') }}" class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 hover:border-emerald-500/50 text-emerald-400 hover:text-emerald-300 transition-all flex items-center gap-1.5 shadow-sm">
                <span>Start Free Trial</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </header>

    <!-- Main Authentication Modal -->
    <main class="flex-1 flex items-center justify-center p-4 relative z-10 my-4">
        <div class="w-full max-w-md glass-dark rounded-3xl p-8 border border-slate-800 shadow-2xl glow-emerald space-y-6">
            
            <!-- Header -->
            <div class="text-center space-y-1.5">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-bold uppercase tracking-wider mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Terminal Authentication
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Restaurant Owner Sign In</h1>
                <p class="text-xs text-slate-400">Access your cloud POS register, WhatsApp bot, and kitchen display</p>
            </div>

            <!-- Session Alerts -->
            @if(session('success'))
                <div class="p-3.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="p-3.5 rounded-xl bg-cyan-950/60 border border-cyan-500/40 text-cyan-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-cyan-400 shrink-0"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->hasBag('owner') && $errors->owner->any())
                <div class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    <span>{{ $errors->owner->first() }}</span>
                </div>
            @elseif($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Station Role Selector Tabs (Visual UI) -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Station Role</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="selectStation('owner', this)" class="station-tab py-2 rounded-xl border border-emerald-500 bg-emerald-500/10 text-emerald-300 text-xs font-bold transition-all flex items-center justify-center gap-1">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        <span>Admin</span>
                    </button>
                    <button type="button" onclick="selectStation('cashier', this)" class="station-tab py-2 rounded-xl border border-slate-700 bg-slate-900 text-slate-400 text-xs font-semibold hover:border-slate-600 transition-all flex items-center justify-center gap-1">
                        <i data-lucide="tablet" class="w-3.5 h-3.5"></i>
                        <span>POS</span>
                    </button>
                    <button type="button" onclick="selectStation('kitchen', this)" class="station-tab py-2 rounded-xl border border-slate-700 bg-slate-900 text-slate-400 text-xs font-semibold hover:border-slate-600 transition-all flex items-center justify-center gap-1">
                        <i data-lucide="chef-hat" class="w-3.5 h-3.5"></i>
                        <span>Kitchen</span>
                    </button>
                </div>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('landing.owner-login') }}" class="space-y-4" onsubmit="sessionStorage.setItem('owner_authenticated_session','active')">
                @csrf

                <!-- Restaurant Name or Phone Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Restaurant Name or Phone</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="text"
                            name="restaurant_name"
                            value="{{ old('restaurant_name') }}"
                            placeholder="e.g. Copper Kettle Grill or 03001234567"
                            required
                            autocomplete="organization"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                        >
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1">Enter your restaurant name or registered WhatsApp number.</p>
                </div>

                <!-- Password with Show/Hide -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-300">Owner Password</label>
                        <a href="{{ route('owner.forgot-password') }}" class="text-[11px] text-emerald-400 hover:text-emerald-300 font-medium">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="password"
                            id="owner_password_field"
                            name="password"
                            placeholder="Enter terminal security password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300"
                            title="Show/hide password"
                        >
                            <i id="eye-icon" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Terminal & SSL -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 select-none">
                        <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                        <span>Remember this terminal</span>
                    </label>
                    <span class="text-emerald-400 font-mono text-[10px] flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> 256-Bit SSL
                    </span>
                </div>

                <!-- Action Button -->
                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all flex items-center justify-center gap-2"
                >
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Access Foodio POS Register</span>
                </button>
            </form>

            <div class="pt-4 border-t border-slate-800 text-center space-y-2 text-xs text-slate-400">
                <p>
                    Don't have an active restaurant?
                    <a href="{{ route('onboarding.signup') }}" class="text-emerald-400 font-bold hover:underline ml-1">
                        Start 14-Day Free Trial →
                    </a>
                </p>
                <div class="pt-2 flex items-center justify-center gap-4 text-[11px] text-slate-500">
                    <a href="/" class="hover:text-slate-300 flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i> Home
                    </a>
                    <span>•</span>
                    <a href="{{ route('admin.force-logout') }}" class="hover:text-slate-300">
                        Platform Superadmin
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 text-center text-xs text-slate-500 relative z-10">
        <p>Foodio Restaurant OS • Meta WhatsApp Cloud API Partner • Live POS Sync</p>
    </footer>

    <script>
        lucide.createIcons();

        function togglePassword() {
            const input = document.getElementById('owner_password_field');
            const eye = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        function selectStation(role, btn) {
            document.querySelectorAll('.station-tab').forEach(el => {
                el.className = 'station-tab py-2 rounded-xl border border-slate-700 bg-slate-900 text-slate-400 text-xs font-semibold hover:border-slate-600 transition-all flex items-center justify-center gap-1';
            });
            btn.className = 'station-tab py-2 rounded-xl border border-emerald-500 bg-emerald-500/10 text-emerald-300 text-xs font-bold transition-all flex items-center justify-center gap-1';
        }
    </script>
</body>
</html>
