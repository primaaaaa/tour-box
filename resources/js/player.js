/**
 * TourBox 360 Public Player Alpine Component
 */
export function tourPlayer(config) {
    return {
        views: config.views,
        currentView: null,
        activeInfoModal: null,
        engine: null,

        initPlayer() {
            if (this.views.length === 0) return;

            // Starting point logic (V-FR-02, FR-12)
            const starting = this.views.find(v => v.id == config.startingViewId) || this.views[0];
            this.currentView = starting;

            this.engine = new window.TourBoxEngine({
                container: 'three-container',
                isEditor: false,
                views: this.views,
                initialViewId: starting.id,
                onViewChange: (view) => {
                    this.currentView = view;
                },
                onHotspotClick: (hotspot) => {
                    // Navigation arrow transition (V-FR-03, FR-06)
                    if (hotspot.type === 'navigation' && hotspot.target_view_id) {
                        this.switchView(hotspot.target_view_id);
                    } else if (hotspot.type === 'info') {
                        // Open info card with carousel (V-FR-04, FR-08, FR-09, FR-10)
                        this.activeInfoModal = hotspot;
                    }
                }
            });
        },

        switchView(viewId) {
            const target = this.views.find(v => v.id == viewId);
            if (target) {
                this.currentView = target;
                if (this.engine) {
                    this.engine.loadView(target);
                }
            }
        },

        resetCamera() {
            if (this.engine) {
                this.engine.lat = 0;
                this.engine.lon = 0;
            }
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }
    };
}
