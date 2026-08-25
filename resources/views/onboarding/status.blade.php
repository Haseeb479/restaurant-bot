<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status — {{ $restaurant->name }} | Foodio</title>
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
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Application Status</span>
                </div>
            </a>
            <div class="text-xs font-semibold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200">
                Restaurant: <strong class="text-slate-900">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-12 flex flex-col justify-center">

        @if($restaurant->status === 'active' || $restaurant->registration_status === 'approved')
            <!-- Approved Card -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-8 sm:p-10 shadow-2xl shadow-slate-200/60 text-center">
                <div class="w-20 h-20 bg-brand-50 text-brand-600 border border-brand-200 rounded-3xl flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
                    🎉
                </div>
                <div class="inline-block px-3.5 py-1 bg-brand-100 text-brand-800 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    ● Account Approved &amp; Active
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-3">Welcome to Foodio, {{ $restaurant->name }}!</h1>
                <p class="text-slate-500 text-sm max-w-md mx-auto mb-8 leading-relaxed">
                    Your restaurant registration has been approved by Super Admin. Your dedicated management dashboard is live and ready.
                </p>

                <a 
                    href="{{ route('dashboard.orders', $restaurant->id) }}" 
                    class="inline-flex items-center justify-center gap-2 w-full py-4 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-base rounded-2xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99]"
                >
                    <span>Launch Restaurant Dashboard</span>
                    <span>→</span>
                </a>
            </div>

        @elseif($restaurant->status === 'rejected' || $restaurant->registration_status === 'rejected')
            <!-- Rejected Card -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-8 sm:p-10 shadow-2xl shadow-slate-200/60 text-center">
                <div class="w-20 h-20 bg-rose-50 text-rose-600 border border-rose-200 rounded-3xl flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
                    ❌
                </div>
                <div class="inline-block px-3.5 py-1 bg-rose-100 text-rose-800 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    Registration Rejected
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-3">Application Not Approved</h1>
                <p class="text-slate-500 text-sm max-w-md mx-auto mb-6">
                    Super Admin could not verify your restaurant credentials or payment reference.
                </p>

                @if($restaurant->rejection_reason)
                    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-800 text-left mb-6">
                        <strong class="block font-bold mb-1">Reason for Rejection:</strong>
                        <p>{{ $restaurant->rejection_reason }}</p>
                    </div>
                @endif

                <p class="text-xs text-slate-400 mb-6">If you believe this was an error, please submit a new application with correct details.</p>

                <a 
                    href="{{ route('onboarding.signup') }}" 
                    class="inline-flex items-center justify-center gap-2 w-full py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition"
                >
                    <span>Submit New Application →</span>
                </a>
            </div>

        @else
            <!-- Pending Review Card -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-8 sm:p-10 shadow-2xl shadow-slate-200/60 text-center">
                <div class="w-20 h-20 bg-amber-50 text-amber-600 border border-amber-200 rounded-3xl flex items-center justify-center mx-auto mb-6 text-4xl animate-pulse">
                    ⏳
                </div>
                <div class="inline-block px-3.5 py-1 bg-amber-100 text-amber-800 font-bold text-xs uppercase tracking-wider rounded-full mb-3">
                    Pending Super Admin Review
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-3">Application Under Review</h1>
                <p class="text-slate-500 text-sm max-w-md mx-auto mb-8 leading-relaxed">
                    We've received your signup and subscription payment! Super Admin has been notified and is verifying your branch documents.
                </p>

                <!-- Application Summary Box -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 text-left text-xs space-y-2.5 mb-8">
                    <div class="flex justify-between text-slate-600">
                        <span>Restaurant Name:</span>
                        <strong class="text-slate-900">{{ $restaurant->name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Owner Name:</span>
                        <strong class="text-slate-900">{{ $restaurant->owner_name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>WhatsApp Number:</span>
                        <code class="text-brand-700 font-bold font-mono">{{ $restaurant->whatsapp_number }}</code>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Chosen Plan:</span>
                        <span class="font-semibold text-brand-700">{{ $restaurant->subscriptionPlan?->name ?? ucfirst($restaurant->plan) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Payment Status:</span>
                        <span class="text-brand-600 font-bold">✓ Completed (Paid)</span>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 text-xs text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>This page auto-refreshes every 10 seconds.</span>
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
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

</body>
</html>
