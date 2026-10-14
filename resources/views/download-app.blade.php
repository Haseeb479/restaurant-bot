<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Foodio Android App (.APK) — High Speed Ordering</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white font-['Plus_Jakarta_Sans',sans-serif]">

    <!-- Top Bar -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 font-black">
                    F
                </div>
                <span class="text-xl font-black tracking-tight text-white">Foodio<span class="text-emerald-400">.</span></span>
            </a>
            <a href="/" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 transition">
                <span>← Back to Website</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-xl mx-auto px-4 py-8 flex-1 flex flex-col justify-center">

        <!-- App Icon & Title Badge -->
        <div class="text-center mb-6">
            <div class="relative inline-block">
                <div class="w-24 h-24 mx-auto rounded-3xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shadow-2xl shadow-emerald-500/30 border border-emerald-300/30 mb-4 animate-bounce-subtle">
                    <svg class="w-14 h-14 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <!-- Android Robot Icon -->
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993s-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5902 8.4116 13.8533 8.125 12 8.125c-1.8534 0-3.5902.2866-5.1368.8247L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.784h24c-.3432-4.1251-2.6889-7.5973-6.1185-9.4626"/>
                    </svg>
                </div>
                <span class="absolute -top-1 -right-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-slate-950 shadow-md">
                    Android APK
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Foodio Android App
            </h1>
            <p class="text-slate-400 text-sm mt-1.5 max-w-sm mx-auto">
                Get the fast, native mobile experience with instant ordering, live notifications & table dining.
            </p>
        </div>

        <!-- Download Action Card -->
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 sm:p-7 shadow-xl shadow-black/40 backdrop-blur-xl mb-6">
            
            <div class="flex items-center justify-between pb-5 border-b border-slate-700/60 mb-5 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="font-bold text-slate-200">Latest Release v{{ $version }}</span>
                </div>
                <span>Standalone APK • Android 7.0+</span>
            </div>

            <a href="{{ route('download.app') }}" class="group relative flex items-center justify-center gap-3 w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-base shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                    <path d="M12 16l4-5h-3V4h-2v7H8l4 5zm9 4H3v-2h18v2z"/>
                </svg>
                <span>Download Android APK</span>
                @if($fileSize)
                    <span class="text-xs bg-slate-950/20 px-2 py-0.5 rounded-md font-bold">({{ $fileSize }})</span>
                @endif
            </a>

            <p class="text-[11px] text-center text-slate-400 mt-3">
                Direct safe download from Foodio servers • No Google Play Store account required
            </p>
        </div>

        <!-- Chrome Installation Guide -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-5 mb-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                How to Install in Google Chrome
            </h2>

            <ol class="space-y-3 text-xs text-slate-300">
                <li class="flex items-start gap-2.5">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold flex items-center justify-center text-[10px]">1</span>
                    <div>
                        <strong class="text-white">Tap Download:</strong> If Chrome shows <em>"File might be harmful"</em>, tap <strong>"Download anyway"</strong> (standard Android notice for direct APKs).
                    </div>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold flex items-center justify-center text-[10px]">2</span>
                    <div>
                        <strong class="text-white">Open the File:</strong> Tap <strong>"Open"</strong> in Chrome's download notification once complete.
                    </div>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold flex items-center justify-center text-[10px]">3</span>
                    <div>
                        <strong class="text-white">Allow & Install:</strong> If prompted, tap <strong>Settings</strong> $\rightarrow$ enable <strong>"Allow from this source"</strong> $\rightarrow$ tap <strong>Install</strong>.
                    </div>
                </li>
            </ol>
        </div>

        <!-- App Features Highlights -->
        <div class="grid grid-cols-2 gap-3 text-left">
            <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5">
                <div class="text-emerald-400 font-bold text-xs mb-1">⚡ Ultra Fast</div>
                <div class="text-[11px] text-slate-400">Instant menu loading with zero browser lag or reload delays.</div>
            </div>
            <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5">
                <div class="text-emerald-400 font-bold text-xs mb-1">🔔 Live Orders</div>
                <div class="text-[11px] text-slate-400">Real-time status updates directly on your mobile device.</div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-4 text-center text-xs text-slate-500">
        Foodio Restaurant & Dine-In App • Standalone Offline APK
    </footer>

</body>
</html>
