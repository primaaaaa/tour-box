@extends('layouts.app', ['hideNav' => true])

@section('content')
@php
    $viewsJson = $workspace->views->map(function ($view) {
        return [
            'id' => $view->id,
            'name' => $view->name,
            'custom_id' => $view->custom_id,
            'has_nadir_censor' => (bool) $view->has_nadir_censor,
            'nadir_censor_type' => $view->nadir_censor_type,
            'default_yaw' => $view->default_yaw,
            'default_pitch' => $view->default_pitch,
            'view_type' => $view->view_type ?? 'cubemap',
            'cubemap' => $view->getCubemapUrls(),
            'panels' => $view->getPanelsUrls(),
            'hotspots' => $view->hotspots->map(function ($h) {
                return [
                    'id' => $h->id,
                    'type' => $h->type,
                    'pos_x' => $h->pos_x,
                    'pos_y' => $h->pos_y,
                    'pos_z' => $h->pos_z,
                    'target_view_id' => $h->target_view_id,
                    'target_view_name' => $h->targetView?->name,
                    'info_title' => $h->info_title,
                    'info_caption' => $h->info_caption,
                    'carousel' => $h->carouselImages->map(fn($img) => $img->getUrl())->values()->all(),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $shareUrl = url('/' . $user->username . '/' . $workspace->slug);
    $initialViewId = $workspace->starting_view_id ?? ($workspace->views->first()?->id ?? null);
@endphp

<div class="relative w-screen h-screen overflow-hidden bg-slate-950 select-none"
     x-data="tourEditor({
         workspaceId: {{ $workspace->id }},
         views: {{ Js::from($viewsJson) }},
         initialViewId: {{ Js::from($initialViewId) }},
         startingViewId: {{ Js::from($workspace->starting_view_id) }},
         shareUrl: '{{ $shareUrl }}'
     })"
     x-init="initEditor()">

    <!-- 3D Viewport Canvas Container -->
    <div id="three-container" class="absolute inset-0 w-full h-full"></div>

    <!-- Top Toolbar Header -->
    <header class="absolute top-4 inset-x-4 z-30 flex items-center justify-between pointer-events-none">
        <!-- Left: Workspace Info & Back -->
        <div class="glass-panel px-4 py-2.5 rounded-2xl flex items-center gap-3 pointer-events-auto border border-slate-700 shadow-xl">
            <a href="{{ route('profile') }}" class="p-1.5 rounded-xl hover:bg-slate-800 text-slate-400 hover:text-white transition-colors" title="Kembali ke Profil">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="h-5 w-px bg-slate-700"></div>
            <div>
                <h2 class="text-sm font-bold text-white leading-tight">{{ $workspace->title }}</h2>
                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                    <span x-text="currentView ? currentView.name : 'Belum ada view'"></span>
                    <span class="text-slate-600">•</span>
                    <span class="font-mono text-indigo-400" x-text="currentView ? ('#' + (currentView.custom_id || currentView.id)) : ''"></span>
                </div>
            </div>
        </div>

        <!-- Center: Current View Controls (Nadir, Starting Point, Custom ID) -->
        <template x-if="currentView">
            <div class="glass-panel px-4 py-2 rounded-2xl flex items-center gap-2 pointer-events-auto border border-slate-700 shadow-xl">
                <!-- Starting Point Button -->
                <button @click="setAsStartingPoint()"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-medium flex items-center gap-1.5 transition-all"
                        :class="startingViewId === currentView.id ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'hover:bg-slate-800 text-slate-400 hover:text-slate-200'">
                    <svg class="w-4 h-4" :class="startingViewId === currentView.id ? 'fill-amber-400 text-amber-400' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <span x-text="startingViewId === currentView.id ? 'Starting Point' : 'Jadikan Titik Mulai'"></span>
                </button>

                <div class="h-4 w-px bg-slate-700"></div>

                <!-- Nadir Censor Toggle (FR-14) -->
                <button @click="toggleNadirCensor()"
                        type="button"
                        class="px-3 py-1.5 rounded-xl text-xs font-medium flex items-center gap-1.5 transition-all"
                        :class="currentView.has_nadir_censor ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 text-slate-400 hover:text-slate-200'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                    <span x-text="currentView.has_nadir_censor ? 'Sensor Bawah Aktif' : 'Sensor Bagian Bawah'"></span>
                </button>

                <div class="h-4 w-px bg-slate-700"></div>

                <!-- Edit View Name & ID -->
                <button @click="editViewModalOpen = true"
                        type="button"
                        class="p-1.5 rounded-xl hover:bg-slate-800 text-slate-400 hover:text-white"
                        title="Ubah Nama & ID View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
            </div>
        </template>

        <!-- Right: Share Tour Link -->
        <div class="glass-panel px-3 py-2 rounded-2xl flex items-center gap-2 pointer-events-auto border border-slate-700 shadow-xl">
            <a :href="shareUrl" target="_blank"
               class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>Pratinjau Tur</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            <button @click="copyShareLink()"
                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-600/30 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                <span x-text="copiedLink ? 'Tersalin!' : 'Bagikan Link'"></span>
            </button>
        </div>
    </header>

    <!-- Floating Mode Selector Toolbar (Left side) -->
    <div class="absolute left-4 top-1/2 -translate-y-1/2 z-30 flex flex-col gap-2 pointer-events-auto">
        <div class="glass-panel p-2 rounded-2xl border border-slate-700 shadow-2xl flex flex-col gap-2">
            <!-- Mode Navigate -->
            <button @click="setMode('navigate')"
                    type="button"
                    class="p-3 rounded-xl flex items-center justify-center transition-all group relative"
                    :class="mode === 'navigate' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/40' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                </svg>
                <span class="absolute left-full ml-3 px-2 py-1 rounded bg-slate-900 text-white text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity border border-slate-800 shadow">
                    Mode Jelajah (Pan & Look)
                </span>
            </button>

            <!-- Mode Add Arrow (FR-05) -->
            <button @click="setMode('add_arrow')"
                    type="button"
                    class="p-3 rounded-xl flex items-center justify-center transition-all group relative"
                    :class="mode === 'add_arrow' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/40' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                <span class="absolute left-full ml-3 px-2 py-1 rounded bg-slate-900 text-white text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity border border-slate-800 shadow">
                    + Tambah Panah Navigasi
                </span>
            </button>

            <!-- Mode Add Info (FR-07) -->
            <button @click="setMode('add_info')"
                    type="button"
                    class="p-3 rounded-xl flex items-center justify-center transition-all group relative"
                    :class="mode === 'add_info' ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="absolute left-full ml-3 px-2 py-1 rounded bg-slate-900 text-white text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity border border-slate-800 shadow">
                    + Tambah Tombol Informasi
                </span>
            </button>
        </div>

        <!-- Mode Instruction Banner -->
        <div x-show="mode !== 'navigate'" x-cloak
             class="glass-panel px-3 py-2 rounded-xl border border-indigo-500/30 text-indigo-300 text-xs max-w-[200px] shadow-xl">
            <span x-text="mode === 'add_arrow' ? '👉 Klik pada area 360 untuk meletakkan panah penghubung' : 'ℹ️ Klik pada objek 360 untuk meletakkan tombol informasi'"></span>
        </div>
    </div>

    <!-- Bottom Views Filmstrip Bar -->
    <div class="absolute bottom-4 inset-x-4 z-30 pointer-events-none">
        <div class="max-w-4xl mx-auto glass-panel p-3 rounded-2xl border border-slate-700 shadow-2xl pointer-events-auto flex items-center gap-3 overflow-x-auto">
            <!-- Add View Button -->
            <button @click="openAddViewModal()"
                    class="shrink-0 w-24 h-20 rounded-xl border-2 border-dashed border-indigo-500/50 hover:border-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 flex flex-col items-center justify-center gap-1 transition-all group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="text-[11px] font-semibold">+ View Baru</span>
            </button>

            <!-- Views Thumbnails Strip -->
            <template x-for="v in views" :key="v.id">
                <div @click="switchView(v.id)"
                     class="shrink-0 w-28 h-20 rounded-xl overflow-hidden relative cursor-pointer border-2 transition-all group"
                     :class="currentView && currentView.id === v.id ? 'border-indigo-500 ring-2 ring-indigo-500/50 scale-105' : 'border-slate-700 hover:border-slate-500 opacity-75 hover:opacity-100'">

                    <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                        <img x-show="v.cubemap && v.cubemap.pz" :src="v.cubemap ? v.cubemap.pz : ''" class="w-full h-full object-cover">
                        <span x-show="!v.cubemap || !v.cubemap.pz" class="text-xs text-slate-500 font-mono">360</span>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 p-1.5 bg-gradient-to-t from-black/90 via-black/50 to-transparent flex items-center justify-between">
                        <span class="text-[11px] font-medium text-white truncate max-w-[80px]" x-text="v.name"></span>
                        <span x-show="startingViewId === v.id" class="text-amber-400 text-xs">★</span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- MODAL 1: Tambah View 360 (Kamera / Upload) -->
    <div x-show="addViewModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="closeAddViewModal()"
             class="glass-panel max-w-2xl w-full p-6 sm:p-8 rounded-3xl border border-slate-700 shadow-2xl relative">
            <button @click="closeAddViewModal()" class="absolute top-6 right-6 text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <h3 class="text-2xl font-extrabold text-white tracking-tight">Tambah View 360 Derajat</h3>
            <p class="text-sm text-slate-400 mt-1">
                Pilih metode: memotret sekeliling 6 sisi dengan kamera ponsel/webcam atau upload 6 file gambar cubemap.
            </p>

            <!-- Tab Headers -->
            <div class="flex items-center gap-2 mt-6 p-1 bg-slate-800/80 rounded-xl border border-slate-700">
                <button @click="captureTab = 'camera'; startWebcamCapture()"
                        type="button"
                        class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-all"
                        :class="captureTab === 'camera' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    </svg>
                    Potret 6 Sisi Kamera
                </button>
                <button @click="captureTab = 'upload'; stopWebcamCapture()"
                        type="button"
                        class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-all"
                        :class="captureTab === 'upload' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload 6 Gambar Manual
                </button>
            </div>

            <!-- View Info Inputs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nama 360 View</label>
                    <input type="text" x-model="newViewName" placeholder="Contoh: Ruang Tamu"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">ID View (Opsional, FR-03)</label>
                    <input type="text" x-model="newViewCustomId" placeholder="living-room"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- TAB 1: Camera 6-Side Smart Capture -->
            <div x-show="captureTab === 'camera'" class="mt-4">
                <x-camera-viewfinder
                    video-id="editor-capture-video"
                    ar-canvas-id="editor-ar-sphere-canvas"
                    left-canvas-id="editor-puzzle-left-canvas"
                    right-canvas-id="editor-puzzle-right-canvas"
                    on-snap="snapPhoto()"
                />
            </div>

            <!-- TAB 2: Upload 6 Images Manual -->
            <div x-show="captureTab === 'upload'" class="mt-6">
                <p class="text-xs text-slate-400 mb-3">
                    Unggah 6 foto berformat rasio 1:1 untuk setiap sisi kubemap:
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="face in ['pz', 'px', 'nz', 'nx', 'py', 'ny']" :key="face">
                        <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700 text-center flex flex-col items-center justify-center">
                            <span class="text-xs font-bold text-slate-300" x-text="getFaceLabel(face)"></span>
                            <div class="w-16 h-16 rounded-lg bg-slate-900 border border-slate-700 my-2 overflow-hidden flex items-center justify-center">
                                <img x-show="capturedFaces[face]" :src="capturedFaces[face]" class="w-full h-full object-cover">
                                <span x-show="!capturedFaces[face]" class="text-[10px] text-slate-500" x-text="face.toUpperCase()"></span>
                            </div>
                            <label class="cursor-pointer px-2.5 py-1 rounded bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 text-[11px] font-medium transition-colors">
                                Pilih File
                                <input type="file" accept="image/*" class="hidden" @change="handleFileUpload($event, face)">
                            </label>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Action Footer -->
            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <button @click="closeAddViewModal()" type="button"
                        class="px-4 py-2.5 rounded-xl text-sm text-slate-400 hover:text-white transition-colors">
                    Batal
                </button>
                <button @click="saveNewView()"
                        :disabled="isSavingView || !canSaveView()"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <span x-text="isSavingView ? 'Menyimpan 360 View...' : 'Selesai & Tambahkan ke Tur'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Tambah Panah Navigasi -->
    <div x-show="addArrowModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="addArrowModalOpen = false"
             class="glass-panel max-w-md w-full p-6 sm:p-8 rounded-3xl border border-slate-700 shadow-2xl relative">
            <h3 class="text-xl font-bold text-white tracking-tight">Hubungkan ke View Lain (FR-05, FR-06)</h3>
            <p class="text-xs text-slate-400 mt-1">
                Pilih view tujuan yang akan dibuka ketika panah ini ditekan oleh pengunjung.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Pilih View Tujuan</label>
                    <select x-model="selectedTargetViewId"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Pilih View Tujuan --</option>
                        <template x-for="v in availableTargetViews" :key="v.id">
                            <option :value="v.id" x-text="v.name + ' (' + (v.custom_id || v.id) + ')'"></option>
                        </template>
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300">
                    Posisi 3D: <span class="font-mono" x-text="'[' + pendingHotspotPos.pos_x + ', ' + pendingHotspotPos.pos_y + ', ' + pendingHotspotPos.pos_z + ']'"></span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <button @click="addArrowModalOpen = false" type="button"
                        class="px-4 py-2 rounded-xl text-sm text-slate-400 hover:text-white">
                    Batal
                </button>
                <button @click="saveNavigationHotspot()"
                        :disabled="!selectedTargetViewId || isSavingHotspot"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30">
                    Simpan Panah Navigasi
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Tambah Tombol Informasi -->
    <div x-show="addInfoModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="addInfoModalOpen = false"
             class="glass-panel max-w-lg w-full p-6 sm:p-8 rounded-3xl border border-slate-700 shadow-2xl relative">
            <h3 class="text-xl font-bold text-white tracking-tight">Tambah Hotspot Informasi (FR-07 - FR-10)</h3>
            <p class="text-xs text-slate-400 mt-1">
                Masukkan judul informasi, gambar carousel, dan caption keterangan objek.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Judul Informasi (FR-08)</label>
                    <input type="text" x-model="infoHotspotTitle" placeholder="Contoh: Lukisan Antik & Lampu"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Gambar Carousel (FR-09)</label>
                    <input type="file" multiple accept="image/*" @change="handleInfoImagesUpload($event)"
                           class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                    <p class="text-[11px] text-slate-500 mt-1">Pilih lebih dari 1 gambar untuk ditampilkan dalam slider/carousel.</p>

                    <div class="flex items-center gap-2 mt-2 overflow-x-auto py-1">
                        <template x-for="(img, i) in infoCarouselPreviews" :key="i">
                            <div class="relative w-14 h-14 rounded-lg overflow-hidden border border-slate-700 shrink-0">
                                <img :src="img" class="w-full h-full object-cover">
                                <button @click="infoCarouselPreviews.splice(i, 1)" class="absolute top-0.5 right-0.5 bg-black/70 rounded-full p-0.5 text-rose-400 text-xs">×</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Caption / Keterangan (FR-10)</label>
                    <textarea x-model="infoHotspotCaption" rows="3" placeholder="Tuliskan cerita atau detail objek ini..."
                              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <button @click="addInfoModalOpen = false" type="button"
                        class="px-4 py-2 rounded-xl text-sm text-slate-400 hover:text-white">
                    Batal
                </button>
                <button @click="saveInfoHotspot()"
                        :disabled="!infoHotspotTitle || isSavingHotspot"
                        class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-sm font-semibold shadow-lg shadow-sky-600/30">
                    Simpan Informasi
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Edit View Name & Custom ID -->
    <div x-show="editViewModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="editViewModalOpen = false"
             class="glass-panel max-w-md w-full p-6 rounded-3xl border border-slate-700 shadow-2xl relative">
            <h3 class="text-xl font-bold text-white tracking-tight">Ubah Nama & ID View</h3>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Nama View</label>
                    <input type="text" x-model="currentView.name"
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">ID Unik (FR-03)</label>
                    <input type="text" x-model="currentView.custom_id"
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm font-mono">
                </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <button @click="deleteCurrentView()" type="button" class="text-rose-400 hover:text-rose-300 text-xs font-semibold">
                    Hapus View Ini
                </button>
                <div class="flex items-center gap-2">
                    <button @click="editViewModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs text-slate-400 hover:text-white">Batal</button>
                    <button @click="saveViewDetails()" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Information Modal Component (FR-08, FR-09, FR-10) -->
    <x-info-modal />
</div>
@endsection
