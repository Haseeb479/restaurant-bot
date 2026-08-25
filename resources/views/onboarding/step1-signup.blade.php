<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Started — Register Your Restaurant | Bot Platform</title>
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
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition">
                    ⚡
                </div>
                <span class="font-extrabold text-lg tracking-tight text-white">FoodBot <span class="text-emerald-400">SaaS</span></span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('landing') }}" class="text-sm font-semibold text-slate-400 hover:text-white transition">Already registered? Log in</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="max-w-xl mx-auto mb-10">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-800 -z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-0 h-1 bg-emerald-500 -z-0 transition-all duration-500"></div>

                <!-- Step 1 (Active) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900 shadow-lg shadow-emerald-500/30">
                        1
                    </div>
                    <span class="text-xs font-bold text-emerald-400 mt-2">Owner Signup</span>
                </div>

                <!-- Step 2 (Upcoming) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-slate-800 text-slate-400 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        2
                    </div>
                    <span class="text-xs font-semibold text-slate-500 mt-2">Choose Plan</span>
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

        <!-- Form Card -->
        <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700/70 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/50">
            <div class="text-center max-w-xl mx-auto mb-8">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Create Your Restaurant Bot</h1>
                <p class="text-slate-400 text-sm mt-2">Automate WhatsApp orders, live rider GPS tracking, and menu management with AI.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-medium flex items-center gap-3">
                    <span>✓</span> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-medium">
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
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                        <span>👤</span> 1. Owner & Contact Information
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Owner Full Name *</label>
                            <input 
                                type="text" 
                                name="owner_name" 
                                value="{{ old('owner_name') }}" 
                                placeholder="e.g. Haseeb Ahmed" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Owner Email Address *</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                placeholder="e.g. haseeb@example.com" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Owner Personal Mobile *</label>
                            <input 
                                type="text" 
                                name="owner_phone" 
                                value="{{ old('owner_phone') }}" 
                                placeholder="03XXXXXXXXX" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Dashboard Login Password *</label>
                            <input 
                                type="password" 
                                name="owner_password" 
                                placeholder="Minimum 6 characters" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                    </div>
                </div>

                <!-- Section 2: Restaurant Details -->
                <div class="pt-2 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                        <span>🏪</span> 2. Restaurant Details & WhatsApp Number
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Restaurant / Brand Name *</label>
                            <input 
                                type="text" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="e.g. Fezio Cafe & Grill" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">WhatsApp Ordering Number (Bot SIM) *</label>
                            <input 
                                type="text" 
                                name="whatsapp_number" 
                                value="{{ old('whatsapp_number') }}" 
                                placeholder="03XXXXXXXXX or +923XXXXXXXXX" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                            <p class="text-[11px] text-slate-400 mt-1">This is the number customers will send WhatsApp messages to.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">City</label>
                            <input 
                                type="text" 
                                name="city" 
                                value="{{ old('city') }}" 
                                placeholder="e.g. Lodhran, Multan, Lahore" 
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Full Branch Address</label>
                            <input 
                                type="text" 
                                name="address" 
                                value="{{ old('address') }}" 
                                placeholder="e.g. Main Bazar, Near City Center" 
                                class="w-full px-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-white text-sm placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button 
                        type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-base rounded-2xl shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/30 transition duration-200 active:scale-[0.99] flex items-center justify-center gap-2"
                    >
                        <span>Continue to Choose Plan</span>
                        <span>→</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} FoodBot SaaS Platform. All rights reserved. Built for Restaurants & Cloud Kitchens.
    </footer>

</body>
</html>
