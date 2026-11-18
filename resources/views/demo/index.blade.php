@extends('layouts.app')

@section('content')
<div class="relative flex-1 w-full flex flex-col h-[calc(100vh-64px)] overflow-hidden bg-slate-950 select-none"
     x-data="demoExperience({
         views: {{ Js::from($views) }}
     })"
     x-init="initDemo()">

    <!-- 3D Three.js Viewport -->
    <div id="three-container" class="absolute inset-0 w-full h-full"></div>

    <!-- Top Overlay Badge & Actions -->
    <div class="absolute top-4 inset-x-4 z-30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel px-4 py-2 rounded-2xl flex items-center gap-3 pointer-events-auto border border-indigo-500/30 shadow-2xl">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                Demo Mode (FR-20)
            </span>
            <div class="text-xs text-slate-300">
                View: <span class="font-bold text-white" x-text="currentView ? currentView.name : ''"></span>
            </div>
        </div>

        <div class="flex items-center gap-2 pointer-events-auto">
            <!-- CTA: Coba Potret Kamera Sekarang -->
            <button @click="openCameraModal()"
                    type="button"
                    class="px-4 py-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 text-white font-bold text-xs sm:text-sm shadow-xl shadow-indigo-600/40 flex items-center gap-2 transition-all hover:scale-105 active:scale-95">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>📸 Coba Potret Kamera 6 Sisi</span>
            </button>

            <!-- Nadir Sensor Toggle -->
            <template x-if="currentView">
                <button @click="toggleNadirCensor()"
                        type="button"
                        class="glass-panel px-3 py-2 rounded-2xl text-xs font-medium flex items-center gap-1.5 transition-all"
                        :class="currentView.has_nadir_censor ? 'bg-indigo-600 text-white border-indigo-500' : 'text-slate-300 hover:text-white border-slate-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                    <span class="hidden sm:inline" x-text="currentView.has_nadir_censor ? 'Sensor Bawah Aktif' : 'Sensor Bawah (Nadir)'"></span>
                </button>
            </template>

            <!-- Register link -->
            <a href="{{ route('register') }}"
               class="glass-panel px-3.5 py-2 rounded-2xl text-indigo-400 hover:text-white hover:bg-slate-800 text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1">
                <span>Daftar Akun</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>

    <!-- Success Floating Banner -->
    <div x-show="showSuccessBanner" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute top-20 inset-x-4 max-w-lg mx-auto z-40">
        <div class="glass-panel p-4 rounded-2xl border border-emerald-500/40 bg-emerald-950/80 text-emerald-300 shadow-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🎉</span>
                <div>
                    <h4 class="text-sm font-bold text-white">Hasil Foto Kamera Anda Berhasil Dimuat!</h4>
                    <p class="text-xs text-emerald-200/90 mt-0.5">Geser layar (drag) untuk melihat sekeliling dalam 360°.</p>
                </div>
            </div>
            <button @click="showSuccessBanner = false" class="text-emerald-400 hover:text-white p-1">✕</button>
        </div>
    </div>

    <!-- Bottom Views Filmstrip Bar -->
    <div class="absolute bottom-4 inset-x-4 z-30 pointer-events-none">
        <div class="max-w-2xl mx-auto glass-panel p-2.5 rounded-2xl border border-slate-700/80 shadow-2xl pointer-events-auto flex items-center justify-center gap-2 overflow-x-auto">
            <template x-for="v in views" :key="v.id">
                <button @click="switchView(v.id)"
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                        :class="currentView && currentView.id === v.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                    <span class="w-2 h-2 rounded-full" :class="v.is_user_capture ? 'bg-emerald-400 animate-pulse' : (currentView && currentView.id === v.id ? 'bg-white' : 'bg-slate-600')"></span>
                    <span x-text="v.name"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Reusable Camera Modal Component -->
    <x-camera-modal videoId="demo-capture-video" canvasId="demo-guide-canvas" />

    <!-- Reusable Information Modal Component -->
    <x-info-modal />
</div>
@endsection
