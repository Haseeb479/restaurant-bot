<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Smart AI WhatsApp Ordering & Live GPS Tracking for Restaurants</title>
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
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
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
        .gradient-wa { background: linear-gradient(135deg, #059669 0%, #047857 50%, #064e3b 100%); }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

    <!-- ── Header Navigation Bar ─────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-100 transition duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-lg shadow-brand-600/30 group-hover:scale-105 transition">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.08L2 22l5.09-1.34C8.54 21.49 10.22 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm4.64 14.34c-.23.64-1.34 1.25-1.85 1.33-.48.08-1.08.11-3.23-.78-2.61-1.08-4.27-3.76-4.4-3.93-.13-.17-1.06-1.41-1.06-2.69s.67-1.9 1-2.17c.28-.27.61-.34.81-.34.2 0 .41 0 .59.01.19.01.44-.07.69.52.26.61.88 2.14.95 2.3.07.16.12.35.02.56-.1.21-.15.34-.3.51-.15.17-.32.38-.45.51-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.3.14.47.12.65-.08.17-.2.74-.86.94-1.15.2-.29.41-.24.68-.14.28.1.1.75 2.13.88 2.27.13.14.22.21.25.26.04.05.04.83-.19 1.47z"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">WhatsApp AI Platform</span>
                </div>
            </a>

            <!-- Center Navigation Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#features" class="hover:text-brand-600 transition">Features</a>
                <a href="#how-it-works" class="hover:text-brand-600 transition">How It Works</a>
                <a href="#pricing" class="hover:text-brand-600 transition">Pricing</a>
                <a href="#faq" class="hover:text-brand-600 transition">FAQ</a>
                <a href="{{ route('order.track.live') }}" class="hover:text-brand-600 text-slate-500 transition flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    Live Track
                </a>
            </nav>

            <!-- Right Action Buttons -->
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="{{ route('landing.owner-login-page') }}" class="text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 rounded-xl hover:bg-slate-50 transition">
                    Sign In
                </a>
                <a href="{{ route('admin.login') }}" class="hidden sm:inline-flex text-xs font-semibold text-slate-500 hover:text-slate-900 border border-slate-200 px-3 py-2 rounded-xl hover:bg-slate-50 transition">
                    Superadmin
                </a>
                <a href="{{ route('onboarding.signup') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-md shadow-brand-600/25 hover:shadow-lg hover:shadow-brand-600/35 transition active:scale-95">
                    <span>Get Started</span>
                    <span>→</span>
                </a>
            </div>

        </div>
    </header>

    <!-- ── Hero Section (Matching SawaBot / Modern SaaS Style) ─ -->
    <section class="relative pt-6 pb-20 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Big Hero Banner Card (Green Rounded Container) -->
            <div class="relative gradient-wa rounded-[2.5rem] p-8 sm:p-12 lg:p-16 text-white shadow-2xl shadow-brand-900/20 overflow-hidden">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10">
                    
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Mini Badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold tracking-wide text-brand-100">
                            <span class="w-2 h-2 rounded-full bg-brand-300"></span>
                            #1 WhatsApp Restaurant Bot in Pakistan
                        </div>

                        <!-- Big Punchy Title -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.15]">
                            Stop Losing Customers On <span class="text-brand-300 underline decoration-brand-400/40 underline-offset-8">WhatsApp</span>.
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-base sm:text-lg text-emerald-50/90 font-normal leading-relaxed max-w-xl">
                            AI WhatsApp assistant that answers menus, takes automated customer orders 24/7, and provides <strong>Foodpanda-style Live Rider GPS tracking</strong> without commission fees.
                        </p>

                        <!-- Hero CTA Buttons -->
                        <div class="flex flex-wrap items-center gap-4 pt-2">
                            <a href="{{ route('onboarding.signup') }}" class="px-7 py-4 rounded-full bg-white text-slate-950 hover:bg-brand-50 font-extrabold text-base shadow-xl shadow-black/10 hover:scale-[1.02] transition active:scale-95 flex items-center gap-2">
                                <span>Get Started Free</span>
                                <span>→</span>
                            </a>
                            <a href="#how-it-works" class="px-6 py-4 rounded-full bg-white/10 hover:bg-white/20 text-white font-bold text-base border border-white/20 backdrop-blur-md transition flex items-center gap-2">
                                <span>See How It Works</span>
                            </a>
                        </div>

                        <!-- Hero Feature Pills -->
                        <div class="pt-6 border-t border-white/15 grid grid-cols-3 gap-3 sm:gap-4 text-xs font-bold text-emerald-100">
                            <div class="flex items-center gap-2">
                                <span class="text-base">⚡</span>
                                <span>Instant Setup</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-base">🛵</span>
                                <span>Live GPS Maps</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-base">💸</span>
                                <span>0% Commission</span>
                            </div>
                        </div>

                    </div>

                    <!-- Right Showcase: Simulated Smartphone with WhatsApp Live Order -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="w-full max-w-[340px] bg-slate-950 rounded-[3rem] p-3 shadow-2xl shadow-black/50 border-4 border-slate-800">
                            
                            <div class="bg-[#efeae2] rounded-[2.3rem] overflow-hidden flex flex-col h-[520px] text-slate-900">
                                
                                <div class="bg-[#075E54] text-white px-4 py-3.5 flex items-center gap-3 shadow-sm">
                                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm">
                                        🍔
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold truncate">Fezio Cafe & Grill</div>
                                        <div class="text-[10px] text-emerald-200 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Verified WhatsApp Bot
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1 p-3.5 space-y-3 overflow-y-auto text-xs">
                                    <div class="flex justify-end">
                                        <div class="bg-[#dcf8c6] text-slate-900 p-2.5 rounded-2xl rounded-tr-xs max-w-[82%] shadow-sm">
                                            Assalam o Alaikum! I want 1x Zinger Burger and 1x Loaded Fries for delivery at Main Bazar Lodhran.
                                            <div class="text-[9px] text-slate-400 text-right mt-1">2:45 PM ✓✓</div>
                                        </div>
                                    </div>

                                    <div class="flex justify-start">
                                        <div class="bg-white text-slate-900 p-3 rounded-2xl rounded-tl-xs max-w-[88%] shadow-sm border border-slate-200/60 space-y-1.5">
                                            <div class="font-bold text-emerald-800 text-[11px]">🎉 Order Confirmed! #FZ1048</div>
                                            <div class="text-slate-600 text-[11px]">
                                                • 1x Zinger Burger (Rs. 450)<br>
                                                • 1x Loaded Fries (Rs. 350)<br>
                                                • Delivery: Rs. 50
                                            </div>
                                            <div class="font-black text-slate-900 border-t border-slate-100 pt-1">
                                                Total: Rs. 850 (COD)
                                            </div>
                                            <div class="bg-emerald-50 text-emerald-700 p-1.5 rounded-lg text-[10px] font-semibold flex items-center gap-1">
                                                <span>🛵</span> Rider dispatched with Live GPS!
                                            </div>
                                            <div class="text-[9px] text-slate-400 text-right">2:45 PM</div>
                                        </div>
                                    </div>

                                    <div class="flex justify-start">
                                        <div class="bg-white text-slate-900 p-2.5 rounded-xl max-w-[88%] shadow-sm border border-slate-200">
                                            <div class="text-[10px] font-bold text-slate-700 mb-1">📍 Real-time Rider Tracking</div>
                                            <div class="h-14 bg-emerald-100 rounded-lg flex items-center justify-center text-xs font-bold text-emerald-800 border border-emerald-200">
                                                🛵 5 mins away (ETA 2:50 PM)
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-[#f0f2f5] p-2 flex items-center gap-2 border-t border-slate-200">
                                    <div class="flex-1 bg-white rounded-full px-3 py-1.5 text-[11px] text-slate-400">
                                        Type a message...
                                    </div>
                                    <div class="w-7 h-7 rounded-full bg-[#00a884] text-white flex items-center justify-center text-xs">
                                        ➤
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- ── Capability Agent Cards ─────────────────────────────── -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 mb-16 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 flex items-start gap-4 hover:-translate-y-1 transition duration-200">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold shrink-0">
                    🤖
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">AI Ordering Agent</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Answers menu questions, suggests combo deals, and handles order confirmations 24/7 in Roman Urdu & English.</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 flex items-start gap-4 hover:-translate-y-1 transition duration-200">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl font-bold shrink-0">
                    🛵
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">Live Rider GPS Map</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Riders stream live GPS directly from WhatsApp links. Customers watch their scooter move on the map with real-time ETA.</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 flex items-start gap-4 hover:-translate-y-1 transition duration-200">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold shrink-0">
                    📊
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">Kitchen POS & Thermal Bills</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Live order sound alert, instant 80mm thermal receipt printing, customer database, and automated Google Sheets sync.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ── Features Section ───────────────────────────────────── -->
    <section id="features" class="py-20 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full">Everything You Need</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">Built Specifically for Modern Pakistani Restaurants</h2>
                <p class="text-slate-600 text-sm sm:text-base mt-3">Eliminate third-party aggregators charging 25-30% commissions. Own your customers directly on WhatsApp.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">💬</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Automated WhatsApp Ordering</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers browse your categories, customize sizes & add-ons, and place orders directly in chat without downloading an app.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">📍</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Foodpanda-Style Live Map Tracking</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers receive clean tracking codes (like <code>#FZ1048</code>) with an interactive OpenStreetMap link showing their rider approaching.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">📄</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">1-Click Excel & Image Menu OCR</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Upload your existing paper menu photo, PDF, or Excel sheet. Our AI extracts categories, items, and prices into your bot in 10 seconds.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">🖨️</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Kitchen Live Feed & Print Bills</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Real-time sound bell when new orders arrive. Print standardized customer receipts and kitchen KOT tickets with a single tap.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">📢</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">WhatsApp Deal Broadcasts</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Send special weekend deals and promo vouchers directly to all past customers who have ever ordered from your restaurant.</p>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm hover:shadow-xl hover:border-brand-500/30 transition duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mb-6">📊</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Google Sheets Auto-Sync</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Every order automatically streams into your private Google Spreadsheet for real-time accounting and ledger management.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ── How It Works ───────────────────────────────────────── -->
    <section id="how-it-works" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full">Simple 3-Step Setup</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">Live in Under 5 Minutes</h2>
                <p class="text-slate-600 text-sm mt-2">No complicated hardware or technical knowledge required.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">1</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Register & Pick a Plan</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Sign up with your restaurant details and choose a flexible monthly subscription that matches your order volume.</p>
                </div>

                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">2</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Scan WhatsApp QR Code</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Scan the QR code from your phone's WhatsApp Linked Devices. Your bot goes live instantly without Meta API approval delays.</p>
                </div>

                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">3</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Receive Orders & Live Track</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers chat to place orders. Dispatched riders broadcast live GPS location to customers on an interactive map.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ── Pricing Section ────────────────────────────────────── -->
    <section id="pricing" class="py-20 bg-slate-50 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full">Transparent Pricing</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">Simple Plans for Every Stage</h2>
                <p class="text-slate-600 text-sm mt-2">Zero commission per order. Upgrade or cancel anytime.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-6xl mx-auto">
                
                <!-- Starter -->
                <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-xl transition duration-300">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Starter</h3>
                        <p class="text-xs text-slate-500 mt-1">Perfect for cafes & single cloud kitchens.</p>
                        
                        <div class="my-6">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xs text-slate-400 font-bold">PKR</span>
                                <span class="text-4xl font-black text-slate-900">Rs. 3,000</span>
                                <span class="text-xs text-slate-500 font-medium">/ month</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm text-slate-600 border-t border-slate-100 pt-6">
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Up to 500 orders/month</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> 40 Menu Items</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> AI WhatsApp Bot</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Kitchen Thermal Print</li>
                        </ul>
                    </div>

                    <a href="{{ route('onboarding.signup') }}" class="mt-8 w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm text-center block transition">
                        Get Started with Starter
                    </a>
                </div>

                <!-- Pro -->
                <div class="bg-white rounded-3xl p-8 border-2 border-brand-500 shadow-xl shadow-brand-500/10 flex flex-col justify-between relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-[11px] font-black uppercase tracking-wider px-4 py-1 rounded-full shadow-md">
                        Most Popular
                    </div>

                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Pro</h3>
                        <p class="text-xs text-slate-500 mt-1">For busy restaurants & growing delivery hubs.</p>
                        
                        <div class="my-6">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xs text-slate-400 font-bold">PKR</span>
                                <span class="text-4xl font-black text-slate-900">Rs. 7,000</span>
                                <span class="text-xs text-slate-500 font-medium">/ month</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm text-slate-600 border-t border-slate-100 pt-6">
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Up to 2,000 orders/month</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> 150 Menu Items</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> <strong>Live Rider GPS Map Tracking</strong></li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Excel & Image Menu OCR</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> WhatsApp Deal Broadcasts</li>
                        </ul>
                    </div>

                    <a href="{{ route('onboarding.signup') }}" class="mt-8 w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm text-center block shadow-md shadow-brand-600/20 transition">
                        Get Started with Pro
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-xl transition duration-300">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Enterprise</h3>
                        <p class="text-xs text-slate-500 mt-1">Multi-branch franchises & high-volume brands.</p>
                        
                        <div class="my-6">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xs text-slate-400 font-bold">PKR</span>
                                <span class="text-4xl font-black text-slate-900">Rs. 15,000</span>
                                <span class="text-xs text-slate-500 font-medium">/ month</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm text-slate-600 border-t border-slate-100 pt-6">
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Unlimited orders/month</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> 500+ Menu Items</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Dedicated WhatsApp Server</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Custom Webhooks & POS API</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> 24/7 Priority Support</li>
                        </ul>
                    </div>

                    <a href="{{ route('onboarding.signup') }}" class="mt-8 w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm text-center block transition">
                        Get Started with Enterprise
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- ── FAQ Section ────────────────────────────────────────── -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full">Got Questions?</span>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-3">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-4">
                <details class="group bg-slate-50 border border-slate-200/70 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex items-center justify-between cursor-pointer font-bold text-base text-slate-900">
                        <span>Do I need a Meta / Facebook Business API verification?</span>
                        <span class="text-brand-600 transition group-open:rotate-180">▼</span>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        No! Foodio connects directly via QR code web pairing, so you can connect your existing WhatsApp Business or normal SIM number in 30 seconds without waiting weeks for Meta verification.
                    </p>
                </details>

                <details class="group bg-slate-50 border border-slate-200/70 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex items-center justify-between cursor-pointer font-bold text-base text-slate-900">
                        <span>How does the live rider GPS tracking work?</span>
                        <span class="text-brand-600 transition group-open:rotate-180">▼</span>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        When you dispatch an order, your rider receives a WhatsApp link. When the rider taps the link, their phone automatically broadcasts live GPS coordinates to the customer's web tracking map with real-time ETA.
                    </p>
                </details>

                <details class="group bg-slate-50 border border-slate-200/70 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex items-center justify-between cursor-pointer font-bold text-base text-slate-900">
                        <span>Can I print receipts in my kitchen?</span>
                        <span class="text-brand-600 transition group-open:rotate-180">▼</span>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        Yes! Foodio comes with a 1-tap print bill feature formatted for standard 80mm and 58mm ESC/POS thermal receipt printers.
                    </p>
                </details>

                <details class="group bg-slate-50 border border-slate-200/70 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex items-center justify-between cursor-pointer font-bold text-base text-slate-900">
                        <span>What payment methods are supported for subscriptions?</span>
                        <span class="text-brand-600 transition group-open:rotate-180">▼</span>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        We accept Visa/Mastercard (Stripe), JazzCash, EasyPaisa, and Direct Bank Transfer (IBAN). Once paid, Super Admin approves your restaurant and activates your portal.
                    </p>
                </details>
            </div>

        </div>
    </section>

    <!-- ── Bottom CTA Banner ──────────────────────────────────── -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-20">
        <div class="gradient-wa rounded-[2.5rem] p-10 sm:p-14 text-center text-white relative overflow-hidden shadow-2xl">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight max-w-2xl mx-auto">
                Ready to Turn WhatsApp Into Your #1 Sales Channel?
            </h2>
            <p class="text-emerald-100 text-sm sm:text-base mt-3 max-w-xl mx-auto">
                Join restaurants across Pakistan who are scaling their online orders without paying commissions.
            </p>
            <div class="mt-8 flex justify-center">
                <a href="{{ route('onboarding.signup') }}" class="px-8 py-4 rounded-full bg-white text-slate-950 hover:bg-brand-50 font-extrabold text-base shadow-xl hover:scale-105 transition active:scale-95">
                    Start Your Restaurant Registration →
                </a>
            </div>
        </div>
    </section>

    <!-- ── Footer ─────────────────────────────────────────────── -->
    <footer class="bg-slate-950 text-slate-400 py-12 border-t border-slate-900 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-xl bg-brand-600 flex items-center justify-center text-white font-black text-xs">
                    ⚡
                </div>
                <span class="text-white font-extrabold text-sm">Foodio</span>
                <span class="text-slate-600">|</span>
                <span>&copy; {{ date('Y') }} Foodio Technologies. All rights reserved.</span>
            </div>

            <div class="flex items-center gap-6 font-semibold">
                <a href="{{ route('landing.owner-login-page') }}" class="hover:text-white transition">Owner Sign In</a>
                <a href="{{ route('onboarding.signup') }}" class="hover:text-white transition">Register</a>
                <a href="{{ route('admin.login') }}" class="hover:text-white transition">Superadmin Portal</a>
                <a href="{{ route('order.track.live') }}" class="hover:text-white transition">Track Order</a>
            </div>

        </div>
    </footer>

</body>
</html>
