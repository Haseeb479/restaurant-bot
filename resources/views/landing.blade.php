<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — Pure hospitality. Zero commissions.</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        whatsapp: {
                            DEFAULT: '#25D366',
                            dark: '#128C7E',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Warm Minimalist Alabaster Background & Soft Caustics */
        body {
            background-color: #F8F7F4;
            background-image: 
                radial-gradient(at 15% 10%, rgba(16, 185, 129, 0.05) 0px, transparent 40%),
                radial-gradient(at 85% 15%, rgba(209, 250, 229, 0.3) 0px, transparent 45%),
                radial-gradient(at 50% 90%, rgba(240, 253, 244, 0.5) 0px, transparent 60%);
            color: #111827;
        }

        /* Frosted Glass Floating Styles */
        .glass-pill-nav {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
            border-radius: 9999px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.85);
            box-shadow: 
                0 20px 45px -15px rgba(0, 0, 0, 0.06),
                0 1px 3px rgba(0, 0, 0, 0.02);
            border-radius: 28px;
        }

        .glass-metric-chip {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.07), 0 2px 6px rgba(0, 0, 0, 0.02);
            border-radius: 20px;
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(220, 226, 232, 0.8);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-glass:hover {
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .btn-primary-glass {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.25);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-primary-glass:hover {
            background: #047857;
            box-shadow: 0 12px 28px -4px rgba(4, 120, 87, 0.35);
            transform: translateY(-1.5px);
        }

        /* Floating Bobbing Keyframes */
        @keyframes float-subtle-1 {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        @keyframes float-subtle-2 {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }
        .animate-float-1 { animation: float-subtle-1 4.8s ease-in-out infinite; }
        .animate-float-2 { animation: float-subtle-2 5.6s ease-in-out infinite; }
    </style>
</head>
<body class="antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Floating Pill Navigation (Matching Exact Uploaded Mockup) -->
    <header class="sticky top-6 z-50 px-4 sm:px-6 max-w-5xl mx-auto">
        <nav class="glass-pill-nav px-6 py-3.5 flex items-center justify-between transition-all">
            
            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 3.84 2.16 7.18 5.34 8.86L6.5 22l3.41-1.07C10.59 21.08 11.28 21.14 12 21.14c5.52 0 10-4.48 10-9.14S17.52 2 12 2z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">Foodio</span>
            </a>

            <!-- Center Nav Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-700">
                <a href="#modules" class="hover:text-emerald-600 transition flex items-center gap-1">
                    <span>Modules</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
                </a>
                <a href="#pos" class="hover:text-emerald-600 transition">POS</a>
                <a href="#whatsapp" class="hover:text-emerald-600 transition flex items-center gap-1">
                    <span>WhatsApp</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
                </a>
                <a href="#pricing" class="hover:text-emerald-600 transition">Pricing</a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-4">
                <a href="{{ route('landing.owner-login-page') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900 transition">
                    Sign In
                </a>
                <a href="{{ route('onboarding.signup') }}" class="btn-glass px-5 py-2 rounded-full text-xs font-bold text-slate-900 hover:text-emerald-700">
                    Get Started
                </a>
            </div>

        </nav>
    </header>

    <!-- Main Hero Section (Matching Exact Uploaded Mockup) -->
    <section class="pt-16 pb-20 px-4 sm:px-6 relative overflow-hidden">
        <div class="max-w-6xl mx-auto text-center space-y-6">
            
            <!-- Exact Headline From Uploaded Mockup -->
            <h1 class="text-5xl sm:text-7xl font-extrabold text-slate-900 tracking-tight leading-[1.08]">
                Pure hospitality.<br>
                Zero commissions.
            </h1>

            <p class="max-w-xl mx-auto text-base sm:text-lg text-slate-500 font-normal leading-relaxed">
                Connect your restaurant's WhatsApp directly with our cloud POS, automated kitchen ticket printing, and guest table seating.
            </p>

            <!-- Centerpiece: Floating Liquid Glass Tray with Devices & Metric Chips -->
            <div class="pt-8 relative max-w-5xl mx-auto">
                
                <!-- Main Device Glass Platter Container -->
                <div class="relative mx-auto rounded-[32px] p-2 sm:p-4 transition-all">
                    
                    <!-- High-Res Mockup Image Platter (Matching Exact Theme) -->
                    <div class="relative rounded-[28px] overflow-hidden shadow-2xl shadow-slate-900/10 border border-white/80 bg-white/40 backdrop-blur-md">
                        <img 
                            src="/images/foodio_pure_hospitality.jpg" 
                            alt="Foodio Pure Hospitality Zero Commissions UI Showcase" 
                            class="w-full object-cover rounded-[26px]"
                        >
                    </div>

                    <!-- Left Floating Metric Chip 1: 0% Commission -->
                    <div class="hidden lg:flex absolute top-1/4 -left-6 z-20 glass-metric-chip px-5 py-4 flex-col items-center justify-center animate-float-1">
                        <span class="text-3xl font-extrabold text-emerald-600">0%</span>
                        <span class="text-xs font-semibold text-slate-600 mt-0.5">Commission</span>
                    </div>

                    <!-- Left Floating Metric Chip 2: 2.1s Instant KOT Print -->
                    <div class="hidden lg:flex absolute bottom-16 -left-2 z-20 glass-metric-chip px-5 py-3.5 flex-col items-center justify-center animate-float-2">
                        <span class="text-2xl font-extrabold text-emerald-600">2.1s</span>
                        <span class="text-xs font-semibold text-slate-600 mt-0.5">Instant KOT Print</span>
                    </div>

                    <!-- Right Floating Metric Chip 3: 99.8% Bot Precision -->
                    <div class="hidden lg:flex absolute bottom-20 -right-4 z-20 glass-metric-chip px-5 py-4 flex-col items-center justify-center animate-float-1">
                        <span class="text-3xl font-extrabold text-emerald-600">99.8%</span>
                        <span class="text-xs font-semibold text-slate-600 mt-0.5">Bot Precision</span>
                    </div>

                </div>

            </div>

            <!-- Primary CTAs Below Showcase -->
            <div class="pt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('onboarding.signup') }}" class="btn-primary-glass px-8 py-3.5 rounded-full text-sm font-bold flex items-center gap-2">
                    <span>Start 14-Day Free Access</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
                <a href="#pos" class="btn-glass px-8 py-3.5 rounded-full text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="play" class="w-4 h-4 text-emerald-600"></i>
                    <span>Explore Modules</span>
                </a>
            </div>

        </div>
    </section>

    <!-- Interactive Cloud POS & WhatsApp Synergy (Matching Light Theme) -->
    <section id="pos" class="py-16 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto space-y-10">
            
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600">Operational Precision</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">The Unified Restaurant Architecture</h2>
                <p class="text-slate-500 text-sm">Experience how orders flow from WhatsApp into the Cloud POS and kitchen display without paper friction.</p>
            </div>

            <!-- Dual Interactive Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Foodio Cloud POS Tablet Simulator (7 cols) -->
                <div class="lg:col-span-7 glass-card p-6 sm:p-8 space-y-5">
                    
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200/80">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h3 class="text-sm font-bold text-slate-900">Foodio Cloud POS — Counter Register</h3>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                            Terminal Synced
                        </span>
                    </div>

                    <!-- Table Selector & Catalog -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Select Table / Station</span>
                            <span class="text-xs font-semibold text-emerald-600">Table 02 Selected</span>
                        </div>

                        <!-- Table Grid (Matching Mockup) -->
                        <div class="grid grid-cols-5 gap-2.5 text-center text-xs font-bold">
                            <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 cursor-pointer hover:border-emerald-400">1</div>
                            <div class="p-3 rounded-xl bg-emerald-600 text-white shadow-sm cursor-pointer">2 ✓</div>
                            <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 cursor-pointer hover:border-emerald-400">3</div>
                            <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 cursor-pointer hover:border-emerald-400">4</div>
                            <div class="p-3 rounded-xl bg-white border border-slate-200 text-slate-700 cursor-pointer hover:border-emerald-400">5</div>
                        </div>

                        <!-- Active Order Breakdown -->
                        <div class="p-4 rounded-2xl bg-white/80 border border-slate-200/80 space-y-2.5 text-xs">
                            <div class="flex justify-between items-center text-slate-800">
                                <span class="font-semibold">1x Woodfired Margherita Pizza</span>
                                <span class="font-bold font-mono">$18.00</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-800">
                                <span class="font-semibold">1x Craft Hibiscus Tea</span>
                                <span class="font-bold font-mono">$5.00</span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex justify-between items-center font-bold text-slate-900 text-sm">
                                <span>Total (Table 02)</span>
                                <span class="text-emerald-600 font-mono text-base">$23.00</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button onclick="alert('KOT printed to kitchen thermal printer!')" class="btn-glass flex-1 py-3 rounded-xl text-xs font-bold text-slate-800 flex items-center justify-center gap-1.5">
                                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-600"></i>
                                <span>Print KOT Ticket</span>
                            </button>
                            <button onclick="alert('Order settled and receipt sent via WhatsApp!')" class="btn-primary-glass flex-1 py-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Settle & Close</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Right: Foodio WhatsApp Bot Smartphone (5 cols) -->
                <div id="whatsapp" class="lg:col-span-5 glass-card p-6 sm:p-8 space-y-4">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200/80">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-whatsapp flex items-center justify-center text-white shadow-sm">
                                <i data-lucide="message-circle" class="w-4 h-4 fill-current"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 flex items-center gap-1">
                                    Foodio Assistant
                                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                </h3>
                                <p class="text-[10px] text-slate-500">Live WhatsApp 2.0 Webhook</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            Active
                        </span>
                    </div>

                    <!-- Chat Stream -->
                    <div id="chat-messages" class="space-y-3 py-2 min-h-[260px] max-h-[300px] overflow-y-auto text-xs pr-1">
                        
                        <div class="flex items-start gap-2">
                            <div class="bg-white p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-200/80 shadow-sm space-y-1">
                                <p class="font-medium text-slate-800">👋 Welcome to <strong>Bistro Woodfire</strong>! How may I delight your palate today?</p>
                                <div class="pt-1 flex flex-wrap gap-1">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] text-slate-700 font-semibold">1. Menu 📖</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-[10px] text-slate-700 font-semibold">2. Book Table 🍽️</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-end justify-end gap-2">
                            <div class="bg-emerald-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-sm">
                                <p>Can I order 1 Margherita Pizza to Table 2?</p>
                                <span class="text-[9px] text-emerald-200 block text-right mt-1">11:42 ✓✓</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <div class="bg-white p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-200/80 shadow-sm space-y-1">
                                <p class="font-medium text-slate-800">✅ Placed! Sent to <strong>Table 2 POS register</strong> and kitchen thermal printer.</p>
                                <span class="text-[10px] text-slate-400 block font-mono">Kitchen ticket #1094 printed.</span>
                            </div>
                        </div>

                    </div>

                    <!-- Input Bar -->
                    <div class="pt-2 border-t border-slate-200/80 flex items-center gap-2">
                        <input type="text" id="sim-input" placeholder="Type: 'Add Hibiscus Tea' or 'Check bill'..." class="flex-1 bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-sm">
                        <button onclick="sendSimText()" class="px-4 py-2 rounded-xl bg-whatsapp text-white hover:bg-emerald-600 font-bold text-xs flex items-center gap-1 shadow-sm transition">
                            <span>Send</span>
                            <i data-lucide="send" class="w-3 h-3"></i>
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- Interactive Commission Savings & ROI Calculator -->
    <section id="modules" class="py-16 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto glass-card p-8 sm:p-12 space-y-8">
            
            <div class="text-center space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600">Zero Commission Freedom</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">How Much Are Marketplace Commissions Costing You?</h2>
                <p class="text-xs sm:text-sm text-slate-500">Calculate how much money you save by shifting orders to direct WhatsApp channels.</p>
            </div>

            <div class="space-y-6 pt-2">
                <div>
                    <div class="flex justify-between items-center text-sm font-bold mb-2">
                        <span class="text-slate-700">Monthly Restaurant Sales:</span>
                        <span id="sales-value" class="text-emerald-600 font-extrabold text-lg">Rs. 1,000,000 / mo</span>
                    </div>
                    <input type="range" id="sales-slider" min="200000" max="5000000" step="50000" value="1000000" oninput="updateCalculator()" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-center">
                        <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Third-Party App Commission (30%)</span>
                        <div id="commission-lost" class="text-3xl font-extrabold text-rose-600 mt-1">-Rs. 300,000 / mo</div>
                        <p class="text-xs text-slate-500 mt-1">Lost to delivery apps: <span id="annual-lost" class="text-rose-700 font-bold">Rs. 3,600,000 / yr</span></p>
                    </div>

                    <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-center">
                        <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Saved with Foodio Flat SaaS</span>
                        <div id="savings-amount" class="text-3xl font-extrabold text-emerald-600 mt-1">+Rs. 292,000 / mo</div>
                        <p class="text-xs text-slate-500 mt-1">Retained by your restaurant: <span id="annual-savings" class="text-emerald-700 font-bold">Rs. 3,504,000 / yr</span></p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Minimalist Transparent Pricing Cards -->
    <section id="pricing" class="py-16 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto space-y-12">
            
            <div class="text-center max-w-xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600">Pure Hospitality</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Simple Plans. Flat Monthly SaaS.</h2>
                <p class="text-slate-500 text-sm">No transaction cuts. Keep 100% of your customer revenue.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                
                <!-- Starter Plan -->
                <div class="glass-card p-8 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Starter</span>
                        <h3 class="text-2xl font-bold text-slate-900">Cloud POS Core</h3>
                        <p class="text-xs text-slate-500">For cafes and food trucks needing fast billing and receipt printing.</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-slate-900">Free</span>
                            <span class="text-xs text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">14-Day Free Trial</span>
                        </div>
                        <ul class="text-xs text-slate-600 space-y-2.5 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> 1 Active Cloud POS Terminal</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Digital Dine-in QR Menu</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Thermal Receipt Printer Sync</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="btn-glass w-full py-3 rounded-full text-xs font-bold text-slate-900 text-center block">
                        Start Free Trial
                    </a>
                </div>

                <!-- Growth Plan (Featured) -->
                <div class="glass-card p-8 border-2 border-emerald-500/80 shadow-xl flex flex-col justify-between space-y-6 relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-emerald-600 text-white font-extrabold text-[10px] uppercase tracking-wider shadow-sm">
                        Most Popular Choice
                    </div>
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Full System</span>
                        <h3 class="text-2xl font-bold text-slate-900">POS + WhatsApp Pro</h3>
                        <p class="text-xs text-slate-500">The complete operating system powering high-volume restaurants.</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xs font-bold text-slate-500">PKR</span>
                            <span class="text-4xl font-extrabold text-slate-900">Rs. 8k</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-600 space-y-2.5 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Unlimited POS Terminals</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Official WhatsApp Ordering Bot</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Table Seating & Kitchen KDS</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Automated Customer Re-Order Alerts</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="btn-primary-glass w-full py-3.5 rounded-full text-xs font-bold text-center block">
                        Launch Free 14-Day Trial
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="glass-card p-8 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Franchises</span>
                        <h3 class="text-2xl font-bold text-slate-900">Multi-Outlet OS</h3>
                        <p class="text-xs text-slate-500">For multi-branch chains, centralized cloud kitchens, and franchises.</p>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xs font-bold text-slate-500">PKR</span>
                            <span class="text-4xl font-extrabold text-slate-900">Rs. 16k</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-600 space-y-2.5 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Multi-branch central inventory sync</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Custom WhatsApp AI Persona</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i> Dedicated Server & SLA Guarantee</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="btn-glass w-full py-3 rounded-full text-xs font-bold text-slate-900 text-center block">
                        Contact Enterprise
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- Clean Minimalist Footer -->
    <footer class="border-t border-slate-200/80 py-12 px-4 sm:px-8 bg-white/50 backdrop-blur-md">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-emerald-600 flex items-center justify-center text-white">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 3.84 2.16 7.18 5.34 8.86L6.5 22l3.41-1.07C10.59 21.08 11.28 21.14 12 21.14c5.52 0 10-4.48 10-9.14S17.52 2 12 2z"/></svg>
                </div>
                <span class="font-bold text-slate-900 text-sm">Foodio</span>
                <span>— Pure hospitality. Zero commissions.</span>
            </div>
            <div class="flex items-center gap-6 font-semibold text-slate-600">
                <a href="{{ route('landing') }}" class="hover:text-emerald-600">Home</a>
                <a href="{{ route('landing.owner-login-page') }}" class="hover:text-emerald-600">Terminal Sign In</a>
                <a href="{{ route('onboarding.signup') }}" class="hover:text-emerald-600">Register Restaurant</a>
            </div>
            <p>© 2026 Foodio Technologies Inc.</p>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        lucide.createIcons();

        function updateCalculator() {
            const sales = parseInt(document.getElementById('sales-slider').value);
            document.getElementById('sales-value').textContent = 'Rs. ' + sales.toLocaleString() + ' / mo';
            const commission = Math.round(sales * 0.30);
            const savings = commission - 8000;
            document.getElementById('commission-lost').textContent = '-Rs. ' + commission.toLocaleString() + ' / mo';
            document.getElementById('annual-lost').textContent = 'Rs. ' + (commission * 12).toLocaleString() + ' / yr';
            document.getElementById('savings-amount').textContent = '+Rs. ' + savings.toLocaleString() + ' / mo';
            document.getElementById('annual-savings').textContent = 'Rs. ' + (savings * 12).toLocaleString() + ' / yr';
        }

        function sendSimText() {
            const input = document.getElementById('sim-input');
            const text = input.value.trim();
            if (!text) return;
            const chat = document.getElementById('chat-messages');

            const userBubble = document.createElement('div');
            userBubble.className = 'flex items-end justify-end gap-2';
            userBubble.innerHTML = `
                <div class="bg-emerald-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow-sm">
                    <p>${escapeHtml(text)}</p>
                    <span class="text-[9px] text-emerald-200 block text-right mt-1">Just now ✓✓</span>
                </div>
            `;
            chat.appendChild(userBubble);
            input.value = '';
            chat.scrollTop = chat.scrollHeight;

            setTimeout(() => {
                const botBubble = document.createElement('div');
                botBubble.className = 'flex items-start gap-2';
                botBubble.innerHTML = `
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-200/80 shadow-sm space-y-1">
                        <p class="font-medium text-slate-800">✅ Placed! Order: "${escapeHtml(text)}" routed directly to Table 2 POS ticket.</p>
                        <span class="text-[10px] text-slate-400 block font-mono">Kitchen ticket printed.</span>
                    </div>
                `;
                chat.appendChild(botBubble);
                chat.scrollTop = chat.scrollHeight;
            }, 500);
        }

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        document.getElementById('sim-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendSimText();
        });
    </script>
</body>
</html>
