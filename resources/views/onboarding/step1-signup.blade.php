<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant — Foodio</title>
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
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased flex flex-col">

    <!-- ── Header ─────────────────────────────────────────────── -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 h-18 py-4 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/30 group-hover:scale-105 transition">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.08L2 22l5.09-1.34C8.54 21.49 10.22 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm4.64 14.34c-.23.64-1.34 1.25-1.85 1.33-.48.08-1.08.11-3.23-.78-2.61-1.08-4.27-3.76-4.4-3.93-.13-.17-1.06-1.41-1.06-2.69s.67-1.9 1-2.17c.28-.27.61-.34.81-.34.2 0 .41 0 .59.01.19.01.44-.07.69.52.26.61.88 2.14.95 2.3.07.16.12.35.02.56-.1.21-.15.34-.3.51-.15.17-.32.38-.45.51-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.3.14.47.12.65-.08.17-.2.74-.86.94-1.15.2-.29.41-.24.68-.14.28.1.1.75 2.13.88 2.27.13.14.22.21.25.26.04.05.04.83-.19 1.47z"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Restaurant Platform</span>
                </div>
            </a>
            <a href="{{ route('landing.owner-login-page') }}" class="text-xs font-bold text-slate-600 hover:text-brand-600 transition flex items-center gap-1.5 border border-slate-200 px-4 py-2 rounded-full hover:border-brand-300 hover:bg-brand-50">
                Already registered? Sign In →
            </a>
        </div>
    </header>

    <!-- ── Main ───────────────────────────────────────────────── -->
    <main class="flex-1 max-w-3xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="mb-10">
            <div class="flex items-center justify-between relative max-w-lg mx-auto">
                <!-- Track line -->
                <div class="absolute left-0 top-5 w-full h-0.5 bg-slate-200"></div>

                <!-- Step 1 Active -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">1</div>
                    <span class="text-[11px] font-bold text-brand-600 whitespace-nowrap">Owner Signup</span>
                </div>

                <!-- Step 2 -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 font-extrabold flex items-center justify-center ring-4 ring-white z-10">2</div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Choose Plan</span>
                </div>

                <!-- Step 3 -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 font-extrabold flex items-center justify-center ring-4 ring-white z-10">3</div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Payment</span>
                </div>

                <!-- Step 4 -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 font-extrabold flex items-center justify-center ring-4 ring-white z-10">4</div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Review & Access</span>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-200/40 p-8 sm:p-10">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Create Your Restaurant Bot</h1>
                <p class="text-slate-500 text-sm mt-2">Automate WhatsApp orders, live rider GPS tracking, and menu management with AI.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 text-sm font-medium flex items-center gap-2">
                    <span>✓</span> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('onboarding.signup.submit') }}" class="space-y-6">
                @csrf

                <!-- Section 1: Owner Information -->
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-brand-600 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-brand-50 rounded-md flex items-center justify-center text-[11px]">👤</span>
                        1. Owner &amp; Contact Information
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Owner Full Name *</label>
                            <input
                                type="text"
                                name="owner_name"
                                value="{{ old('owner_name') }}"
                                placeholder="e.g. Haseeb Ahmed"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Owner Email Address *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="e.g. haseeb@example.com"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Owner Personal Mobile *</label>
                            <input
                                type="text"
                                name="owner_phone"
                                value="{{ old('owner_phone') }}"
                                placeholder="03XXXXXXXXX"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Dashboard Login Password *</label>
                            <input
                                type="password"
                                name="owner_password"
                                placeholder="Minimum 6 characters"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                    </div>
                </div>

                <!-- Section 2: Restaurant Details -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-black uppercase tracking-wider text-brand-600 mb-4 flex items-center gap-2">
                        <span class="w-5 h-5 bg-brand-50 rounded-md flex items-center justify-center text-[11px]">🏪</span>
                        2. Restaurant Details &amp; WhatsApp Number
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Restaurant / Brand Name *</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="e.g. Fezio Cafe & Grill"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Ordering Number (Bot SIM) *</label>
                            <input
                                type="text"
                                name="whatsapp_number"
                                value="{{ old('whatsapp_number') }}"
                                placeholder="03XXXXXXXXX or +923XXXXXXXXX"
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                            <p class="text-[11px] text-slate-400 mt-1.5">This is the number customers will send WhatsApp messages to.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                            <input
                                type="text"
                                name="city"
                                value="{{ old('city') }}"
                                placeholder="e.g. Lodhran, Multan, Lahore"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Branch Address</label>
                            <input
                                type="text"
                                name="address"
                                value="{{ old('address') }}"
                                placeholder="e.g. Main Bazar, Near City Center"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full py-4 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-base rounded-2xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99] flex items-center justify-center gap-2"
                    >
                        <span>Continue to Choose Plan</span>
                        <span>→</span>
                    </button>
                </div>

            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            By registering, your application will be reviewed and approved by our team within 24 hours.
        </p>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60 mt-8">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

</body>
</html>
