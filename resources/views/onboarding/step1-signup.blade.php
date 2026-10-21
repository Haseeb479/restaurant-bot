<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Register Your Restaurant</title>
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
            background-color: #F5F2EB;
            background-image: 
                radial-gradient(ellipse at 12% 18%, rgba(35, 30, 20, 0.045) 0%, transparent 48%),
                radial-gradient(ellipse at 88% 12%, rgba(16, 185, 129, 0.055) 0%, transparent 45%),
                radial-gradient(ellipse at 92% 70%, rgba(30, 25, 15, 0.05) 0%, transparent 52%),
                radial-gradient(ellipse at 20% 75%, rgba(35, 30, 20, 0.04) 0%, transparent 42%),
                linear-gradient(145deg, #F9F7F2 0%, #F4F1EA 45%, #EBE7DD 100%);
            color: #111827;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.48);
            backdrop-filter: blur(32px) saturate(170%);
            -webkit-backdrop-filter: blur(32px) saturate(170%);
            border: 1px solid rgba(255, 255, 255, 0.78);
            box-shadow: 
                0 35px 70px -15px rgba(45, 55, 72, 0.16),
                0 15px 30px -8px rgba(0, 0, 0, 0.06),
                inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.95);
            border-radius: 32px;
        }

        .btn-primary-glass {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.3), 0 4px 8px -2px rgba(0, 0, 0, 0.1);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-primary-glass:hover {
            background: #047857;
            box-shadow: 0 16px 36px -4px rgba(4, 120, 87, 0.42), 0 6px 12px -2px rgba(4, 120, 87, 0.2);
            transform: translateY(-1px);
        }

        .glass-pill-nav {
            background: rgba(255, 255, 255, 0.52);
            backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06), inset 0 1px 1px 0 rgba(255, 255, 255, 0.9);
            border-radius: 9999px;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex selection:bg-emerald-500 selection:text-white">

    <!-- Split Screen Layout -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 min-h-screen">
        
        <!-- Left Column: Trust, Social Proof & Partner Value (5 cols) -->
        <div class="lg:col-span-5 p-8 sm:p-12 flex flex-col justify-between border-r border-slate-200/80 bg-white/40 backdrop-blur-md relative overflow-hidden">
            
            <div class="space-y-6">
                <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12c0 3.84 2.16 7.18 5.34 8.86L6.5 22l3.41-1.07C10.59 21.08 11.28 21.14 12 21.14c5.52 0 10-4.48 10-9.14S17.52 2 12 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">Foodio</span>
                    <span class="text-[10px] font-mono font-bold bg-slate-200/60 text-slate-700 px-2 py-0.5 rounded-full ml-1">ONBOARDING</span>
                </a>

                <div class="space-y-2 pt-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600">Pure Hospitality</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                        Keep 100% of your revenue. Automate WhatsApp ordering into your POS.
                    </h2>
                </div>

                <!-- Showcase Image Asset -->
                <div class="glass-card p-3 rounded-2xl overflow-hidden mt-4 shadow-md">
                    <img src="/images/foodio_pure_hospitality.jpg" alt="Foodio System" class="w-full object-cover rounded-xl">
                </div>
            </div>

            <!-- Testimonial & Badges -->
            <div class="space-y-4 pt-6 border-t border-slate-200/80">
                <div class="flex items-center gap-1 text-amber-500">
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                    <span class="text-xs text-slate-600 font-bold ml-2">5.0 / 5.0 Rating</span>
                </div>
                <p class="text-xs text-slate-600 italic leading-relaxed">
                    "Foodio eliminated our aggregator commissions completely while giving our guests an effortless WhatsApp ordering experience."
                </p>
                <div class="flex items-center gap-4 text-slate-500 text-xs font-semibold pt-1">
                    <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i> No Credit Card Required</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> 14-Day Free Access</span>
                </div>
            </div>

        </div>

        <!-- Right Column: Registration Form (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-12 flex flex-col justify-between">
            
            <div class="max-w-xl mx-auto w-full space-y-6">
                
                <!-- Progress Stepper -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                        <span class="text-slate-900">Restaurant Registration</span>
                        <span class="text-emerald-700 font-mono font-bold">Step 1 of 3: Account Setup</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full w-1/3"></div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Create Your Restaurant Bot</h1>
                    <p class="text-xs text-slate-500 mt-1">Configure your automated WhatsApp ordering bot and Cloud POS credentials.</p>
                </div>

                <!-- Validation Alerts -->
                @if(session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-rose-700">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            <span>Please correct the errors below:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700 pl-1">
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
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            <i data-lucide="store" class="w-3.5 h-3.5"></i> 1. Restaurant Details
                        </span>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Restaurant or Brand Name *</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="e.g. Copper Kettle Grill & Bar"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                            >
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">City *</label>
                                <input
                                    type="text"
                                    name="city"
                                    value="{{ old('city') }}"
                                    placeholder="e.g. Lahore, Karachi, Islamabad"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Address / Branch</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="{{ old('address') }}"
                                    placeholder="e.g. Main Boulevard, Gulberg"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 2. WhatsApp Integration -->
                    <div class="space-y-3 pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-bold uppercase tracking-wider text-whatsapp flex items-center gap-1.5">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> 2. WhatsApp Bot Integration
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Restaurant WhatsApp Number *</label>
                                <input
                                    type="text"
                                    name="whatsapp_number"
                                    value="{{ old('whatsapp_number') }}"
                                    required
                                    placeholder="e.g. 03001234567"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                                >
                                <span class="text-[10px] text-slate-500">The number customers text to order.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Owner Contact Phone *</label>
                                <input
                                    type="text"
                                    name="owner_phone"
                                    value="{{ old('owner_phone') }}"
                                    required
                                    placeholder="e.g. 03217654321"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                                >
                                <span class="text-[10px] text-slate-500">Direct personal phone for shift alerts.</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Owner Full Name *</label>
                            <input
                                type="text"
                                name="owner_name"
                                value="{{ old('owner_name') }}"
                                required
                                placeholder="e.g. Ahmed Khan"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                            >
                        </div>
                    </div>

                    <!-- 3. Security Credentials -->
                    <div class="space-y-3 pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i> 3. Master Terminal Credentials
                        </span>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Owner Work Email *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                placeholder="owner@restaurant.com"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Terminal Master Password *</label>
                            <input
                                type="password"
                                name="owner_password"
                                required
                                placeholder="Minimum 8 characters with letters & numbers"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm transition"
                            >
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            class="btn-primary-glass w-full py-3.5 rounded-full text-xs font-bold flex items-center justify-center gap-2"
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
                <a href="{{ route('landing.owner-login-page') }}" class="text-emerald-700 hover:underline font-bold ml-1">
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
