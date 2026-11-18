import * as THREE from 'three';

/**
 * TourBox 360 Core Engine using Three.js
 * Handles Wide-Angle Camera, 6-face Cubemap, Nadir Blur Censor,
 * 3D Hotspot Raycasting (Navigation & Info), and Smooth View Transitions.
 */
export class TourBoxEngine {
    constructor(options = {}) {
        this.container = typeof options.container === 'string'
            ? document.getElementById(options.container)
            : options.container;

        if (!this.container) {
            throw new Error('TourBoxEngine: Container element not found.');
        }

        this.isEditor = !!options.isEditor;
        this.views = options.views || [];
        this.currentViewId = options.initialViewId || (this.views[0]?.id ?? null);
        this.onHotspotClick = options.onHotspotClick || null;
        this.onViewChange = options.onViewChange || null;
        this.onSceneClick = options.onSceneClick || null; // Used in editor to place hotspots

        // Camera parameters (NFR-04: Wide Angle)
        this.defaultFov = 82;
        this.minFov = 40;
        this.maxFov = 95;

        // Spherical coordinates for camera rotation
        this.lat = 0;
        this.lon = 0;
        this.phi = 0;
        this.theta = 0;

        // Interaction state
        this.isUserInteracting = false;
        this.onMouseDownMouseX = 0;
        this.onMouseDownMouseY = 0;
        this.onMouseDownLon = 0;
        this.onMouseDownLat = 0;

        // Touch interaction for mobile
        this.touchStartDist = 0;

        this.hotspotMeshes = [];
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();

        this.initThree();
        this.setupEventListeners();
        this.animate = this.animate.bind(this);
        requestAnimationFrame(this.animate);

        if (this.currentViewId) {
            this.loadView(this.currentViewId);
        }
    }

    initThree() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        // 1. Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0a0f1d);

        // 2. Camera (Wide angle Perspective Camera)
        this.camera = new THREE.PerspectiveCamera(this.defaultFov, width / height, 1, 2000);
        this.camera.target = new THREE.Vector3(0, 0, 0);

        // 3. Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(width, height);
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.0;
        this.container.appendChild(this.renderer.domElement);

        // 4. Cubic Room 6-Planes with anti-seam overlap (Presisi tanpa distorsi mirror)
        const ROOM_SIZE = 1002; // Overlap 0.2% agar sambungan sudut tidak bergaris hitam
        const ROOM_DIST = 500;

        this.roomGroup = new THREE.Group();
        this.planeMeshes = {};
        this.cubeMaterials = {};

        // Plane configurations facing inward directly to center (0,0,0)
        // 0: Depan (PZ / Utara)      -> pos [0, 0, -ROOM_DIST], rot [0, 0, 0]
        // 1: Kanan (PX / Timur)      -> pos [ROOM_DIST, 0, 0], rot [0, -Math.PI / 2, 0]
        // 2: Belakang (NZ / Selatan) -> pos [0, 0, ROOM_DIST], rot [0, Math.PI, 0]
        // 3: Kiri (NX / Barat)       -> pos [-ROOM_DIST, 0, 0], rot [0, Math.PI / 2, 0]
        // 4: Plafon (PY / Atas)      -> pos [0, ROOM_DIST, 0], rot [Math.PI / 2, 0, 0]
        // 5: Lantai (NY / Bawah)     -> pos [0, -ROOM_DIST, 0], rot [-Math.PI / 2, 0, 0]
        const planeConfigs = [
            { key: 'pz', name: 'Depan (Utara)', pos: [0, 0, -ROOM_DIST], rot: [0, 0, 0] },
            { key: 'px', name: 'Kanan (Timur)', pos: [ROOM_DIST, 0, 0], rot: [0, -Math.PI / 2, 0] },
            { key: 'nz', name: 'Belakang (Selatan)', pos: [0, 0, ROOM_DIST], rot: [0, Math.PI, 0] },
            { key: 'nx', name: 'Kiri (Barat)', pos: [-ROOM_DIST, 0, 0], rot: [0, Math.PI / 2, 0] },
            { key: 'py', name: 'Plafon (Atas)', pos: [0, ROOM_DIST, 0], rot: [Math.PI / 2, 0, 0] },
            { key: 'ny', name: 'Lantai (Bawah)', pos: [0, -ROOM_DIST, 0], rot: [-Math.PI / 2, 0, 0] }
        ];

