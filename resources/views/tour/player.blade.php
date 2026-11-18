@extends('layouts.app', ['hideNav' => true])

@section('content')
<div class="relative w-screen h-screen overflow-hidden bg-slate-950 select-none"
     x-data="tourPlayer({
         views: {{ Js::from($viewsPayload) }},
         startingViewId: {{ Js::from($startingViewId) }},
         workspaceTitle: '{{ $workspace->title }}',
         authorName: '{{ $user->name }}'
     })"
     x-init="initPlayer()">

    <!-- 3D Three.js Canvas -->
    <div id="three-container" class="absolute inset-0 w-full h-full"></div>

    <!-- Top Overlay HUD -->
    <header class="absolute top-4 inset-x-4 z-30 flex items-center justify-between pointer-events-none">
        <div class="glass-panel px-4 py-2.5 rounded-2xl flex items-center gap-3 pointer-events-auto border border-slate-700/80 shadow-2xl">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-sky-400 flex items-center justify-center text-white font-bold text-xs shadow-md">
                360
            </div>
            <div>
                <h1 class="text-sm font-bold text-white tracking-tight">{{ $workspace->title }}</h1>
                <p class="text-[11px] text-slate-400">Oleh <span class="text-indigo-400 font-medium">{{ $user->name }}</span> • <span x-text="currentView ? currentView.name : ''"></span></p>
            </div>
        </div>

        <!-- Controls: Fullscreen & Reset View -->
        <div class="glass-panel p-1.5 rounded-2xl flex items-center gap-1 pointer-events-auto border border-slate-700/80 shadow-2xl">
            <button @click="resetCamera()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Reset Arah Pandang">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button @click="toggleFullscreen()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Layar Penuh">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Bottom Navigation Bar (Thumbnails) -->
    <div class="absolute bottom-4 inset-x-4 z-30 pointer-events-none">
        <div class="max-w-2xl mx-auto glass-panel p-2.5 rounded-2xl border border-slate-700/80 shadow-2xl pointer-events-auto flex items-center justify-center gap-2 overflow-x-auto">
            <template x-for="v in views" :key="v.id">
                <button @click="switchView(v.id)"
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                        :class="currentView && currentView.id === v.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="currentView && currentView.id === v.id ? 'bg-white' : 'bg-slate-600'"></span>
                    <span x-text="v.name"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Reusable Information Modal Component (V-FR-04, FR-08, FR-09, FR-10) -->
    <x-info-modal />
</div>
@endsection
