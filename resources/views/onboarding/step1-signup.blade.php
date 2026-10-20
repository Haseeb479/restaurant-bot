<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Restaurant Registration & Free Trial</title>
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
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex selection:bg-emerald-500 selection:text-white">

    <!-- Full Split Layout -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 min-h-screen">
        
        <!-- Left Column: Trust, Social Proof & Partner Value (5 cols) -->
        <div class="lg:col-span-5 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 p-8 sm:p-12 flex flex-col justify-between border-r border-slate-800 relative overflow-hidden">
            
            <!-- Ambient Glow -->
            <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-[120px] pointer-events-none"></div>

            <!-- Top Branding -->
            <div class="relative z-10 space-y-6">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                        <i data-lucide="utensils" class="w-5 h-5 text-slate-950 stroke-[2.5]"></i>
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-tight text-white">Foodio <span class="text-xs text-emerald-400 font-mono">ONBOARDING</span></span>
                        <p class="text-[10px] text-slate-400">Zero Commission Restaurant OS</p>
                    </div>
                </a>

                <div class="space-y-2 pt-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Launch in Under 10 Minutes</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                        Stop losing 30% on every food order. Automate WhatsApp ordering directly into your POS.
                    </h2>
                </div>
            </div>

            <!-- Featured Testimonial Card -->
            <div class="relative z-10 my-8 glass-dark rounded-2xl p-6 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-1 text-amber-400">
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                </div>
                <p class="text-xs sm:text-sm text-slate-300 italic leading-relaxed">
                    "Foodio completely transformed our operations. By routing orders through our automated WhatsApp bot straight to the kitchen POS, we cut aggregator commissions by $3,400 each month."
                </p>
                <div class="flex items-center gap-3 pt-2 border-t border-slate-800">
                    <div class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center font-bold text-emerald-400 text-xs border border-emerald-500/30">
                        MG
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white">Marcus Greene</h4>
                        <p class="text-[10px] text-slate-400">Founder & Managing Director, Truffle Bistro Group</p>
                    </div>
                </div>
            </div>

            <!-- Trust Badges Footer -->
            <div class="relative z-10 space-y-3 pt-4 border-t border-slate-800">
                <div class="flex items-center gap-4 text-slate-400 text-xs">
                    <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i> No Credit Card Required</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Cancel Anytime</span>
                </div>
                <p class="text-[11px] text-slate-500">Official Meta WhatsApp Cloud Partner • 99.9% Uptime Guarantee</p>
            </div>

        </div>

        <!-- Right Column: Registration Form (7 cols) -->
        <div class="lg:col-span-7 bg-slate-950 p-6 sm:p-12 flex flex-col justify-between">
            
            <div class="max-w-xl mx-auto w-full space-y-6">
                
                <!-- Progress Indicator -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400">
                        <span>Restaurant Registration</span>
                        <span class="text-emerald-400 font-mono">Step 1 of 3: Account Setup</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-900 rounded-full overflow-hidden border border-slate-800">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 w-1/3"></div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Register Your Restaurant</h1>
                    <p class="text-xs text-slate-400 mt-1">Configure your automated WhatsApp ordering bot and Cloud POS credentials.</p>
                </div>

                <!-- Session / Validation Errors -->
                @if(session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-rose-400">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            <span>Please correct the errors below:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-200 pl-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Registration Form -->
                <form method="POST" action="{{ route('onboarding.signup.submit') }}" class="space-y-4">
                    @csrf

                    <!-- Section: Restaurant Information -->
                    <div class="space-y-3 pt-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="store" class="w-3.5 h-3.5"></i> 1. Restaurant Details
                        </span>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Restaurant or Brand Name *</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="e.g. Copper Kettle Grill & Bar"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                            >
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">City *</label>
                                <input
                                    type="text"
                                    name="city"
                                    value="{{ old('city') }}"
                                    placeholder="e.g. Lahore, Karachi, Islamabad"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Address / Branch</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="{{ old('address') }}"
                                    placeholder="e.g. Main Boulevard, Gulberg"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Section: WhatsApp & Contact Details -->
                    <div class="space-y-3 pt-4 border-t border-slate-800">
                        <span class="text-xs font-bold uppercase tracking-wider text-whatsapp flex items-center gap-1.5">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> 2. WhatsApp Bot Integration
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Restaurant WhatsApp Number *</label>
                                <input
                                    type="text"
                                    name="whatsapp_number"
                                    value="{{ old('whatsapp_number') }}"
                                    required
                                    placeholder="e.g. 03001234567"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                                >
                                <span class="text-[10px] text-slate-500">The number customers will text to order.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Owner Contact Phone *</label>
                                <input
                                    type="text"
                                    name="owner_phone"
                                    value="{{ old('owner_phone') }}"
                                    required
                                    placeholder="e.g. 03217654321"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                                >
                                <span class="text-[10px] text-slate-500">Your direct personal phone for urgent alerts.</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Owner Full Name *</label>
                            <input
                                type="text"
                                name="owner_name"
                                value="{{ old('owner_name') }}"
                                required
                                placeholder="e.g. Ahmed Khan"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Section: Security & Credentials -->
                    <div class="space-y-3 pt-4 border-t border-slate-800">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i> 3. Master Admin Login
                        </span>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Owner Work Email *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                placeholder="owner@restaurant.com"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Terminal Master Password *</label>
                            <input
                                type="password"
                                name="owner_password"
                                required
                                placeholder="Minimum 8 characters with letters and numbers"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
                            >
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all flex items-center justify-center gap-2"
                        >
                            <span>Proceed to Choose Plan</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>

                </form>

            </div>

            <!-- Bottom Link -->
            <div class="text-center text-xs text-slate-500 pt-6">
                Already have an active terminal? 
                <a href="{{ route('landing.owner-login-page') }}" class="text-emerald-400 hover:underline font-bold ml-1">
                    Sign in to POS Portal →
                </a>
            </div>

        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
