@extends('layouts.app')

@section('content')
<div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 glass-panel p-8 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl"
         x-data="{ username: '{{ old('username', '') }}' }">
        <div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h2 class="text-center text-3xl font-extrabold text-white tracking-tight">
                Daftar Akun TourBox
            </h2>
            <p class="mt-2 text-center text-sm text-slate-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-indigo-400 hover:text-indigo-300">
                    Masuk di sini
                </a>
            </p>
        </div>

        <form class="mt-8 space-y-5" action="{{ route('register') }}" method="POST">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300 mb-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="Contoh: Budi Santoso">
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-slate-300 mb-1">Username (Unik)</label>
                <div class="relative">
                    <input id="username" name="username" type="text" required x-model="username"
                           class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                           placeholder="budisantoso">
                </div>
                <p class="mt-1 text-xs text-slate-400">
                    Preview Link Tur: <span class="text-indigo-400 font-mono">https://threesixtour/<span x-text="username ? username.toLowerCase().replace(/[^a-z0-9_-]/g, '') : 'nama-user'">nama-user</span>/nama-workspace</span>
                </p>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300 mb-1">Alamat Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="budi@example.com">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-1">Kata Sandi</label>
                <input id="password" name="password" type="password" required
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="Minimal 6 karakter">
            </div>

            <!-- Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-1">Konfirmasi Kata Sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                       placeholder="Ulangi kata sandi">
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-3.5 px-4 rounded-xl text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-t border-slate-800">
            <a href="{{ route('demo') }}" class="text-xs text-slate-400 hover:text-slate-300 inline-flex items-center gap-1">
                <span>Ingin coba langsung tanpa daftar?</span>
                <span class="text-indigo-400 font-medium">Buka Demo &rarr;</span>
            </a>
        </div>
    </div>
</div>
@endsection
