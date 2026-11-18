@props([
    'videoId' => 'capture-video',
    'arCanvasId' => 'ar-sphere-canvas',
    'leftCanvasId' => 'puzzle-left-canvas',
    'rightCanvasId' => 'puzzle-right-canvas',
    'onSnap' => 'snapPhoto()',
])

<!-- Top Mode Switch & Status Panel -->
<div class="mb-3 space-y-2">
    <!-- Mode Switcher Tabs -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center p-1 bg-slate-900/90 border border-slate-700/80 rounded-2xl text-xs font-semibold shadow-inner">
            <button @click="switchCaptureMode('cubemap_6')" type="button"
                    class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5"
                    :class="captureMode === 'cubemap_6' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'">
                <span>🧱 Mode Tembok 6 Sisi</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md bg-emerald-500/20 text-emerald-300 font-bold">Rekomendasi</span>
            </button>
            <button @click="switchCaptureMode('sphere_panels')" type="button"
                    class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5"
                    :class="captureMode === 'sphere_panels' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'">
                <span>🧭 Mode 8 Sisi (45°)</span>
            </button>
        </div>

        <!-- Lens Zoom (0.5x / 1x) -->
        <div class="flex items-center gap-1 bg-slate-900/90 border border-slate-700 rounded-full p-1 text-xs">
            <button @click="setCameraZoom(0.5)" type="button"
                    class="px-2.5 py-0.5 rounded-full font-bold transition-all"
                    :class="cameraZoom === 0.5 ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'">
                0.5x
            </button>
            <button @click="setCameraZoom(1.0)" type="button"
                    class="px-2.5 py-0.5 rounded-full font-bold transition-all"
                    :class="cameraZoom === 1.0 ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'">
                1x
            </button>
        </div>
    </div>

    <!-- Mode 1: Sphere Panels Progress Header -->
    <template x-if="captureMode === 'sphere_panels'">
        <div class="glass-panel p-3 rounded-2xl border border-slate-700/80 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-xl shrink-0">
                    🧭
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs font-bold text-white">8 Sisi Vertikal (45°)</h4>
                        <span class="text-[11px] font-mono text-indigo-300 font-bold" x-text="capturedPanelsCount + ' / ' + (totalPanelsCount || 8) + ' Tembok Terpasang'"></span>
                    </div>
                    <p class="text-[11px] text-slate-300 mt-0.5" x-text="sensorStatusText || 'Putar badan setiap 45° menghadap panel kosong...'"></p>
                </div>
            </div>

            <!-- Selesai Sekarang Button (Active after at least 1 panel) -->
            <button x-show="capturedPanelsCount >= 1" x-cloak
                    @click="finishCaptureEarly()"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition-all shrink-0 animate-pulse">
                <span>✅ Selesai & Lihat 3D</span>
            </button>
        </div>
    </template>

    <!-- Mode 2: Cubemap 6-Faces Header -->
    <template x-if="captureMode === 'cubemap_6'">
        <div class="glass-panel p-3 rounded-2xl border border-slate-700/80 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="text-3xl shrink-0" x-text="currentStepIcon || '📷'"></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-extrabold text-white" x-text="currentStepTitle || currentStepName"></h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30" x-text="currentStepCode"></span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30" x-text="currentStepCompass || '🧭 Utara'"></span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5 leading-snug" x-text="currentStepTip"></p>
                </div>
            </div>

            <!-- Selesai Sekarang Button for Cubemap (Active after at least 1 face) -->
            <button x-show="Object.keys(capturedFaces).length >= 1" x-cloak
                    @click="finishCaptureEarly()"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition-all shrink-0 animate-pulse">
                <span>✅ Selesai & Lihat 3D</span>
            </button>
        </div>
    </template>

    <!-- Sensor permission button for iOS Safari -->
    <div x-show="needsSensorPermission" x-cloak class="text-center pt-1">
        <button @click="requestSensorPermission()" type="button"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-all shadow-lg shadow-amber-500/10">
            📱 Ketuk untuk Mengaktifkan Sensor Gyroscope HP
        </button>
    </div>
