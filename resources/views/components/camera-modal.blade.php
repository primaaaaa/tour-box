@props([
    'videoId' => 'demo-capture-video',
    'arCanvasId' => 'demo-ar-sphere-canvas',
    'leftCanvasId' => 'demo-puzzle-left-canvas',
    'rightCanvasId' => 'demo-puzzle-right-canvas',
    'showModal' => 'cameraModalOpen',
    'onClose' => 'closeCameraModal()',
    'onSnap' => 'snapPhoto()',
])

<div x-show="{{ $showModal }}" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/95 backdrop-blur-md flex items-center justify-center p-3 sm:p-4">
    <div @click.away="{{ $onClose }}"
         class="glass-panel max-w-xl w-full p-4 sm:p-6 rounded-3xl border border-slate-700 shadow-2xl relative">
        <!-- Close Button -->
        <button @click="{{ $onClose }}" class="absolute top-4 right-4 text-slate-400 hover:text-white z-30 transition-colors p-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <x-camera-viewfinder
            :video-id="$videoId"
            :ar-canvas-id="$arCanvasId"
            :left-canvas-id="$leftCanvasId"
            :right-canvas-id="$rightCanvasId"
            :on-snap="$onSnap"
        />
    </div>
</div>
