import * as THREE from 'three';

/**
 * TourBox Advanced Camera Engine
 * Supports:
 * 1. Mode "sphere_panels": AR 3D Wall Placement ("Naro Tembok" Photo Sphere)
 *    - Renders 3D holographic panel frames floating in real 3D space around the user.
 *    - Snapped photos immediately freeze and stick into their 3D coordinates.
 *    - Users can see previously captured walls standing in the room as they pan around.
 *    - Ultra low-poly (only 28 quads), guaranteed 60 FPS without dropping frames.
 * 2. Mode "cubemap_6": Classic 6-Face Cubemap for 0.5x Ultra-Wide lenses.
 */
export class TourBoxCaptureEngine {
    constructor(options = {}) {
        this.videoElement = options.videoElement;
        this.arCanvas = options.arCanvas || null;
        this.leftPuzzleCanvas = options.leftPuzzleCanvas || null;
        this.rightPuzzleCanvas = options.rightPuzzleCanvas || null;
        this.onStepChange = options.onStepChange || null;
        this.onSensorUpdate = options.onSensorUpdate || null;
        this.onPanelSnapped = options.onPanelSnapped || null;
        this.onComplete = options.onComplete || null;

        this.mode = options.mode || 'sphere_panels'; // 'sphere_panels' or 'cubemap_6'

        // 6-Face Cubemap Steps
        this.steps = [
            {
                key: 'pz',
                name: 'Depan (Utara / 0°)',
                compassName: 'Utara',
                compassDeg: '0°',
                targetYaw: null,
                targetPitch: 0,
                targetLabel: '🧭 Utara (0°)',
                title: 'Langkah 1: Tembok DEPAN (Utara)',
                tip: 'Arahkan kamera tegak sejajar dada menghadap dinding DEPAN. Arah ini menjadi patokan Utara (0°).',
                icon: '🧭'
            },
            {
                key: 'px',
                name: 'Kanan (Timur / +90°)',
                compassName: 'Timur',
                compassDeg: '+90°',
                targetYaw: 90,
                targetPitch: 0,
                targetLabel: '🧭 Timur (+90°)',
                title: 'Langkah 2: Tembok KANAN (Timur)',
                tip: 'Putar HP 90° ke KANAN (Timur). Posisikan titik target 🎯 tepat di lingkaran ⭕!',
                icon: '➡️'
            },
            {
                key: 'nz',
                name: 'Belakang (Selatan / 180°)',
                compassName: 'Selatan',
                compassDeg: '180°',
                targetYaw: 180,
                targetPitch: 0,
                targetLabel: '🧭 Selatan (180°)',
                title: 'Langkah 3: Tembok BELAKANG (Selatan)',
                tip: 'Putar HP 90° lagi ke KANAN menghadap BELAKANG (Selatan). Putar HP pada porosnya!',
                icon: '⬇️'
            },
            {
                key: 'nx',
                name: 'Kiri (Barat / 270°)',
                compassName: 'Barat',
                compassDeg: '270°',
                targetYaw: 270,
                targetPitch: 0,
                targetLabel: '🧭 Barat (270°)',
                title: 'Langkah 4: Tembok KIRI (Barat)',
                tip: 'Putar HP 90° lagi ke KANAN menghadap KIRI (Barat) untuk menutup putaran 360° secara sempurna.',
                icon: '⬅️'
            },
            {
                key: 'py',
                name: 'Plafon (Atas)',
                compassName: 'Plafon',
                compassDeg: '+90° Pitch',
                targetYaw: null,
                targetPitch: 90,
                targetLabel: '🔼 Plafon (Atas)',
                title: 'Langkah 5: Langit-langit (PLAFON)',
                tip: 'Dongakkan kamera HP tegak lurus ke atas menghadap plafon ruangan.',
                icon: '🔼'
            },
            {
                key: 'ny',
                name: 'Lantai (Bawah)',
                compassName: 'Lantai',
                compassDeg: '-90° Pitch',
                targetYaw: null,
                targetPitch: -90,
                targetLabel: '🔽 Lantai (Bawah)',
                title: 'Langkah 6: Lantai Ruangan (LANTAI)',
                tip: 'Tundukkan kamera HP tegak lurus ke bawah menghadap lantai ruangan.',
                icon: '🔽'
            }
        ];

        this.currentStepIndex = 0;
        this.capturedImages = {};
        this.capturedCanvases = new Array(6).fill(null);
        this.stream = null;
        this.videoTrack = null;

        // Multi-Panel Sphere Data (28 Panels: 12 Equator, 8 Upper, 8 Lower)
        this.spherePanels = [];
        this.panelMeshes = [];
        this.activeTargetPanelIndex = 0;
        this.capturedPanelsCount = 0;
        this.initSphereGrid();

        // Sensor & Compass State
        this.baseHeading = null;
        this.currentHeading = 0;
        this.baseAlpha = null;
        this.currentAlpha = 0;
        this.currentBeta = 0;
        this.currentGamma = 0;
        this.hasGyroSensor = false;
        this.isAligned = false;

        // Camera Devices & Zoom
        this.availableVideoDevices = [];
        this.selectedCameraIndex = 0;
        this.currentZoomFactor = 1.0;

        // AR Three.js Overlay (Sphere Panels & 6-Face Cubic Room "Naro Tembok")
        this.arRenderer = null;
        this.arScene = null;
        this.arCamera = null;
        this.arAnimating = false;
        this.arSphereGroup = null;
        this.arCubeGroup = null;
        this.arCubeMeshes = {};

        this.handleOrientation = this.handleOrientation.bind(this);
        this.renderArLoop = this.renderArLoop.bind(this);
    }

