/**
 * TourBox 360 Guest Demo Experience Alpine Component with Gyro Target Anchor
 */
export function demoExperience(config) {
    return {
        views: config.views,
        currentView: null,
        activeInfoModal: null,
        engine: null,
        cameraModalOpen: false,
        showSuccessBanner: false,

        // Camera Engine state
        captureMode: 'cubemap_6', // 'cubemap_6' (default standard 6-wall room) or 'sphere_panels'
        captureEngine: null,
        cameraStepIndex: 0,
        capturedPanelsCount: 0,
        totalPanelsCount: 8,
        currentStepName: 'Depan (Front)',
        currentStepTitle: 'Langkah 1: Tembok DEPAN',
        currentStepCode: 'PZ',
        currentStepTip: 'Arahkan kamera tegak sejajar dada, bebas ke dinding mana pun.',
        currentStepIcon: '📷',
        currentStepCompass: '🧭 Utara (0°)',
        capturedFaces: {},
        captureSteps: [
            { key: 'pz', short: 'Utara' },
            { key: 'px', short: 'Timur' },
            { key: 'nz', short: 'Selatan' },
            { key: 'nx', short: 'Barat' },
            { key: 'py', short: 'Plafon' },
            { key: 'ny', short: 'Lantai' }
        ],

        // Gyro Target Anchor & Sensor Feedback
        targetXOffset: 0,
        targetYOffset: 0,
        isCameraAligned: false,
        sensorStatusIcon: '🧭',
        sensorStatusText: 'Mengalibrasi sensor...',
        shutterButtonHint: '',
        cameraZoom: 1.0,
        needsSensorPermission: false,

        initDemo() {
            if (!this.views.length) return;
            this.currentView = this.views[0];

            this.engine = new window.TourBoxEngine({
                container: 'three-container',
                isEditor: false,
                views: this.views,
                initialViewId: this.currentView.id,
                onViewChange: (v) => {
                    this.currentView = v;
                },
                onHotspotClick: (hotspot) => {
                    if (hotspot.type === 'navigation' && hotspot.target_view_id) {
                        this.switchView(hotspot.target_view_id);
                    } else if (hotspot.type === 'info') {
                        this.activeInfoModal = hotspot;
                    }
                }
            });
        },

        switchView(id) {
            const v = this.views.find(item => item.id == id);
            if (v) {
                this.currentView = v;
                if (this.engine) {
                    this.engine.loadView(v);
                }
            }
        },

        toggleNadirCensor() {
            if (!this.currentView) return;
            this.currentView.has_nadir_censor = !this.currentView.has_nadir_censor;
            this.engine.setNadirCensor(this.currentView.has_nadir_censor, this.currentView.nadir_censor_type);
        },

        openCameraModal() {
            this.capturedFaces = {};
            this.cameraStepIndex = 0;
            this.capturedPanelsCount = 0;
            this.targetXOffset = 0;
            this.targetYOffset = 0;
            this.isCameraAligned = false;
            this.cameraModalOpen = true;
            this.$nextTick(() => this.startLiveCapture());
        },

        switchCaptureMode(newMode) {
            this.captureMode = newMode;
            if (this.captureEngine) {
                this.captureEngine.setMode(newMode);
            }
        },

        finishCaptureEarly() {
            if (this.captureEngine) {
                if (this.captureMode === 'sphere_panels') {
                    this.captureEngine.finishSphereCapture();
                } else {
                    this.captureEngine.finishCubemapEarly();
                }
            }
        },

        finishSphereEarly() {
            this.finishCaptureEarly();
        },

        closeCameraModal() {
            this.stopLiveCapture();
            this.cameraModalOpen = false;
        },

        async startLiveCapture() {
            const videoEl = document.getElementById('demo-capture-video');
            const arCanvas = document.getElementById('demo-ar-sphere-canvas');
            const leftCanvas = document.getElementById('demo-puzzle-left-canvas');
            const rightCanvas = document.getElementById('demo-puzzle-right-canvas');
            if (!videoEl) return;

            this.captureEngine = new window.TourBoxCaptureEngine({
                videoElement: videoEl,
                arCanvas: arCanvas,
                leftPuzzleCanvas: leftCanvas,
                rightPuzzleCanvas: rightCanvas,
                mode: this.captureMode,
                onStepChange: (stepIdx, stepObj, images) => {
                    this.cameraStepIndex = stepIdx;
                    this.currentStepName = stepObj.name;
                    this.currentStepTitle = stepObj.title;
                    this.currentStepCode = stepObj.key.toUpperCase();
                    this.currentStepTip = stepObj.tip;
                    this.currentStepIcon = stepObj.icon;
                    this.currentStepCompass = stepObj.targetLabel || ('🧭 ' + stepObj.compassName);
                    this.capturedFaces = { ...images };
                },
                onPanelSnapped: (count, total, panels) => {
                    this.capturedPanelsCount = count;
                    this.totalPanelsCount = total;
                },
                onSensorUpdate: (data) => {
                    this.targetXOffset = data.xOffset;
                    this.targetYOffset = data.yOffset;
                    this.isCameraAligned = data.isAligned;
                    this.sensorStatusIcon = data.statusIcon;
                    this.sensorStatusText = data.statusText;
                    this.shutterButtonHint = data.shutterHint;
                    this.needsSensorPermission = data.needsSensorPermission;
                    if (typeof data.capturedCount === 'number') {
                        this.capturedPanelsCount = data.capturedCount;
                    }
                },
                onComplete: (result) => {
                    this.finishUserCapture(result);
                }
            });

            try {
                await this.captureEngine.startCamera();
            } catch (err) {
                alert('Tidak dapat mengaktifkan kamera. Pastikan izin kamera telah diberikan di browser Anda.');
                this.closeCameraModal();
            }
        },

        setCameraZoom(factor) {
            this.cameraZoom = factor;
            if (this.captureEngine) {
                this.captureEngine.setZoom(factor);
            }
        },

        stopLiveCapture() {
            if (this.captureEngine) {
                this.captureEngine.stopCamera();
            }
        },

        async requestSensorPermission() {
            if (this.captureEngine) {
                const ok = await this.captureEngine.requestSensorPermission();
                if (ok) this.needsSensorPermission = false;
            }
        },

        snapPhoto() {
            if (this.captureEngine) {
                this.captureEngine.captureCurrentStep();
            }
        },

        finishUserCapture(result) {
            this.closeCameraModal();

            const isSphere = result && result.view_type === 'sphere_panels';

            // Create new View from User Camera Capture directly in Demo
            const capturedView = {
                id: 'demo-capture-' + Date.now(),
                name: isSphere ? '360° (8 Sisi Vertikal 45°)' : 'Foto Kamera Anda 360°',
                custom_id: 'my-camera-room',
                view_type: isSphere ? 'sphere_panels' : 'cubemap',
                panorama_url: isSphere ? (result.panorama_url || null) : null,
                has_nadir_censor: true,
                nadir_censor_type: 'blur',
                default_yaw: 0,
                default_pitch: 0,
                is_user_capture: true,
                cubemap: isSphere ? {} : {
                    pz: result.pz,
                    px: result.px,
                    nz: result.nz,
                    nx: result.nx,
                    py: result.py,
                    ny: result.ny
                },
                panels: isSphere ? (result.panels || []) : [],
                hotspots: [
                    {
                        id: 999,
                        type: 'info',
                        pos_x: 0,
                        pos_y: 20,
                        pos_z: -300,
                        info_title: isSphere ? 'Ruangan Bola 3D Anda!' : 'Foto 360 Dari Kamera Anda!',
                        info_caption: isSphere
                            ? 'Ruangan ini dibuat dari susunan panel dinding 3D nyata tanpa memerlukan kamera 0.5x!'
                            : 'Ini adalah hasil pemotretan langsung dari kamera perangkat Anda dengan panduan sensor putar dan puzzle.',
                        carousel: []
                    }
                ]
            };

            this.views.unshift(capturedView);
            if (this.engine) {
                this.engine.addView(capturedView);
            }
            this.switchView(capturedView.id);

            this.showSuccessBanner = true;
            setTimeout(() => {
                this.showSuccessBanner = false;
            }, 6000);
        }
    };
}
