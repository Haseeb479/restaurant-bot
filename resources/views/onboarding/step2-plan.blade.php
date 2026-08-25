<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Plan — {{ $restaurant->name }} | Foodio</title>
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
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Header Navbar -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 h-18 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 group">
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
            <div class="text-xs font-semibold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200">
                Registering: <strong class="text-slate-900">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="mb-10 max-w-lg mx-auto">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-5 w-full h-0.5 bg-slate-200"></div>
                <div class="absolute left-0 top-5 w-1/3 h-0.5 bg-brand-600"></div>

                <!-- Step 1 (Done) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">
                        ✓
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Owner Signup</span>
                </div>

                <!-- Step 2 (Active) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">
                        2
                    </div>
                    <span class="text-[11px] font-bold text-brand-600 whitespace-nowrap">Choose Plan</span>
                </div>

                <!-- Step 3 (Upcoming) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 font-extrabold flex items-center justify-center ring-4 ring-white z-10">
                        3
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Payment</span>
                </div>

                <!-- Step 4 (Upcoming) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 font-extrabold flex items-center justify-center ring-4 ring-white z-10">
                        4
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Review & Access</span>
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="text-center max-w-xl mx-auto mb-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Select Your Subscription Plan</h1>
            <p class="text-slate-500 text-sm mt-2">Zero commission per order. Upgrade or switch plans anytime.</p>
        </div>

        @if($errors->any())
            <div class="max-w-md mx-auto mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.plan.submit', $restaurant->id) }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto items-stretch">
                @foreach($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'pro';
                    @endphp
                    <div class="bg-white rounded-3xl p-8 border {{ $isPopular ? 'border-2 border-brand-500 shadow-xl shadow-brand-500/10 ring-4 ring-brand-50' : 'border-slate-200/80 shadow-md' }} flex flex-col justify-between relative hover:shadow-xl transition duration-300">
                        
                        @if($isPopular)
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-[10px] font-black uppercase tracking-wider px-3.5 py-1 rounded-full shadow-md">
                                Most Popular
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                            </div>
                            <p class="text-xs text-slate-500 min-h-[32px]">{{ $plan->description ?? 'Ideal plan for automated restaurant operations.' }}</p>

                            <div class="my-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs text-slate-400 font-bold">PKR</span>
                                    <span class="text-4xl font-black text-slate-900">Rs. {{ number_format($plan->price_pkr) }}</span>
                                    <span class="text-xs text-slate-500 font-medium">/ month</span>
                                </div>
                            </div>

                            <ul class="space-y-3 text-xs text-slate-600 border-t border-slate-100 pt-6">
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span>Up to <strong>{{ $plan->max_orders_per_month >= 999999 ? 'Unlimited' : number_format($plan->max_orders_per_month) }}</strong> orders/month</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span><strong>{{ $plan->max_menu_items >= 999999 ? 'Unlimited' : $plan->max_menu_items }}</strong> Menu Items</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span>24/7 AI WhatsApp Ordering Bot</span>
                                </li>
                                @if($plan->slug !== 'starter')
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span><strong>Live Rider GPS Map Tracking</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span>Excel &amp; Image Menu OCR</span>
                                </li>
                                @endif
                                <li class="flex items-center gap-2.5">
                                    <span class="text-brand-600 font-bold">✓</span>
                                    <span>Kitchen Thermal Print Receipts</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-4">
                            <button
                                type="submit"
                                name="plan_id"
                                value="{{ $plan->id }}"
                                class="w-full py-3.5 {{ $isPopular ? 'bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-600/25' : 'bg-slate-900 hover:bg-slate-800 text-white' }} font-bold text-sm rounded-xl transition duration-200 active:scale-[0.99] flex items-center justify-center gap-2"
                            >
                                <span>Choose {{ $plan->name }}</span>
                                <span>→</span>
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>

        </form>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

</body>
</html>
