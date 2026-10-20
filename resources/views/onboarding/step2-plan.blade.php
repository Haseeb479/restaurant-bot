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
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <!-- Header Navbar -->
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-18 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                    <i data-lucide="utensils" class="w-4 h-4 text-slate-950 stroke-[2.5]"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-lg font-extrabold text-white tracking-tight leading-none">Foodio<span class="text-emerald-400">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Subscription Setup</span>
                </div>
            </a>
            <div class="text-xs font-semibold text-slate-300 bg-slate-800 px-3.5 py-1.5 rounded-full border border-slate-700 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Configuring: <strong class="text-white">{{ $restaurant->name }}</strong></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-10 space-y-8">

        <!-- Progress Steps -->
        <div class="max-w-lg mx-auto">
            <div class="flex items-center justify-between text-xs font-bold text-slate-400 mb-2">
                <span class="text-emerald-400">Step 1: Account (Done)</span>
                <span class="text-white font-mono">Step 2: Choose Plan (Active)</span>
                <span>Step 3: Verification</span>
            </div>
            <div class="w-full h-1.5 bg-slate-900 rounded-full overflow-hidden border border-slate-800">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 w-2/3"></div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="text-center max-w-xl mx-auto space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Zero Commission Per Order</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Select Your Subscription Plan</h1>
            <p class="text-slate-400 text-xs sm:text-sm">Keep 100% of customer order revenue. Upgrade, downgrade, or cancel anytime.</p>
        </div>

        @if($errors->any())
            <div class="max-w-md mx-auto p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400"></i>
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
                    <div class="rounded-3xl p-8 {{ $isPopular ? 'glass-dark border-2 border-emerald-500 shadow-2xl shadow-emerald-500/10' : 'glass-card border border-slate-800' }} flex flex-col justify-between relative hover:border-emerald-500/40 transition duration-300 space-y-6">
                        
                        @if($isPopular)
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-emerald-500 text-slate-950 text-[10px] font-black uppercase tracking-wider px-3.5 py-1 rounded-full shadow-md">
                                Most Popular Choice
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-2xl font-bold text-white">{{ $plan->name }}</h3>
                                <p class="text-xs text-slate-400 mt-1 min-h-[32px]">{{ $plan->description ?? 'Ideal plan for automated restaurant operations.' }}</p>
                            </div>

                            <div class="py-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs text-slate-400 font-bold">PKR</span>
                                    <span class="text-4xl font-black text-white">Rs. {{ number_format((float) ($plan->price_monthly ?? $plan->price_pkr ?? 0)) }}</span>
                                    <span class="text-xs text-slate-400 font-medium">/ month</span>
                                </div>
                            </div>

                            <ul class="space-y-3 text-xs text-slate-300 border-t border-slate-800 pt-6">
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span>Up to <strong>{{ $plan->max_orders_per_month >= 999999 ? 'Unlimited' : number_format($plan->max_orders_per_month) }}</strong> orders/month</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span><strong>{{ $plan->max_menu_items >= 999999 ? 'Unlimited' : $plan->max_menu_items }}</strong> Menu Items</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span>24/7 AI WhatsApp Ordering Bot</span>
                                </li>
                                @if($plan->slug !== 'starter')
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span><strong>Automated Order Status Alerts</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span>Excel &amp; Image Menu OCR</span>
                                </li>
                                @endif
                                <li class="flex items-center gap-2.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                    <span>Kitchen Thermal Receipt Printing</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="w-full py-3.5 {{ $isPopular ? 'bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 text-slate-950 font-black shadow-lg shadow-emerald-500/20' : 'bg-slate-900 hover:bg-slate-800 text-white font-bold border border-slate-700' }} text-xs rounded-xl transition duration-200 active:scale-[0.99] flex items-center justify-center gap-2"
                            >
                                <span>Choose {{ $plan->name }}</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </form>

    </main>

    <!-- Footer -->
    <footer class="p-6 border-t border-slate-800 text-center text-xs text-slate-500">
        <p>Foodio Restaurant OS • Safe 256-Bit SSL Encrypted Billing</p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
