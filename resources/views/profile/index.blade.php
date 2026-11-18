@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full" x-data="{ createModalOpen: false, copyTooltip: '' }">
    <!-- Header & Profile Info -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-8 border-b border-slate-800">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-sky-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-500/20">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $user->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        @ {{ $user->username }}
                    </span>
                    <span class="text-xs text-slate-400">{{ $user->email }}</span>
                </div>
            </div>
        </div>

        <button @click="createModalOpen = true"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-600/30 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat Workspace Baru
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 my-8">
        <div class="glass-panel p-5 rounded-2xl border border-slate-800">
            <p class="text-xs font-medium text-slate-400">Total Workspace</p>
            <p class="text-3xl font-extrabold text-white mt-1">{{ $workspaces->count() }}</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-slate-800">
            <p class="text-xs font-medium text-slate-400">Total 360 Views</p>
            <p class="text-3xl font-extrabold text-indigo-400 mt-1">{{ $workspaces->sum('views_count') }}</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 col-span-2 sm:col-span-1">
            <p class="text-xs font-medium text-slate-400">Status Akun</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span class="text-sm font-semibold text-slate-200">Aktif ({{ ucfirst($user->role) }})</span>
            </div>
        </div>
    </div>

    <!-- Workspaces Section -->
    <div class="mt-8">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <span>Daftar Workspace Tur Anda</span>
            <span class="text-xs font-normal text-slate-400">({{ $workspaces->count() }} workspace)</span>
        </h2>

        @if($workspaces->isEmpty())
            <!-- Empty State -->
            <div class="glass-panel text-center py-16 px-4 rounded-3xl border border-dashed border-slate-800">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Belum ada workspace</h3>
                <p class="text-slate-400 text-sm max-w-sm mx-auto mt-1 mb-6">
                    Buat workspace pertama Anda untuk mulai memotret atau mengunggah pemandangan 360 derajat.
                </p>
                <button @click="createModalOpen = true"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition-all shadow-lg shadow-indigo-600/30">
                    + Buat Workspace Pertama
                </button>
            </div>
        @else
            <!-- Grid of Workspaces -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workspaces as $ws)
                    @php
                        $shareUrl = url('/' . $user->username . '/' . $ws->slug);
                    @endphp
                    <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden flex flex-col hover:border-slate-700 transition-all group">
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-white group-hover:text-indigo-400 transition-colors">
                                        {{ $ws->title }}
                                    </h3>
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-800 text-slate-300">
                                        {{ $ws->views_count }} View{{ $ws->views_count == 1 ? '' : 's' }}
                                    </span>
                                </div>

                                <p class="text-sm text-slate-400 line-clamp-2">
                                    {{ $ws->description ?: 'Tidak ada deskripsi workspace.' }}
                                </p>
                            </div>

                            <!-- Share URL Box -->
                            <div class="mt-6 pt-4 border-t border-slate-800/80">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Share Link Format:</label>
                                <div class="flex items-center gap-1.5 p-2 rounded-xl bg-slate-800/60 border border-slate-700/60">
                                    <input type="text" readonly value="{{ $shareUrl }}"
                                           class="bg-transparent text-xs text-indigo-300 font-mono flex-1 focus:outline-none truncate select-all">
                                    <button @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copyTooltip = '{{ $ws->id }}'; setTimeout(() => copyTooltip = '', 2000)"
                                            type="button"
                                            class="p-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 hover:text-white transition-colors relative"
                                            title="Salin Tautan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                        <span x-show="copyTooltip === '{{ $ws->id }}'" x-cloak
                                              class="absolute -top-8 right-0 bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded shadow whitespace-nowrap">
                                            Tersalin!
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Actions -->
                        <div class="bg-slate-900/60 px-6 py-3.5 border-t border-slate-800 flex items-center justify-between text-xs">
                            <form action="{{ route('workspaces.destroy', $ws) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus workspace {{ $ws->title }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-400 transition-colors">
                                    Hapus
                                </button>
                            </form>

                            <div class="flex items-center gap-3">
                                @if($ws->views_count > 0)
                                    <a href="{{ $shareUrl }}" target="_blank" class="text-slate-300 hover:text-white font-medium flex items-center gap-1">
                                        Lihat Tur
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @endif
                                <a href="{{ route('workspaces.editor', $ws) }}"
                                   class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold flex items-center gap-1.5 transition-colors">
                                    Buka 360 Editor
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Buat Workspace Baru -->
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="createModalOpen = false"
             class="glass-panel max-w-lg w-full p-6 sm:p-8 rounded-3xl border border-slate-700 shadow-2xl relative">
            <button @click="createModalOpen = false" class="absolute top-6 right-6 text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <h3 class="text-2xl font-extrabold text-white tracking-tight">Buat Workspace Baru</h3>
            <p class="text-sm text-slate-400 mt-1">
                Workspace digunakan untuk mengelompokkan serangkaian 360 view untuk satu tempat / properti.
            </p>

            <form action="{{ route('workspaces.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-300 mb-1">Judul Workspace</label>
                    <input id="title" name="title" type="text" required
                           class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                           placeholder="Contoh: Villa Bali Indah">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-300 mb-1">Deskripsi (Opsional)</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                              placeholder="Deskripsi singkat mengenai tur ini..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button @click="createModalOpen = false" type="button"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all">
                        Simpan & Buka Editor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
