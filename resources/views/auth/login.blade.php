<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Sign In — Foodio Platform</title>
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
                            50: '#ecfdf5', 100: '#d1fae5',
                            500: '#10b981', 600: '#059669',
                            700: '#047857', 900: '#064e3b',
                        }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200/80">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/30 group-hover:scale-105 transition">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.08L2 22l5.09-1.34C8.54 21.49 10.22 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm4.64 14.34c-.23.64-1.34 1.25-1.85 1.33-.48.08-1.08.11-3.23-.78-2.61-1.08-4.27-3.76-4.4-3.93-.13-.17-1.06-1.41-1.06-2.69s.67-1.9 1-2.17c.28-.27.61-.34.81-.34.2 0 .41 0 .59.01.19.01.44-.07.69.52.26.61.88 2.14.95 2.3.07.16.12.35.02.56-.1.21-.15.34-.3.51-.15.17-.32.38-.45.51-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.3.14.47.12.65-.08.17-.2.74-.86.94-1.15.2-.29.41-.24.68-.14.28.1.1.75 2.13.88 2.27.13.14.22.21.25.26.04.05.04.83-.19 1.47z"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Restaurant Portal</span>
                </div>
            </a>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <a href="/" class="text-slate-500 hover:text-slate-900 transition">← Back to Home</a>
                <a href="{{ route('onboarding.signup') }}" class="text-brand-600 hover:text-brand-700 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200/60 transition">
                    Register New Restaurant
                </a>
            </div>
        </div>
    </header>

    <!-- Main Sign In Container -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-2xl shadow-slate-200/60">
            
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-brand-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl border border-brand-100">
                    🔐
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Restaurant Owner Sign In</h1>
                <p class="text-xs text-slate-500 mt-1.5">Sign in to access your live orders, menu catalog, and WhatsApp bot.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold flex items-center gap-2">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold flex items-center gap-2">
                    <span>ℹ️</span> {{ session('info') }}
                </div>
            @endif

            @if($errors->hasBag('owner') && $errors->owner->any())
                <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span> {{ $errors->owner->first() }}
                </div>
            @elseif($errors->any())
                <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('landing.owner-login') }}" class="space-y-5">
                @csrf

                <!-- Restaurant Name (type-in, not dropdown) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Restaurant Name
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🏪</span>
                        <input
                            type="text"
                            name="restaurant_name"
                            value="{{ old('restaurant_name') }}"
                            placeholder="e.g. Fezio Cafe & Grill"
                            required
                            autocomplete="organization"
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                        >
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">Type your restaurant / brand name exactly as registered.</p>
                </div>

                <!-- Password with show/hide toggle -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Owner Password</label>
                        <a href="{{ route('owner.forgot-password') }}" class="text-[11px] font-semibold text-brand-600 hover:text-brand-700 hover:underline transition">
                            Forgot Password?
                        </a>
                    </div>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🔑</span>
                        <input
                            type="password"
                            id="owner_password_field"
                            name="password"
                            placeholder="Enter your dashboard password"
                            required
                            autocomplete="current-password"
                            class="w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                        >
                        <!-- Show/Hide Toggle -->
                        <button
                            type="button"
                            onclick="toggleOwnerPassword()"
                            id="toggle_pw_btn"
                            tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600 transition p-1 text-base leading-none select-none"
                            title="Show/hide password"
                        >
                            <svg id="eye_icon_open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye_icon_closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    onclick="sessionStorage.setItem('owner_authenticated_session', 'active')"
                    class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99] flex items-center justify-center gap-2"
                >
                    <span>Sign In to Dashboard</span>
                    <span>→</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center space-y-3 text-xs text-slate-500">
                <p>
                    Don't have an account?
                    <a href="{{ route('onboarding.signup') }}" class="text-brand-600 font-bold hover:underline">
                        Register Your Restaurant →
                    </a>
                </p>
                <p>
                    Platform Administrator?
                    <a href="{{ route('admin.force-logout') }}" class="text-slate-600 font-semibold hover:underline hover:text-slate-900 transition">
                        SuperAdmin Login
                    </a>
                </p>
            </div>

        </div>
    </main>

    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

    <script>
    function toggleOwnerPassword() {
        const field = document.getElementById('owner_password_field');
        const eyeOpen   = document.getElementById('eye_icon_open');
        const eyeClosed = document.getElementById('eye_icon_closed');

        if (field.type === 'password') {
            field.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            field.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }

    // Reset authentication token whenever user is on the Login page (e.g. via Browser Back)
    sessionStorage.removeItem('owner_authenticated_session');
    window.addEventListener('pageshow', function() {
        sessionStorage.removeItem('owner_authenticated_session');
    });
    </script>
</body>
</html>
