<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment | {{ $restaurant->name }}</title>
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
                Restaurant: <strong class="text-white">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="max-w-xl mx-auto mb-10">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-800 -z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-2/3 h-1 bg-emerald-500 -z-0 transition-all duration-500"></div>

                <!-- Step 1 (Done) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        ✓
                    </div>
                    <span class="text-xs font-semibold text-slate-400 mt-2">Owner Signup</span>
                </div>

                <!-- Step 2 (Done) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900">
                        ✓
                    </div>
                    <span class="text-xs font-semibold text-slate-400 mt-2">Choose Plan</span>
                </div>

                <!-- Step 3 (Active) -->
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 font-bold flex items-center justify-center ring-4 ring-slate-900 shadow-lg shadow-emerald-500/30">
                        3
                    </div>
                    <span class="text-xs font-bold text-emerald-400 mt-2">Payment</span>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">

            <!-- Left: Payment Form & Gateway Selection -->
            <div class="md:col-span-2 bg-slate-800/60 backdrop-blur-xl border border-slate-700/70 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/50">
                <h2 class="text-2xl font-bold text-white mb-2">Secure Checkout</h2>
                <p class="text-sm text-slate-400 mb-6">Select your payment method below to activate your subscription.</p>

                <form method="POST" action="{{ route('onboarding.payment.submit', $restaurant->id) }}" id="paymentForm" class="space-y-6">
                    @csrf

                    <!-- Payment Methods Tabs -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3">Select Payment Method</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

                            <!-- Stripe / Card -->
                            <label class="payment-method-card flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-emerald-500 bg-slate-900/80 cursor-pointer text-center transition hover:border-emerald-400" onclick="selectMethod('stripe')">
                                <input type="radio" name="payment_method" value="stripe" checked class="hidden">
                                <span class="text-2xl mb-1">💳</span>
                                <span class="text-xs font-bold text-white">Card / Stripe</span>
                            </label>

                            <!-- JazzCash -->
                            <label class="payment-method-card flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-700 bg-slate-900/40 cursor-pointer text-center transition hover:border-slate-500" onclick="selectMethod('jazzcash')">
                                <input type="radio" name="payment_method" value="jazzcash" class="hidden">
                                <span class="text-2xl mb-1">📱</span>
                                <span class="text-xs font-bold text-white">JazzCash</span>
                            </label>

                            <!-- EasyPaisa -->
                            <label class="payment-method-card flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-700 bg-slate-900/40 cursor-pointer text-center transition hover:border-slate-500" onclick="selectMethod('easypaisa')">
                                <input type="radio" name="payment_method" value="easypaisa" class="hidden">
                                <span class="text-2xl mb-1">🟢</span>
                                <span class="text-xs font-bold text-white">EasyPaisa</span>
                            </label>

                            <!-- Bank Transfer -->
                            <label class="payment-method-card flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-700 bg-slate-900/40 cursor-pointer text-center transition hover:border-slate-500" onclick="selectMethod('bank_transfer')">
                                <input type="radio" name="payment_method" value="bank_transfer" class="hidden">
                                <span class="text-2xl mb-1">🏛️</span>
                                <span class="text-xs font-bold text-white">Bank Transfer</span>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Method Details -->
                    <div id="method-details-stripe" class="method-section bg-slate-900/90 border border-slate-700/80 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between text-xs text-slate-400 border-b border-slate-800 pb-3">
                            <span>Credit / Debit Card (Stripe Gateway)</span>
                            <div class="flex gap-2 text-lg">
                                <span>💳</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cardholder Name</label>
                            <input type="text" value="{{ $restaurant->owner_name }}" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Card Number</label>
                            <input type="text" value="•••• •••• •••• 4242" readonly class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Expires</label>
                                <input type="text" value="12/28" readonly class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">CVC</label>
                                <input type="text" value="•••" readonly class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div id="method-details-jazzcash" class="method-section hidden bg-slate-900/90 border border-slate-700/80 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between text-xs text-slate-400 border-b border-slate-800 pb-3">
                            <span>JazzCash Mobile Account / Till Payment</span>
                            <span class="text-amber-400 font-bold">JazzCash Till: 0300-1234567</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your JazzCash Mobile Number</label>
                            <input type="text" placeholder="03XXXXXXXXX" value="{{ $restaurant->owner_phone }}" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Transaction ID / TID (optional)</label>
                            <input type="text" name="payment_reference" placeholder="e.g. 1029384756" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div id="method-details-easypaisa" class="method-section hidden bg-slate-900/90 border border-slate-700/80 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between text-xs text-slate-400 border-b border-slate-800 pb-3">
                            <span>EasyPaisa Account Payment</span>
                            <span class="text-emerald-400 font-bold">EasyPaisa: 0312-3456789</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your EasyPaisa Mobile Number</label>
                            <input type="text" placeholder="03XXXXXXXXX" value="{{ $restaurant->owner_phone }}" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">EasyPaisa Transaction ID (optional)</label>
                            <input type="text" name="payment_reference" placeholder="e.g. EP-998877" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div id="method-details-bank_transfer" class="method-section hidden bg-slate-900/90 border border-slate-700/80 rounded-2xl p-5 space-y-4">
                        <div class="text-xs text-slate-400 border-b border-slate-800 pb-3">
                            <strong class="text-white block mb-1">Meezan Bank Ltd</strong>
                            <span>Account: 01020304050607 | Title: FoodBot Technologies | IBAN: PK64MEZN0001020304050607</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Bank Deposit / Transfer Reference #</label>
                            <input type="text" name="payment_reference" placeholder="e.g. FT260825990" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-base rounded-2xl shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/30 transition duration-200 active:scale-[0.99] flex items-center justify-center gap-2"
                    >
                        <span>Confirm & Pay Rs. {{ number_format($amount, 0) }}</span>
                        <span>✓</span>
                    </button>
                </form>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="bg-slate-800/40 border border-slate-700/70 rounded-3xl p-6">
                <h3 class="text-base font-bold text-white mb-4">Subscription Summary</h3>

                <div class="space-y-3 text-sm border-b border-slate-700 pb-4 mb-4">
                    <div class="flex justify-between text-slate-400">
                        <span>Restaurant:</span>
                        <strong class="text-white">{{ $restaurant->name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Plan Tier:</span>
                        <span class="font-semibold text-emerald-400">{{ $plan->name }} ({{ ucfirst($interval) }})</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Orders Limit:</span>
                        <span class="text-slate-300">{{ number_format($plan->max_orders_per_month) }} orders/mo</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Currency:</span>
                        <span class="text-slate-300">PKR</span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-lg font-black text-white pt-1 mb-6">
                    <span>Total Due:</span>
                    <span class="text-emerald-400">Rs. {{ number_format($amount, 0) }}</span>
                </div>

                <div class="bg-slate-900/60 rounded-2xl p-4 text-xs text-slate-400 space-y-2 border border-slate-800">
                    <div class="flex items-start gap-2">
                        <span class="text-emerald-400 font-bold">🔒</span>
                        <span>256-Bit Encrypted & PCI-Compliant checkout.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-emerald-400 font-bold">⚡</span>
                        <span>Super Admin verifies your branch details within minutes.</span>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} FoodBot SaaS Platform. All rights reserved.
    </footer>

    <script>
    function selectMethod(method) {
        // Update tabs styling
        document.querySelectorAll('.payment-method-card').forEach(c => {
            c.classList.remove('border-emerald-500', 'bg-slate-900/80');
            c.classList.add('border-slate-700', 'bg-slate-900/40');
        });
        const activeRadio = document.querySelector(`input[value="${method}"]`);
        if (activeRadio) {
            activeRadio.checked = true;
            activeRadio.closest('.payment-method-card').classList.add('border-emerald-500', 'bg-slate-900/80');
            activeRadio.closest('.payment-method-card').classList.remove('border-slate-700', 'bg-slate-900/40');
        }

        // Show details section
        document.querySelectorAll('.method-section').forEach(s => s.classList.add('hidden'));
        const target = document.getElementById(`method-details-${method}`);
        if (target) target.classList.remove('hidden');
    }
    </script>

</body>
</html>
