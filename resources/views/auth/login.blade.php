@extends('layouts.app')

@section('content')
<div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 glass-panel p-8 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl">
        <div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
            </div>
            <h2 class="text-center text-3xl font-extrabold text-white tracking-tight">
                Masuk ke Akun Anda
            </h2>
            <p class="mt-2 text-center text-sm text-slate-400">
                Belum memiliki akun?
                <a href="{{ route('register') }}" class="font-medium text-indigo-400 hover:text-indigo-300">
                    Daftar akun baru
                </a>
            </p>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf

            <!-- Email or Username -->
            <div>
                <label for="login" class="block text-sm font-medium text-slate-300 mb-1">Email atau Username</label>
                <input id="login" name="login" type="text" required value="{{ old('login') }}" autofocus
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="email@example.com atau username">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-1">Kata Sandi</label>
                <input id="password" name="password" type="password" required
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-700 rounded bg-slate-800">
                    <label for="remember" class="ml-2 block text-sm text-slate-400">
                        Ingat Saya
                    </label>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-3.5 px-4 rounded-xl text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Masuk ke TourBox
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-t border-slate-800">
            <a href="{{ route('demo') }}" class="text-xs text-slate-400 hover:text-slate-300 inline-flex items-center gap-1">
                <span>Ingin mencoba tanpa login?</span>
                <span class="text-indigo-400 font-medium">Buka Demo Mode &rarr;</span>
            </a>
        </div>
    </div>
</div>
@endsection
