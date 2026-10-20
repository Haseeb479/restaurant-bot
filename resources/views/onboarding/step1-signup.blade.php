<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — 3D Spatial Restaurant Onboarding</title>
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

        .clay-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.15),
                0 20px 40px -15px rgba(0, 0, 0, 0.7);
            border-radius: 28px;
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
        }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased min-h-screen flex selection:bg-emerald-500 selection:text-slate-950">

    <!-- Full 3D Split Layout -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 min-h-screen">
        
        <!-- Left Column: 3D Spatial Visual & Trust (5 cols) -->
        <div class="lg:col-span-5 p-8 sm:p-12 flex flex-col justify-between border-r border-slate-800 relative overflow-hidden bg-slate-950/60 backdrop-blur-md">
            
            <div class="relative z-10 space-y-6">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition-transform">
                        <i data-lucide="utensils" class="w-5 h-5 text-slate-950 stroke-[2.8]"></i>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white">Foodio <span class="text-[10px] bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black px-2 py-0.5 rounded-full uppercase tracking-wider">3D ONBOARDING</span></span>
                        <p class="text-[10px] text-slate-400">Zero Commission Restaurant OS</p>
                    </div>
                </a>

                <div class="space-y-2 pt-2">
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Launch in Under 10 Minutes</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                        Automate WhatsApp orders directly into your kitchen and Cloud POS.
                    </h2>
                </div>

                <!-- 3D Linked Visual Preview Card -->
                <div class="clay-card p-2 rounded-2xl border border-white/10 shadow-2xl overflow-hidden mt-4">
                    <img src="/images/foodio_3d_linked.jpg" alt="3D Ecosystem" class="w-full object-cover rounded-xl">
                </div>
            </div>

            <!-- Testimonial & Badges -->
            <div class="relative z-10 space-y-4 pt-6 border-t border-slate-800">
                <div class="flex items-center gap-1 text-amber-400">
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <span class="text-xs text-slate-300 font-bold ml-2">5.0 / 5.0 Rating</span>
                </div>
                <p class="text-xs text-slate-400 italic">
                    "Foodio's automated bot slashed our delivery aggregator commissions by $3,400 monthly while keeping orders running 24/7."
                </p>
                <div class="flex items-center gap-4 text-slate-400 text-xs font-bold pt-1">
                    <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i> No Credit Card Required</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> 14-Day Full Free Access</span>
                </div>
            </div>

        </div>

        <!-- Right Column: Registration Form (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-12 flex flex-col justify-between">
            
            <div class="max-w-xl mx-auto w-full space-y-6">
                
                <!-- 3D Step Progress Indicator -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400">
                        <span class="text-white">Restaurant Registration</span>
                        <span class="text-emerald-400 font-mono font-bold">Step 1 of 3: Account Setup</span>
                    </div>
                    <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden border border-slate-800 p-0.5">
                        <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 rounded-full w-1/3"></div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white">Create Your Restaurant Bot</h1>
                    <p class="text-xs text-slate-400 mt-1">Configure your automated WhatsApp ordering bot and Cloud POS credentials.</p>
                </div>

                <!-- Validation Alerts -->
                @if(session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs space-y-1">
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

                <!-- Form -->
                <form method="POST" action="{{ route('onboarding.signup.submit') }}" class="space-y-4">
                    @csrf

                    <!-- 1. Restaurant Details -->
                    <div class="space-y-3 pt-2">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="store" class="w-3.5 h-3.5"></i> 1. Restaurant Details
                        </span>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Restaurant or Brand Name *</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="e.g. Copper Kettle Grill & Bar"
                                class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                            >
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">City *</label>
                                <input
                                    type="text"
                                    name="city"
                                    value="{{ old('city') }}"
                                    placeholder="e.g. Lahore, Karachi, Islamabad"
                                    class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Address / Branch</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="{{ old('address') }}"
                                    placeholder="e.g. Main Boulevard, Gulberg"
                                    class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 2. WhatsApp Integration -->
                    <div class="space-y-3 pt-4 border-t border-slate-800">
                        <span class="text-xs font-black uppercase tracking-wider text-whatsapp flex items-center gap-1.5">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> 2. WhatsApp Bot Integration
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Restaurant WhatsApp Number *</label>
                                <input
                                    type="text"
                                    name="whatsapp_number"
                                    value="{{ old('whatsapp_number') }}"
                                    required
                                    placeholder="e.g. 03001234567"
                                    class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                                >
                                <span class="text-[10px] text-slate-500">The number customers text to order.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Owner Contact Phone *</label>
                                <input
                                    type="text"
                                    name="owner_phone"
                                    value="{{ old('owner_phone') }}"
                                    required
                                    placeholder="e.g. 03217654321"
                                    class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                                >
                                <span class="text-[10px] text-slate-500">Direct phone for urgent shift alerts.</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Owner Full Name *</label>
                            <input
                                type="text"
                                name="owner_name"
                                value="{{ old('owner_name') }}"
                                required
                                placeholder="e.g. Ahmed Khan"
                                class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                            >
                        </div>
                    </div>

                    <!-- 3. Security Credentials -->
                    <div class="space-y-3 pt-4 border-t border-slate-800">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i> 3. Master Terminal Credentials
                        </span>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Owner Work Email *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                placeholder="owner@restaurant.com"
                                class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Terminal Master Password *</label>
                            <input
                                type="password"
                                name="owner_password"
                                required
                                placeholder="Minimum 8 characters with letters & numbers"
                                class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 shadow-inner"
                            >
                        </div>
                    </div>

                    <!-- 3D Submit Button -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            class="btn-3d w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2"
                        >
                            <span>Proceed to Choose Plan</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>

                </form>

            </div>

            <!-- Bottom Nav -->
            <div class="text-center text-xs text-slate-500 pt-6">
                Already registered? 
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
