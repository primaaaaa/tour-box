<div x-show="activeInfoModal !== null" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
    <div @click.away="activeInfoModal = null"
         class="glass-panel max-w-lg w-full p-6 sm:p-8 rounded-3xl border border-slate-700 shadow-2xl relative">
        <!-- Close Button -->
        <button @click="activeInfoModal = null" class="absolute top-5 right-5 text-slate-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <!-- Information Title (FR-08) -->
        <h3 class="text-2xl font-extrabold text-white tracking-tight mb-4" x-text="activeInfoModal?.info_title"></h3>

        <!-- Image Carousel (FR-09) -->
        <template x-if="activeInfoModal && activeInfoModal.carousel && activeInfoModal.carousel.length > 0">
            <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-black mb-4 group shadow-xl"
                 x-data="{ activeIdx: 0 }">
                <img :src="activeInfoModal.carousel[activeIdx]" class="w-full h-full object-cover">

                <!-- Previous & Next Controls -->
                <template x-if="activeInfoModal.carousel.length > 1">
                    <div class="absolute inset-x-2 top-1/2 -translate-y-1/2 flex justify-between pointer-events-none">
                        <button @click="activeIdx = (activeIdx - 1 + activeInfoModal.carousel.length) % activeInfoModal.carousel.length"
                                class="p-2 rounded-full bg-black/60 hover:bg-black/85 text-white pointer-events-auto transition-colors">
                            ‹
                        </button>
                        <button @click="activeIdx = (activeIdx + 1) % activeInfoModal.carousel.length"
                                class="p-2 rounded-full bg-black/60 hover:bg-black/85 text-white pointer-events-auto transition-colors">
                            ›
                        </button>
                    </div>
                </template>

                <!-- Carousel Navigation Dots -->
                <template x-if="activeInfoModal.carousel.length > 1">
                    <div class="absolute bottom-2.5 inset-x-0 flex justify-center gap-1.5">
                        <template x-for="(cImg, cI) in activeInfoModal.carousel" :key="cI">
                            <span class="w-2 h-2 rounded-full transition-all"
                                  :class="activeIdx === cI ? 'bg-white w-4' : 'bg-white/40'"></span>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        <!-- Caption Text (FR-10) -->
        <p class="text-slate-300 text-sm leading-relaxed" x-text="activeInfoModal?.info_caption"></p>
    </div>
</div>
