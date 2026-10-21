<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Plan — {{ $restaurant->name }} | Foodio</title>
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
            backdrop-filter: blur(28px) saturate(170%);
            -webkit-backdrop-filter: blur(28px) saturate(170%);
            border: 1px solid rgba(255, 255, 255, 0.78);
            box-shadow: 
                0 35px 70px -15px rgba(45, 55, 72, 0.14),
                0 15px 30px -8px rgba(0, 0, 0, 0.06),
                inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.95);
            border-radius: 28px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card:hover {
            box-shadow: 
                0 45px 85px -15px rgba(45, 55, 72, 0.18),
                0 20px 40px -8px rgba(0, 0, 0, 0.08),
                inset 0 1px 1.5px 0 rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        .glass-card-featured {
            background: rgba(255, 255, 255, 0.58);
            backdrop-filter: blur(28px) saturate(170%);
            -webkit-backdrop-filter: blur(28px) saturate(170%);
            border: 2px solid rgba(16, 185, 129, 0.75);
            box-shadow: 
                0 45px 85px -15px rgba(16, 185, 129, 0.20),
                0 20px 40px -8px rgba(45, 55, 72, 0.14),
                inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.95);
            border-radius: 28px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card-featured:hover {
            box-shadow: 
                0 50px 95px -15px rgba(16, 185, 129, 0.25),
                0 24px 45px -8px rgba(45, 55, 72, 0.18),
                inset 0 1px 1.5px 0 rgba(255, 255, 255, 1);
            transform: translateY(-2px);
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

        .btn-featured {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -3px rgba(16, 185, 129, 0.35);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-featured:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 12px 28px -3px rgba(16, 185, 129, 0.45);
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
    <header class="p-6 relative z-10 flex items-center justify-between max-w-6xl mx-auto w-full">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-sm">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12c0 3.84 2.16 7.18 5.34 8.86L6.5 22l3.41-1.07C10.59 21.08 11.28 21.14 12 21.14c5.52 0 10-4.48 10-9.14S17.52 2 12 2z"/>
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="text-xl font-bold tracking-tight text-slate-900 leading-none">Foodio</span>
                <span class="text-[9px] font-mono font-bold text-slate-500 mt-0.5 uppercase tracking-wider">Plan Activation</span>
            </div>
        </a>

        <div class="flex items-center gap-2 glass-pill-nav px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Configuring: <strong class="text-slate-900">{{ $restaurant->name }}</strong></span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-6 sm:py-8 space-y-8">

        <!-- Progress Steps -->
        <div class="max-w-md mx-auto space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span class="text-emerald-700 font-bold flex items-center gap-1">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    Account Created
                </span>
                <span class="text-slate-900 font-bold">Step 2: Choose Plan</span>
                <span>Step 3: Verification</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200/80 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 w-2/3 rounded-full"></div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="text-center max-w-xl mx-auto space-y-2">
            <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-3 py-1 rounded-full">
                0% Commission • Pure Hospitality
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Select Your Subscription Plan
            </h1>
            <p class="text-slate-600 text-xs sm:text-sm">
                Keep 100% of customer order revenue. Seamless WhatsApp bot + Cloud POS. Upgrade or cancel anytime.
            </p>
        </div>

        @if($errors->any())
            <div class="max-w-md mx-auto p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.plan.submit', $restaurant->id) }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto items-stretch">
                @foreach($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'pro' || strtolower($plan->slug) === 'growth';
                    @endphp
                    <div class="{{ $isPopular ? 'glass-card-featured ring-4 ring-emerald-500/10' : 'glass-card' }} p-8 flex flex-col justify-between relative transition duration-300 hover:shadow-xl space-y-6">
                        
                        @if($isPopular)
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider px-3.5 py-1 rounded-full shadow-md">
                                Recommended
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900">{{ $plan->name }}</h3>
                                <p class="text-xs text-slate-500 mt-1 min-h-[32px]">{{ $plan->description ?? 'Ideal plan for automated restaurant operations.' }}</p>
                            </div>

                            <div class="py-2">
                                @if(($plan->price_monthly ?? 0) <= 0 || $plan->slug === 'starter')
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-black text-slate-900">Free</span>
                                        <span class="text-xs text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">14-Day Free Trial</span>
                                    </div>
                                @else
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-xs text-slate-500 font-bold">PKR</span>
                                        <span class="text-4xl font-black text-slate-900">Rs. {{ number_format((float) ($plan->price_monthly ?? $plan->price_pkr ?? 0)) }}</span>
                                        <span class="text-xs text-slate-500 font-medium">/ month</span>
                                    </div>
                                @endif
                            </div>

                            <ul class="space-y-3 text-xs text-slate-600 border-t border-slate-200/80 pt-6">
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Up to <strong class="text-slate-900">{{ $plan->max_orders_per_month >= 999999 ? 'Unlimited' : number_format($plan->max_orders_per_month) }}</strong> orders/month</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span><strong class="text-slate-900">{{ $plan->max_menu_items >= 999999 ? 'Unlimited' : $plan->max_menu_items }}</strong> Menu Items</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>24/7 AI WhatsApp Ordering Bot</span>
                                </li>
                                @if($plan->slug !== 'starter')
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span><strong class="text-slate-900">Automated Order Status Alerts</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Excel &amp; Image Menu OCR</span>
                                </li>
                                @endif
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Kitchen Thermal Receipt Printing</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="w-full py-3.5 {{ $isPopular ? 'btn-featured' : 'btn-primary-glass' }} text-xs font-bold rounded-xl transition duration-200 active:scale-[0.99] flex items-center justify-center gap-2"
                            >
                                @if(($plan->price_monthly ?? 0) <= 0 || $plan->slug === 'starter')
                                    <span>Start Free 14-Day Trial</span>
                                @elseif($isPopular)
                                    <span>Launch Free 14-Day Trial</span>
                                @else
                                    <span>Choose {{ $plan->name }}</span>
                                @endif
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </form>

    </main>

    <!-- Footer -->
    <footer class="p-6 border-t border-slate-200/80 text-center text-xs text-slate-500">
        <p>Foodio Restaurant OS • Safe 256-Bit SSL Encrypted Billing • Zero Transaction Fees</p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