    /**
     * Initialize 28 Spherical Wall Panels (Equator 12, Upper 8, Lower 8)
     */
    /**
     * Initialize Anti-Overlap 22 Spherical Wall Panels (Equator 8, Upper 6, Lower 6, Plafon 1, Lantai 1)
     * Matches standard 1x smartphone camera FOV (~50°) with 45° angular intervals.
     * Prevents duplicate/doubled objects across adjacent panel seams.
     */
    initSphereGrid() {
        this.spherePanels = [];

        // Ring 1: Equator (Dinding Utama / 0° Pitch) - 8 Panels (every 45°)
        const equatorCompassNames = [
            'Depan / Utara (0°)', 'Depan-Kanan / TL (45°)', 'Kanan / Timur (90°)', 'Belakang-Kanan / TG (135°)',
            'Belakang / Selatan (180°)', 'Belakang-Kiri / BD (225°)', 'Kiri / Barat (270°)', 'Depan-Kiri / BL (315°)'
        ];

        for (let i = 0; i < 8; i++) {
            const yaw = i * 45;
            this.spherePanels.push({
                id: `eq_${i}`,
                index: this.spherePanels.length,
                ring: 'equator',
                pitch: 0,
                yaw: yaw,
                width: 400,
                height: 400,
                name: `Dinding ${i + 1} (${equatorCompassNames[i]})`,
                short: `${yaw}°`,
                isCaptured: false,
                image: null
            });
        }

        // Ring 2: Upper Ring (Miring Atas / +35° Pitch) - 6 Panels (every 60°)
        for (let i = 0; i < 6; i++) {
            const yaw = i * 60;
            this.spherePanels.push({
                id: `up_${i}`,
                index: this.spherePanels.length,
                ring: 'upper',
                pitch: 35,
                yaw: yaw,
                width: 420,
                height: 380,
                name: `Atas ${i + 1} (${yaw}°)`,
                short: `▲${yaw}°`,
                isCaptured: false,
                image: null
            });
        }

        // Ring 3: Lower Ring (Miring Bawah / -35° Pitch) - 6 Panels (every 60°)
        for (let i = 0; i < 6; i++) {
            const yaw = i * 60;
            this.spherePanels.push({
                id: `dn_${i}`,
                index: this.spherePanels.length,
                ring: 'lower',
                pitch: -35,
                yaw: yaw,
                width: 420,
                height: 380,
                name: `Bawah ${i + 1} (${yaw}°)`,
                short: `▼${yaw}°`,
                isCaptured: false,
                image: null
            });
        }

        // Ring 4: Plafon (Langit-langit / +90° Pitch)
        this.spherePanels.push({
            id: 'top_0',
            index: this.spherePanels.length,
            ring: 'zenith',
            pitch: 90,
            yaw: 0,
            width: 480,
            height: 480,
            name: 'Plafon (Langit-langit)',
            short: '▲90°',
            isCaptured: false,
            image: null
        });

        // Ring 5: Lantai (Bagian Bawah / -90° Pitch)
        this.spherePanels.push({
            id: 'bot_0',
            index: this.spherePanels.length,
            ring: 'nadir',
            pitch: -90,
            yaw: 0,
            width: 480,
            height: 480,
            name: 'Lantai Ruangan',
            short: '▼90°',
            isCaptured: false,
            image: null
        });
    }

    setMode(newMode) {
        this.mode = newMode;
        if (!this.arScene && this.arCanvas) {
            this.initArScene();
        }
        if (this.arSphereGroup && this.arCubeGroup) {
            this.arSphereGroup.visible = (this.mode === 'sphere_panels');
            this.arCubeGroup.visible = (this.mode === 'cubemap_6');
        }
        this.updateSensorFeedback();
    }

    async startCamera() {
        try {
            if (this.stream) {
                this.stopCamera();
            }

            this.currentStepIndex = 0;
            this.baseHeading = null;
            this.baseAlpha = null;
            this.capturedImages = {};
            this.capturedCanvases = new Array(6).fill(null);
            this.capturedPanelsCount = 0;
            this.initSphereGrid();

            await this.initCameraStream();
            this.initOrientationSensors();

            if (this.arCanvas) {
                this.initArScene();
            }

            this.updatePuzzleGuides();
            this.updateSensorFeedback();
            return true;
        } catch (err) {
            console.error('Gagal mengakses kamera:', err);
            throw err;
        }
    }

    async initCameraStream() {
        const constraints = {
            audio: false,
            video: {
                width: { ideal: 1920 },
                height: { ideal: 1440 },
                aspectRatio: { ideal: 4 / 3 },
                facingMode: { ideal: 'environment' }
            }
        };

        if (this.availableVideoDevices.length > 0 && this.availableVideoDevices[this.selectedCameraIndex]) {
            constraints.video.deviceId = { exact: this.availableVideoDevices[this.selectedCameraIndex].deviceId };
        }

        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
        if (this.videoElement) {
            this.videoElement.srcObject = this.stream;
            this.videoTrack = this.stream.getVideoTracks()[0];

            await new Promise(resolve => {
                this.videoElement.onloadedmetadata = () => {
                    this.videoElement.play();
                    resolve();
                };
            });

            await this.detectCameraDevices();
        }
    }

