<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Subscription Plan | {{ $restaurant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen antialiased flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <!-- Header Navbar -->
    <nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-emerald-500/20">
                    ⚡
                </div>
                <span class="font-extrabold text-lg tracking-tight text-white">FoodBot <span class="text-emerald-400">SaaS</span></span>
            </div>
            <div class="text-xs text-slate-400">
                Registering: <strong class="text-white">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="max-w-xl mx-auto mb-10">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-800 -z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1/3 h-1 bg-emerald-500 -z-0 transition-all duration-500"></div>

                <!-- Step 1 (Done) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        ✓
                    </div>
                    <span class="text-xs font-semibold text-slate-400 mt-2">Owner Signup</span>
                </div>

                <!-- Step 2 (Active) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900 shadow-lg shadow-emerald-500/30">
                        2
                    </div>
                    <span class="text-xs font-bold text-emerald-400 mt-2">Choose Plan</span>
                </div>

                <!-- Step 3 (Upcoming) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-slate-800 text-slate-400 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        3
                    </div>
                    <span class="text-xs font-semibold text-slate-500 mt-2">Payment</span>
                </div>

                <!-- Step 4 (Upcoming) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-slate-800 text-slate-400 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        4
                    </div>
                    <span class="text-xs font-semibold text-slate-500 mt-2">Review & Access</span>
                </div>
            </div>
        </div>

        <div class="text-center max-w-2xl mx-auto mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Select the Right Plan for {{ $restaurant->name }}</h1>
            <p class="text-slate-400 text-sm mt-3">Transparent pricing with no hidden fees. Upgrade, downgrade, or cancel anytime.</p>
        </div>

        <!-- Plans Grid -->
        <form method="POST" action="{{ route('onboarding.plan.submit', $restaurant->id) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 items-stretch">
                @foreach($plans as $plan)
                    @php
                        $isPopular = $plan->is_popular || $plan->slug === 'pro';
                    @endphp
                    <div class="relative flex flex-col justify-between rounded-3xl p-6 lg:p-8 transition duration-300 {{ $isPopular ? 'bg-gradient-to-b from-slate-800 to-slate-900/90 border-2 border-emerald-500 shadow-2xl shadow-emerald-500/10' : 'bg-slate-800/60 border border-slate-700/80 hover:border-slate-600' }}">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black text-[11px] uppercase tracking-wider px-4 py-1 rounded-full shadow-md">
                                Most Popular Choice
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-white">{{ $plan->name }}</h3>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-slate-700/60 text-slate-300 font-medium">
                                    Up to {{ number_format($plan->max_orders_per_month) }} orders/mo
                                </span>
                            </div>

                            <div class="mb-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs text-slate-400 font-bold">PKR</span>
                                    <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">Rs. {{ number_format($plan->price_monthly, 0) }}</span>
                                    <span class="text-xs text-slate-400 font-medium">/ month</span>
                                </div>
                                @if($plan->price_yearly > 0)
                                    <p class="text-xs text-emerald-400 font-semibold mt-1">or Rs. {{ number_format($plan->price_yearly, 0) }} / year (Save 17%)</p>
                                @endif
                            </div>

                            <!-- Features List -->
                            <div class="border-t border-slate-700/60 pt-6 mb-8">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-3">Included Features:</span>
                                <ul class="space-y-3 text-sm text-slate-300">
                                    @php
                                        $features = is_array($plan->features) ? $plan->features : ['AI WhatsApp Automated Ordering', 'Live Customer Tracking', 'Menu Management', 'Dashboard Analytics'];
                                    @endphp
                                    @foreach($features as $feature)
                                        <li class="flex items-start gap-2.5">
                                            <span class="text-emerald-400 font-bold">✓</span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <!-- Choose Plan Radio & Button -->
                        <div class="pt-4 border-t border-slate-700/60">
                            <button 
                                type="submit" 
                                name="plan_id" 
                                value="{{ $plan->id }}"
                                class="w-full py-3.5 px-4 rounded-xl font-bold text-sm transition duration-200 flex items-center justify-center gap-2 {{ $isPopular ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-500/20' : 'bg-slate-700 hover:bg-slate-600 text-white' }}"
                            >
                                <span>Select {{ $plan->name }} Plan</span>
                                <span>→</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} FoodBot SaaS Platform. All rights reserved.
    </footer>

</body>
</html>