        planeConfigs.forEach(cfg => {
            const mat = new THREE.MeshBasicMaterial({
                color: 0x1e293b,
                side: THREE.DoubleSide
            });
            const geo = new THREE.PlaneGeometry(ROOM_SIZE, ROOM_SIZE);
            const mesh = new THREE.Mesh(geo, mat);
            mesh.position.set(...cfg.pos);
            mesh.rotation.set(...cfg.rot);
            mesh.name = cfg.key;
            this.roomGroup.add(mesh);
            this.planeMeshes[cfg.key] = mesh;
            this.cubeMaterials[cfg.key] = mat;
        });

        this.scene.add(this.roomGroup);

        // 5. Group for Multi-Panel Sphere Mode ("Naro Tembok" 3D)
        this.spherePanelsGroup = new THREE.Group();
        this.scene.add(this.spherePanelsGroup);

        // 6. Nadir Sensor Disc (FR-14: Sensor bagian bawah / tripod)
        this.setupNadirCensor();

        // 7. Group for Hotspots
        this.hotspotsGroup = new THREE.Group();
        this.scene.add(this.hotspotsGroup);
    }

    setupNadirCensor() {
        // Create circular disk at y = -490
        const diskGeo = new THREE.CircleGeometry(160, 48);
        diskGeo.rotateX(-Math.PI / 2);

        // Procedural radial blur texture for nadir
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');

        const grad = ctx.createRadialGradient(256, 256, 30, 256, 256, 250);
        grad.addColorStop(0, 'rgba(15, 23, 42, 0.95)');
        grad.addColorStop(0.7, 'rgba(15, 23, 42, 0.7)');
        grad.addColorStop(1, 'rgba(15, 23, 42, 0.0)');

        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.arc(256, 256, 250, 0, Math.PI * 2);
        ctx.fill();

        // Optional TourBox logo text in center of nadir cap
        ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.font = 'bold 24px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('TOURBOX 360', 256, 264);

        const nadirTexture = new THREE.CanvasTexture(canvas);
        const nadirMat = new THREE.MeshBasicMaterial({
            map: nadirTexture,
            transparent: true,
            depthWrite: false,
            side: THREE.DoubleSide
        });

        this.nadirMesh = new THREE.Mesh(diskGeo, nadirMat);
        this.nadirMesh.position.y = -490;
        this.nadirMesh.visible = false;
        this.scene.add(this.nadirMesh);
    }

    setNadirCensor(enabled, type = 'blur') {
        if (this.nadirMesh) {
            this.nadirMesh.visible = !!enabled;
        }
    }

    /**
     * Safely load a texture, handling Data URLs and remote URLs without crossOrigin issues.
     */
    loadTexture(url, onLoad, onError) {
        if (!url) return;
        const loader = new THREE.TextureLoader();
        // data: and blob: URLs should not use crossOrigin = 'anonymous' to prevent browser security blocks
        if (!url.startsWith('data:') && !url.startsWith('blob:')) {
            loader.setCrossOrigin('anonymous');
        }
        loader.load(
            url,
            (tex) => {
                tex.colorSpace = THREE.SRGBColorSpace;
                tex.generateMipmaps = true;
                tex.minFilter = THREE.LinearMipmapLinearFilter;
                tex.needsUpdate = true;
                if (typeof onLoad === 'function') onLoad(tex);
            },
            undefined,
            (err) => {
                console.warn('TourBoxEngine: Texture load warning for', typeof url === 'string' ? url.substring(0, 50) : url, err);
                if (typeof onError === 'function') onError(err);
            }
        );
    }

    /**
     * Register or update a view in the engine's view list.
     */
    addView(view) {
        if (!view || !view.id) return;
        const existingIdx = this.views.findIndex(v => v.id == view.id);
        if (existingIdx >= 0) {
            this.views[existingIdx] = view;
        } else {
            this.views.unshift(view);
        }
    }

    /**
     * Load a view either by viewId or directly by view object.
     */
    loadView(viewOrId) {
        let view = null;
        if (typeof viewOrId === 'object' && viewOrId !== null) {
            view = viewOrId;
            this.addView(view);
        } else {
            view = this.views.find(v => v.id == viewOrId);
        }

        if (!view) {
            console.warn(`TourBoxEngine: View "${viewOrId}" not found in engine views.`);
            return;
        }

        this.currentViewId = view.id;

        // Update nadir censor
        this.setNadirCensor(view.has_nadir_censor, view.nadir_censor_type);

        if (view.view_type === 'sphere_panels' && Array.isArray(view.panels) && view.panels.length > 0) {
            // Mode 2: Multi-Panel Sphere ("Naro Tembok" 3D)
            this.roomGroup.visible = false;
            this.spherePanelsGroup.visible = true;

            // Clear existing panels
            while (this.spherePanelsGroup.children.length > 0) {
                const child = this.spherePanelsGroup.children[0];
                if (child.material && child.material.map) child.material.map.dispose();
                if (child.material) child.material.dispose();
                if (child.geometry) child.geometry.dispose();
                this.spherePanelsGroup.remove(child);
            }

            const R = 480;
            view.panels.forEach((p) => {
                const pitch = typeof p.pitch === 'number' ? p.pitch : 0;
                const yaw = typeof p.yaw === 'number' ? p.yaw : 0;

                const phi = THREE.MathUtils.degToRad(90 - pitch);
                const theta = THREE.MathUtils.degToRad(yaw);

                const x = R * Math.sin(phi) * Math.sin(theta);
                const y = R * Math.cos(phi);
                const z = -R * Math.sin(phi) * Math.cos(theta);

                // Anti-overlap sizing matched to 45° FOV
                const pw = p.width || (p.ring === 'equator' ? 400 : (p.ring === 'zenith' || p.ring === 'nadir' ? 480 : 420));
                const ph = p.height || (p.ring === 'equator' ? 400 : (p.ring === 'zenith' || p.ring === 'nadir' ? 480 : 380));
                const geo = new THREE.PlaneGeometry(pw, ph);

                const mat = new THREE.MeshBasicMaterial({
                    color: 0xffffff,
                    side: THREE.DoubleSide
                });

                if (p.image) {
                    this.loadTexture(p.image, (tex) => {
                        mat.map = tex;
                        mat.needsUpdate = true;
                    });
                }

                const mesh = new THREE.Mesh(geo, mat);
                mesh.position.set(x, y, z);
                // Look directly at camera at (0, 0, 0).
                // DO NOT rotateY(Math.PI) which flips it away from camera causing backface culling!
                mesh.lookAt(0, 0, 0);
                this.spherePanelsGroup.add(mesh);
            });
        } else {
            // Mode 1: 6-Plane Cubic Room (Depan, Kanan, Belakang, Kiri, Plafon, Lantai)
            this.roomGroup.visible = true;
            this.spherePanelsGroup.visible = false;

            // Clear any lingering sphere panels
            while (this.spherePanelsGroup.children.length > 0) {
                const child = this.spherePanelsGroup.children[0];
                if (child.material && child.material.map) child.material.map.dispose();
                if (child.material) child.material.dispose();
                if (child.geometry) child.geometry.dispose();
                this.spherePanelsGroup.remove(child);
            }

            // Face order: Front(pz), Right(px), Back(nz), Left(nx), Top(py), Bottom(ny)
            const faceKeys = ['pz', 'px', 'nz', 'nx', 'py', 'ny'];

            faceKeys.forEach((key) => {
                const url = view.cubemap?.[key];
                if (url) {
                    this.loadTexture(
                        url,
                        (texture) => {
                            if (this.cubeMaterials[key]) {
                                this.cubeMaterials[key].map = texture;
                                this.cubeMaterials[key].color.setHex(0xffffff);
                                this.cubeMaterials[key].needsUpdate = true;
                            }
                        },
                        () => {
                            // Fallback procedural face if failed
                            if (this.cubeMaterials[key]) {
                                this.cubeMaterials[key].map = this.createFallbackFaceTexture(view.name, key.toUpperCase());
                                this.cubeMaterials[key].color.setHex(0xffffff);
                                this.cubeMaterials[key].needsUpdate = true;
                            }
                        }
                    );
                } else {
                    // Procedural placeholder face
                    if (this.cubeMaterials[key]) {
                        this.cubeMaterials[key].map = this.createFallbackFaceTexture(view.name, key.toUpperCase());
                        this.cubeMaterials[key].color.setHex(0xffffff);
                        this.cubeMaterials[key].needsUpdate = true;
                    }
                }
            });
        }

        // Set default view rotation
        if (typeof view.default_yaw === 'number') {
            this.lon = view.default_yaw;
        }
        if (typeof view.default_pitch === 'number') {
            this.lat = view.default_pitch;
        }

        // Render hotspots
        this.renderHotspots(view.hotspots || []);

        if (typeof this.onViewChange === 'function') {
            this.onViewChange(view);
        }
    }

    createFallbackFaceTexture(viewName, faceName) {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');

        // Background
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, 512, 512);

        // Grid
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 4;
        for (let i = 0; i <= 512; i += 64) {
            ctx.beginPath();
            ctx.moveTo(i, 0); ctx.lineTo(i, 512);
            ctx.moveTo(0, i); ctx.lineTo(512, i);
            ctx.stroke();
        }

        // Label
        ctx.fillStyle = '#6366f1';
        ctx.font = 'bold 36px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(viewName || 'TourBox 360', 256, 230);

        ctx.fillStyle = '#94a3b8';
        ctx.font = '24px monospace';
        ctx.fillText(`Sisi: ${faceName}`, 256, 280);

        return new THREE.CanvasTexture(canvas);
    }

    renderHotspots(hotspots) {
        // Clear existing hotspot meshes
        while (this.hotspotsGroup.children.length > 0) {
            const obj = this.hotspotsGroup.children[0];
            this.hotspotsGroup.remove(obj);
            if (obj.material?.map) obj.material.map.dispose();
            if (obj.material) obj.material.dispose();
        }
        this.hotspotMeshes = [];

        hotspots.forEach(hotspot => {
            const sprite = this.createHotspotSprite(hotspot);
            sprite.position.set(hotspot.pos_x, hotspot.pos_y, hotspot.pos_z);
            sprite.userData = hotspot;
            this.hotspotsGroup.add(sprite);
            this.hotspotMeshes.push(sprite);
        });
    }

    createHotspotSprite(hotspot) {
        const canvas = document.createElement('canvas');
        canvas.width = 128;
        canvas.height = 128;
        const ctx = canvas.getContext('2d');

        const isNav = hotspot.type === 'navigation';

        // Outer pulsing ring
        ctx.fillStyle = isNav ? 'rgba(99, 102, 241, 0.3)' : 'rgba(14, 165, 233, 0.3)';
        ctx.beginPath();
        ctx.arc(64, 64, 58, 0, Math.PI * 2);
        ctx.fill();

        // Inner glowing badge
        ctx.fillStyle = isNav ? '#6366f1' : '#0ea5e9';
        ctx.beginPath();
        ctx.arc(64, 64, 42, 0, Math.PI * 2);
        ctx.fill();

        ctx.lineWidth = 4;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        // Icon inside
        ctx.fillStyle = '#ffffff';
        if (isNav) {
            // Arrow pointing up/forward
            ctx.beginPath();
            ctx.moveTo(64, 40);
            ctx.lineTo(84, 68);
            ctx.lineTo(72, 68);
            ctx.lineTo(72, 88);
            ctx.lineTo(56, 88);
            ctx.lineTo(56, 68);
            ctx.lineTo(44, 68);
            ctx.closePath();
            ctx.fill();
        } else {
            // 'i' Information icon
            ctx.font = 'bold 44px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('i', 64, 64);
        }

        const texture = new THREE.CanvasTexture(canvas);
        const spriteMat = new THREE.SpriteMaterial({
            map: texture,
            transparent: true,
            depthTest: false
        });

        const sprite = new THREE.Sprite(spriteMat);
        sprite.scale.set(48, 48, 1);
        return sprite;
    }

    setupEventListeners() {
        const dom = this.renderer.domElement;

        // Pointer down
        const onPointerDown = (clientX, clientY) => {
            this.isUserInteracting = true;
            this.onMouseDownMouseX = clientX;
            this.onMouseDownMouseY = clientY;
            this.onMouseDownLon = this.lon;
            this.onMouseDownLat = this.lat;
        };

        // Pointer move
        const onPointerMove = (clientX, clientY) => {
            if (this.isUserInteracting) {
                this.lon = (this.onMouseDownMouseX - clientX) * 0.15 + this.onMouseDownLon;
                this.lat = (clientY - this.onMouseDownMouseY) * 0.15 + this.onMouseDownLat;
            }

            // Raycasting hover detection
            const rect = dom.getBoundingClientRect();
            this.mouse.x = ((clientX - rect.left) / rect.width) * 2 - 1;
            this.mouse.y = -((clientY - rect.top) / rect.height) * 2 + 1;

            this.raycaster.setFromCamera(this.mouse, this.camera);
            const intersects = this.raycaster.intersectObjects(this.hotspotMeshes);

            if (intersects.length > 0) {
                dom.style.cursor = 'pointer';
            } else {
                dom.style.cursor = this.isUserInteracting ? 'grabbing' : 'default';
            }
        };

        // Pointer up
        const onPointerUp = (clientX, clientY) => {
            const dragDistance = Math.hypot(
                clientX - this.onMouseDownMouseX,
                clientY - this.onMouseDownMouseY
            );

            this.isUserInteracting = false;
            dom.style.cursor = 'default';

            // Only trigger click if not dragged
            if (dragDistance < 5) {
                const rect = dom.getBoundingClientRect();
                this.mouse.x = ((clientX - rect.left) / rect.width) * 2 - 1;
                this.mouse.y = -((clientY - rect.top) / rect.height) * 2 + 1;

                this.raycaster.setFromCamera(this.mouse, this.camera);
                const intersects = this.raycaster.intersectObjects(this.hotspotMeshes);

                if (intersects.length > 0) {
                    const hitHotspot = intersects[0].object.userData;
                    if (typeof this.onHotspotClick === 'function') {
                        this.onHotspotClick(hitHotspot);
                    }
                } else if (this.isEditor && typeof this.onSceneClick === 'function') {
                    // In editor mode, clicking scene returns 3D coordinates on cubic room or sphere panels for new hotspot
                    const targets = (this.spherePanelsGroup.visible && this.spherePanelsGroup.children.length > 0)
                        ? this.spherePanelsGroup.children
                        : this.roomGroup.children;
                    const cubeIntersects = this.raycaster.intersectObjects(targets);
                    if (cubeIntersects.length > 0) {
                        const point = cubeIntersects[0].point;
                        // Normalize to distance ~350 units from camera
                        const normalizedPoint = point.clone().normalize().multiplyScalar(350);
                        this.onSceneClick({
                            pos_x: Math.round(normalizedPoint.x * 10) / 10,
                            pos_y: Math.round(normalizedPoint.y * 10) / 10,
                            pos_z: Math.round(normalizedPoint.z * 10) / 10
                        });
                    }
                }
            }
        };

        // Mouse events
        dom.addEventListener('mousedown', (e) => onPointerDown(e.clientX, e.clientY));
        window.addEventListener('mousemove', (e) => onPointerMove(e.clientX, e.clientY));
        window.addEventListener('mouseup', (e) => onPointerUp(e.clientX, e.clientY));

        // Touch events (Mobile support NFR-01)
        dom.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                onPointerDown(e.touches[0].clientX, e.touches[0].clientY);
            } else if (e.touches.length === 2) {
                this.touchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
            }
        }, { passive: true });

        dom.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) {
                onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
            } else if (e.touches.length === 2) {
                // Pinch to zoom
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const delta = (this.touchStartDist - dist) * 0.1;
                this.camera.fov = Math.max(this.minFov, Math.min(this.maxFov, this.camera.fov + delta));
                this.camera.updateProjectionMatrix();
                this.touchStartDist = dist;
            }
        }, { passive: true });

        dom.addEventListener('touchend', (e) => {
            if (e.changedTouches.length === 1) {
                onPointerUp(e.changedTouches[0].clientX, e.changedTouches[0].clientY);
            }
        });

        // Mouse Wheel (Zoom)
        dom.addEventListener('wheel', (e) => {
            e.preventDefault();
            this.camera.fov = Math.max(this.minFov, Math.min(this.maxFov, this.camera.fov + e.deltaY * 0.05));
            this.camera.updateProjectionMatrix();
        }, { passive: false });

        // Resize handler
        window.addEventListener('resize', () => this.onResize());
    }

    onResize() {
        if (!this.container) return;
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    animate() {
        requestAnimationFrame(this.animate);

        // Clamp latitude to prevent flipping upside down
        this.lat = Math.max(-85, Math.min(85, this.lat));

        this.phi = THREE.MathUtils.degToRad(90 - this.lat);
        this.theta = THREE.MathUtils.degToRad(this.lon);

        // Direct forward along -Z when lon = 0 (Depan / Utara)
        // Turning to +X when lon = 90 (Kanan / Timur)
        // Turning to +Z when lon = 180 (Belakang / Selatan)
        // Turning to -X when lon = 270 (Kiri / Barat)
        const targetX = 500 * Math.sin(this.phi) * Math.sin(this.theta);
        const targetY = 500 * Math.cos(this.phi);
        const targetZ = -500 * Math.sin(this.phi) * Math.cos(this.theta);

        this.camera.lookAt(targetX, targetY, targetZ);

        // Hotspot pulsing animation
        const time = Date.now() * 0.003;
        this.hotspotMeshes.forEach((mesh, index) => {
            const scale = 48 + Math.sin(time + index) * 4;
            mesh.scale.set(scale, scale, 1);
        });

        this.renderer.render(this.scene, this.camera);
    }

    destroy() {
        if (this.renderer && this.renderer.domElement) {
            this.renderer.domElement.remove();
            this.renderer.dispose();
        }
        Object.values(this.cubeMaterials).forEach(m => {
            if (m.map) m.map.dispose();
            m.dispose();
        });
        this.hotspotsGroup.clear();
    }
}
