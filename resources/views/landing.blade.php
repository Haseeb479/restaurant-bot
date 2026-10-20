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
        /* 3D Spatial Canvas & Lighting */
        body {
            background-color: #070a10;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 15%, rgba(20, 184, 166, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(16, 185, 129, 0.05) 0px, transparent 65%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 60%);
        }

        .perspective-container {
            perspective: 1400px;
        }

        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.4s ease;
        }

        .card-3d:hover {
            transform: translateY(-6px) rotateX(2deg) rotateY(-1.5deg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 40px -5px rgba(16, 185, 129, 0.3);
        }

        .clay-card {
            background: linear-gradient(135deg, rgba(26, 36, 52, 0.8), rgba(13, 19, 32, 0.95));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.18),
                0 25px 50px -15px rgba(0, 0, 0, 0.7);
            border-radius: 28px;
        }

        .btn-3d {
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 22px -4px rgba(16, 185, 129, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.5);
        }
        .btn-3d:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 14px 28px -4px rgba(16, 185, 129, 0.55), inset 0 1px 2px rgba(255, 255, 255, 0.7);
        }
        .btn-3d:active {
            transform: translateY(2px) scale(0.98);
        }

        /* 3D Animations */
        @keyframes float-hero {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }
        @keyframes ticket-curl {
            0% { transform: rotateX(-50deg) translateY(-25px); opacity: 0; }
            100% { transform: rotateX(0deg) translateY(0); opacity: 1; }
        }

        .animate-float { animation: float-hero 5.5s ease-in-out infinite; }
        .ticket-animated { transform-origin: top center; animation: ticket-curl 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="text-slate-100 font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 overflow-x-hidden">

    <!-- Top Spatial Announcement Pill -->
    <div class="py-2.5 px-4 text-center text-xs text-slate-300 relative z-30 border-b border-emerald-500/20 bg-slate-950/80 backdrop-blur-md">
        <span class="inline-flex items-center gap-2 font-medium">
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-extrabold text-[10px] tracking-wider uppercase border border-emerald-500/30">
                3D Spatial Release
            </span>
            <span>Foodio 3D OS: Complete Cloud POS + Autonomous WhatsApp Commerce Architecture</span>
            <a href="{{ route('onboarding.signup') }}" class="underline text-emerald-400 hover:text-emerald-300 font-bold ml-1 inline-flex items-center gap-1">
                Start 14-Day Free Access <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </span>
    </div>

    <!-- 3D Suspended Navigation Bar -->
    <header class="sticky top-4 z-50 px-4 sm:px-8 max-w-7xl mx-auto">
        <nav class="clay-card px-5 py-3.5 flex items-center justify-between border border-white/10 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-10 -left-10 w-40 h-24 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group relative z-10">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-700 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                    <i data-lucide="box" class="w-5 h-5 text-slate-950 stroke-[2.8]"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white flex items-center gap-1.5">
                        Foodio
                        <span class="text-[9px] bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                            3D OS
                        </span>
                    </span>
                    <p class="text-[10px] text-slate-400 font-medium -mt-1">Cloud POS & WhatsApp Automation</p>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <div class="hidden lg:flex items-center gap-7 text-xs font-bold text-slate-300 relative z-10">
                <a href="#showcase-explorer" class="hover:text-emerald-400 transition-colors flex items-center gap-1">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-400"></i> 4-Design 3D Explorer
                </a>
                <a href="#floorplan-module" class="hover:text-emerald-400 transition-colors">Floorplan</a>
                <a href="#node-studio" class="hover:text-emerald-400 transition-colors">WhatsApp Studio</a>
                <a href="#kds-printer" class="hover:text-emerald-400 transition-colors">KDS & Thermal</a>
                <a href="#logistics-radar" class="hover:text-emerald-400 transition-colors">Fleet Radar</a>
                <a href="#pricing" class="hover:text-emerald-400 transition-colors">Pricing</a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 relative z-10">
                <a href="{{ route('landing.owner-login-page') }}" class="hidden sm:inline-flex px-3.5 py-2 text-xs font-bold text-slate-200 hover:text-white transition-colors">
                    Sign In
                </a>
                <a href="{{ route('onboarding.signup') }}" class="btn-3d px-5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500 text-slate-950 font-black text-xs flex items-center gap-1.5">
                    <span>Get Started</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-slate-950"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- 3D Spatial Hero -->
    <section class="relative pt-12 pb-16 px-4 sm:px-6 overflow-hidden perspective-container">
        <div class="max-w-6xl mx-auto text-center relative z-10 space-y-6">
            
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full clay-card text-emerald-300 text-xs font-black border border-emerald-500/30 shadow-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>The Intelligent 3D Spatial Operating System for Hospitality</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.1]">
                Zero Commission. Direct WhatsApp Ordering. <br>
                <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">
                  Synchronized with 3D Cloud POS.
                </span>
            </h1>

            <p class="max-w-2xl mx-auto text-sm sm:text-base text-slate-400 leading-relaxed font-normal">
                An autonomous AI waiter operating directly on your WhatsApp number, connected in real-time to a 3D Cloud POS, automated thermal kitchen printing, and rider logistics fleet.
            </p>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('onboarding.signup') }}" class="btn-3d px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400 text-slate-950 font-black text-base flex items-center gap-2.5">
                    <i data-lucide="sparkles" class="w-5 h-5 text-slate-950"></i>
                    <span>Start 14-Day Free Access</span>
                </a>
                <a href="#showcase-explorer" class="clay-card px-8 py-4 rounded-2xl text-white font-extrabold text-base border border-slate-700/80 hover:border-emerald-500/50 transition-all flex items-center gap-2.5 hover:scale-[1.02]">
                    <i data-lucide="layers" class="w-5 h-5 text-emerald-400"></i>
                    <span>Explore 4 3D Mockup Designs ↓</span>
                </a>
            </div>

        </div>
    </section>

    <!-- CORE FEATURE: THE 4 3D ANIMATION MOCKUP DESIGNS EXPLORER -->
    <section id="showcase-explorer" class="py-12 px-4 sm:px-6 relative perspective-container">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Interactive 3D System Explorer</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white">4 Distinct 3D Animation UI Mockup Designs</h2>
                <p class="text-slate-400 text-sm">Click the tabs below to explore each operational phase modeled in full 3D spatial depth.</p>
            </div>

            <!-- 4 Interactive Tabs -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 max-w-5xl mx-auto">
                <button onclick="switchDesign(1, this)" id="tab-btn-1" class="design-tab py-3.5 px-4 rounded-2xl border border-emerald-500 bg-emerald-500/20 text-emerald-300 text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg">
                    <i data-lucide="bot" class="w-4 h-4 text-emerald-400"></i>
                    <span>1. Kinetic Hero & Bot</span>
                </button>
                <button onclick="switchDesign(2, this)" id="tab-btn-2" class="design-tab py-3.5 px-4 rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-500 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4 text-slate-400"></i>
                    <span>2. Login & 3D Printer</span>
                </button>
                <button onclick="switchDesign(3, this)" id="tab-btn-3" class="design-tab py-3.5 px-4 rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-500 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-400"></i>
                    <span>3. Command Deck</span>
                </button>
                <button onclick="switchDesign(4, this)" id="tab-btn-4" class="design-tab py-3.5 px-4 rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-500 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-slate-400"></i>
                    <span>4. Live Fleet Radar</span>
                </button>
            </div>

            <!-- Active 3D Design Display Container -->
            <div class="clay-card p-4 sm:p-6 border border-white/10 shadow-2xl card-3d relative overflow-hidden">
                
                <!-- Main Image Preview -->
                <div class="rounded-2xl overflow-hidden relative shadow-2xl border border-white/10 bg-slate-950">
                    <img 
                        id="design-display-img"
                        src="/images/foodio_3d_landing.jpg" 
                        alt="3D Spatial UI Mockup Design" 
                        class="w-full object-cover transition-opacity duration-300"
                    >
                </div>

                <!-- Dynamic Feature Bar Below Image -->
                <div class="pt-6 grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-slate-800/80 mt-6 text-left">
                    <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                        <span class="text-[10px] text-emerald-400 font-bold uppercase tracking-wider block" id="feature-tag-1">Primary 3D Object</span>
                        <h4 class="text-xs font-black text-white mt-1" id="feature-title-1">Floating Clay Mascot & POS Tablet</h4>
                        <p class="text-[11px] text-slate-400 mt-1" id="feature-desc-1">Suspended zero-gravity AI mascot with real-time cursor gyro-tilt.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                        <span class="text-[10px] text-teal-400 font-bold uppercase tracking-wider block" id="feature-tag-2">Motion Physics</span>
                        <h4 class="text-xs font-black text-white mt-1" id="feature-title-2">Sine-Wave Floating & Parallax</h4>
                        <p class="text-[11px] text-slate-400 mt-1" id="feature-desc-2">Fluid bobbing physics and spatial depth layers that react to scroll.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                        <span class="text-[10px] text-cyan-400 font-bold uppercase tracking-wider block" id="feature-tag-3">SaaS Synergy</span>
                        <h4 class="text-xs font-black text-white mt-1" id="feature-title-3">WhatsApp-to-POS Conduit</h4>
                        <p class="text-[11px] text-slate-400 mt-1" id="feature-desc-3">Direct order packet pulses traveling straight into the Cloud POS register.</p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- MODULE 2: 3D Spatial Restaurant Floorplan & Interactive Table Dispatcher -->
    <section id="floorplan-module" class="py-16 px-4 sm:px-6 relative perspective-container border-t border-slate-900">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Module 02 • Spatial Operations</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">3D Interactive Dine-In Floorplan</h2>
                <p class="text-slate-400 text-sm">Real-time 3D spatial table pods with status halos, steam particle indicators, and one-tap bill settling.</p>
            </div>

            <!-- 3D Floorplan Canvas -->
            <div class="clay-card p-6 sm:p-8 border border-white/10 shadow-2xl card-3d">
                
                <div class="flex flex-wrap items-center justify-between pb-6 border-b border-slate-800 gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <h3 class="text-sm font-black text-white">Dining Floor: Main Hall (12 Tables Active)</h3>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold">
                        <span class="flex items-center gap-1.5 text-emerald-400"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> 5 Vacant</span>
                        <span class="flex items-center gap-1.5 text-amber-400"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> 5 Dining</span>
                        <span class="flex items-center gap-1.5 text-cyan-400"><span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span> 2 Billing</span>
                    </div>
                </div>

                <!-- 3D Table Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 pt-6">
                    
                    <div onclick="inspectTable('T-01', 'Dining', 'Truffle Smash Burger x2', '$29.00')" class="clay-card p-4 text-center border border-amber-500/40 hover:border-amber-400 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-amber-500/20 text-amber-300 flex items-center justify-center font-black mb-2 border border-amber-500/30">
                            <i data-lucide="utensils" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 01</span>
                        <span class="text-[10px] text-amber-400 font-bold">Dining • 4 Guests</span>
                        <span class="text-[11px] text-slate-300 font-mono block mt-1">$29.00</span>
                    </div>

                    <div onclick="inspectTable('T-02', 'Vacant', 'None', '$0.00')" class="clay-card p-4 text-center border border-emerald-500/40 hover:border-emerald-400 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black mb-2 border border-emerald-500/30">
                            <i data-lucide="check" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 02</span>
                        <span class="text-[10px] text-emerald-400 font-bold">Vacant & Clean</span>
                        <span class="text-[11px] text-slate-500 font-mono block mt-1">Ready</span>
                    </div>

                    <div onclick="inspectTable('T-03', 'Bill Requested', 'Margherita Pizza, Wine', '$38.50')" class="clay-card p-4 text-center border border-cyan-500/50 hover:border-cyan-400 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black mb-2 border border-cyan-500/30">
                            <i data-lucide="receipt" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 03</span>
                        <span class="text-[10px] text-cyan-400 font-bold">Bill Requested</span>
                        <span class="text-[11px] text-cyan-300 font-mono font-bold block mt-1">$38.50</span>
                    </div>

                    <div onclick="inspectTable('T-04', 'Dining', 'Flame Grill Steak, Fries', '$42.00')" class="clay-card p-4 text-center border border-amber-500/40 hover:border-amber-400 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-amber-500/20 text-amber-300 flex items-center justify-center font-black mb-2 border border-amber-500/30">
                            <i data-lucide="flame" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 04</span>
                        <span class="text-[10px] text-amber-400 font-bold">Dining • 2 Guests</span>
                        <span class="text-[11px] text-slate-300 font-mono block mt-1">$42.00</span>
                    </div>

                    <div onclick="inspectTable('T-05', 'Vacant', 'None', '$0.00')" class="clay-card p-4 text-center border border-emerald-500/40 hover:border-emerald-400 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black mb-2 border border-emerald-500/30">
                            <i data-lucide="check" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 05</span>
                        <span class="text-[10px] text-emerald-400 font-bold">Vacant & Clean</span>
                        <span class="text-[11px] text-slate-500 font-mono block mt-1">Ready</span>
                    </div>

                    <div onclick="inspectTable('T-06', 'WhatsApp Live Order', 'Woodfire Pizza x1, Craft Tea', '$23.00')" class="clay-card p-4 text-center border border-emerald-400 bg-emerald-950/30 hover:border-emerald-300 cursor-pointer transition-all hover:scale-105 group">
                        <div class="w-12 h-12 mx-auto rounded-full bg-whatsapp/20 text-whatsapp flex items-center justify-center font-black mb-2 border border-emerald-500/30">
                            <i data-lucide="message-square" class="w-5 h-5 text-emerald-400"></i>
                        </div>
                        <span class="text-xs font-black text-white block">Table 06</span>
                        <span class="text-[10px] text-emerald-300 font-bold">WhatsApp Direct</span>
                        <span class="text-[11px] text-emerald-400 font-mono font-bold block mt-1">$23.00</span>
                    </div>

                </div>

                <!-- Inspector Drawer -->
                <div id="table-inspector" class="mt-6 p-4 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-emerald-400 font-black">
                            <i data-lucide="layers" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-bold" id="inspect-title">Selected Table: Table 01</span>
                            <span class="text-white font-extrabold" id="inspect-desc">Active Order: Truffle Smash Burger x2 ($29.00)</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="alert('Table bill split into 2 tickets!')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold">Split Check</button>
                        <button onclick="alert('Table KOT sent to kitchen thermal printer!')" class="btn-3d px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-black">Settle & Clear Table</button>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- MODULE 3: 3D Autonomous WhatsApp Conversational Builder (Node Studio) -->
    <section id="node-studio" class="py-16 px-4 sm:px-6 relative perspective-container border-t border-slate-900">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Module 03 • AI Flow Engine</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">3D WhatsApp Conversational Studio</h2>
                <p class="text-slate-400 text-sm">Visual node architecture showing how incoming customer messages automatically trigger language detection, menu catalogs, and instant POS tickets.</p>
            </div>

            <div class="clay-card p-6 sm:p-10 border border-white/10 shadow-2xl space-y-6 card-3d">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                    
                    <div class="clay-card p-5 border border-emerald-500/40 relative">
                        <div class="w-8 h-8 rounded-xl bg-whatsapp/20 text-whatsapp flex items-center justify-center font-black mb-3">
                            <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <span class="text-[10px] text-emerald-400 font-black uppercase tracking-wider block">Trigger Node</span>
                        <h4 class="text-sm font-black text-white mt-1">Inbound Message</h4>
                        <p class="text-[11px] text-slate-400 mt-1">Customer texts "Hi" or scans table QR code.</p>
                    </div>

                    <div class="clay-card p-5 border border-teal-500/40 relative">
                        <div class="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center font-black mb-3">
                            <i data-lucide="globe" class="w-4 h-4 text-teal-400"></i>
                        </div>
                        <span class="text-[10px] text-teal-400 font-black uppercase tracking-wider block">AI Classifier</span>
                        <h4 class="text-sm font-black text-white mt-1">Urdu / English NLP</h4>
                        <p class="text-[11px] text-slate-400 mt-1">Auto-detects language & replies in native tongue.</p>
                    </div>

                    <div class="clay-card p-5 border border-cyan-500/40 relative">
                        <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black mb-3">
                            <i data-lucide="book-open" class="w-4 h-4 text-cyan-400"></i>
                        </div>
                        <span class="text-[10px] text-cyan-400 font-black uppercase tracking-wider block">Menu Dispatch</span>
                        <h4 class="text-sm font-black text-white mt-1">Interactive Catalog</h4>
                        <p class="text-[11px] text-slate-400 mt-1">Serves food pictures, prices, and portion modifiers.</p>
                    </div>

                    <div class="clay-card p-5 border border-emerald-400 relative">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center font-black mb-3">
                            <i data-lucide="zap" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <span class="text-[10px] text-emerald-400 font-black uppercase tracking-wider block">POS Commit</span>
                        <h4 class="text-sm font-black text-white mt-1">Cloud POS Sync</h4>
                        <p class="text-[11px] text-slate-400 mt-1">Auto-prints KOT ticket and triggers kitchen timers.</p>
                    </div>

                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-300 font-medium">✨ Real-time latency: <strong>0.28 seconds</strong> from customer message to kitchen ticket.</span>
                    <a href="{{ route('onboarding.signup') }}" class="text-emerald-400 font-bold hover:underline">Customize Bot Flow →</a>
                </div>
            </div>

        </div>
    </section>

    <!-- MODULE 4: 3D Kitchen Display System (KDS) & Haptic Thermal Printer -->
    <section id="kds-printer" class="py-16 px-4 sm:px-6 relative perspective-container border-t border-slate-900">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Module 04 • Kitchen Automation</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">3D Kitchen Display System & Thermal Printing</h2>
                <p class="text-slate-400 text-sm">Visual kanban swimlanes with preparation countdown timers and automatic ESC/POS thermal printing.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="clay-card p-5 border border-cyan-500/30 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <span class="text-xs font-black uppercase text-cyan-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span> Incoming (2)
                        </span>
                        <span class="text-[10px] text-slate-500">Auto-Synced</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-black text-white">Ticket #1098</span>
                            <span class="text-[10px] text-cyan-400 font-mono font-bold">Table 06</span>
                        </div>
                        <p class="text-xs text-slate-300">1x Woodfired Margherita Pizza</p>
                        <p class="text-[10px] text-slate-500">Extra Basil, Thin Crust</p>
                        <button onclick="alert('Ticket #1098 moved to Preparing station!')" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white transition">Start Cooking →</button>
                    </div>
                </div>

                <div class="clay-card p-5 border border-amber-500/30 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <span class="text-xs font-black uppercase text-amber-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span> Preparing (1)
                        </span>
                        <span class="text-[10px] text-amber-400 font-mono font-bold">Avg. 11m</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-amber-500/40 space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-black text-white">Ticket #1094</span>
                            <span class="text-[10px] text-amber-400 font-mono font-bold">Prep: 04:20</span>
                        </div>
                        <p class="text-xs text-slate-300">2x Truffle Smash Burgers, 1x Fries</p>
                        <p class="text-[10px] text-slate-500">Medium Well, No Onions</p>
                        <button onclick="alert('Ticket #1094 marked ready! WhatsApp ping sent to customer.')" class="btn-3d w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition">Mark Ready & Notify</button>
                    </div>
                </div>

                <div class="clay-card p-5 border border-emerald-500/30 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <span class="text-xs font-black uppercase text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Dispatched (3)
                        </span>
                        <span class="text-[10px] text-emerald-400 font-mono font-bold">Live Fleet</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-emerald-500/40 space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-black text-white">Ticket #1089</span>
                            <span class="text-[10px] text-emerald-400 font-mono font-bold">With Rider #03</span>
                        </div>
                        <p class="text-xs text-slate-300">Delivering to Gulberg III</p>
                        <span class="text-[10px] text-slate-400 block">ETA: 6 mins</span>
                        <a href="#logistics-radar" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-emerald-400 block text-center transition">Track on Live Radar →</a>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- MODULE 5: 3D Real-Time Logistics Radar & Fleet Tracker -->
    <section id="logistics-radar" class="py-16 px-4 sm:px-6 relative perspective-container border-t border-slate-900">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Module 05 • Logistics Engine</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">3D Real-Time Delivery Radar</h2>
                <p class="text-slate-400 text-sm">Track in-house riders in 3D perspective with GPS breadcrumbs, driver speedometers, and live customer updates.</p>
            </div>

            <div class="clay-card p-6 sm:p-8 border border-white/10 shadow-2xl space-y-6 card-3d">
                
                <div class="flex flex-wrap items-center justify-between pb-4 border-b border-slate-800 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center font-bold">
                            <i data-lucide="navigation" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-white">Rider Fleet Active: 4 In Flight</h4>
                            <span class="text-[10px] text-slate-400">Average Transit Duration: 14.2 minutes</span>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 font-mono font-bold text-xs border border-emerald-500/30">GPS Radar Online</span>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-400 mb-4">
                        <span>Bistro Kitchen Hub</span>
                        <span class="text-emerald-400 font-mono">1.2 km in transit</span>
                        <span>Customer Doorstep</span>
                    </div>

                    <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden relative border border-slate-700/60 p-0.5">
                        <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 rounded-full w-2/3"></div>
                    </div>

                    <div class="pt-4 flex items-center justify-between text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <i data-lucide="bike" class="w-4 h-4 text-emerald-400"></i>
                            <span>Rider Bilal Khan • Speed: 28 km/h</span>
                        </div>
                        <span class="font-bold text-white">Arrival in ~6 minutes</span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-16 px-4 sm:px-6 bg-slate-950/60 border-t border-slate-900 perspective-container">
        <div class="max-w-6xl mx-auto space-y-12">
            
            <div class="text-center max-w-xl mx-auto space-y-2">
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Flat Monthly Plans</span>
                <h2 class="text-3xl sm:text-4xl font-black text-white">Simple Pricing. Zero Commissions.</h2>
                <p class="text-slate-400 text-sm">Every plan includes 3D modules and unlimited order processing.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                
                <div class="clay-card p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-6 card-3d">
                    <div class="space-y-4">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Starter</span>
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
    <footer class="border-t border-slate-800 py-12 px-4 sm:px-8 bg-slate-950/90 backdrop-blur-md">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            
            <div class="space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center text-slate-950 font-black">
                        <i data-lucide="box" class="w-4 h-4 text-slate-950 stroke-[2.8]"></i>
                    </div>
                    <span class="text-lg font-black text-white">Foodio 3D OS</span>
                </div>
                <p class="text-xs text-slate-400">The premier cloud restaurant automation system, unifying POS and direct WhatsApp commerce.</p>
                <p class="text-[11px] text-slate-600">© 2026 Foodio Technologies Inc. All rights reserved.</p>
            </div>

            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 mb-3">3D Spatial Suite</h4>
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

    <!-- Interactive Scripts for 4-Design Switcher & Floorplan -->
    <script>
        lucide.createIcons();

        // 4 Design Metadata
        const designs = {
            1: {
                img: '/images/foodio_3d_landing.jpg',
                tag1: 'Primary 3D Object',
                title1: 'Floating Clay Mascot & POS Tablet',
                desc1: 'Suspended zero-gravity AI mascot with real-time cursor gyro-tilt.',
                tag2: 'Motion Physics',
                title2: 'Sine-Wave Floating & Parallax',
                desc2: 'Fluid bobbing physics and spatial depth layers that react to scroll.',
                tag3: 'SaaS Synergy',
                title3: 'WhatsApp-to-POS Conduit',
                desc3: 'Direct order packet pulses traveling straight into the Cloud POS register.'
            },
            2: {
                img: '/images/foodio_3d_linked.jpg',
                tag1: 'Primary 3D Object',
                title1: 'Suspended Keycard & Thermal Printer',
                desc1: '3D biometric security passkey and physical desktop receipt printer.',
                tag2: 'Motion Physics',
                title2: 'Curling Paper Receipt Physics',
                desc2: 'Simulated 3D thermal print head spitting out tickets with micro-shakes.',
                tag3: 'SaaS Synergy',
                title3: 'Instant Kitchen Ticket Dispatch',
                desc3: 'Automated ESC/POS thermal printing for kitchen cooking stations.'
            },
            3: {
                img: '/images/foodio_command_center.jpg',
                tag1: 'Primary 3D Object',
                title1: 'Multi-Tenant Operations Deck',
                desc1: '12-table dine-in floorplan, live customer stream & KDS kanban board.',
                tag2: 'Motion Physics',
                title2: 'Pulsing Halos & Steam Particles',
                desc2: 'Color-coded table status discs (Mint Vacant, Amber Dining, Cyan Billing).',
                tag3: 'SaaS Synergy',
                title3: 'Human Staff Takeover Mode',
                desc3: '1-click toggle to pause the AI bot and take over customer conversation manually.'
            },
            4: {
                img: '/images/foodio_customer_tracking.jpg',
                tag1: 'Primary 3D Object',
                title1: '3D Real-Time Fleet Radar',
                desc1: 'Low-poly 3D city map with moving delivery motorcycle and customer pin.',
                tag2: 'Motion Physics',
                title2: 'GPS Breadcrumb Ribbon Gliding',
                desc2: 'Motorcycle smoothly advances along illuminated turquoise route with radar pings.',
                tag3: 'SaaS Synergy',
                title3: 'Public Web Tracking Link',
                desc3: 'No app download required; customers track orders directly in browser.'
            }
        };

        function switchDesign(id, btn) {
            // Update button styles
            document.querySelectorAll('.design-tab').forEach(el => {
                el.className = 'design-tab py-3.5 px-4 rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-400 text-xs font-bold hover:border-slate-500 transition-all flex items-center justify-center gap-2';
            });
            btn.className = 'design-tab py-3.5 px-4 rounded-2xl border border-emerald-500 bg-emerald-500/20 text-emerald-300 text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg';

            // Transition image
            const img = document.getElementById('design-display-img');
            img.style.opacity = '0';
            
            setTimeout(() => {
                img.src = designs[id].img;
                document.getElementById('feature-tag-1').textContent = designs[id].tag1;
                document.getElementById('feature-title-1').textContent = designs[id].title1;
                document.getElementById('feature-desc-1').textContent = designs[id].desc1;

                document.getElementById('feature-tag-2').textContent = designs[id].tag2;
                document.getElementById('feature-title-2').textContent = designs[id].title2;
                document.getElementById('feature-desc-2').textContent = designs[id].desc2;

                document.getElementById('feature-tag-3').textContent = designs[id].tag3;
                document.getElementById('feature-title-3').textContent = designs[id].title3;
                document.getElementById('feature-desc-3').textContent = designs[id].desc3;

                img.style.opacity = '1';
            }, 180);
        }

        function inspectTable(id, status, desc, amount) {
            document.getElementById('inspect-title').textContent = `Selected: Table ${id} (${status})`;
            document.getElementById('inspect-desc').textContent = `Active Order: ${desc} (${amount})`;
        }
    </script>
</body>
</html>
