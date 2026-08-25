<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Checkout — {{ $restaurant->name }} | Foodio</title>
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
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Restaurant Platform</span>
                </div>
            </a>
            <div class="text-xs font-semibold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200">
                Restaurant: <strong class="text-slate-900">{{ $restaurant->name }}</strong>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl mx-auto w-full px-4 py-10">

        <!-- Progress Steps -->
        <div class="mb-10 max-w-lg mx-auto">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-5 w-full h-0.5 bg-slate-200"></div>
                <div class="absolute left-0 top-5 w-2/3 h-0.5 bg-brand-600"></div>

                <!-- Step 1 (Done) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">
                        ✓
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Owner Signup</span>
                </div>

                <!-- Step 2 (Done) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">
                        ✓
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Choose Plan</span>
                </div>

                <!-- Step 3 (Active) -->
                <div class="relative flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white font-extrabold flex items-center justify-center shadow-md shadow-brand-600/25 ring-4 ring-white z-10">
                        3
                    </div>
                    <span class="text-[11px] font-bold text-brand-600 whitespace-nowrap">Payment</span>
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

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Payment Method Form (8 cols) -->
            <div class="md:col-span-8 bg-white rounded-3xl p-8 border border-slate-200/80 shadow-xl shadow-slate-200/50">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Choose Payment Method</h2>
                <p class="text-xs text-slate-500 mb-6">Select your preferred payment gateway in Pakistan or international card.</p>

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('onboarding.payment.submit', $restaurant->id) }}" class="space-y-6">
                    @csrf

                    <!-- Payment Method Selector -->
                    <div class="grid grid-cols-2 gap-3" id="payment-methods">
                        
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_gateway" value="easypaisa" class="peer sr-only" checked onchange="switchMethod('easypaisa')">
                            <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-brand-500 peer-checked:bg-brand-50/50 text-center transition">
                                <div class="text-2xl mb-1">🟢</div>
                                <div class="font-bold text-xs text-slate-900">EasyPaisa</div>
                                <div class="text-[10px] text-slate-500">Instant Wallet</div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_gateway" value="jazzcash" class="peer sr-only" onchange="switchMethod('jazzcash')">
                            <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-brand-500 peer-checked:bg-brand-50/50 text-center transition">
                                <div class="text-2xl mb-1">🔴</div>
                                <div class="font-bold text-xs text-slate-900">JazzCash</div>
                                <div class="text-[10px] text-slate-500">Mobile Account</div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_gateway" value="stripe" class="peer sr-only" onchange="switchMethod('stripe')">
                            <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-brand-500 peer-checked:bg-brand-50/50 text-center transition">
                                <div class="text-2xl mb-1">💳</div>
                                <div class="font-bold text-xs text-slate-900">Credit / Debit</div>
                                <div class="text-[10px] text-slate-500">Visa & Mastercard</div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="payment_gateway" value="bank_transfer" class="peer sr-only" onchange="switchMethod('bank_transfer')">
                            <div class="p-4 rounded-2xl border-2 border-slate-200 peer-checked:border-brand-500 peer-checked:bg-brand-50/50 text-center transition">
                                <div class="text-2xl mb-1">🏛️</div>
                                <div class="font-bold text-xs text-slate-900">Bank Transfer</div>
                                <div class="text-[10px] text-slate-500">Online IBAN / Raast</div>
                            </div>
                        </label>

                    </div>

                    <!-- Dynamic Details Box -->
                    <div id="method-instructions" class="bg-slate-50 border border-slate-200 rounded-2xl p-5 text-xs text-slate-700 space-y-2">
                        <div class="font-bold text-slate-900 text-sm mb-1" id="instruction-title">EasyPaisa Account Details</div>
                        <p id="instruction-body">Send <strong>Rs. {{ number_format($plan->price_pkr) }}</strong> to EasyPaisa Account: <strong>0300-1234567</strong> (Foodio Technologies).</p>
                    </div>

                    <!-- Transaction / Ref Input -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2" id="ref-label">Transaction ID (TID) / Reference Number *</label>
                        <input
                            type="text"
                            name="transaction_ref"
                            value="{{ old('transaction_ref') }}"
                            placeholder="e.g. 10293847561 or Bank Slip ID"
                            required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition"
                        >
                        <p class="text-[11px] text-slate-400 mt-1.5">Enter the confirmation code from your SMS or banking receipt for Super Admin verification.</p>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-4 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-base rounded-2xl shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition active:scale-[0.99] flex items-center justify-center gap-2"
                    >
                        <span>Submit Payment for Approval</span>
                        <span>→</span>
                    </button>
                </form>
            </div>

            <!-- Right: Order Summary (4 cols) -->
            <div class="md:col-span-4 space-y-4">
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-md">
                    <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3 mb-4">Subscription Summary</h3>
                    
                    <div class="space-y-3 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Plan:</span>
                            <strong class="text-slate-900">{{ $plan->name }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Billing Cycle:</span>
                            <span class="text-slate-900 font-semibold">1 Month</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Platform Fee:</span>
                            <span class="text-brand-600 font-bold">0% Commission</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-sm font-black text-slate-900">
                            <span>Total Due:</span>
                            <span class="text-brand-600">Rs. {{ number_format($plan->price_pkr) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-brand-50 border border-brand-200/80 rounded-2xl p-4 text-xs text-brand-900 flex items-start gap-2.5">
                    <span class="text-lg">🛡️</span>
                    <div>
                        <strong class="block font-bold">Fast Approval Guarantee</strong>
                        <p class="text-brand-800 text-[11px] mt-0.5">Super Admin reviews and activates accounts within minutes after payment confirmation.</p>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        &copy; {{ date('Y') }} Foodio Technologies. All rights reserved.
    </footer>

    <script>
    const instructions = {
        easypaisa: {
            title: "EasyPaisa Account Details",
            body: "Send <strong>Rs. {{ number_format($plan->price_pkr) }}</strong> to EasyPaisa Account: <strong>0300-1234567</strong> (Foodio Technologies). Enter the 11-digit TID from your SMS below."
        },
        jazzcash: {
            title: "JazzCash Account Details",
            body: "Send <strong>Rs. {{ number_format($plan->price_pkr) }}</strong> to JazzCash Account: <strong>0300-7654321</strong> (Foodio Technologies). Enter the TID from your SMS below."
        },
        stripe: {
            title: "Card Payment Reference",
            body: "Visa / Mastercard processing. Enter your card transaction reference or approval code below."
        },
        bank_transfer: {
            title: "Direct Bank Transfer (IBAN / Raast)",
            body: "Transfer to Bank Alfalah: <strong>PK36ALFH0001001234567890</strong> (Title: Foodio Tech). Enter the IBFT Reference Number below."
        }
    };

    function switchMethod(key) {
        const info = instructions[key];
        if (info) {
            document.getElementById('instruction-title').innerHTML = info.title;
            document.getElementById('instruction-body').innerHTML = info.body;
        }
    }
    </script>
</body>
</html>
