/**
 * TourBox 360 Editor Alpine Component with Gyro Target Anchor & Puzzle Guides
 */
export function tourEditor(config) {
    return {
        workspaceId: config.workspaceId,
        views: config.views,
        startingViewId: config.startingViewId,
        currentView: null,
        mode: 'navigate', // 'navigate', 'add_arrow', 'add_info'
        shareUrl: config.shareUrl,
        copiedLink: false,

        // Modals
        addViewModalOpen: false,
        addArrowModalOpen: false,
        addInfoModalOpen: false,
        editViewModalOpen: false,
        activeInfoModal: null,

        // Add View State
        captureTab: 'camera', // 'camera' or 'upload'
        captureMode: 'cubemap_6', // 'cubemap_6' (default standard 6-wall room) or 'sphere_panels'
        newViewName: '',
        newViewCustomId: '',
        capturedFaces: {},
        capturedPanels: [],
        capturedPanelsCount: 0,
        totalPanelsCount: 8,
        isSavingView: false,

        // Camera Engine & Sensor Feedback
        captureEngine: null,
        cameraStepIndex: 0,
        currentStepName: 'Depan (Front)',
        currentStepTitle: 'Langkah 1: Tembok DEPAN',
        currentStepCode: 'PZ',
        currentStepTip: 'Arahkan kamera lurus ke depan sejajar mata Anda.',
        currentStepIcon: '📷',
        currentStepCompass: '🧭 Utara (0°)',
        targetXOffset: 0,
        targetYOffset: 0,
        isCameraAligned: false,
        sensorStatusIcon: '🧭',
        sensorStatusText: 'Mengalibrasi sensor...',
        shutterButtonHint: '',
        cameraZoom: 1.0,
        needsSensorPermission: false,

        captureSteps: [
            { key: 'pz', short: 'Utara' },
            { key: 'px', short: 'Timur' },
            { key: 'nz', short: 'Selatan' },
            { key: 'nx', short: 'Barat' },
            { key: 'py', short: 'Plafon' },
            { key: 'ny', short: 'Lantai' }
        ],

        // Hotspots Placement
        pendingHotspotPos: { pos_x: 0, pos_y: 0, pos_z: 0 },
        selectedTargetViewId: '',
        infoHotspotTitle: '',
        infoHotspotCaption: '',
        infoCarouselPreviews: [],
        isSavingHotspot: false,

        engine: null,

        initEditor() {
            if (this.views.length > 0) {
                const initView = this.views.find(v => v.id == config.initialViewId) || this.views[0];
                this.currentView = initView;
            }

            // Init Three.js Engine
            this.engine = new window.TourBoxEngine({
                container: 'three-container',
                isEditor: true,
                views: this.views,
                initialViewId: this.currentView?.id,
                onViewChange: (view) => {
                    this.currentView = view;
                },
                onHotspotClick: (hotspot) => {
                    if (hotspot.type === 'navigation' && hotspot.target_view_id) {
                        this.switchView(hotspot.target_view_id);
                    } else if (hotspot.type === 'info') {
                        this.activeInfoModal = hotspot;
                    }
                },
                onSceneClick: (coords) => {
                    if (this.mode === 'add_arrow') {
                        this.pendingHotspotPos = coords;
                        this.selectedTargetViewId = '';
                        this.addArrowModalOpen = true;
                    } else if (this.mode === 'add_info') {
                        this.pendingHotspotPos = coords;
                        this.infoHotspotTitle = '';
                        this.infoHotspotCaption = '';
                        this.infoCarouselPreviews = [];
                        this.addInfoModalOpen = true;
                    }
                }
            });
        },

        setMode(newMode) {
            this.mode = newMode;
        },

        switchView(viewId) {
            const v = this.views.find(item => item.id == viewId);
            if (v) {
                this.currentView = v;
                if (this.engine) {
                    this.engine.loadView(v);
                }
            }
        },

        get availableTargetViews() {
            if (!this.currentView) return this.views;
            return this.views.filter(v => v.id !== this.currentView.id);
        },

        // Starting point (FR-12)
        async setAsStartingPoint() {
            if (!this.currentView) return;
            try {
                const res = await fetch(`/api/views/${this.currentView.id}/starting-point`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                this.startingViewId = data.starting_view_id;
            } catch (err) {
                alert('Gagal menyetel starting point.');
            }
        },

        // Nadir Censor (FR-14)
        async toggleNadirCensor() {
            if (!this.currentView) return;
            this.currentView.has_nadir_censor = !this.currentView.has_nadir_censor;
            this.engine.setNadirCensor(this.currentView.has_nadir_censor, this.currentView.nadir_censor_type);

            try {
                await fetch(`/api/views/${this.currentView.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        has_nadir_censor: this.currentView.has_nadir_censor
                    })
                });
            } catch (err) {
                console.error('Gagal memperbarui status sensor nadir:', err);
            }
        },

        copyShareLink() {
            navigator.clipboard.writeText(this.shareUrl);
            this.copiedLink = true;
            setTimeout(() => this.copiedLink = false, 2500);
        },

        openAddViewModal() {
            this.capturedFaces = {};
            this.capturedPanels = [];
            this.capturedPanelsCount = 0;
            this.cameraStepIndex = 0;
            this.targetXOffset = 0;
            this.targetYOffset = 0;
            this.isCameraAligned = false;
            this.newViewName = 'View ' + (this.views.length + 1);
            this.newViewCustomId = 'view-' + (this.views.length + 1);
            this.addViewModalOpen = true;
            this.captureTab = 'camera';
            this.$nextTick(() => this.startWebcamCapture());
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

        closeAddViewModal() {
            this.stopWebcamCapture();
            this.addViewModalOpen = false;
        },

        async startWebcamCapture() {
            const videoEl = document.getElementById('editor-capture-video');
            const arCanvas = document.getElementById('editor-ar-sphere-canvas');
            const leftCanvas = document.getElementById('editor-puzzle-left-canvas');
            const rightCanvas = document.getElementById('editor-puzzle-right-canvas');
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
                    this.capturedPanels = panels.filter(p => p.isCaptured);
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
                    if (result && result.view_type === 'sphere_panels') {
                        this.capturedPanels = result.panels || [];
                        this.capturedPanoramaUrl = result.panorama_url || null;
                        this.saveNewView();
                    } else {
                        this.capturedFaces = { ...result };
                        delete this.capturedFaces.view_type;
                        this.saveNewView();
                    }
                }
            });

            try {
                await this.captureEngine.startCamera();
            } catch (err) {
                alert('Tidak dapat mengaktifkan kamera. Pastikan izin kamera aktif atau gunakan tab Upload.');
                this.captureTab = 'upload';
            }
        },

        setCameraZoom(factor) {
            this.cameraZoom = factor;
            if (this.captureEngine) {
                this.captureEngine.setZoom(factor);
            }
        },

        stopWebcamCapture() {
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

        getFaceLabel(face) {
            const map = {
                pz: 'Depan (+Z)',
                px: 'Kanan (+X)',
                nz: 'Belakang (-Z)',
                nx: 'Kiri (-X)',
                py: 'Atas (+Y)',
                ny: 'Bawah (-Y)'
            };
            return map[face] || face;
        },

        handleFileUpload(event, face) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.capturedFaces[face] = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        canSaveView() {
            if (this.newViewName.trim().length === 0) return false;
            if (this.captureTab === 'camera') {
                if (this.captureMode === 'sphere_panels') {
                    return this.capturedPanelsCount >= 4;
                }
                return Object.keys(this.capturedFaces).length >= 6;
            }
            return Object.keys(this.capturedFaces).length > 0;
        },

        async saveNewView() {
            this.isSavingView = true;

            const isSphere = this.captureTab === 'camera' && this.captureMode === 'sphere_panels' && this.capturedPanels.length > 0;

            const payload = {
                name: this.newViewName,
                custom_id: this.newViewCustomId,
                view_type: isSphere ? 'sphere_panels' : 'cubemap',
                panorama_url: isSphere ? this.capturedPanoramaUrl : null,
                panels_data: isSphere ? this.capturedPanels : null,
                ...this.capturedFaces
            };

            try {
                const res = await fetch(`/api/workspaces/${this.workspaceId}/views`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                this.views.push(data.view);
                if (this.engine) {
                    this.engine.addView(data.view);
                }
                this.closeAddViewModal();
                this.switchView(data.view.id);
            } catch (err) {
                alert('Gagal menyimpan view baru.');
            } finally {
                this.isSavingView = false;
            }
        },

        async saveNavigationHotspot() {
            if (!this.currentView || !this.selectedTargetViewId) return;
            this.isSavingHotspot = true;

            const payload = {
                type: 'navigation',
                pos_x: this.pendingHotspotPos.pos_x,
                pos_y: this.pendingHotspotPos.pos_y,
                pos_z: this.pendingHotspotPos.pos_z,
                target_view_id: this.selectedTargetViewId
            };

            try {
                const res = await fetch(`/api/views/${this.currentView.id}/hotspots`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (!this.currentView.hotspots) this.currentView.hotspots = [];
                this.currentView.hotspots.push(data.hotspot);
                this.engine.renderHotspots(this.currentView.hotspots);

                this.addArrowModalOpen = false;
                this.mode = 'navigate';
            } catch (err) {
                alert('Gagal menyimpan panah navigasi.');
            } finally {
                this.isSavingHotspot = false;
            }
        },

        handleInfoImagesUpload(e) {
            const files = Array.from(e.target.files);
            files.forEach(f => {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    this.infoCarouselPreviews.push(ev.target.result);
                };
                reader.readAsDataURL(f);
            });
        },

        async saveInfoHotspot() {
            if (!this.currentView || !this.infoHotspotTitle) return;
            this.isSavingHotspot = true;

            const payload = {
                type: 'info',
                pos_x: this.pendingHotspotPos.pos_x,
                pos_y: this.pendingHotspotPos.pos_y,
                pos_z: this.pendingHotspotPos.pos_z,
                info_title: this.infoHotspotTitle,
                info_caption: this.infoHotspotCaption,
                carousel_images: this.infoCarouselPreviews
            };

            try {
                const res = await fetch(`/api/views/${this.currentView.id}/hotspots`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (!this.currentView.hotspots) this.currentView.hotspots = [];
                this.currentView.hotspots.push(data.hotspot);
                this.engine.renderHotspots(this.currentView.hotspots);

                this.addInfoModalOpen = false;
                this.mode = 'navigate';
            } catch (err) {
                alert('Gagal menyimpan hotspot info.');
            } finally {
                this.isSavingHotspot = false;
            }
        },

        async saveViewDetails() {
            if (!this.currentView) return;
            try {
                await fetch(`/api/views/${this.currentView.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.currentView.name,
                        custom_id: this.currentView.custom_id
                    })
                });
                this.editViewModalOpen = false;
            } catch (err) {
                alert('Gagal memperbarui detail view.');
            }
        },

        async deleteCurrentView() {
            if (!this.currentView || !confirm('Hapus view 360 ini?')) return;
            try {
                await fetch(`/api/views/${this.currentView.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                this.views = this.views.filter(v => v.id !== this.currentView.id);
                this.editViewModalOpen = false;
                if (this.views.length > 0) {
                    this.switchView(this.views[0].id);
                } else {
                    this.currentView = null;
                }
            } catch (err) {
                alert('Gagal menghapus view.');
            }
        }
    };
}
