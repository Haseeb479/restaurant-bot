<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Foodio Platform</title>
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
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

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
            <a href="{{ route('landing.owner-login-page') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition flex items-center gap-1">
                ← Back to Sign In
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            @if(session('reset_sent'))
                <!-- Success State -->
                <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-2xl shadow-slate-200/60 text-center">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center mx-auto mb-5 text-3xl border border-brand-100">
                        ✅
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Request Submitted!</h1>
                    <p class="text-sm text-slate-500 leading-relaxed mb-6">
                        Your password reset request has been sent to the <strong>Foodio Admin Team</strong>. 
                        They will reset your password and notify you on your registered email or WhatsApp within <strong>24 hours</strong>.
                    </p>
                    <div class="bg-brand-50 border border-brand-200 rounded-2xl p-4 mb-6 text-xs text-brand-800 text-left space-y-1.5">
                        <div class="font-bold text-sm mb-2">📋 What happens next:</div>
                        <div class="flex items-start gap-2"><span>1️⃣</span><span>Admin verifies your identity via registered email/phone.</span></div>
                        <div class="flex items-start gap-2"><span>2️⃣</span><span>Admin resets your dashboard password.</span></div>
                        <div class="flex items-start gap-2"><span>3️⃣</span><span>New password is sent to your registered contact.</span></div>
                    </div>
                    <a href="{{ route('landing.owner-login-page') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm rounded-xl transition">
                        Back to Sign In →
                    </a>
                </div>

            @else
                <!-- Forgot Password Form -->
                <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-2xl shadow-slate-200/60">
                    
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl border border-amber-100">
                            🔓
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Forgot Your Password?</h1>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Enter your restaurant name and registered email. Your reset request will be sent to the Foodio Admin team.
                        </p>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                            <span>⚠️</span> {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('owner.forgot-password.submit') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Restaurant Name</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🏪</span>
                                <input
                                    type="text"
                                    name="restaurant_name"
                                    value="{{ old('restaurant_name') }}"
                                    placeholder="e.g. Fezio Cafe & Grill"
                                    required
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Registered Email Address</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">📧</span>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="e.g. owner@example.com"
                                    required
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Owner Mobile Number</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">📱</span>
                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="03XXXXXXXXX"
                                    required
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                                >
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">We use this to verify your identity before resetting.</p>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-brand-600/25 transition active:scale-[0.99] flex items-center justify-center gap-2"
                        >
                            <span>Submit Reset Request</span>
                            <span>→</span>
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
                        <p>
                            Remembered it?
                            <a href="{{ route('landing.owner-login-page') }}" class="text-brand-600 font-bold hover:underline">
                                Back to Sign In →
                            </a>
                        </p>
                        <p class="mt-2 text-slate-400">
                            For urgent support, contact the Foodio admin directly.
                        </p>
                    </div>

                </div>
            @endif

        </div>
    </main>

    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

</body>
</html>
