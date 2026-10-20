<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — 3D Spatial Terminal Sign In</title>
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
            background-color: #0b0f17;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 10%, rgba(20, 184, 166, 0.10) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 60%);
        }

        .perspective-container {
            perspective: 1000px;
        }

        .clay-card-3d {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.2),
                0 30px 60px -12px rgba(0, 0, 0, 0.8),
                0 0 40px -10px rgba(16, 185, 129, 0.25);
            border-radius: 32px;
            transform-style: preserve-3d;
            transition: transform 0.3s ease-out;
        }

        .btn-3d {
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.4);
        }
        .btn-3d:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 14px 28px -4px rgba(16, 185, 129, 0.5), inset 0 1px 2px rgba(255, 255, 255, 0.6);
        }
        .btn-3d:active {
            transform: translateY(2px) scale(0.98);
            box-shadow: 0 4px 10px -2px rgba(16, 185, 129, 0.4);
        }

        /* 3D Keycard Floating Animation */
        @keyframes float-keycard {
            0%, 100% { transform: translateY(0px) rotate(-2deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }
        .keycard-float {
            animation: float-keycard 4s ease-in-out infinite;
        }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-slate-950 relative overflow-x-hidden">

    <!-- Top Minimal Navigation -->
    <header class="p-6 relative z-10 flex items-center justify-between max-w-7xl mx-auto w-full">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition-transform">
                <i data-lucide="utensils" class="w-5 h-5 text-slate-950 stroke-[2.8]"></i>
            </div>
            <span class="text-lg font-black text-white tracking-tight">Foodio <span class="text-[10px] bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black px-2 py-0.5 rounded-full uppercase tracking-wider">3D PORTAL</span></span>
        </a>

        <div class="flex items-center gap-4 text-xs font-bold">
            <span class="text-slate-400 hidden sm:inline">New restaurant?</span>
            <a href="{{ route('onboarding.signup') }}" class="btn-3d px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 hover:border-emerald-500/50 text-emerald-400 hover:text-emerald-300 transition-all flex items-center gap-1.5 shadow-sm">
                <span>Start Free Trial</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    </header>

    <!-- Main 3D Suspended Container -->
    <main class="flex-1 flex items-center justify-center p-4 relative z-10 my-4 perspective-container">
        
        <div id="login-card-3d" class="w-full max-w-md clay-card-3d p-8 sm:p-10 border border-white/10 relative">
            
            <!-- 3D Suspended Security Keycard Avatar -->
            <div class="keycard-float -mt-16 mx-auto w-20 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-600 p-1 flex items-center justify-center shadow-xl shadow-emerald-500/30 border border-white/30 text-slate-950">
                <div class="w-full h-full rounded-xl bg-slate-950/20 backdrop-blur-sm flex items-center justify-center text-white">
                    <i data-lucide="shield-check" class="w-7 h-7 text-emerald-300"></i>
                </div>
            </div>

            <!-- Header -->
            <div class="text-center space-y-1.5 pt-4">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Spatial Authorization
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Restaurant Sign In</h1>
                <p class="text-xs text-slate-400">Access your 3D Cloud POS, WhatsApp bot, and kitchen display</p>
            </div>

            <!-- Session Alerts -->
            @if(session('success'))
                <div class="mt-4 p-3.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mt-4 p-3.5 rounded-xl bg-cyan-950/60 border border-cyan-500/40 text-cyan-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-cyan-400 shrink-0"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->hasBag('owner') && $errors->owner->any())
                <div class="mt-4 p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    <span>{{ $errors->owner->first() }}</span>
                </div>
            @elseif($errors->any())
                <div class="mt-4 p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Station Role Selector Pucks -->
            <div class="pt-4">
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Station Role</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="selectStation('owner', this)" class="station-tab py-2.5 rounded-xl border border-emerald-500 bg-emerald-500/10 text-emerald-300 text-xs font-black transition-all flex items-center justify-center gap-1 shadow-sm">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        <span>Admin</span>
                    </button>
                    <button type="button" onclick="selectStation('cashier', this)" class="station-tab py-2.5 rounded-xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-600 transition-all flex items-center justify-center gap-1">
                        <i data-lucide="tablet" class="w-3.5 h-3.5"></i>
                        <span>POS</span>
                    </button>
                    <button type="button" onclick="selectStation('kitchen', this)" class="station-tab py-2.5 rounded-xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-600 transition-all flex items-center justify-center gap-1">
                        <i data-lucide="chef-hat" class="w-3.5 h-3.5"></i>
                        <span>Kitchen</span>
                    </button>
                </div>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('landing.owner-login') }}" class="space-y-4 pt-3" onsubmit="sessionStorage.setItem('owner_authenticated_session','active')">
                @csrf

                <!-- Restaurant Name or Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Restaurant Name or Phone</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="text"
                            name="restaurant_name"
                            value="{{ old('restaurant_name') }}"
                            placeholder="e.g. Copper Kettle Grill or 03001234567"
                            required
                            autocomplete="organization"
                            class="w-full bg-slate-900/90 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all shadow-inner"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-300">Terminal Password</label>
                        <a href="{{ route('owner.forgot-password') }}" class="text-[11px] text-emerald-400 hover:text-emerald-300 font-bold">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input
                            type="password"
                            id="owner_password_field"
                            name="password"
                            placeholder="Enter password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-slate-900/90 border border-slate-700 rounded-xl pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all shadow-inner"
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300"
                            title="Show/hide password"
                        >
                            <i id="eye-icon" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Terminal -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 select-none">
                        <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0">
                        <span>Remember terminal</span>
                    </label>
                    <span class="text-emerald-400 font-mono text-[10px] flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> 256-Bit SSL
                    </span>
                </div>

                <!-- 3D Push Button -->
                <button
                    type="submit"
                    class="btn-3d w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2 mt-2"
                >
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Access Foodio POS Register</span>
                </button>
            </form>

            <div class="pt-5 border-t border-slate-800 text-center space-y-2 text-xs text-slate-400 mt-4">
                <p>
                    Don't have an active restaurant?
                    <a href="{{ route('onboarding.signup') }}" class="text-emerald-400 font-bold hover:underline ml-1">
                        Start 14-Day Free Trial →
                    </a>
                </p>
                <div class="pt-1 flex items-center justify-center gap-4 text-[11px] text-slate-500">
                    <a href="/" class="hover:text-slate-300 flex items-center gap-1 font-medium">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i> Home
                    </a>
                    <span>•</span>
                    <a href="{{ route('admin.force-logout') }}" class="hover:text-slate-300 font-medium">
                        Platform Superadmin
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="p-6 text-center text-xs text-slate-500 relative z-10">
        <p>Foodio 3D Restaurant OS • Cloud POS & Meta WhatsApp Integration</p>
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
                el.className = 'station-tab py-2.5 rounded-xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-600 transition-all flex items-center justify-center gap-1';
            });
            btn.className = 'station-tab py-2.5 rounded-xl border border-emerald-500 bg-emerald-500/10 text-emerald-300 text-xs font-black transition-all flex items-center justify-center gap-1 shadow-sm';
        }

        // 3D Tilt Effect on Mouse Move
        const card = document.getElementById('login-card-3d');
        document.addEventListener('mousemove', (e) => {
            const xAxis = (window.innerWidth / 2 - e.pageX) / 45;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 45;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
    </script>
</body>
</html>
