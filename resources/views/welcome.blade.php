@extends('layouts.app')

@section('content')
<div class="relative overflow-hidden flex-1 flex flex-col justify-center py-12 lg:py-20">
    <!-- Background glowing accents -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 right-10 w-[400px] h-[400px] bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-medium mb-8">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
            Platform Tur Virtual 360° Modern untuk Ruangmu
        </div>

        <!-- Main Headline -->
        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight sm:leading-none">
            Ciptakan Tampilan <span class="bg-gradient-to-r from-indigo-400 via-sky-300 to-indigo-200 bg-clip-text text-transparent">360 Derajat</span> Dari Mana Saja
        </h1>

        <!-- Subheadline -->
        <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Cukup ikuti langkah memotret 6 sisi sekeliling ruangan, atau upload fotomu. Hubungkan ruangan dengan panah navigasi, tambahkan info carousel, dan bagikan link-nya ke siapapun.
        </p>

        <!-- CTA Buttons -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            @auth
                <a href="{{ route('profile') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-xl shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 group">
                    Buka Profil & Workspace Saya
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-xl shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 group">
                    Mulai Buat Tur Gratis
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            @endauth

            <a href="{{ route('demo') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl glass-panel hover:bg-slate-800/80 text-slate-200 font-semibold transition-all flex items-center justify-center gap-2 group">
                <svg class="w-5 h-5 text-indigo-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                </svg>
                <span>Coba Demo Kamera 360 (Tanpa Login)</span>
            </a>
        </div>

        <!-- 4 Core Feature Cards -->
        <div class="mt-20 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-left">
            <!-- Card 1 -->
            <div class="glass-panel p-6 rounded-2xl border border-slate-800 hover:border-indigo-500/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Panduan Foto 6 Sisi</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Dilengkapi panduan overlay (onion-skinning) dari foto sebelumnya sehingga hasil pemotretan selalu pas dan presisi.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="glass-panel p-6 rounded-2xl border border-slate-800 hover:border-indigo-500/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Three.js Wide Angle</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Render 3D cubemap 60 FPS menggunakan Three.js dengan kamera sudut lebar yang mulus di berbagai perangkat.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="glass-panel p-6 rounded-2xl border border-slate-800 hover:border-indigo-500/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Hotspot Interaktif</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Tambahkan panah untuk berpindah view dan tombol info lengkap dengan modal carousel foto serta deskripsi.
                </p>
            </div>

            <!-- Card 4 -->
            <div class="glass-panel p-6 rounded-2xl border border-slate-800 hover:border-indigo-500/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Share Link Unik</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Bagikan tur melalui format link cantik <span class="text-indigo-400 font-mono text-xs">threesixtour/user/workspace</span> dengan starting point otomatis.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
