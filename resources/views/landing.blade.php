<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — The Intelligent Restaurant OS (Cloud POS + WhatsApp Automation)</title>
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
        .glass-dark {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .glow-box-emerald:hover {
            box-shadow: 0 0 35px -5px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-950 border-b border-emerald-500/20 py-2 px-4 text-center text-xs sm:text-sm text-slate-300">
        <span class="inline-flex items-center gap-2 font-medium">
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold text-[11px] tracking-wide uppercase">New Release</span>
            <span>Official WhatsApp Cloud API 2.0 Integration & Split-Bill Cloud POS is now live!</span>
            <a href="{{ route('onboarding.signup') }}" class="underline text-emerald-400 hover:text-emerald-300 font-bold ml-1 inline-flex items-center gap-1">
                Claim 14-Day Free Access <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </span>
    </div>

    <!-- Sticky Floating Navbar -->
    <header class="sticky top-0 z-50 px-4 sm:px-8 py-4">
        <nav class="max-w-7xl mx-auto glass-dark rounded-2xl px-5 py-3.5 flex items-center justify-between border border-slate-800 shadow-2xl">
            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-600 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/25 group-hover:scale-105 transition-transform">
                    <i data-lucide="utensils" class="w-5 h-5 text-slate-950 stroke-[2.5]"></i>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-1.5">
                        Foodio
                        <span class="text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">OS</span>
                    </span>
                    <p class="text-[10px] text-slate-400 font-medium -mt-1">POS & WhatsApp Automation</p>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <div class="hidden lg:flex items-center gap-8 text-sm font-semibold text-slate-300">
                <a href="#features" class="hover:text-emerald-400 transition-colors">Core Modules</a>
                <a href="#pos-demo" class="hover:text-emerald-400 transition-colors">Cloud POS</a>
                <a href="#whatsapp-sim" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                    <i data-lucide="message-square" class="w-4 h-4 text-emerald-400"></i> WhatsApp Bot
                </a>
                <a href="#calculator" class="hover:text-emerald-400 transition-colors">ROI Calculator</a>
                <a href="#pricing" class="hover:text-emerald-400 transition-colors">Pricing</a>
                <a href="/foodio-redesign/showcase.html" target="_blank" class="px-2.5 py-1 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold hover:bg-indigo-500/30 transition-all flex items-center gap-1">
                    <span>3-in-1 Showcase</span>
                    <i data-lucide="external-link" class="w-3 h-3"></i>
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="{{ route('landing.owner-login-page') }}" class="hidden sm:inline-flex px-4 py-2 text-sm font-semibold text-slate-200 hover:text-white transition-colors">
                    Sign In
                </a>
                <a href="{{ route('onboarding.signup') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <span>Start Free Trial</span>
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 px-4 sm:px-6 overflow-hidden">
        <!-- Ambient Radial Glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[500px] bg-emerald-500/10 rounded-full blur-[140px] pointer-events-none"></div>

        <div class="max-w-6xl mx-auto text-center relative z-10 space-y-6">
            
            <!-- Top Pill -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-emerald-500/30 text-emerald-400 text-xs font-semibold shadow-inner">
                <i data-lucide="zap" class="w-3.5 h-3.5 fill-current"></i>
                <span>Stop paying 25–30% delivery app commissions</span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold text-white tracking-tight leading-[1.1]">
                The Intelligent Operating System for Restaurants: <br>
                <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
                    Cloud POS + WhatsApp Automation
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="max-w-3xl mx-auto text-base sm:text-lg text-slate-400 font-normal leading-relaxed">
                Empower your restaurant with direct WhatsApp ordering, real-time table reservations, cloud billing, and automated kitchen printing—all synchronized into a single, unified POS dashboard.
            </p>

            <!-- Dual Primary CTAs -->
            <div class="pt-2 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('onboarding.signup') }}" class="px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-base shadow-xl shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all flex items-center gap-2.5">
                    <i data-lucide="rocket" class="w-5 h-5 text-slate-950"></i>
                    <span>Launch Your Restaurant Free</span>
                </a>
                <a href="#whatsapp-sim" class="px-8 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-base border border-slate-700/80 shadow-lg hover:border-slate-600 transition-all flex items-center gap-2.5">
                    <i data-lucide="play-circle" class="w-5 h-5 text-emerald-400"></i>
                    <span>Try Live WhatsApp Demo</span>
                </a>
            </div>

            <!-- Metric Badges Strip -->
            <div class="pt-8 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                <div class="glass-card rounded-2xl p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">3x Faster</div>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Table Turnaround</p>
                </div>
                <div class="glass-card rounded-2xl p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-400">0% Comm.</div>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Keep 100% Customer Revenue</p>
                </div>
                <div class="glass-card rounded-2xl p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">99.8%</div>
                    <p class="text-xs text-slate-400 mt-1 font-medium">WhatsApp Bot Order Accuracy</p>
                </div>
                <div class="glass-card rounded-2xl p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-extrabold text-teal-400">2,500+</div>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Outlets & Cloud Kitchens</p>
                </div>
            </div>

        </div>
    </section>

    <!-- Interactive Dual Showcase (Cloud POS Tablet + WhatsApp Bot Live Phone) -->
    <section id="pos-demo" class="py-16 px-4 sm:px-6 relative">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Live Hardware & Software Synergy</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">See the Foodio Ecosystem in Action</h2>
                <p class="text-slate-400 text-sm sm:text-base">Watch an order travel from a customer's WhatsApp conversation straight into the POS terminal and kitchen display in under 2 seconds.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Tablet Cloud POS UI (7 cols) -->
                <div class="lg:col-span-7 glass-dark rounded-3xl p-5 border border-slate-800 shadow-2xl relative overflow-hidden">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-mono text-slate-400 ml-2 font-bold flex items-center gap-1.5">
                                <i data-lucide="tablet" class="w-3.5 h-3.5 text-emerald-400"></i> Foodio POS Terminal — Station #01
                            </span>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Online & Synced
                        </span>
                    </div>

                    <!-- POS Active Register Dashboard Mock -->
                    <div class="pt-4 grid grid-cols-1 sm:grid-cols-12 gap-4">
                        
                        <!-- Category & Items Grid (7 cols) -->
                        <div class="sm:col-span-7 space-y-3">
                            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-semibold">
                                <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-slate-950 font-bold whitespace-nowrap">Popular</button>
                                <button class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Burgers</button>
                                <button class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Pizzas</button>
                                <button class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Drinks</button>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 cursor-pointer transition-all">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Truffle Smash Burger</span>
                                        <span class="text-xs font-bold text-emerald-400">$14.50</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">In Stock (38 left)</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 cursor-pointer transition-all">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Woodfired Margherita</span>
                                        <span class="text-xs font-bold text-emerald-400">$18.00</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">In Stock (22 left)</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 cursor-pointer transition-all">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Artisan Truffle Fries</span>
                                        <span class="text-xs font-bold text-emerald-400">$8.50</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Chef's Special</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 cursor-pointer transition-all">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Craft Hibiscus Tea</span>
                                        <span class="text-xs font-bold text-emerald-400">$5.00</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Fresh Brewed</span>
                                </div>
                            </div>

                            <!-- Live Kitchen Stream Indicator -->
                            <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="chef-hat" class="w-4 h-4 text-amber-400"></i>
                                    <span class="text-slate-300 font-medium">Kitchen Queue: <strong>4 Tickets Preparing</strong></span>
                                </div>
                                <span class="text-[11px] text-amber-400 font-semibold">Avg. Prep: 11 mins</span>
                            </div>
                        </div>

                        <!-- Current Order Ticket Panel (5 cols) -->
                        <div class="sm:col-span-5 bg-slate-900 rounded-2xl p-3.5 border border-slate-800 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                    <div>
                                        <h4 class="text-xs font-extrabold text-white">Order #FD-1094</h4>
                                        <span class="text-[10px] text-emerald-400 font-semibold">Source: WhatsApp Bot</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">Table 07</span>
                                </div>

                                <div class="pt-2 space-y-2 text-xs">
                                    <div class="flex justify-between text-slate-300">
                                        <span>1x Truffle Smash Burger</span>
                                        <span class="font-bold text-white">$14.50</span>
                                    </div>
                                    <div class="flex justify-between text-slate-300">
                                        <span>1x Artisan Truffle Fries</span>
                                        <span class="font-bold text-white">$8.50</span>
                                    </div>
                                    <div class="flex justify-between text-slate-300">
                                        <span>1x Craft Hibiscus Tea</span>
                                        <span class="font-bold text-white">$5.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-800 pt-2 space-y-2">
                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Subtotal</span>
                                    <span>$28.00</span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Tax (8%)</span>
                                    <span>$2.24</span>
                                </div>
                                <div class="flex justify-between text-sm font-extrabold text-white">
                                    <span>Total Due</span>
                                    <span class="text-emerald-400">$30.24</span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <button class="py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white flex items-center justify-center gap-1">
                                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-400"></i> Print KOT
                                    </button>
                                    <button class="py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black flex items-center justify-center gap-1">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Settle Bill
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- Right: Interactive WhatsApp Smartphone Simulator (5 cols) -->
                <div id="whatsapp-sim" class="lg:col-span-5 glass-dark rounded-3xl p-5 border border-slate-800 shadow-2xl relative">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-whatsapp flex items-center justify-center text-white">
                                <i data-lucide="message-circle" class="w-5 h-5 fill-current"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-white flex items-center gap-1">
                                    Foodio SmartBot
                                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                                </h3>
                                <p class="text-[10px] text-slate-400">Automated Restaurant Assistant</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">Live Demo</span>
                    </div>

                    <!-- Chat History -->
                    <div id="chat-messages" class="py-4 space-y-3 min-h-[300px] max-h-[340px] overflow-y-auto text-xs pr-1">
                        
                        <!-- Bot message -->
                        <div class="flex items-start gap-2">
                            <div class="bg-slate-800 text-slate-200 p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1">
                                <p class="font-medium">👋 Welcome to <strong>Bistro Gourmet</strong>! I am your AI food assistant powered by Foodio. How can I delight you today?</p>
                                <div class="pt-1 flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-700 text-[10px] text-emerald-300 font-semibold">1. Browse Menu 📖</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-700 text-[10px] text-teal-300 font-semibold">2. Reserve Table 🍽️</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-700 text-[10px] text-cyan-300 font-semibold">3. Repeat Last Order ⚡</span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer message -->
                        <div class="flex items-end justify-end gap-2">
                            <div class="bg-emerald-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow">
                                <p>Can I order 1 Truffle Smash Burger and 1 Truffle Fries to Table #7?</p>
                                <span class="text-[9px] text-emerald-200 block text-right mt-1">15:42 ✓✓</span>
                            </div>
                        </div>

                        <!-- Bot response -->
                        <div class="flex items-start gap-2">
                            <div class="bg-slate-800 text-slate-200 p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1.5">
                                <p class="font-medium">✅ Order received! Placed directly on <strong>Table #7 POS</strong>.</p>
                                <div class="bg-slate-900 p-2 rounded-xl text-[11px] space-y-1 border border-slate-800 font-mono">
                                    <div class="text-slate-300">1x Truffle Smash Burger: $14.50</div>
                                    <div class="text-slate-300">1x Artisan Truffle Fries: $8.50</div>
                                    <div class="text-emerald-400 font-bold border-t border-slate-800 pt-1">Total: $23.00 + tax</div>
                                </div>
                                <p class="text-[11px] text-slate-400">Estimated kitchen prep time: <strong>12 minutes</strong>. Would you like to add craft tea?</p>
                            </div>
                        </div>

                    </div>

                    <!-- Interactive Sim Action Buttons -->
                    <div class="pt-3 border-t border-slate-800 flex items-center gap-2">
                        <input type="text" id="demo-input" placeholder="Type: 'Book a table' or 'Add Drink'..." class="flex-1 bg-slate-900 border border-slate-700 text-xs rounded-xl px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        <button onclick="sendSimMessage()" class="px-3.5 py-2 rounded-xl bg-whatsapp text-white hover:bg-emerald-600 font-bold text-xs flex items-center gap-1 shadow">
                            <span>Send</span>
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-500 text-center mt-2">Try clicking buttons or typing above to see instantaneous automated responses.</p>
                </div>

            </div>

        </div>
    </section>

    <!-- Core Modules Grid -->
    <section id="features" class="py-16 px-4 sm:px-6 bg-slate-900/40 border-y border-slate-900">
        <div class="max-w-7xl mx-auto space-y-12">
            
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Complete Automation Suite</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Everything Your Restaurant Needs to Scale</h2>
                <p class="text-slate-400 text-sm">Replace 5 fractured tools with one seamless, high-speed ecosystem designed for hospitality.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Feature 1 -->
                <div class="glass-card rounded-2xl p-6 border border-slate-800 hover:border-emerald-500/40 transition-all space-y-4 glow-box-emerald">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                        <i data-lucide="tablet-smartphone" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Lightning Fast Cloud POS</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Works online and offline. Handles table layouts, split checks, multi-currency, and Bluetooth thermal kitchen receipt printers with zero lag.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Offline sync protection</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Split bill & tip management</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Multi-terminal staff permissions</li>
                    </ul>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card rounded-2xl p-6 border border-slate-800 hover:border-emerald-500/40 transition-all space-y-4 glow-box-emerald">
                    <div class="w-12 h-12 rounded-xl bg-whatsapp/20 text-emerald-400 flex items-center justify-center font-bold">
                        <i data-lucide="bot" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Autonomous WhatsApp Bot</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Turns WhatsApp into a high-converting digital ordering desk. Sends rich digital catalogs, takes address details, and collects card or cash payments.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Meta Official Cloud API certified</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Instant reservation confirmations</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Re-engagement & loyalty marketing</li>
                    </ul>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card rounded-2xl p-6 border border-slate-800 hover:border-emerald-500/40 transition-all space-y-4 glow-box-emerald">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                        <i data-lucide="monitor" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Smart Kitchen Display (KDS)</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Eliminate lost paper tickets. Orders from POS terminals, dine-in QR codes, and WhatsApp populate directly on kitchen prep monitors with timers.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Station routing (Grill, Bar, Pastry)</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> Audible kitchen alerts & timers</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i> 1-tap "Ready" WhatsApp ping</li>
                    </ul>
                </div>

            </div>

        </div>
    </section>

    <!-- Interactive Commission Savings & ROI Calculator -->
    <section id="calculator" class="py-16 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto glass-dark rounded-3xl p-6 sm:p-10 border border-slate-800 space-y-8">
            
            <div class="text-center space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Commission Free Independence</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">How Much Money Are Aggregators Taking From You?</h2>
                <p class="text-xs sm:text-sm text-slate-400">See your exact annual savings by shifting orders to direct Foodio WhatsApp channels.</p>
            </div>

            <div class="space-y-6 pt-4">
                <div>
                    <div class="flex justify-between items-center text-sm font-semibold mb-2">
                        <span class="text-slate-300">Monthly Restaurant Sales:</span>
                        <span id="sales-value" class="text-emerald-400 font-extrabold text-lg">$25,000 / mo</span>
                    </div>
                    <input type="range" id="sales-slider" min="5000" max="100000" step="1000" value="25000" oninput="updateCalculator()" class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                    <div class="bg-rose-950/30 border border-rose-900/50 rounded-2xl p-5 text-center">
                        <span class="text-xs font-semibold text-rose-300">Third-Party App Commission (30%)</span>
                        <div id="commission-lost" class="text-2xl sm:text-3xl font-extrabold text-rose-400 mt-2">-$7,500 / mo</div>
                        <p class="text-[11px] text-slate-400 mt-1">Lost to delivery marketplaces annually: <span id="annual-lost" class="text-rose-300 font-bold">$90,000</span></p>
                    </div>

                    <div class="bg-emerald-950/30 border border-emerald-900/50 rounded-2xl p-5 text-center">
                        <span class="text-xs font-semibold text-emerald-300">Saved with Foodio Flat SaaS</span>
                        <div id="savings-amount" class="text-2xl sm:text-3xl font-extrabold text-emerald-400 mt-2">+$7,401 / mo</div>
                        <p class="text-[11px] text-slate-400 mt-1">Net profit retained by your restaurant: <span id="annual-savings" class="text-emerald-300 font-bold">$88,812</span></p>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <a href="{{ route('onboarding.signup') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-500 text-slate-950 font-bold text-sm hover:bg-emerald-400 transition-all shadow-lg shadow-emerald-500/20">
                        <span>Keep 100% of Your Revenue Today</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-16 px-4 sm:px-6 bg-slate-900/20 border-t border-slate-900">
        <div class="max-w-6xl mx-auto space-y-12">
            
            <div class="text-center max-w-xl mx-auto space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Transparent Pricing</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Simple Plans. Flat Monthly SaaS.</h2>
                <p class="text-slate-400 text-sm">No transaction cuts. No hidden per-order commissions.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                
                <!-- Starter Plan -->
                <div class="glass-card rounded-3xl p-6 sm:p-8 border border-slate-800 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Starter Outlets</span>
                        <h3 class="text-2xl font-bold text-white">Cloud POS Core</h3>
                        <p class="text-xs text-slate-400">For cafes and food trucks needing fast billing and receipt printing.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-white">$49</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> 1 Active Cloud POS Terminal</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Digital Dine-in QR Menu</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Thermal Receipt Printer Sync</li>
                            <li class="flex items-center gap-2 text-slate-500"><i data-lucide="x" class="w-4 h-4"></i> Automated WhatsApp Bot</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs text-center transition-all">
                        Start Free 14-Day Trial
                    </a>
                </div>

                <!-- Growth Plan (Featured) -->
                <div class="glass-dark rounded-3xl p-6 sm:p-8 border-2 border-emerald-500 shadow-2xl shadow-emerald-500/10 flex flex-col justify-between space-y-6 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-emerald-500 text-slate-950 font-extrabold text-[10px] uppercase tracking-wider">
                        Most Popular Choice
                    </div>
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Full Automation</span>
                        <h3 class="text-2xl font-bold text-white">POS + WhatsApp Pro</h3>
                        <p class="text-xs text-slate-400">The complete system powering high-volume restaurants and bistros.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-white">$99</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Unlimited POS Terminals</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Official WhatsApp Ordering Bot</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Interactive Table Floorplan & KDS</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Re-Order Marketing Broadcasts</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Priority 24/7 WhatsApp Support</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 text-slate-950 font-extrabold text-xs text-center transition-all shadow-lg shadow-emerald-500/20">
                        Launch Free Trial Now
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="glass-card rounded-3xl p-6 sm:p-8 border border-slate-800 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Franchise & Chains</span>
                        <h3 class="text-2xl font-bold text-white">Multi-Outlet Enterprise</h3>
                        <p class="text-xs text-slate-400">For multi-branch chains, centralized cloud kitchens, and franchises.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-extrabold text-white">$249</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Multi-branch central inventory sync</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Custom WhatsApp AI Prompts</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Dedicated Server & SLA Guarantee</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Custom ERP & Accounting Webhooks</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs text-center transition-all">
                        Contact Enterprise Team
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- Global Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-12 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-slate-950 font-black">
                        <i data-lucide="utensils" class="w-4 h-4 text-slate-950 stroke-[2.5]"></i>
                    </div>
                    <span class="text-lg font-bold text-white">Foodio OS</span>
                </div>
                <p class="text-xs text-slate-400">The premier cloud restaurant automation system, unifying POS and direct WhatsApp commerce.</p>
                <p class="text-[11px] text-slate-600">© 2026 Foodio Technologies Inc. All rights reserved.</p>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3">Platform Navigation</h4>
                <ul class="text-xs text-slate-400 space-y-2">
                    <li><a href="{{ route('landing') }}" class="hover:text-emerald-400">Home Landing</a></li>
                    <li><a href="{{ route('landing.owner-login-page') }}" class="hover:text-emerald-400">Restaurant Portal Login</a></li>
                    <li><a href="{{ route('onboarding.signup') }}" class="hover:text-emerald-400">Start Free Trial</a></li>
                    <li><a href="/foodio-redesign/showcase.html" target="_blank" class="hover:text-emerald-400 font-bold text-emerald-400">3-Screen Live Showcase</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3">POS & Integrations</h4>
                <ul class="text-xs text-slate-400 space-y-2">
                    <li><span class="hover:text-slate-300">WhatsApp Business Cloud API</span></li>
                    <li><span class="hover:text-slate-300">Kitchen Display Systems (KDS)</span></li>
                    <li><span class="hover:text-slate-300">Thermal Receipt Printers</span></li>
                    <li><span class="hover:text-slate-300">Stripe & Apple Pay Terminal</span></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3">Terminal Access</h4>
                <div class="glass-card rounded-xl p-3 border border-slate-800 space-y-2">
                    <p class="text-[11px] text-slate-400">Need instant cashier access? Jump to the terminal login:</p>
                    <a href="{{ route('landing.owner-login-page') }}" class="block w-full text-center py-2 rounded-lg bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/30 hover:bg-emerald-500/30 transition-all">
                        Access Foodio POS →
                    </a>
                </div>
            </div>

        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // ROI Calculator Function
        function updateCalculator() {
            const sales = parseInt(document.getElementById('sales-slider').value);
            document.getElementById('sales-value').textContent = '$' + sales.toLocaleString() + ' / mo';
            
            const commission = Math.round(sales * 0.30);
            const foodioCost = 99;
            const savings = commission - foodioCost;
            
            document.getElementById('commission-lost').textContent = '-$' + commission.toLocaleString() + ' / mo';
            document.getElementById('annual-lost').textContent = '$' + (commission * 12).toLocaleString();
            
            document.getElementById('savings-amount').textContent = '+$' + savings.toLocaleString() + ' / mo';
            document.getElementById('annual-savings').textContent = '$' + (savings * 12).toLocaleString();
        }

        // Simulator Interactive Messages
        function sendSimMessage() {
            const input = document.getElementById('demo-input');
            const text = input.value.trim();
            if (!text) return;
            
            const chat = document.getElementById('chat-messages');

            // Append user message
            const userBubble = document.createElement('div');
            userBubble.className = 'flex items-end justify-end gap-2';
            userBubble.innerHTML = `
                <div class="bg-emerald-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow">
                    <p>${escapeHtml(text)}</p>
                    <span class="text-[9px] text-emerald-200 block text-right mt-1">Just now ✓✓</span>
                </div>
            `;
            chat.appendChild(userBubble);
            input.value = '';
            chat.scrollTop = chat.scrollHeight;

            // Simulated automated response after 600ms
            setTimeout(() => {
                const botBubble = document.createElement('div');
                botBubble.className = 'flex items-start gap-2';
                
                let responseContent = `Got it! We have logged your request: "${escapeHtml(text)}" and instantly forwarded it to the kitchen display screen & cashier station #01.`;
                if (text.toLowerCase().includes('table') || text.toLowerCase().includes('reserve')) {
                    responseContent = `🍽️ Table reservation request detected! We have 2 window booths free at 7:30 PM. Shall I confirm for party of 2?`;
                } else if (text.toLowerCase().includes('drink') || text.toLowerCase().includes('tea')) {
                    responseContent = `🥤 Added 1x Craft Hibiscus Tea ($5.00) to Table #7 ticket! Updated subtotal synced with POS.`;
                }

                botBubble.innerHTML = `
                    <div class="bg-slate-800 text-slate-200 p-3 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1">
                        <p class="font-medium">${responseContent}</p>
                        <span class="text-[9px] text-slate-500 block">Instant WhatsApp Bot response • 0.3s</span>
                    </div>
                `;
                chat.appendChild(botBubble);
                chat.scrollTop = chat.scrollHeight;
            }, 500);
        }

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        document.getElementById('demo-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendSimMessage();
            }
        });
    </script>
</body>
</html>
