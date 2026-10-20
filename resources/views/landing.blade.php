<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodio — 3D Spatial Restaurant OS (Cloud POS + WhatsApp Automation)</title>
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
        /* 3D Spatial Studio Lighting & Claymorphic Depth */
        body {
            background-color: #0b0f17;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 10%, rgba(20, 184, 166, 0.10) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 60%);
        }

        .perspective-container {
            perspective: 1200px;
        }

        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.4s ease;
        }

        .card-3d:hover {
            transform: translateY(-8px) rotateX(3deg) rotateY(-2deg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px -5px rgba(16, 185, 129, 0.3);
        }

        .clay-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.15),
                0 20px 40px -15px rgba(0, 0, 0, 0.6);
            border-radius: 28px;
        }

        .btn-3d {
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.4);
        }
        .btn-3d:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 14px 28px -4px rgba(16, 185, 129, 0.5), inset 0 1px 2px rgba(255, 255, 255, 0.6);
        }
        .btn-3d:active {
            transform: translateY(2px) scale(0.98);
            box-shadow: 0 4px 10px -2px rgba(16, 185, 129, 0.4);
        }

        /* 3D Floating Animations */
        @keyframes float-smooth {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(1deg); }
        }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(-1.5deg); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.08); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        .animate-float-1 { animation: float-smooth 5s ease-in-out infinite; }
        .animate-float-2 { animation: float-slow 6.5s ease-in-out infinite; }
        .animate-pulse-ring { animation: pulse-ring 3s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

        /* 3D Curling Thermal Ticket Animation */
        @keyframes ticket-curl {
            0% { transform: rotateX(-45deg) translateY(-20px); opacity: 0; }
            100% { transform: rotateX(0deg) translateY(0); opacity: 1; }
        }
        .ticket-animated {
            transform-origin: top center;
            animation: ticket-curl 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 overflow-x-hidden">

    <!-- Top Spatial Announcement Pill -->
    <div class="py-2.5 px-4 text-center text-xs text-slate-300 relative z-30 border-b border-emerald-500/20 bg-slate-950/70 backdrop-blur-md">
        <span class="inline-flex items-center gap-2 font-medium">
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold text-[10px] tracking-wider uppercase border border-emerald-500/30">
                3D Spatial Release
            </span>
            <span>Foodio 3D OS: Automated WhatsApp Commerce & Cloud POS is officially live!</span>
            <a href="{{ route('onboarding.signup') }}" class="underline text-emerald-400 hover:text-emerald-300 font-bold ml-1 inline-flex items-center gap-1">
                Start 14-Day Free Access <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </span>
    </div>

    <!-- 3D Suspended Glass Navigation Bar -->
    <header class="sticky top-4 z-50 px-4 sm:px-8 max-w-7xl mx-auto">
        <nav class="clay-card px-5 py-3.5 flex items-center justify-between border border-white/10 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-10 -left-10 w-40 h-24 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Brand Logo with 3D Icon -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group relative z-10">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-700 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                    <i data-lucide="utensils" class="w-5 h-5 text-slate-950 stroke-[2.8]"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white flex items-center gap-1.5">
                        Foodio
                        <span class="text-[9px] bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                            3D OS
                        </span>
                    </span>
                    <p class="text-[10px] text-slate-400 font-medium -mt-1">Cloud POS & WhatsApp Automation</p>
                </div>
            </a>

            <!-- Desktop Module Nav -->
            <div class="hidden lg:flex items-center gap-8 text-sm font-bold text-slate-300 relative z-10">
                <a href="#spatial-hero" class="hover:text-emerald-400 transition-colors flex items-center gap-1">
                    <i data-lucide="box" class="w-4 h-4 text-emerald-400"></i> 3D Showcase
                </a>
                <a href="#pos-demo" class="hover:text-emerald-400 transition-colors">Cloud POS</a>
                <a href="#whatsapp-sim" class="hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                    <i data-lucide="message-square" class="w-4 h-4 text-whatsapp"></i> WhatsApp Bot
                </a>
                <a href="#calculator" class="hover:text-emerald-400 transition-colors">ROI Calculator</a>
                <a href="#pricing" class="hover:text-emerald-400 transition-colors">Pricing</a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 relative z-10">
                <a href="{{ route('landing.owner-login-page') }}" class="hidden sm:inline-flex px-4 py-2 text-xs font-bold text-slate-200 hover:text-white transition-colors">
                    Terminal Sign In
                </a>
                <a href="{{ route('onboarding.signup') }}" class="btn-3d px-5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500 text-slate-950 font-black text-xs flex items-center gap-1.5">
                    <span>Launch Free</span>
                    <i data-lucide="sparkles" class="w-4 h-4 text-slate-950"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- 3D Spatial Hero Section -->
    <section id="spatial-hero" class="relative pt-12 pb-20 px-4 sm:px-6 overflow-hidden perspective-container">
        <!-- Directional Ambient Glows -->
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[850px] h-[550px] bg-emerald-500/10 rounded-full blur-[160px] pointer-events-none"></div>

        <div class="max-w-6xl mx-auto text-center relative z-10 space-y-6">
            
            <!-- Hero Pill -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full clay-card text-emerald-300 text-xs font-bold border border-emerald-500/30 shadow-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>The 3D Spatial Operating System for Modern Restaurants</span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.1]">
                Zero Commission. Direct WhatsApp Ordering. <br>
                <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
                  Synchronized with 3D Cloud POS.
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="max-w-2xl mx-auto text-sm sm:text-base text-slate-400 leading-relaxed font-normal">
                Convert your restaurant's WhatsApp into an autonomous, 24/7 ordering waiter. Orders drop into your cloud POS, fire instantly to kitchen printers, and dispatch riders without aggregator fees.
            </p>

            <!-- Dual 3D Buttons -->
            <div class="pt-2 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('onboarding.signup') }}" class="btn-3d px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400 text-slate-950 font-black text-base flex items-center gap-2.5">
                    <i data-lucide="rocket" class="w-5 h-5 text-slate-950"></i>
                    <span>Start 14-Day Free Trial</span>
                </a>
                <a href="#whatsapp-sim" class="clay-card px-8 py-4 rounded-2xl text-white font-extrabold text-base border border-slate-700/80 hover:border-emerald-500/50 transition-all flex items-center gap-2.5 hover:scale-[1.02]">
                    <i data-lucide="play" class="w-5 h-5 text-emerald-400"></i>
                    <span>Try Interactive 3D Simulator</span>
                </a>
            </div>

            <!-- 3D Spatial Visual Showcase Container (Interactive Tilt Canvas) -->
            <div id="spatial-canvas" class="pt-8 relative max-w-5xl mx-auto">
                <div class="clay-card p-3 rounded-[32px] border border-white/10 shadow-2xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-transparent to-transparent z-10 pointer-events-none"></div>

                    <!-- 3D Spatial Mockup Asset -->
                    <div class="rounded-[24px] overflow-hidden relative">
                        <img 
                            src="/images/foodio_3d_landing.jpg" 
                            alt="Foodio 3D Spatial Restaurant OS Showcase" 
                            class="w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                        >
                    </div>

                    <!-- Floating 3D Micro-Pill 1 (Top Left) -->
                    <div class="absolute top-8 left-8 z-20 clay-card px-4 py-2 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-2.5 animate-float-1">
                        <div class="w-8 h-8 rounded-xl bg-whatsapp/20 text-whatsapp flex items-center justify-center font-bold">
                            <i data-lucide="bot" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <div class="text-left">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase">AI Assistant</span>
                            <span class="text-xs font-black text-white">99.8% Bot Order Accuracy</span>
                        </div>
                    </div>

                    <!-- Floating 3D Micro-Pill 2 (Bottom Right) -->
                    <div class="absolute bottom-8 right-8 z-20 clay-card px-4 py-2.5 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-float-2">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div class="text-left">
                            <span class="text-[10px] text-emerald-400 font-bold block uppercase">Zero Commission</span>
                            <span class="text-xs font-black text-white">Keep 100% of Order Sales</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric Badges Strip -->
            <div class="pt-8 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                <div class="clay-card p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-black text-white">3x Faster</div>
                    <p class="text-xs text-slate-400 mt-1 font-semibold">Table Turnaround</p>
                </div>
                <div class="clay-card p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400">0% Comm.</div>
                    <p class="text-xs text-slate-400 mt-1 font-semibold">Keep 100% Customer Cash</p>
                </div>
                <div class="clay-card p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-black text-white">2.1s Avg.</div>
                    <p class="text-xs text-slate-400 mt-1 font-semibold">Instant Kitchen Print Sync</p>
                </div>
                <div class="clay-card p-4 text-center border border-slate-800">
                    <div class="text-2xl sm:text-3xl font-black text-teal-400">2,500+</div>
                    <p class="text-xs text-slate-400 mt-1 font-semibold">Active Restaurants & Cafes</p>
                </div>
            </div>

        </div>
    </section>

    <!-- Interactive 3D Cloud POS & WhatsApp Phone Simulator -->
    <section id="pos-demo" class="py-16 px-4 sm:px-6 relative perspective-container">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Spatial Interactive Terminal</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Watch an Order Travel Across the 3D Ecosystem</h2>
                <p class="text-slate-400 text-sm">Experience how an order moves from the customer's WhatsApp chat directly into the kitchen thermal receipt printer and cloud register.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: 3D POS Tablet with Curling Thermal Receipt (7 cols) -->
                <div class="lg:col-span-7 clay-card p-6 border border-white/10 shadow-2xl relative overflow-hidden card-3d">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-rose-500 shadow-md"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500 shadow-md"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-md"></span>
                            <span class="text-xs font-mono text-slate-300 ml-2 font-bold flex items-center gap-1.5">
                                <i data-lucide="tablet" class="w-3.5 h-3.5 text-emerald-400"></i> Cloud POS Register — Terminal 01
                            </span>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold flex items-center gap-1 border border-emerald-500/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> 3D Sync Live
                        </span>
                    </div>

                    <!-- POS Grid -->
                    <div class="pt-5 grid grid-cols-1 sm:grid-cols-12 gap-4">
                        
                        <!-- Catalog (7 cols) -->
                        <div class="sm:col-span-7 space-y-3">
                            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
                                <button class="px-3.5 py-1.5 rounded-xl bg-emerald-500 text-slate-950 font-black whitespace-nowrap shadow">Popular</button>
                                <button class="px-3.5 py-1.5 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Burgers</button>
                                <button class="px-3.5 py-1.5 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Bistro Pizza</button>
                                <button class="px-3.5 py-1.5 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 whitespace-nowrap">Drinks</button>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div onclick="addToRegister('Truffle Smash Burger', 14.50)" class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500 cursor-pointer transition-all hover:scale-[1.02]">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Truffle Smash Burger</span>
                                        <span class="text-xs font-black text-emerald-400">$14.50</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 block">In Stock (38 left)</span>
                                </div>
                                <div onclick="addToRegister('Woodfired Margherita', 18.00)" class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500 cursor-pointer transition-all hover:scale-[1.02]">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Woodfired Margherita</span>
                                        <span class="text-xs font-black text-emerald-400">$18.00</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 block">In Stock (22 left)</span>
                                </div>
                                <div onclick="addToRegister('Artisan Truffle Fries', 8.50)" class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500 cursor-pointer transition-all hover:scale-[1.02]">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Artisan Truffle Fries</span>
                                        <span class="text-xs font-black text-emerald-400">$8.50</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 block">Chef's Special</span>
                                </div>
                                <div onclick="addToRegister('Craft Hibiscus Tea', 5.00)" class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500 cursor-pointer transition-all hover:scale-[1.02]">
                                    <div class="flex justify-between items-start">
                                        <span class="text-xs font-bold text-white">Craft Hibiscus Tea</span>
                                        <span class="text-xs font-black text-emerald-400">$5.00</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 block">Fresh Brewed</span>
                                </div>
                            </div>

                            <!-- 3D Thermal Printer Badge -->
                            <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </div>
                                    <span class="text-slate-300 font-medium">ESC/POS Thermal Printer: <strong>Ready & Listening</strong></span>
                                </div>
                                <span class="text-[10px] text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Auto-Print On</span>
                            </div>
                        </div>

                        <!-- Current Order Ticket Panel (5 cols) with Curling Ticket Effect -->
                        <div class="sm:col-span-5 bg-slate-900/95 rounded-2xl p-4 border border-slate-800 flex flex-col justify-between space-y-4 shadow-xl">
                            <div id="ticket-container" class="ticket-animated space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                                    <div>
                                        <h4 class="text-xs font-black text-white">Order #FD-1094</h4>
                                        <span class="text-[10px] text-emerald-400 font-bold">Source: WhatsApp Bot</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-400 text-[10px] font-black border border-emerald-500/30">Table 07</span>
                                </div>

                                <div id="register-items" class="space-y-2 text-xs">
                                    <div class="flex justify-between text-slate-300">
                                        <span>1x Truffle Smash Burger</span>
                                        <span class="font-bold text-white">$14.50</span>
                                    </div>
                                    <div class="flex justify-between text-slate-300">
                                        <span>1x Artisan Truffle Fries</span>
                                        <span class="font-bold text-white">$8.50</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-800 pt-3 space-y-2">
                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Subtotal</span>
                                    <span id="ticket-subtotal">$23.00</span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Tax (8%)</span>
                                    <span id="ticket-tax">$1.84</span>
                                </div>
                                <div class="flex justify-between text-sm font-black text-white">
                                    <span>Total Due</span>
                                    <span id="ticket-total" class="text-emerald-400 font-black">$24.84</span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <button onclick="triggerPrintSound()" class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white flex items-center justify-center gap-1.5 transition">
                                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-400"></i> Print KOT
                                    </button>
                                    <button onclick="settleBill()" class="btn-3d py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black flex items-center justify-center gap-1.5">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Settle Bill
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- Right: Interactive WhatsApp 3D Smartphone Simulator (5 cols) -->
                <div id="whatsapp-sim" class="lg:col-span-5 clay-card p-6 border border-white/10 shadow-2xl relative card-3d">
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-2xl bg-whatsapp flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 font-bold">
                                <i data-lucide="message-circle" class="w-5 h-5 fill-current"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-black text-white flex items-center gap-1">
                                    Foodio SmartBot 3D
                                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                                </h3>
                                <p class="text-[10px] text-slate-400">Bilingual English / Urdu Assistant</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 font-mono font-bold">Live Sandbox</span>
                    </div>

                    <!-- Chat History -->
                    <div id="chat-messages" class="py-4 space-y-3 min-h-[300px] max-h-[340px] overflow-y-auto text-xs pr-1">
                        
                        <!-- Bot Message -->
                        <div class="flex items-start gap-2">
                            <div class="bg-slate-800 text-slate-200 p-3.5 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1.5 shadow">
                                <p class="font-medium">👋 Welcome to <strong>Bistro Woodfire</strong>! I'm your Foodio 3D assistant. How may I serve you?</p>
                                <div class="pt-1 flex flex-wrap gap-1.5">
                                    <button onclick="autoFill('1')" class="px-2.5 py-1 rounded-lg bg-slate-700 text-[10px] text-emerald-300 font-bold hover:bg-slate-600">1. Browse Menu 📖</button>
                                    <button onclick="autoFill('Book Table for 2 at 8 PM')" class="px-2.5 py-1 rounded-lg bg-slate-700 text-[10px] text-teal-300 font-bold hover:bg-slate-600">2. Reserve Table 🍽️</button>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Message -->
                        <div class="flex items-end justify-end gap-2">
                            <div class="bg-emerald-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] shadow">
                                <p>Can I get 1 Truffle Smash Burger and 1 Truffle Fries to Table #7?</p>
                                <span class="text-[9px] text-emerald-200 block text-right mt-1">16:42 ✓✓</span>
                            </div>
                        </div>

                        <!-- Bot Reply -->
                        <div class="flex items-start gap-2">
                            <div class="bg-slate-800 text-slate-200 p-3.5 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1.5 shadow">
                                <p class="font-medium">✅ Placed directly on <strong>Table #7 POS</strong> & printed to kitchen.</p>
                                <div class="bg-slate-900 p-2.5 rounded-xl text-[11px] space-y-1 border border-slate-800 font-mono">
                                    <div class="text-slate-300">1x Truffle Smash Burger: $14.50</div>
                                    <div class="text-slate-300">1x Artisan Truffle Fries: $8.50</div>
                                    <div class="text-emerald-400 font-bold border-t border-slate-800 pt-1">Total: $23.00 + tax</div>
                                </div>
                                <p class="text-[11px] text-slate-400">Prep time: <strong>11 minutes</strong>. Would you like a beverage?</p>
                            </div>
                        </div>

                    </div>

                    <!-- Input Bar -->
                    <div class="pt-3 border-t border-slate-800 flex items-center gap-2">
                        <input type="text" id="demo-input" placeholder="Type: 'Add Hibiscus Tea' or 'Reserve Table'..." class="flex-1 bg-slate-900 border border-slate-700 text-xs rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        <button onclick="sendSimMessage()" class="btn-3d px-4 py-2.5 rounded-xl bg-whatsapp text-white hover:bg-emerald-600 font-bold text-xs flex items-center gap-1 shadow">
                            <span>Send</span>
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-500 text-center mt-2">Type or click pills to trigger real-time simulated WhatsApp responses.</p>
                </div>

            </div>

        </div>
    </section>

    <!-- 3D Linked Pages Showcase Section -->
    <section class="py-16 px-4 sm:px-6 bg-slate-900/30 border-y border-slate-800 perspective-container">
        <div class="max-w-6xl mx-auto space-y-8 text-center">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Spatial Architecture</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">Explore the 3D Linked Pages Ecosystem</h2>
            <p class="text-slate-400 text-sm max-w-2xl mx-auto">From tactile login authorization to live spatial kitchen commands, experience every screen in unified 3D depth.</p>

            <div class="clay-card p-3 rounded-[32px] border border-white/10 shadow-2xl relative overflow-hidden group">
                <img 
                    src="/images/foodio_3d_linked.jpg" 
                    alt="Foodio 3D Linked Pages Showcase" 
                    class="w-full object-cover rounded-[24px] transition-transform duration-700 ease-out group-hover:scale-105"
                >
            </div>

            <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('landing.owner-login-page') }}" class="btn-3d px-6 py-3.5 rounded-2xl bg-slate-900 border border-slate-700 text-white font-bold text-xs flex items-center gap-2 hover:border-emerald-500">
                    <i data-lucide="log-in" class="w-4 h-4 text-emerald-400"></i>
                    <span>Open 3D Terminal Login</span>
                </a>
                <a href="{{ route('onboarding.signup') }}" class="btn-3d px-6 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black text-xs flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4 text-slate-950"></i>
                    <span>Open 3D Onboarding Flow</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Interactive Commission Savings & ROI Calculator -->
    <section id="calculator" class="py-16 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto clay-card p-6 sm:p-10 border border-white/10 space-y-8 card-3d">
            
            <div class="text-center space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Zero Commission Independence</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">How Much Money Are Aggregators Taking From You?</h2>
                <p class="text-xs sm:text-sm text-slate-400">Calculate how much revenue stays in your pocket by migrating orders to direct WhatsApp channels.</p>
            </div>

            <div class="space-y-6 pt-4">
                <div>
                    <div class="flex justify-between items-center text-sm font-bold mb-2">
                        <span class="text-slate-300">Monthly Restaurant Sales:</span>
                        <span id="sales-value" class="text-emerald-400 font-black text-lg">$25,000 / mo</span>
                    </div>
                    <input type="range" id="sales-slider" min="5000" max="100000" step="1000" value="25000" oninput="updateCalculator()" class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                    <div class="bg-rose-950/30 border border-rose-900/50 rounded-2xl p-5 text-center">
                        <span class="text-xs font-bold text-rose-300">Third-Party App Commission (30%)</span>
                        <div id="commission-lost" class="text-2xl sm:text-3xl font-black text-rose-400 mt-2">-$7,500 / mo</div>
                        <p class="text-[11px] text-slate-400 mt-1">Lost to delivery marketplaces: <span id="annual-lost" class="text-rose-300 font-bold">$90,000 / yr</span></p>
                    </div>

                    <div class="bg-emerald-950/30 border border-emerald-900/50 rounded-2xl p-5 text-center">
                        <span class="text-xs font-bold text-emerald-300">Saved with Foodio Flat SaaS</span>
                        <div id="savings-amount" class="text-2xl sm:text-3xl font-black text-emerald-400 mt-2">+$7,401 / mo</div>
                        <p class="text-[11px] text-slate-400 mt-1">Retained by your restaurant: <span id="annual-savings" class="text-emerald-300 font-bold">$88,812 / yr</span></p>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <a href="{{ route('onboarding.signup') }}" class="btn-3d inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl bg-emerald-500 text-slate-950 font-black text-xs hover:bg-emerald-400 transition-all">
                        <span>Reclaim 100% of Your Revenue Today</span>
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
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Transparent Pricing</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Flat Monthly SaaS. No Commissions.</h2>
                <p class="text-slate-400 text-sm">Every plan includes unlimited order processing and zero cuts.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                
                <!-- Starter Plan -->
                <div class="clay-card p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-6 card-3d">
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Starter Outlets</span>
                        <h3 class="text-2xl font-bold text-white">Cloud POS Core</h3>
                        <p class="text-xs text-slate-400">For cafes and food trucks needing fast billing and receipt printing.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-black text-white">$49</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> 1 Active Cloud POS Terminal</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Digital Dine-in QR Menu</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Thermal Receipt Printer Sync</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs text-center transition-all">
                        Start Free Trial
                    </a>
                </div>

                <!-- Growth Plan (Featured) -->
                <div class="clay-card p-6 sm:p-8 border-2 border-emerald-500 shadow-2xl flex flex-col justify-between space-y-6 relative card-3d">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full bg-emerald-500 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow-lg">
                        Most Popular Choice
                    </div>
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Full 3D Automation</span>
                        <h3 class="text-2xl font-bold text-white">POS + WhatsApp Pro</h3>
                        <p class="text-xs text-slate-400">The complete system powering high-volume restaurants and bistros.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-black text-white">$99</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Unlimited POS Terminals</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Official WhatsApp Ordering Bot</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> 3D Table Floorplan & KDS</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Automated Customer Re-Order Broadcasts</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="btn-3d w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black text-xs text-center transition-all">
                        Launch Free 14-Day Trial
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="clay-card p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-6 card-3d">
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Chains & Franchises</span>
                        <h3 class="text-2xl font-bold text-white">Multi-Outlet OS</h3>
                        <p class="text-xs text-slate-400">For multi-branch chains, centralized cloud kitchens, and franchises.</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-black text-white">$249</span>
                            <span class="text-xs text-slate-400">/ month</span>
                        </div>
                        <ul class="text-xs text-slate-300 space-y-2.5 pt-4 border-t border-slate-800">
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Multi-branch central inventory sync</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Custom WhatsApp AI Persona</li>
                            <li class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-400"></i> Dedicated Server & SLA</li>
                        </ul>
                    </div>
                    <a href="{{ route('onboarding.signup') }}" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs text-center transition-all">
                        Contact Enterprise
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- Global Footer -->
    <footer class="border-t border-slate-800 py-12 px-4 sm:px-8 bg-slate-950/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <div class="space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center text-slate-950 font-black">
                        <i data-lucide="utensils" class="w-4 h-4 text-slate-950 stroke-[2.8]"></i>
                    </div>
                    <span class="text-lg font-black text-white">Foodio 3D OS</span>
                </div>
                <p class="text-xs text-slate-400">The premier cloud restaurant automation system, unifying POS and direct WhatsApp commerce.</p>
                <p class="text-[11px] text-slate-600">© 2026 Foodio Technologies Inc. All rights reserved.</p>
            </div>

            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 mb-3">Spatial Navigation</h4>
                <ul class="text-xs text-slate-400 space-y-2 font-medium">
                    <li><a href="{{ route('landing') }}" class="hover:text-emerald-400">3D Home Landing</a></li>
                    <li><a href="{{ route('landing.owner-login-page') }}" class="hover:text-emerald-400">Terminal Sign In</a></li>
                    <li><a href="{{ route('onboarding.signup') }}" class="hover:text-emerald-400">Restaurant Onboarding</a></li>
                    <li><a href="/foodio-redesign/showcase.html" target="_blank" class="hover:text-emerald-400 font-bold text-emerald-400">3-Screen Showcase</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 mb-3">POS & WhatsApp</h4>
                <ul class="text-xs text-slate-400 space-y-2 font-medium">
                    <li><span class="hover:text-slate-300">WhatsApp Cloud API 2.0</span></li>
                    <li><span class="hover:text-slate-300">Kitchen Display Systems (KDS)</span></li>
                    <li><span class="hover:text-slate-300">Thermal Receipt Printers</span></li>
                    <li><span class="hover:text-slate-300">In-House Rider Logistics</span></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 mb-3">Operator Terminal</h4>
                <div class="clay-card p-3.5 border border-white/10 space-y-2">
                    <p class="text-[11px] text-slate-400">Need immediate terminal access? Log into your station:</p>
                    <a href="{{ route('landing.owner-login-page') }}" class="btn-3d block w-full text-center py-2.5 rounded-xl bg-emerald-500 text-slate-950 text-xs font-black">
                        Access Foodio POS →
                    </a>
                </div>
            </div>

        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        lucide.createIcons();

        // Register items list
        const registerItems = [
            { name: '1x Truffle Smash Burger', price: 14.50 },
            { name: '1x Artisan Truffle Fries', price: 8.50 }
        ];

        function addToRegister(name, price) {
            registerItems.push({ name: '1x ' + name, price: price });
            renderRegister();
        }

        function renderRegister() {
            const container = document.getElementById('register-items');
            container.innerHTML = '';
            let subtotal = 0;
            registerItems.forEach(item => {
                subtotal += item.price;
                const div = document.createElement('div');
                div.className = 'flex justify-between text-slate-300';
                div.innerHTML = `<span>${escapeHtml(item.name)}</span><span class="font-bold text-white">$${item.price.toFixed(2)}</span>`;
                container.appendChild(div);
            });
            const tax = subtotal * 0.08;
            const total = subtotal + tax;
            document.getElementById('ticket-subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('ticket-tax').textContent = '$' + tax.toFixed(2);
            document.getElementById('ticket-total').textContent = '$' + total.toFixed(2);

            // Re-trigger curl animation
            const ticket = document.getElementById('ticket-container');
            ticket.classList.remove('ticket-animated');
            void ticket.offsetWidth;
            ticket.classList.add('ticket-animated');
        }

        function triggerPrintSound() {
            alert('KOT Ticket sent to ESC/POS Thermal Printer (Station #01 Kitchen)!');
        }

        function settleBill() {
            alert('Order #FD-1094 Settled! Cash drawer opened and customer WhatsApp receipt sent.');
        }

        function updateCalculator() {
            const sales = parseInt(document.getElementById('sales-slider').value);
            document.getElementById('sales-value').textContent = '$' + sales.toLocaleString() + ' / mo';
            const commission = Math.round(sales * 0.30);
            const savings = commission - 99;
            document.getElementById('commission-lost').textContent = '-$' + commission.toLocaleString() + ' / mo';
            document.getElementById('annual-lost').textContent = '$' + (commission * 12).toLocaleString() + ' / yr';
            document.getElementById('savings-amount').textContent = '+$' + savings.toLocaleString() + ' / mo';
            document.getElementById('annual-savings').textContent = '$' + (savings * 12).toLocaleString() + ' / yr';
        }

        function autoFill(text) {
            document.getElementById('demo-input').value = text;
            sendSimMessage();
        }

        function sendSimMessage() {
            const input = document.getElementById('demo-input');
            const text = input.value.trim();
            if (!text) return;
            const chat = document.getElementById('chat-messages');

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

            setTimeout(() => {
                const botBubble = document.createElement('div');
                botBubble.className = 'flex items-start gap-2';
                let responseContent = `Order updated! We logged: "${escapeHtml(text)}" and automatically sent it to kitchen printer & POS terminal.`;
                if (text.toLowerCase().includes('tea') || text.toLowerCase().includes('drink')) {
                    responseContent = `🥤 Added 1x Craft Hibiscus Tea ($5.00) to Table #7 ticket! Synced with POS.`;
                    addToRegister('Craft Hibiscus Tea', 5.00);
                } else if (text.toLowerCase().includes('table') || text.toLowerCase().includes('reserve')) {
                    responseContent = `🍽️ Table reservation confirmed! Table #04 reserved for 2 guests.`;
                }

                botBubble.innerHTML = `
                    <div class="bg-slate-800 text-slate-200 p-3.5 rounded-2xl rounded-tl-none max-w-[85%] border border-slate-700/60 space-y-1 shadow">
                        <p class="font-medium">${responseContent}</p>
                        <span class="text-[9px] text-slate-500 block">Instant WhatsApp Bot response • 0.2s</span>
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
            if (e.key === 'Enter') sendSimMessage();
        });
    </script>
</body>
</html>