</div>

<!-- Square Viewfinder with AR 3D Canvas, Gyro Target Anchor & Puzzle Guides -->
<div class="relative w-full aspect-square max-h-[360px] mx-auto rounded-2xl overflow-hidden bg-black viewfinder-box"
     :class="isCameraAligned ? 'aligned' : ''">

    <!-- Video Camera Stream (Hardware Accelerated DOM layer) -->
    <video id="{{ $videoId }}" autoplay playsinline muted class="w-full h-full object-cover"></video>

    <!-- AR 3D Canvas ("Naro Tembok" Hologram & Fixed Captured Walls) -->
    <canvas id="{{ $arCanvasId }}"
            class="absolute inset-0 w-full h-full pointer-events-none z-10"></canvas>

    <!-- 2D Architectural Wall Guidelines HUD Overlay (Mode 6 Sisi) -->
    <div x-show="captureMode === 'cubemap_6'" class="absolute inset-0 pointer-events-none z-15">
        <!-- Rule of thirds dashed lines -->
        <div class="absolute inset-x-0 top-1/3 border-b border-indigo-500/20 border-dashed"></div>
        <div class="absolute inset-x-0 bottom-1/3 border-b border-indigo-500/20 border-dashed"></div>
        <div class="absolute inset-y-0 left-1/3 border-r border-indigo-500/20 border-dashed"></div>
        <div class="absolute inset-y-0 right-1/3 border-r border-indigo-500/20 border-dashed"></div>

        <!-- Center Horizon & Plumb Line -->
        <div class="absolute inset-x-8 top-1/2 -translate-y-1/2 border-b transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400/60' : 'border-indigo-400/30'"></div>
        <div class="absolute inset-y-8 left-1/2 -translate-x-1/2 border-r transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400/60' : 'border-indigo-400/30'"></div>

        <!-- Corner Wall Brackets -->
        <div class="absolute top-3 left-3 w-5 h-5 border-t-2 border-l-2 transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400' : 'border-indigo-400/50'"></div>
        <div class="absolute top-3 right-3 w-5 h-5 border-t-2 border-r-2 transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400' : 'border-indigo-400/50'"></div>
        <div class="absolute bottom-3 left-3 w-5 h-5 border-b-2 border-l-2 transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400' : 'border-indigo-400/50'"></div>
        <div class="absolute bottom-3 right-3 w-5 h-5 border-b-2 border-r-2 transition-colors duration-200"
             :class="isCameraAligned ? 'border-emerald-400' : 'border-indigo-400/50'"></div>
    </div>

    <!-- Left Puzzle Guide (Cubemap Mode & Sphere Panels Mode) -->
    <div x-show="(captureMode === 'cubemap_6' && cameraStepIndex >= 1 && cameraStepIndex <= 3) || (captureMode === 'sphere_panels' && capturedPanelsCount >= 1)" x-cloak
         class="puzzle-strip-container puzzle-left z-20 opacity-80 pointer-events-none">
        <canvas id="{{ $leftCanvasId }}" class="puzzle-strip-canvas"></canvas>
        <div class="puzzle-seam-line"></div>
        <div class="puzzle-badge" x-text="captureMode === 'sphere_panels' ? '🧩 Tepi Foto Lalu' : '🧩 Sambungkan Garis'"></div>
    </div>

    <!-- Right Puzzle Guide (Cubemap Mode Only) -->
    <div x-show="captureMode === 'cubemap_6' && cameraStepIndex === 3" x-cloak
         class="puzzle-strip-container puzzle-right z-20">
        <canvas id="{{ $rightCanvasId }}" class="puzzle-strip-canvas"></canvas>
        <div class="puzzle-right-seam"></div>
        <div class="puzzle-badge">🔁 Menutup 360°</div>
    </div>

    <!-- Center Bullseye Reticle (⭕) -->
    <div class="center-reticle z-20">
        <div class="reticle-ring"></div>
        <div class="reticle-dot"></div>
    </div>

    <!-- Floating Gyro Target Anchor (🎯 Titik Target Melayang) -->
    <div class="target-anchor z-25"
         :style="'transform: translate(' + targetXOffset + 'px, ' + targetYOffset + 'px)'">
        <div class="target-dot">
            <span class="target-pulse"></span>
            <span class="text-base leading-none">🎯</span>
        </div>
    </div>

    <!-- Live Angle Status Badge inside Viewfinder -->
    <div class="angle-status-badge z-30"
         :class="isCameraAligned ? 'bg-emerald-500 text-white font-bold' : ''">
        <span x-text="sensorStatusIcon">🧭</span>
        <span x-text="sensorStatusText">Arahkan kamera...</span>
    </div>
