@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Verify Your Email</h2>
            <p class="mt-2 text-sm text-slate-600">
                A verification link has been generated for <span class="font-semibold text-slate-800">{{ $restaurant->email }}</span>.
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-3xl border border-slate-200 sm:px-10">
            @if (session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2">
                    <span>✓</span> {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
                    <span>⚠️</span> {{ session('error') }}
                </div>
            @endif

            <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                Please click the button in your email or tap the verification link below to verify your account and proceed to choosing your subscription plan.
            </p>

            @if (!empty($rawToken))
                <div class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Direct Verification Link</span>
                    <a href="{{ route('onboarding.verify', ['id' => $restaurant->id, 'token' => $rawToken]) }}"
                       class="text-xs font-semibold text-brand-600 hover:text-brand-700 underline break-all">
                        {{ route('onboarding.verify', ['id' => $restaurant->id, 'token' => $rawToken]) }}
                    </a>
                </div>
            @endif

            <form action="{{ route('onboarding.resend-verification', $restaurant->id) }}" method="POST" class="space-y-4">
                @csrf
                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold shadow transition flex items-center justify-center gap-2">
                    <span>✉️</span> Resend Verification Link
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('landing.owner-login-page') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                    ← Back to Sign In
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
