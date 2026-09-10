<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Emergency Recovery — Foodio</title>
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
                            50: '#ecfdf5', 100: '#d1fae5',
                            500: '#10b981', 600: '#059669',
                            700: '#047857', 900: '#064e3b',
                        }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Header -->
    <header class="bg-slate-800/80 border-b border-slate-700/60 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/30">
                    🛡️
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight text-white leading-none">Foodio<span class="text-brand-400">.</span></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Platform Security Recovery</span>
                </div>
            </a>
            <a href="{{ route('admin.login') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
                ← Back to Superadmin Login
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-slate-800 border border-slate-700 rounded-3xl p-8 sm:p-10 shadow-2xl">
            
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                    🚨
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Superadmin Recovery</h1>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                    Reset your master password using your server deployment security key (configured in server environment).
                </p>
            </div>

            @if($errors->any())
                <div class="mb-6 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $e)
                        <div class="flex items-center gap-1.5">⚠️ {{ $e }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.forgot-password.submit') }}" class="space-y-5">
                @csrf

                <!-- Recovery Key -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                        Emergency Recovery Secret / APP_KEY *
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🔐</span>
                        <input
                            type="password"
                            id="recovery_key_input"
                            name="recovery_key"
                            required
                            placeholder="Enter server recovery key"
                            class="w-full pl-10 pr-12 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition"
                        >
                        <button type="button" onclick="toggleField('recovery_key_input', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-400 p-1 text-sm">
                            👁
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">Master recovery key or deployment <code>APP_KEY</code>.</p>
                </div>

                <!-- New Master Password -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">New Master Password *</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🔑</span>
                        <input
                            type="password"
                            id="new_password_input"
                            name="new_password"
                            required
                            minlength="8"
                            placeholder="Minimum 8 characters"
                            class="w-full pl-10 pr-12 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition"
                        >
                        <button type="button" onclick="toggleField('new_password_input', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-400 p-1 text-sm">
                            👁
                        </button>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">Confirm New Password *</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">✓</span>
                        <input
                            type="password"
                            id="new_password_confirmation_input"
                            name="new_password_confirmation"
                            required
                            minlength="8"
                            placeholder="Repeat new master password"
                            class="w-full pl-10 pr-12 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition"
                        >
                        <button type="button" onclick="toggleField('new_password_confirmation_input', this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-400 p-1 text-sm">
                            👁
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-brand-600/30 transition active:scale-[0.99] flex items-center justify-center gap-2"
                >
                    <span>Reset Master Password</span>
                    <span>→</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-slate-700/60 text-center text-xs text-slate-400">
                <a href="{{ route('admin.login') }}" class="text-brand-400 hover:underline">
                    ← Return to Superadmin Sign In
                </a>
            </div>

        </div>
    </main>

    <footer class="py-6 text-center text-xs text-slate-500 border-t border-slate-800">
        &copy; {{ date('Y') }} Foodio Platform Security Operations.
    </footer>

    <script>
    function toggleField(id, btn) {
        const field = document.getElementById(id);
        if (field.type === 'password') {
            field.type = 'text';
            btn.textContent = '🙈';
        } else {
            field.type = 'password';
            btn.textContent = '👁';
        }
    }
    </script>
</body>
</html>