</div>

<!-- Shutter Button Area -->
<div class="mt-4 flex items-center justify-center gap-4">
    <button @click="{{ $onSnap }}"
            type="button"
            class="px-8 py-3.5 rounded-2xl font-extrabold text-sm shadow-xl flex items-center gap-2 transition-all hover:scale-105 active:scale-95"
            :class="isCameraAligned ? 'bg-emerald-500 hover:bg-emerald-400 text-white ring-4 ring-emerald-500/40 shadow-emerald-500/30' : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/30'">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
        </svg>
        <span x-text="captureMode === 'sphere_panels' ? (isCameraAligned ? '🧱 Pasang Tembok Ini Sekarang!' : (shutterButtonHint || 'Arahkan ke Bingkai Tembok')) : (shutterButtonHint || ('Ambil Foto (' + (cameraStepIndex + 1) + '/6)'))"></span>
    </button>
</div>

<!-- Mini Progress Bar / Strip -->
<div class="mt-4 pt-3 border-t border-slate-800">
    <!-- Sphere Panels Mode: Slim Progress Bar -->
    <template x-if="captureMode === 'sphere_panels'">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5">
                <span>Progress 8 Sisi Vertikal:</span>
                <span class="font-bold text-emerald-400" x-text="capturedPanelsCount + ' / ' + (totalPanelsCount || 8) + ' Tembok'"></span>
            </div>
            <div class="w-full h-2.5 rounded-full bg-slate-900 border border-slate-800 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 transition-all duration-300"
                     :style="'width: ' + ((capturedPanelsCount / (totalPanelsCount || 8)) * 100) + '%'"></div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 text-center">
                💡 Pegang HP tegak sejajar dada, putar badan ke sekeliling setiap 45° untuk memasang 8 sisi tembok!
            </p>
        </div>
    </template>

    <!-- Cubemap Mode: 6 Faces Strip -->
    <template x-if="captureMode === 'cubemap_6'">
        <div>
            <div class="grid grid-cols-6 gap-2">
                <template x-for="(step, idx) in captureSteps" :key="step.key">
                    <div class="flex flex-col items-center">
                        <div class="w-full aspect-square rounded-lg border flex items-center justify-center overflow-hidden bg-slate-900 transition-all"
                             :class="capturedFaces[step.key] ? 'border-emerald-500 ring-2 ring-emerald-500/50' : (idx === cameraStepIndex ? 'border-indigo-500 ring-2 ring-indigo-500/40' : 'border-slate-800')">
                            <img x-show="capturedFaces[step.key]" :src="capturedFaces[step.key]" class="w-full h-full object-cover">
                            <span x-show="!capturedFaces[step.key]" class="text-[10px] text-slate-500" x-text="step.short"></span>
                        </div>
                        <span class="text-[9px] text-slate-400 mt-1" x-text="step.short"></span>
                    </div>
                </template>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 text-center">
                🧱 Putar HP 90° ke sekeliling, atas & bawah untuk melihat 6 dinding kubus terpasang melayang di ruang 3D!
            </p>
        </div>
    </template>
</div>