    async detectCameraDevices() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoInputs = devices.filter(d => d.kind === 'videoinput');
            const rearCameras = videoInputs.filter(d => {
                const label = d.label.toLowerCase();
                return label.includes('back') || label.includes('rear') || label.includes('environment') || !label.includes('front');
            });
            this.availableVideoDevices = rearCameras.length > 0 ? rearCameras : videoInputs;
        } catch (e) {
            console.warn('Enumerate devices failed:', e);
        }
    }

    async setZoom(zoomFactor) {
        this.currentZoomFactor = zoomFactor;
        if (this.videoTrack && typeof this.videoTrack.getCapabilities === 'function') {
            const caps = this.videoTrack.getCapabilities();
            if (caps.zoom) {
                try {
                    const targetZoom = Math.max(caps.zoom.min, Math.min(caps.zoom.max, zoomFactor));
                    await this.videoTrack.applyConstraints({ advanced: [{ zoom: targetZoom }] });
                    return;
                } catch (e) {
                    console.warn('Hardware zoom failed:', e);
                }
            }
        }
    }

    stopCamera() {
        this.arAnimating = false;
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        if (this.videoElement) {
            this.videoElement.srcObject = null;
        }
        if ('ondeviceorientationabsolute' in window) {
            window.removeEventListener('deviceorientationabsolute', this.handleOrientation, true);
        }
        window.removeEventListener('deviceorientation', this.handleOrientation, true);

        if (this.arRenderer) {
            this.arRenderer.dispose();
            this.arRenderer = null;
        }
    }

    async requestSensorPermission() {
        if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
            try {
                const state = await DeviceOrientationEvent.requestPermission();
                if (state === 'granted') {
                    if ('ondeviceorientationabsolute' in window) {
                        window.addEventListener('deviceorientationabsolute', this.handleOrientation, true);
                    }
                    window.addEventListener('deviceorientation', this.handleOrientation, true);
                    this.hasGyroSensor = true;
                    this.updateSensorFeedback();
                    return true;
                }
            } catch (e) {
                console.warn('Sensor permission error:', e);
            }
        }
        return false;
    }

    initOrientationSensors() {
        const attachListeners = () => {
            if ('ondeviceorientationabsolute' in window) {
                window.addEventListener('deviceorientationabsolute', this.handleOrientation, true);
            }
            window.addEventListener('deviceorientation', this.handleOrientation, true);
            this.hasGyroSensor = true;
            this.updateSensorFeedback();
        };

        if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
            DeviceOrientationEvent.requestPermission()
                .then(state => {
                    if (state === 'granted') {
                        attachListeners();
                    }
                })
                .catch(console.warn);
        } else if ('ondeviceorientation' in window) {
            attachListeners();
        }
    }

    computeCompassHeading(event) {
        if (typeof event.webkitCompassHeading === 'number' && !isNaN(event.webkitCompassHeading)) {
            return (event.webkitCompassHeading + 360) % 360;
        }

        if (event.alpha === null || event.beta === null || event.gamma === null) {
            return null;
        }

        const degToRad = Math.PI / 180;
        const a = event.alpha * degToRad;
        const b = event.beta * degToRad;
        const c = event.gamma * degToRad;

        const xWorld = -Math.sin(c) * Math.cos(a) - Math.sin(b) * Math.cos(c) * Math.sin(a);
        const yWorld = -Math.sin(c) * Math.sin(a) + Math.sin(b) * Math.cos(c) * Math.cos(a);

        let heading = Math.atan2(xWorld, yWorld) * (180 / Math.PI);
        if (heading < 0) heading += 360;
        return heading;
    }

    handleOrientation(event) {
        const heading = this.computeCompassHeading(event);
        if (heading !== null) {
            this.currentHeading = heading;
            this.currentAlpha = event.alpha;
            this.currentBeta = event.beta;
            this.currentGamma = event.gamma;
            this.hasGyroSensor = true;
            this.updateSensorFeedback();
        }
    }

    // ==========================================
    // AR 3D SPHERICAL & CUBIC VIEWPORT ("NARO TEMBOK")
    // ==========================================

    initArScene() {
        if (!this.arCanvas) return;

        const width = this.arCanvas.clientWidth || 360;
        const height = this.arCanvas.clientHeight || 360;

        if (!this.arRenderer) {
            this.arRenderer = new THREE.WebGLRenderer({
                canvas: this.arCanvas,
                alpha: true,
                antialias: true,
                powerPreference: 'high-performance'
            });
            this.arRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            this.arRenderer.setSize(width, height);
        }

        this.arScene = new THREE.Scene();
        this.arCamera = new THREE.PerspectiveCamera(65, width / height, 1, 1000);
        this.arCamera.position.set(0, 0, 0);

        // Group 1: Sphere Panels Mode ("Naro Tembok 3D Bola")
        this.arSphereGroup = new THREE.Group();
        this.arScene.add(this.arSphereGroup);
        this.panelMeshes = [];
        const R = 260; // Sphere radius in AR scene

        this.spherePanels.forEach((p, idx) => {
            const phi = THREE.MathUtils.degToRad(90 - p.pitch);
            const theta = THREE.MathUtils.degToRad(p.yaw);

            const x = R * Math.sin(phi) * Math.sin(theta);
            const y = R * Math.cos(phi);
            const z = -R * Math.sin(phi) * Math.cos(theta);

            // Plane geometry for this panel
            const pw = p.width ? Math.round(p.width * (R / 480)) : 215;
            const ph = p.height ? Math.round(p.height * (R / 480)) : 215;
            const geo = new THREE.PlaneGeometry(pw, ph);
            const mat = new THREE.MeshBasicMaterial({
                map: this.createGhostPanelTexture(idx + 1, p.short, false),
                transparent: true,
                opacity: 0.85,
                side: THREE.DoubleSide
            });

            const mesh = new THREE.Mesh(geo, mat);
            mesh.position.set(x, y, z);
            mesh.lookAt(0, 0, 0); // Faces AR camera directly at (0, 0, 0)
            mesh.userData = { panelIndex: idx };

            this.arSphereGroup.add(mesh);
            this.panelMeshes.push(mesh);
        });

        // Group 2: 6-Face Cubic Room Mode ("Naro Tembok 6 Sisi Ruangan")
        this.arCubeGroup = new THREE.Group();
        this.arScene.add(this.arCubeGroup);
        this.arCubeMeshes = {};

        const CUBE_DIST = 180;
        const CUBE_SIZE = 360;

        const cubeFaceConfigs = [
            { key: 'pz', name: 'DEPAN (PZ)', compass: 'Utara (0°)', pos: [0, 0, -CUBE_DIST], rot: [0, 0, 0], icon: '🧭' },
            { key: 'px', name: 'KANAN (PX)', compass: 'Timur (+90°)', pos: [CUBE_DIST, 0, 0], rot: [0, -Math.PI / 2, 0], icon: '➡️' },
            { key: 'nz', name: 'BELAKANG (NZ)', compass: 'Selatan (180°)', pos: [0, 0, CUBE_DIST], rot: [0, Math.PI, 0], icon: '⬇️' },
            { key: 'nx', name: 'KIRI (NX)', compass: 'Barat (270°)', pos: [-CUBE_DIST, 0, 0], rot: [0, Math.PI / 2, 0], icon: '⬅️' },
            { key: 'py', name: 'PLAFON (PY)', compass: 'Langit-langit (+90°)', pos: [0, CUBE_DIST, 0], rot: [Math.PI / 2, 0, 0], icon: '🔼' },
            { key: 'ny', name: 'LANTAI (NY)', compass: 'Lantai Ruangan (-90°)', pos: [0, -CUBE_DIST, 0], rot: [-Math.PI / 2, 0, 0], icon: '🔽' }
        ];

        cubeFaceConfigs.forEach((cfg, idx) => {
            const geo = new THREE.PlaneGeometry(CUBE_SIZE, CUBE_SIZE);
            const isTarget = (idx === this.currentStepIndex);
            const mat = new THREE.MeshBasicMaterial({
                map: this.createGhostCubeTexture(cfg.name, cfg.compass, cfg.icon, isTarget),
                transparent: true,
                opacity: 0.88,
                side: THREE.DoubleSide
            });

            const mesh = new THREE.Mesh(geo, mat);
            mesh.position.set(...cfg.pos);
            mesh.rotation.set(...cfg.rot);
            mesh.userData = { faceKey: cfg.key, index: idx };

            this.arCubeGroup.add(mesh);
            this.arCubeMeshes[cfg.key] = mesh;
        });

        // Set visibility based on active mode
        this.arSphereGroup.visible = (this.mode === 'sphere_panels');
        this.arCubeGroup.visible = (this.mode === 'cubemap_6');

        if (!this.arAnimating) {
            this.arAnimating = true;
            requestAnimationFrame(this.renderArLoop);
        }
    }

    createGhostPanelTexture(number, label, isTarget = false) {
        const c = document.createElement('canvas');
        c.width = 256;
        c.height = 256;
        const ctx = c.getContext('2d');

        // Glass translucent background
        ctx.fillStyle = isTarget ? 'rgba(16, 185, 129, 0.28)' : 'rgba(99, 102, 241, 0.14)';
        ctx.fillRect(8, 8, 240, 240);

        // Dashed border
        ctx.lineWidth = isTarget ? 8 : 4;
        ctx.strokeStyle = isTarget ? '#10b981' : '#818cf8';
        ctx.setLineDash([16, 10]);
        ctx.strokeRect(8, 8, 240, 240);
        ctx.setLineDash([]);

        // Target Reticle Icon
        ctx.font = 'bold 38px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(isTarget ? '✨' : '🎯', 128, 110);

        // Label
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 24px monospace';
        ctx.fillText(label || ('#' + number), 128, 160);

        return new THREE.CanvasTexture(c);
    }

    createGhostCubeTexture(title, compass, icon, isTarget = false) {
        const c = document.createElement('canvas');
        c.width = 512;
        c.height = 512;
        const ctx = c.getContext('2d');

        // Glass translucent background
        const grad = ctx.createLinearGradient(0, 0, 512, 512);
        if (isTarget) {
            grad.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            grad.addColorStop(1, 'rgba(6, 95, 70, 0.25)');
        } else {
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
            grad.addColorStop(1, 'rgba(30, 27, 75, 0.16)');
        }
        ctx.fillStyle = grad;
        ctx.fillRect(16, 16, 480, 480);

        // 3D Architectural grid
        ctx.strokeStyle = isTarget ? 'rgba(52, 211, 153, 0.35)' : 'rgba(129, 140, 248, 0.22)';
        ctx.lineWidth = 3;
        for (let i = 48; i <= 464; i += 52) {
            ctx.beginPath();
            ctx.moveTo(i, 16); ctx.lineTo(i, 496);
            ctx.moveTo(16, i); ctx.lineTo(496, i);
            ctx.stroke();
        }

        // Dashed glowing border
        ctx.lineWidth = isTarget ? 10 : 5;
        ctx.strokeStyle = isTarget ? '#10b981' : '#818cf8';
        ctx.setLineDash([28, 14]);
        ctx.strokeRect(16, 16, 480, 480);
        ctx.setLineDash([]);

        // Futuristic Corner Brackets
        const bLen = 42;
        ctx.lineWidth = isTarget ? 12 : 8;
        ctx.strokeStyle = isTarget ? '#34d399' : '#a5b4fc';
        // Top-left
        ctx.beginPath(); ctx.moveTo(16, 16 + bLen); ctx.lineTo(16, 16); ctx.lineTo(16 + bLen, 16); ctx.stroke();
        // Top-right
        ctx.beginPath(); ctx.moveTo(496 - bLen, 16); ctx.lineTo(496, 16); ctx.lineTo(496, 16 + bLen); ctx.stroke();
        // Bottom-left
        ctx.beginPath(); ctx.moveTo(16, 496 - bLen); ctx.lineTo(16, 496); ctx.lineTo(16 + bLen, 496); ctx.stroke();
        // Bottom-right
        ctx.beginPath(); ctx.moveTo(496 - bLen, 496); ctx.lineTo(496, 496); ctx.lineTo(496, 496 - bLen); ctx.stroke();

        // Icon & Center Text
        ctx.font = 'bold 74px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(isTarget ? '✨' : (icon || '🎯'), 256, 185);

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 42px sans-serif';
        ctx.fillText(title, 256, 285);

        ctx.fillStyle = isTarget ? '#a7f3d0' : '#c7d2fe';
        ctx.font = 'bold 26px monospace';
        ctx.fillText(compass, 256, 345);

        ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
        ctx.font = 'bold 20px sans-serif';
        ctx.fillText('TEMBOK 3D 6 SISI', 256, 400);

        return new THREE.CanvasTexture(c);
    }

    updateActiveCubeFace() {
        const currentStep = this.getCurrentStep();
        if (!currentStep || !this.arCubeMeshes) return;

        Object.entries(this.arCubeMeshes).forEach(([key, mesh]) => {
            const isCaptured = !!this.capturedImages[key];
            const isTarget = (key === currentStep.key);

            if (!isCaptured) {
                mesh.scale.setScalar(isTarget ? 1.05 : 1.0);
            }
        });
    }

    renderArLoop() {
        if (!this.arAnimating || !this.arRenderer) return;
        requestAnimationFrame(this.renderArLoop);

        const pitchDeg = (this.currentBeta !== null) ? this.currentBeta - 90 : 0;
        const pitchRad = THREE.MathUtils.degToRad(pitchDeg);
        const rollRad = THREE.MathUtils.degToRad(-this.currentGamma || 0);

        if (this.mode === 'sphere_panels') {
            // Rotate AR Camera based on Compass & Pitch
            const yawRad = THREE.MathUtils.degToRad(-this.currentHeading);
            this.arCamera.rotation.set(pitchRad, yawRad, rollRad, 'YXZ');

            // Find closest panel
            this.updateActiveArPanel(this.currentHeading, pitchDeg);

            this.arRenderer.render(this.arScene, this.arCamera);
        } else if (this.mode === 'cubemap_6') {
            // Relative yaw from baseHeading (Depan / PZ is at relative yaw 0)
            const base = (this.baseHeading !== null) ? this.baseHeading : this.currentHeading;
            let relativeYaw = (this.currentHeading - base);
            while (relativeYaw > 180) relativeYaw -= 360;
            while (relativeYaw < -180) relativeYaw += 360;

            const yawRad = THREE.MathUtils.degToRad(-relativeYaw);
            this.arCamera.rotation.set(pitchRad, yawRad, rollRad, 'YXZ');
            this.updateActiveCubeFace();
            this.arRenderer.render(this.arScene, this.arCamera);
        }
    }

    updateActiveArPanel(heading, pitch) {
        let bestDist = Infinity;
        let bestIdx = -1;

        // Loop over panels to find nearest uncaptured panel
        for (let i = 0; i < this.spherePanels.length; i++) {
            const p = this.spherePanels[i];
            if (p.isCaptured) continue;

            let deltaYaw = p.yaw - heading;
            while (deltaYaw > 180) deltaYaw -= 360;
            while (deltaYaw < -180) deltaYaw += 360;

            const deltaPitch = p.pitch - pitch;
            const dist = Math.hypot(deltaYaw, deltaPitch);

            if (dist < bestDist) {
                bestDist = dist;
                bestIdx = i;
            }
        }

        // If all panels captured
        if (bestIdx === -1) {
            bestIdx = 0;
        }

        this.activeTargetPanelIndex = bestIdx;
        const targetPanel = this.spherePanels[bestIdx];

        let deltaYaw = targetPanel.yaw - heading;
        while (deltaYaw > 180) deltaYaw -= 360;
        while (deltaYaw < -180) deltaYaw += 360;

        const deltaPitch = targetPanel.pitch - pitch;
        const ALIGN_TOLERANCE_YAW = 7;
        const ALIGN_TOLERANCE_PITCH = 12;
        const isAligned = Math.abs(deltaYaw) <= ALIGN_TOLERANCE_YAW && Math.abs(deltaPitch) <= ALIGN_TOLERANCE_PITCH;
        this.isAligned = isAligned;

        // Highlight nearest ghost mesh
        this.panelMeshes.forEach((mesh, idx) => {
            const p = this.spherePanels[idx];
            if (!p.isCaptured) {
                const isThisTarget = idx === bestIdx;
                mesh.scale.setScalar(isThisTarget ? 1.08 : 1.0);
            }
        });

        // Calculate reticle offset
        const maxOffset = 125;
        const xOffset = Math.max(-maxOffset, Math.min(maxOffset, deltaYaw * 1.5));
        const yOffset = Math.max(-maxOffset, Math.min(maxOffset, -deltaPitch * 2.2));

        const degRemaining = Math.round(Math.hypot(deltaYaw, deltaPitch));
        let statusIcon = '🧭';
        let statusText = `Arahkan ke: ${targetPanel.name}`;
        let shutterHint = `Arahkan ke ${targetPanel.short}`;

        if (isAligned) {
            statusIcon = '✨';
            statusText = `Pas di ${targetPanel.name}! Siap Nempel`;
            shutterHint = `📸 Pasang ${targetPanel.name}`;
        } else if (Math.abs(deltaYaw) > ALIGN_TOLERANCE_YAW) {
            statusIcon = deltaYaw > 0 ? '👉' : '👈';
            statusText = deltaYaw > 0 ? `Putar KANAN ${Math.round(deltaYaw)}°` : `Putar KIRI ${Math.abs(Math.round(deltaYaw))}°`;
            shutterHint = `Putar ${degRemaining}°`;
        } else {
            statusIcon = deltaPitch > 0 ? '🔼' : '🔽';
            statusText = deltaPitch > 0 ? 'Dongakkan sedikit HP' : 'Tundukkan sedikit HP';
            shutterHint = 'Sesuaikan Kemiringan';
        }

        if (typeof this.onSensorUpdate === 'function') {
            this.onSensorUpdate({
                xOffset,
                yOffset,
                deltaYaw,
                deltaPitch,
                isAligned,
                statusIcon,
                statusText,
                shutterHint,
                needsSensorPermission: typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function' && !this.hasGyroSensor,
                activePanel: targetPanel,
                capturedCount: this.capturedPanelsCount,
                totalCount: this.spherePanels.length
            });
        }
    }

    // ==========================================
    // CAPTURE & SNAP LOGIC
    // ==========================================

    captureCurrentStep() {
        const canvas = document.createElement('canvas');
        canvas.width = 1024;
        canvas.height = 1024;
        const ctx = canvas.getContext('2d');

        const video = this.videoElement;
        const hasActiveVideo = video && video.videoWidth > 0 && video.videoHeight > 0;

        if (hasActiveVideo) {
            const size = Math.min(video.videoWidth, video.videoHeight);
            const startX = (video.videoWidth - size) / 2;
            const startY = (video.videoHeight - size) / 2;
            ctx.drawImage(video, startX, startY, size, size, 0, 0, 1024, 1024);
        } else {
            // Elegant procedural photo snapshot when webcam hardware has no active feed
            const grad = ctx.createLinearGradient(0, 0, 1024, 1024);
            grad.addColorStop(0, '#1e1b4b');
            grad.addColorStop(0.5, '#312e81');
            grad.addColorStop(1, '#0f172a');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, 1024, 1024);

            // Subtle Grid
            ctx.strokeStyle = 'rgba(99, 102, 241, 0.25)';
            ctx.lineWidth = 4;
            for (let i = 0; i <= 1024; i += 64) {
                ctx.beginPath();
                ctx.moveTo(i, 0); ctx.lineTo(i, 1024);
                ctx.moveTo(0, i); ctx.lineTo(1024, i);
                ctx.stroke();
            }

            // Target reticle
            ctx.strokeStyle = '#10b981';
            ctx.lineWidth = 10;
            ctx.beginPath();
            ctx.arc(512, 512, 200, 0, Math.PI * 2);
            ctx.stroke();

            const isSphere = this.mode === 'sphere_panels';
            const label = isSphere
                ? (this.spherePanels[this.activeTargetPanelIndex]?.name || `Panel #${this.activeTargetPanelIndex + 1}`)
                : this.getCurrentStep().name;

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 52px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, 512, 470);

            ctx.fillStyle = '#6ee7b7';
            ctx.font = 'bold 30px monospace';
            const heading = Math.round(this.currentHeading || 0);
            const pitch = Math.round((this.currentBeta !== null ? this.currentBeta : 90) - 90);
            ctx.fillText(`KOMPAS: ${heading}° | ELEVASI: ${pitch}°`, 512, 550);

            ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.font = '22px sans-serif';
            ctx.fillText('TOURBOX 360 PHOTO CAPTURE', 512, 620);
        }

        const dataUrl = canvas.toDataURL('image/jpeg', 0.90);

        if (this.mode === 'sphere_panels') {
            return this.snapSpherePanel(canvas, dataUrl);
        } else {
            return this.snapCubemapStep(canvas, dataUrl);
        }
    }

    /**
     * Snap & Place Wall Panel in 3D AR Space
     */
    snapSpherePanel(canvas, dataUrl) {
        const targetIdx = this.activeTargetPanelIndex;
        const panel = this.spherePanels[targetIdx];
        if (!panel) return null;

        // Freeze frame onto 3D Mesh
        panel.isCaptured = true;
        panel.image = dataUrl;

        if (this.panelMeshes[targetIdx]) {
            const tex = new THREE.CanvasTexture(canvas);
            tex.colorSpace = THREE.SRGBColorSpace;
            tex.generateMipmaps = true;

            const mesh = this.panelMeshes[targetIdx];
            mesh.material.map = tex;
            mesh.material.opacity = 1.0;
            mesh.material.transparent = false;
            mesh.material.side = THREE.DoubleSide;
            mesh.material.needsUpdate = true;
            mesh.scale.set(1.12, 1.12, 1);
            setTimeout(() => mesh.scale.set(1.0, 1.0, 1), 350);
        }

        this.capturedPanelsCount++;
        this.lastSnappedCanvas = canvas;

        if (typeof this.onPanelSnapped === 'function') {
            this.onPanelSnapped(this.capturedPanelsCount, this.spherePanels.length, this.spherePanels);
        }

        // Update seam puzzle guide and sensor targeting
        this.updatePuzzleGuides();
        this.updateSensorFeedback();

        // If completed all panels
        if (this.capturedPanelsCount >= this.spherePanels.length) {
            this.finishSphereCapture();
        }

        return dataUrl;
    }

    finishSphereCapture() {
        let capturedOnly = this.spherePanels.filter(p => p.isCaptured);
        // If finish triggered but 0 panels taken, take current frame so user always gets output!
        if (capturedOnly.length === 0) {
            this.captureCurrentStep();
            capturedOnly = this.spherePanels.filter(p => p.isCaptured);
        }

        if (typeof this.onComplete === 'function') {
            this.onComplete({
                view_type: 'sphere_panels',
                panels: capturedOnly
            });
        }
    }

    finishCubemapEarly() {
        if (Object.keys(this.capturedImages).length === 0) {
            this.captureCurrentStep();
        }

        if (typeof this.onComplete === 'function') {
            this.onComplete({
                view_type: 'cubemap',
                ...this.capturedImages
            });
        }
    }

    snapCubemapStep(canvas, dataUrl) {
        const currentStep = this.getCurrentStep();
        this.capturedImages[currentStep.key] = dataUrl;
        this.capturedCanvases[this.currentStepIndex] = canvas;

        // "Naro Tembok" - Instantly pin photo onto the 3D Cube Wall Mesh in AR!
        if (this.arCubeMeshes && this.arCubeMeshes[currentStep.key]) {
            const tex = new THREE.CanvasTexture(canvas);
            tex.colorSpace = THREE.SRGBColorSpace;
            tex.generateMipmaps = true;

            const mesh = this.arCubeMeshes[currentStep.key];
            mesh.material.map = tex;
            mesh.material.opacity = 1.0;
            mesh.material.transparent = false;
            mesh.material.side = THREE.DoubleSide;
            mesh.material.needsUpdate = true;
            mesh.scale.set(1.08, 1.08, 1.08);
            setTimeout(() => mesh.scale.set(1.0, 1.0, 1.0), 350);
        }

        if (this.currentStepIndex === 0) {
            this.baseHeading = this.currentHeading;
            this.baseAlpha = this.currentAlpha;
        }

        if (this.currentStepIndex < this.steps.length - 1) {
            this.currentStepIndex++;
            this.updatePuzzleGuides();
            this.updateSensorFeedback();
            if (typeof this.onStepChange === 'function') {
                this.onStepChange(this.currentStepIndex, this.getCurrentStep(), this.capturedImages);
            }
        } else {
            if (typeof this.onComplete === 'function') {
                this.onComplete({
                    view_type: 'cubemap',
                    ...this.capturedImages
                });
            }
        }

        return dataUrl;
    }

    retakeStep(index) {
        if (index >= 0 && index < this.steps.length) {
            const step = this.steps[index];
            delete this.capturedImages[step.key];
            this.capturedCanvases[index] = null;

            // Reset mesh back to ghost material
            if (this.arCubeMeshes && this.arCubeMeshes[step.key]) {
                const mesh = this.arCubeMeshes[step.key];
                mesh.material.map = this.createGhostCubeTexture(step.name, step.targetLabel || step.compassName, step.icon, true);
                mesh.material.transparent = true;
                mesh.material.opacity = 0.88;
                mesh.material.needsUpdate = true;
            }

            this.currentStepIndex = index;
            if (index === 0) {
                this.baseHeading = null;
                this.baseAlpha = null;
            }
            this.updatePuzzleGuides();
            this.updateSensorFeedback();
            if (typeof this.onStepChange === 'function') {
                this.onStepChange(this.currentStepIndex, this.getCurrentStep(), this.capturedImages);
            }
        }
    }

    getCurrentStep() {
        return this.steps[this.currentStepIndex];
    }

    updateSensorFeedback() {
        if (this.mode === 'sphere_panels') {
            const pitchDeg = (this.currentBeta !== null) ? this.currentBeta - 90 : 0;
            this.updateActiveArPanel(this.currentHeading, pitchDeg);
            return;
        }

        const step = this.getCurrentStep();
        const YAW_TOLERANCE = 10;
        const PITCH_TOLERANCE = 15;
        const currentPitch = (this.currentBeta !== null) ? this.currentBeta - 90 : 0;

        let deltaYaw = 0;
        let deltaPitch = 0;
        let xOffset = 0;
        let yOffset = 0;
        let isAligned = false;
        let statusText = 'Mengalibrasi sensor...';
        let statusIcon = '🧭';
        let shutterHint = 'Arahkan Kamera';

        if (step.targetYaw === null && step.targetPitch === 0) {
            deltaPitch = currentPitch;
            isAligned = Math.abs(deltaPitch) <= PITCH_TOLERANCE;
            xOffset = 0;
            yOffset = Math.max(-100, Math.min(100, -deltaPitch * 2.5));

            if (isAligned) {
                statusIcon = '✨';
                statusText = 'Arah Bebas - Siap Jepret!';
                shutterHint = '📸 Jepret Tembok Depan';
            } else {
                statusIcon = '📐';
                statusText = currentPitch > 0 ? '👇 Tundukkan sedikit HP' : '👆 Tegakkan sedikit HP';
                shutterHint = 'Posisikan HP Tegak Lurus';
            }
        } else if (step.targetPitch === 90) {
            isAligned = currentPitch >= 65;
            xOffset = 0;
            yOffset = Math.max(-110, Math.min(110, (85 - currentPitch) * 2.5));

            if (isAligned) {
                statusIcon = '✨';
                statusText = 'Plafon Pas! Siap Jepret';
                shutterHint = '📸 Jepret Plafon';
            } else {
                statusIcon = '🔼';
                statusText = 'Arahkan HP tegak lurus ke ATAS';
                shutterHint = 'Dongakkan HP ke Atas';
            }
        } else if (step.targetPitch === -90) {
            isAligned = currentPitch <= -65;
            xOffset = 0;
            yOffset = Math.max(-110, Math.min(110, (-85 - currentPitch) * 2.5));

            if (isAligned) {
                statusIcon = '✨';
                statusText = 'Lantai Pas! Siap Selesai';
                shutterHint = '📸 Selesai & Buat 3D';
            } else {
                statusIcon = '🔽';
                statusText = 'Arahkan HP tegak lurus ke BAWAH';
                shutterHint = 'Tundukkan HP ke Bawah';
            }
        } else if (this.baseHeading !== null && step.targetYaw !== null) {
            let clockwiseTurn = (this.currentHeading - this.baseHeading) % 360;
            if (clockwiseTurn < 0) clockwiseTurn += 360;

            let remainingYaw = step.targetYaw - clockwiseTurn;
            while (remainingYaw > 180) remainingYaw -= 360;
            while (remainingYaw < -180) remainingYaw += 360;

            deltaYaw = remainingYaw;
            deltaPitch = currentPitch;

            const maxOffset = 125;
            xOffset = Math.max(-maxOffset, Math.min(maxOffset, deltaYaw * 1.35));
            yOffset = Math.max(-maxOffset, Math.min(maxOffset, -deltaPitch * 2.5));

            isAligned = Math.abs(deltaYaw) <= YAW_TOLERANCE && Math.abs(deltaPitch) <= PITCH_TOLERANCE;

            if (isAligned) {
                statusIcon = '✨';
                statusText = 'Sudut Pas! Siap Jepret';
                shutterHint = `📸 Jepret ${step.name}`;
            } else {
                const remainingDeg = Math.abs(Math.round(deltaYaw));
                if (Math.abs(deltaYaw) > YAW_TOLERANCE) {
                    statusIcon = deltaYaw > 0 ? '👉' : '👈';
                    statusText = deltaYaw > 0 ? `Putar ke KANAN ${remainingDeg}°` : `Putar balik ke KIRI ${remainingDeg}°`;
                    shutterHint = deltaYaw > 0 ? `Putar ke Kanan ${remainingDeg}°` : `Putar balik ke Kiri ${remainingDeg}°`;
                } else {
                    statusIcon = '📐';
                    statusText = deltaPitch > 0 ? 'Tundukkan sedikit HP' : 'Tegakkan sedikit HP';
                    shutterHint = 'Posisikan HP Tegak';
                }
            }
        } else {
            isAligned = true;
            statusIcon = '🎯';
            statusText = 'Klik Shutter untuk Mengambil Foto';
            shutterHint = 'Ambil Foto';
        }

        this.isAligned = isAligned;

        if (typeof this.onSensorUpdate === 'function') {
            this.onSensorUpdate({
                xOffset,
                yOffset,
                deltaYaw,
                deltaPitch,
                isAligned,
                statusIcon,
                statusText,
                shutterHint,
                needsSensorPermission: typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function' && !this.hasGyroSensor
            });
        }
    }

    updatePuzzleGuides() {
        if (this.mode === 'sphere_panels') {
            if (this.leftPuzzleCanvas && this.lastSnappedCanvas && this.capturedPanelsCount >= 1) {
                // Show 14% strip from the right edge of previous panel to guide seamless alignment
                const prev = this.lastSnappedCanvas;
                const sliceW = Math.round(prev.width * 0.14);
                const sliceX = prev.width - sliceW;
                this.leftPuzzleCanvas.width = sliceW;
                this.leftPuzzleCanvas.height = prev.height;
                const pctx = this.leftPuzzleCanvas.getContext('2d');
                pctx.clearRect(0, 0, sliceW, prev.height);
                pctx.drawImage(
                    prev,
                    sliceX, 0, sliceW, prev.height,
                    0, 0, sliceW, prev.height
                );
            }
            return;
        }

        if (this.mode !== 'cubemap_6') return;

        if (this.leftPuzzleCanvas && this.currentStepIndex >= 1 && this.currentStepIndex <= 3) {
            const prevCanvas = this.capturedCanvases[this.currentStepIndex - 1];
            if (prevCanvas) {
                const sliceW = Math.round(prevCanvas.width * 0.25);
                const sliceX = prevCanvas.width - sliceW;
                this.leftPuzzleCanvas.width = sliceW;
                this.leftPuzzleCanvas.height = prevCanvas.height;
                const pctx = this.leftPuzzleCanvas.getContext('2d');
                pctx.clearRect(0, 0, sliceW, prevCanvas.height);
                pctx.drawImage(
                    prevCanvas,
                    sliceX, 0, sliceW, prevCanvas.height,
                    0, 0, sliceW, prevCanvas.height
                );
            }
        }

        if (this.rightPuzzleCanvas && this.currentStepIndex === 3) {
            const firstCanvas = this.capturedCanvases[0];
            if (firstCanvas) {
                const sliceW = Math.round(firstCanvas.width * 0.20);
                this.rightPuzzleCanvas.width = sliceW;
                this.rightPuzzleCanvas.height = firstCanvas.height;
                const prctx = this.rightPuzzleCanvas.getContext('2d');
                prctx.clearRect(0, 0, sliceW, firstCanvas.height);
                prctx.drawImage(
                    firstCanvas,
                    0, 0, sliceW, firstCanvas.height,
                    0, 0, sliceW, firstCanvas.height
                );
            }
        }
    }
}
