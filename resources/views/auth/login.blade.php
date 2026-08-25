<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Sign In — Foodio Platform</title>
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
                            wa: '#25D366',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Header Navbar -->
    <header class="bg-white border-b border-slate-200/80">
        <div class="max-w-6xl mx-auto px-4 h-18 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/30 group-hover:scale-105 transition">
                    ⚡
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Restaurant Portal</span>
                </div>
            </a>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <a href="/" class="text-slate-500 hover:text-slate-900 transition">← Back to Home</a>
                <a href="{{ route('onboarding.signup') }}" class="text-brand-600 hover:text-brand-700 bg-brand-50 px-3.5 py-1.5 rounded-full border border-brand-200/60 transition">
                    Register New Restaurant
                </a>
            </div>
        </div>
    </header>

    <!-- Main Sign In Container -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-2xl shadow-slate-200/60">
            
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner border border-brand-100">
                    🔐
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Restaurant Owner Sign In</h1>
                <p class="text-xs text-slate-500 mt-1.5">Sign in to access your live orders, menu catalog, and WhatsApp bot.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium flex items-center gap-2">
                    <span>✓</span> {{ session('success') }}
                </div>
            @endif

            @if($errors->hasBag('owner') && $errors->owner->any())
                <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
                    {{ $errors->owner->first() }}
                </div>
            @elseif($errors->any())
                <div class="mb-6 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('landing.owner-login') }}" class="space-y-5">
                @csrf

                <!-- Restaurant Selector / Search -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Select Your Restaurant</label>
                    <select 
                        name="restaurant_id" 
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition"
                    >
                        <option value="" disabled {{ old('restaurant_id') ? '' : 'selected' }}>-- Choose your restaurant --</option>
                        @foreach($restaurants as $r)
                            <option value="{{ $r->id }}" {{ (string)old('restaurant_id') === (string)$r->id ? 'selected' : '' }}>
                                {{ $r->name }} {{ $r->city ? "({$r->city})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Owner Password</label>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Enter your dashboard password" 
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition"
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99] flex items-center justify-center gap-2"
                >
                    <span>Sign In to Dashboard</span>
                    <span>→</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-100 text-center space-y-3 text-xs text-slate-500">
                <p>
                    Don't have an account? 
                    <a href="{{ route('onboarding.signup') }}" class="text-brand-600 font-bold hover:underline">
                        Register Your Restaurant →
                    </a>
                </p>
                <p>
                    Platform Administrator? 
                    <a href="{{ route('admin.login') }}" class="text-slate-600 font-semibold hover:underline">
                        SuperAdmin Login
                    </a>
                </p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

</body>
</html>
