<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Smart AI WhatsApp Ordering & Automation for Restaurants</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page bg-white text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

    <!-- ── Header Navigation Bar ─────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-100 transition duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3">
            
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-lg shadow-brand-600/30 group-hover:scale-105 transition">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>
                        <path d="M8.5 9.5c.7 2 2 3.3 4 4"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-black tracking-tight text-slate-900 leading-none">Foodio<span class="text-brand-500">.</span></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">WhatsApp AI Platform</span>
                </div>
            </a>

            <!-- Center Navigation Links -->
            <nav class="hidden xl:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <a href="#pricing" class="nav-link">Pricing</a>
                <a href="#faq" class="nav-link">FAQ</a>
            </nav>

            <!-- Right Action Buttons -->
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="{{ route('landing.owner-login-page') }}" class="text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 rounded-xl hover:bg-slate-50 transition">
                    Sign In
                </a>
                <a href="{{ route('admin.force-logout') }}" class="hidden lg:inline-flex text-xs font-semibold text-slate-500 hover:text-slate-900 border border-slate-200 px-3 py-2 rounded-xl hover:bg-slate-50 transition">
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
            <div class="hero-panel relative gradient-wa rounded-[2.5rem] p-8 sm:p-12 lg:p-16 text-white shadow-2xl shadow-brand-900/20 overflow-hidden">
                
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
                            AI WhatsApp assistant that answers menus, takes automated customer orders 24/7, and prints thermal kitchen receipts without commission fees.
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
                                <svg class="h-4 w-4 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-3 8h7l-6 12 1-9H5l8-11Z"/></svg>
                                <span>Instant Setup</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                <span>Live Status Alerts</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
                                <span>0% Commission</span>
                            </div>
                        </div>

                    </div>

                    <!-- Right Showcase: Simulated Smartphone with WhatsApp Live Order -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="phone-mockup w-full max-w-[340px] bg-slate-950 rounded-[3rem] p-3 shadow-2xl shadow-black/50 border-4 border-slate-800">
                            
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
                                                <span>👨‍🍳</span> Order Accepted & Kitchen Ticket Printed!
                                            </div>
                                            <div class="text-[9px] text-slate-400 text-right">2:45 PM</div>
                                        </div>
                                    </div>

                                    <div class="flex justify-start">
                                        <div class="bg-white text-slate-900 p-2.5 rounded-xl max-w-[88%] shadow-sm border border-slate-200">
                                            <div class="text-[10px] font-bold text-slate-700 mb-1">📦 Order Status Update</div>
                                            <div class="h-14 bg-emerald-100 rounded-lg flex items-center justify-center text-xs font-bold text-emerald-800 border border-emerald-200">
                                                🛵 Out for Delivery • ETA ~25 mins
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
            
            <div class="capability-card bg-white rounded-3xl p-6 border border-slate-100 flex items-start gap-4">
                <div class="icon-tile bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.36-6.36-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0-2.12-2.12M7.76 7.76 5.64 5.64"/><circle cx="12" cy="12" r="4"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">AI Ordering Agent</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Answers menu questions, suggests combo deals, and handles order confirmations 24/7 in Roman Urdu & English.</p>
                </div>
            </div>

            <div class="capability-card bg-white rounded-3xl p-6 border border-slate-100 flex items-start gap-4">
                <div class="icon-tile bg-teal-50 text-teal-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">Instant WhatsApp Updates</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Customers automatically receive status notifications, dispatched rider contacts, and estimated delivery times directly on WhatsApp.</p>
                </div>
            </div>

            <div class="capability-card bg-white rounded-3xl p-6 border border-slate-100 flex items-start gap-4">
                <div class="icon-tile bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5h16M6 16V9m4 7V5m4 11v-4m4 4V7"/></svg>
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
                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/><path d="M8.5 9.5c.7 2 2 3.3 4 4"/></svg></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Automated WhatsApp Ordering</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers browse your categories, customize sizes & add-ons, and place orders directly in chat without downloading an app.</p>
                </div>

                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Automated Order Status Alerts</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers receive clean order tracking codes with real-time status notifications sent directly in WhatsApp from preparation to delivery.</p>
                </div>

                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8m-8 4h8"/></svg></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">1-Click Excel & Image Menu OCR</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Upload your existing paper menu photo, PDF, or Excel sheet. Our AI extracts categories, items, and prices into your bot in 10 seconds.</p>
                </div>

                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Kitchen Live Feed & Print Bills</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Real-time sound bell when new orders arrive. Print standardized customer receipts and kitchen KOT tickets with a single tap.</p>
                </div>

                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 18-5v12L3 13v-2Zm0 2 2 7h4l-2.5-6.3M21 12a3 3 0 0 1-3 3"/></svg></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">WhatsApp Deal Broadcasts</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Send special weekend deals and promo vouchers directly to all past customers who have ever ordered from your restaurant.</p>
                </div>

                <div class="feature-card bg-white rounded-3xl p-8 border border-slate-200/70 shadow-sm">
                    <div class="icon-tile mb-6"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5h16M6 16V9m4 7V5m4 11v-4m4 4V7"/></svg></div>
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
                <div class="step-card bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">1</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Register & Pick a Plan</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Sign up with your restaurant details and choose a flexible monthly subscription that matches your order volume.</p>
                </div>

                <div class="step-card bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">2</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Scan WhatsApp QR Code</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Scan the QR code from your phone's WhatsApp Linked Devices. Your bot goes live instantly without Meta API approval delays.</p>
                </div>

                <div class="step-card bg-slate-50 rounded-3xl p-8 border border-slate-200/60 relative">
                    <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white font-extrabold flex items-center justify-center text-base mb-6 shadow-md shadow-brand-600/20">3</div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Receive Orders & Kitchen Tickets</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Customers chat to place orders 24/7. Live orders pop up on your kitchen dashboard with 1-tap thermal bill printing.</p>
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
                <div class="plan-card bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between">
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
                <div class="plan-card bg-white rounded-3xl p-8 border-2 border-brand-500 shadow-xl shadow-brand-500/10 flex flex-col justify-between relative">
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
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> <strong>Automated Order Status Alerts</strong></li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> Excel & Image Menu OCR</li>
                            <li class="flex items-center gap-2.5"><span class="text-brand-600 font-bold">✓</span> WhatsApp Deal Broadcasts</li>
                        </ul>
                    </div>

                    <a href="{{ route('onboarding.signup') }}" class="mt-8 w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm text-center block shadow-md shadow-brand-600/20 transition">
                        Get Started with Pro
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="plan-card bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between">
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
                        <span>How do customers get updates on their orders?</span>
                        <span class="text-brand-600 transition group-open:rotate-180">▼</span>
                    </summary>
                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                        When an order is confirmed, prepared, or dispatched from your kitchen dashboard, the customer automatically receives an automated WhatsApp message with their unique order tracking code and status updates in real-time.
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
                <div class="brand-mark !h-7 !w-7 !rounded-lg">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-3 8h7l-6 12 1-9H5l8-11Z"/></svg>
                </div>
                <span class="text-white font-extrabold text-sm">Foodio</span>
                <span class="text-slate-600">|</span>
                <span>&copy; {{ date('Y') }} Foodio Technologies. All rights reserved.</span>
            </div>

            <div class="flex items-center gap-6 font-semibold">
                <a href="{{ route('landing.owner-login-page') }}" class="hover:text-white transition">Owner Sign In</a>
                <a href="{{ route('onboarding.signup') }}" class="hover:text-white transition">Register</a>
                <a href="{{ route('admin.force-logout') }}" class="hover:text-white transition">Superadmin Portal</a>
            </div>

        </div>
    </footer>

</body>
</html>
