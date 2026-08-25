<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | {{ $restaurant->name }}</title>
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
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-emerald-500/20">
                    ⚡
                </div>
                <span class="font-extrabold text-lg tracking-tight text-white">FoodBot <span class="text-emerald-400">SaaS</span></span>
            </a>
            <div class="text-xs text-slate-400">
                Restaurant: <strong class="text-white">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-12 flex flex-col justify-center">

        @if($restaurant->status === 'active' || $restaurant->registration_status === 'approved')
            <!-- Approved Card -->
            <div class="bg-slate-800/70 border-2 border-emerald-500/80 rounded-3xl p-8 sm:p-10 shadow-2xl text-center">
                <div class="w-20 h-20 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-lg shadow-emerald-500/20">
                    🎉
                </div>
                <div class="inline-block px-3 py-1 bg-emerald-500/20 text-emerald-400 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    Account Approved & Active
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight mb-3">Welcome to FoodBot, {{ $restaurant->name }}!</h1>
                <p class="text-slate-300 text-sm max-w-md mx-auto mb-8">
                    Your restaurant registration has been approved by Super Admin. Your dedicated dashboard is ready.
                </p>

                <a 
                    href="{{ route('dashboard.orders', $restaurant->id) }}" 
                    class="inline-flex items-center justify-center gap-2 w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-base rounded-2xl shadow-xl shadow-emerald-500/20 transition duration-200"
                >
                    <span>Launch Restaurant Dashboard</span>
                    <span>→</span>
                </a>
            </div>

        @elseif($restaurant->status === 'rejected' || $restaurant->registration_status === 'rejected')
            <!-- Rejected Card -->
            <div class="bg-slate-800/70 border border-rose-500/50 rounded-3xl p-8 sm:p-10 shadow-2xl text-center">
                <div class="w-20 h-20 bg-rose-500/10 text-rose-400 border border-rose-500/30 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                    ❌
                </div>
                <div class="inline-block px-3 py-1 bg-rose-500/20 text-rose-400 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    Registration Rejected
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight mb-3">Application Not Approved</h1>
                <p class="text-slate-300 text-sm max-w-md mx-auto mb-6">
                    Super Admin could not verify your restaurant credentials.
                </p>

                @if($restaurant->rejection_reason)
                    <div class="bg-rose-950/40 border border-rose-800/60 rounded-2xl p-4 text-xs text-rose-300 text-left mb-6">
                        <strong class="block font-bold mb-1">Reason for Rejection:</strong>
                        <p>{{ $restaurant->rejection_reason }}</p>
                    </div>
                @endif

                <p class="text-xs text-slate-500 mb-6">If you believe this was an error, please contact our support team with your payment reference.</p>

                <a 
                    href="{{ route('onboarding.signup') }}" 
                    class="inline-flex items-center justify-center gap-2 w-full py-3 bg-slate-700 hover:bg-slate-600 text-white font-bold text-sm rounded-xl transition"
                >
                    <span>Submit New Application</span>
                </a>
            </div>

        @else
            <!-- Pending Review Card -->
            <div class="bg-slate-800/70 border border-slate-700/80 rounded-3xl p-8 sm:p-10 shadow-2xl text-center">
                <div class="w-20 h-20 bg-amber-500/10 text-amber-400 border border-amber-500/30 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl animate-pulse">
                    ⏳
                </div>
                <div class="inline-block px-3 py-1 bg-amber-500/20 text-amber-400 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    Pending Super Admin Review
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-3">Application Under Review</h1>
                <p class="text-slate-300 text-sm max-w-md mx-auto mb-8">
                    We've received your signup and subscription payment! Super Admin has been notified and is reviewing your branch documents.
                </p>

                <!-- Application Summary Box -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 text-left text-xs space-y-2.5 mb-8">
                    <div class="flex justify-between text-slate-400">
                        <span>Restaurant Name:</span>
                        <strong class="text-white">{{ $restaurant->name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Owner Name:</span>
                        <strong class="text-white">{{ $restaurant->owner_name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>WhatsApp Number:</span>
                        <code class="text-emerald-400 font-mono">{{ $restaurant->whatsapp_number }}</code>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Chosen Plan:</span>
                        <span class="font-semibold text-emerald-400">{{ $restaurant->subscriptionPlan?->name ?? ucfirst($restaurant->plan) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Payment Status:</span>
                        <span class="text-emerald-400 font-bold">✓ Completed (Paid)</span>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-3 text-xs text-slate-400">
                    <span>🔄 This page auto-refreshes every 10 seconds.</span>
                </div>
            </div>

            <!-- Auto refresh script -->
            <script>
                setTimeout(function() {
                    window.location.reload();
                }, 10000);
            </script>
        @endif

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} FoodBot SaaS Platform. All rights reserved.
    </footer>

</body>
</html>
