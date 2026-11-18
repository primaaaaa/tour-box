import * as THREE from '/build/three.module.js';
import { OrbitControls } from './jsm/controls/OrbitControls.js';

// ==========================================
// 1. THREE.JS 3D ENVIRONMENT (6-SIDED CUBIC ROOM)
// ==========================================
const canvas = document.querySelector('.web-gl');
const scene = new THREE.Scene();

// Sudut pandang kamera adaptif (otomatis menyesuaikan HP portrait agar sudut pandang LUAS)
const near = 0.1;
const far = 1000;

const camera = new THREE.PerspectiveCamera(80, window.innerWidth / window.innerHeight, near, far);
camera.position.set(0, 0, 0.1); // Di titik pusat ruangan 3D
scene.add(camera);

const renderer = new THREE.WebGLRenderer({
    antialias: true,
    canvas: canvas,
});
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableZoom = true;
controls.enablePan = false;
controls.enableDamping = true;
controls.dampingFactor = 0.05;
controls.rotateSpeed = -0.4;
controls.minDistance = 0.01;
controls.maxDistance = 40;

// Fungsi adaptif FOV: Di HP mode portrait (layar tegak 9:16/9:20),
// FOV vertikal otomatis diperluas agar sudut pandang horizontal tetap LEBAR (85°+)
// sehingga ruangan tampak luas, lega, dan tidak sempit/terpotong.
function updateCameraFov() {
    const aspect = window.innerWidth / window.innerHeight;
    camera.aspect = aspect;
    if (aspect < 1.0) {
        // Layar HP tegak: Kunci horizontal FOV di ~85° agar ruangan tampak luas
        const targetHFovRad = (85 * Math.PI) / 180;
        const vFovRad = 2 * Math.atan(Math.tan(targetHFovRad / 2) / aspect);
        camera.fov = Math.min(125, (vFovRad * 180) / Math.PI);
    } else {
        camera.fov = 80;
    }
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}
updateCameraFov();

// Status kamera: Ketika kamera aktif, hentikan loop render WebGL
// agar browser dapat menjalankan stream kamera di 60 FPS murni tanpa lag!
let isCameraOpen = false;

// Parameter Geometri Ruangan 3D 6 Sisi (Box Room)
const ROOM_SIZE = 100.2; // Sedikit overlap agar sambungan sudut tidak bergaris
const ROOM_DIST = 50.0;

let roomGroup = null;

// Fungsi membangun ruangan 3D 6 Sisi Presisi (Depan, Kanan, Belakang, Kiri, Plafon, Lantai)
function createCubicRoom(materials) {
    if (roomGroup) {
        scene.remove(roomGroup);
    }
    roomGroup = new THREE.Group();

    // Orientasi 6 Sisi Ruangan menghadap langsung ke titik tengah kamera:
    // 0: Depan (Front)    -> Normal ke +Z (menghadap viewer di 0,0,0)
    // 1: Kanan (Right)    -> Normal ke -X (menghadap viewer)
    // 2: Belakang (Back)  -> Normal ke -Z (menghadap viewer)
    // 3: Kiri (Left)      -> Normal ke +X (menghadap viewer)
    // 4: Plafon (Top)     -> Normal ke -Y (menghadap viewer)
    // 5: Lantai (Bottom)  -> Normal ke +Y (menghadap viewer)
    const planeConfigs = [
        { pos: [0, 0, -ROOM_DIST], rot: [0, 0, 0] },
        { pos: [ROOM_DIST, 0, 0], rot: [0, -Math.PI / 2, 0] },
        { pos: [0, 0, ROOM_DIST], rot: [0, Math.PI, 0] },
        { pos: [-ROOM_DIST, 0, 0], rot: [0, Math.PI / 2, 0] },
        { pos: [0, ROOM_DIST, 0], rot: [Math.PI / 2, 0, 0] },
        { pos: [0, -ROOM_DIST, 0], rot: [-Math.PI / 2, 0, 0] }
    ];

    for (let i = 0; i < 6; i++) {
        const planeGeo = new THREE.PlaneGeometry(ROOM_SIZE, ROOM_SIZE);
        const planeMesh = new THREE.Mesh(planeGeo, materials[i]);
        planeMesh.position.set(...planeConfigs[i].pos);
        planeMesh.rotation.set(...planeConfigs[i].rot);
        roomGroup.add(planeMesh);
    }

    scene.add(roomGroup);
    updateCameraFov();
}

// Muat Contoh Demo Ruang Tamu Bawaan (6 Sisi)
function loadDemoTour() {
    const loader = new THREE.TextureLoader();
    const texUtara = loader.load('./models/ruang-tamu/utara.jpeg');
    const texTimur = loader.load('./models/ruang-tamu/timur.jpeg');
    const texSelatan = loader.load('./models/ruang-tamu/selatan.jpeg');
    const texBarat = loader.load('./models/ruang-tamu/barat.jpeg');
    const texTop = loader.load('./models/ruang-tamu/top.jpeg');
    const texBot = loader.load('./models/ruang-tamu/bot.jpeg');

    const demoMaterials = [
        new THREE.MeshBasicMaterial({ map: texUtara }),   // 0: Depan (Utara)
        new THREE.MeshBasicMaterial({ map: texTimur }),   // 1: Kanan (Timur)
        new THREE.MeshBasicMaterial({ map: texSelatan }), // 2: Belakang (Selatan)
        new THREE.MeshBasicMaterial({ map: texBarat }),   // 3: Kiri (Barat)
        new THREE.MeshBasicMaterial({ map: texTop }),     // 4: Plafon (Top)
        new THREE.MeshBasicMaterial({ map: texBot }),     // 5: Lantai (Bottom)
    ];

    createCubicRoom(demoMaterials);
    camera.position.set(0, 0, 0.1);
    controls.target.set(0, 0, -1);
    controls.update();
}

// Inisialisasi awal dengan contoh demo
loadDemoTour();

// Animation loop (Berhenti merender saat kamera sedang aktif agar 100% bebas lag)
const animate = () => {
    requestAnimationFrame(animate);
    if (!isCameraOpen) {
        controls.update();
        renderer.render(scene, camera);
    }
};
animate();

// Resize handling dengan update FOV otomatis
window.addEventListener('resize', updateCameraFov);


// ==========================================
// 2. GUIDED CAPTURE SYSTEM (SIDE-STRIP PUZZLE UX)
// ==========================================

// 6 Langkah Pengambilan Foto Ruangan Presisi
const STEPS = [
    {
        name: 'Depan (0°)',
        targetYaw: null, // Bebas arah awal!
        targetPitch: 0,
        targetLabel: 'Bebas Arah',
        title: 'Langkah 1: Tembok DEPAN',
        desc: 'Arahkan kamera tegak sejajar dada, bebas ke dinding mana pun.',
        icon: '📷'
    },
    {
        name: 'Kanan (+90°)',
        targetYaw: 90,
        targetPitch: 0,
        targetLabel: 'Putar +90° Kanan',
        title: 'Langkah 2: Tembok KANAN',
        desc: 'Putar badan 90° ke KANAN. Sambungkan garis objek di potongan puzzle kiri!',
        icon: '➡️'
    },
    {
        name: 'Belakang (180°)',
        targetYaw: 180,
        targetPitch: 0,
        targetLabel: 'Putar 180° Belakang',
        title: 'Langkah 3: Tembok BELAKANG',
        desc: 'Putar badan 90° lagi ke KANAN menghadap ke belakang ruangan.',
        icon: '⬇️'
    },
    {
        name: 'Kiri (-90°)',
        targetYaw: 270,
        targetPitch: 0,
        targetLabel: 'Putar -90° Kiri',
        title: 'Langkah 4: Tembok KIRI',
        desc: 'Putar badan 90° lagi ke KANAN. Sambungkan potongan kiri dan penutup kanan!',
        icon: '⬅️'
    },
    {
        name: 'Plafon (Atas)',
        targetYaw: null,
        targetPitch: 90,
        targetLabel: 'Dongak +90° ke Plafon',
        title: 'Langkah 5: Langit-langit (PLAFON)',
        desc: 'Dongakkan kamera HP tegak lurus ke atas menghadap plafon ruangan.',
        icon: '🔼'
    },
    {
        name: 'Lantai (Bawah)',
        targetYaw: null,
        targetPitch: -90,
        targetLabel: 'Tunduk -90° ke Lantai',
        title: 'Langkah 6: LANTAI Ruangan',
        desc: 'Tundukkan kamera HP tegak lurus ke bawah menghadap lantai ruangan.',
        icon: '🔽'
    }
];

let currentStep = 0;
let capturedCanvases = new Array(6).fill(null);
let cameraStream = null;
let currentVideoTrack = null;
let isAligned = false;

// Sensor Gyroscope State
let baseAlpha = null;      // Arah acuan kompas saat user pertama kali jepret Depan
let currentAlpha = 0;     // Kompas HP (0-360)
let currentBeta = 0;      // Kemiringan atas-bawah (-180 s/d 180)
let currentGamma = 0;     // Kemiringan kiri-kanan (-90 s/d 90)
let hasGyroSensor = false;

// Multi-Camera / Ultra-Wide Lens State
let availableVideoDevices = [];
let selectedCameraIndex = 0;
let currentZoomFactor = 1.0;

// DOM Elements
const welcomeModal = document.getElementById('welcome-modal');
const modalBtnStart = document.getElementById('modal-btn-start');
const modalBtnDemo = document.getElementById('modal-btn-demo');
const btnStartRecord = document.getElementById('btn-start-record');
const btnDemo = document.getElementById('btn-demo');

const cameraOverlay = document.getElementById('camera-overlay');
const cameraVideo = document.getElementById('camera-video');
const viewfinderBox = document.getElementById('viewfinder-box');
const cameraFlash = document.getElementById('camera-flash');

// Side-Strip Puzzle Guides Elements
const puzzleGuideLeft = document.getElementById('puzzle-guide-left');
const puzzleStripCanvas = document.getElementById('puzzle-strip-canvas');
const puzzleGuideRight = document.getElementById('puzzle-guide-right');
const puzzleStripRightCanvas = document.getElementById('puzzle-strip-right-canvas');

// Reticle & Gyro Elements
const centerReticle = document.getElementById('center-reticle');
const targetAnchor = document.getElementById('target-anchor');
const angleStatusBadge = document.getElementById('angle-status-badge');
const angleStatusIcon = document.getElementById('angle-status-icon');
const angleStatusText = document.getElementById('angle-status-text');

// Header & Navigation Controls
const stepNumberText = document.getElementById('step-number');
const stepTitleText = document.getElementById('step-title');
const stepDegreeText = document.getElementById('step-target-degree');
const stepDescText = document.getElementById('step-desc');
const stepIcon = document.getElementById('step-icon');

const btnZoomWide = document.getElementById('btn-zoom-wide');
const btnZoom1x = document.getElementById('btn-zoom-1x');
const btnToggleLens = document.getElementById('btn-toggle-lens');

const btnShutter = document.getElementById('btn-shutter');
const shutterHint = document.getElementById('shutter-hint');
const btnRetakeStep = document.getElementById('btn-retake-step');
const btnCancelCamera = document.getElementById('btn-cancel-camera');
const processingOverlay = document.getElementById('processing-overlay');


// ==========================================
// 3. MULTI-CAMERA & HARDWARE ZOOM HANDLER (WIDE FOV)
// ==========================================
async function detectCameraDevices() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return;
        }
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoInputs = devices.filter(device => device.kind === 'videoinput');

        // Filter kamera belakang (environment/rear)
        const rearCameras = videoInputs.filter(device => {
            const label = device.label.toLowerCase();
            return label.includes('back') || label.includes('rear') || label.includes('environment') || !label.includes('front');
        });

        availableVideoDevices = rearCameras.length > 0 ? rearCameras : videoInputs;

        // Deteksi lensa ultra-wide (0.5x)
        const ultraWideIdx = availableVideoDevices.findIndex(dev => {
            const l = dev.label.toLowerCase();
            return l.includes('wide') || l.includes('0.5') || l.includes('ultra') || l.includes('aux');
        });

        if (ultraWideIdx !== -1) {
            btnZoomWide.classList.remove('hidden');
            selectedCameraIndex = ultraWideIdx;
            btnZoomWide.classList.add('active');
            btnZoom1x.classList.remove('active');
        } else {
            // Jika tidak ada label eksplisit wide, default ke kamera belakang pertama
            selectedCameraIndex = 0;
            btnZoom1x.classList.add('active');
        }

        if (availableVideoDevices.length <= 1) {
            btnToggleLens.style.display = 'none';
        } else {
            btnToggleLens.style.display = 'inline-flex';
        }
    } catch (err) {
        console.warn('Gagal mendeteksi daftar kamera:', err);
    }
}

async function setZoomLevel(zoomFactor) {
    currentZoomFactor = zoomFactor;
    if (zoomFactor < 1.0) {
        btnZoomWide.classList.add('active');
        btnZoom1x.classList.remove('active');
    } else {
        btnZoom1x.classList.add('active');
        btnZoomWide.classList.remove('active');
    }

    // Coba terapkan hardware zoom jika didukung
    if (currentVideoTrack && typeof currentVideoTrack.getCapabilities === 'function') {
        const capabilities = currentVideoTrack.getCapabilities();
        if (capabilities.zoom) {
            try {
                const targetZoom = Math.max(capabilities.zoom.min, Math.min(capabilities.zoom.max, zoomFactor));
                await currentVideoTrack.applyConstraints({
                    advanced: [{ zoom: targetZoom }]
                });
                return;
            } catch (e) {
                console.warn('Zoom hardware tidak didukung:', e);
            }
        }
    }

    // Jika hardware zoom tidak tersedia, coba ganti deviceId ke kamera ultra-wide
    if (zoomFactor < 1.0 && availableVideoDevices.length > 1) {
        const wideIdx = availableVideoDevices.findIndex(dev => {
            const l = dev.label.toLowerCase();
            return l.includes('wide') || l.includes('0.5') || l.includes('ultra');
        });
        if (wideIdx !== -1 && wideIdx !== selectedCameraIndex) {
            selectedCameraIndex = wideIdx;
            await restartCameraStream();
        }
    } else if (zoomFactor >= 1.0 && availableVideoDevices.length > 1) {
        // Balik ke kamera standar (1x)
        const stdIdx = availableVideoDevices.findIndex(dev => {
            const l = dev.label.toLowerCase();
            return !l.includes('wide') && !l.includes('0.5') && !l.includes('ultra');
        });
        if (stdIdx !== -1 && stdIdx !== selectedCameraIndex) {
            selectedCameraIndex = stdIdx;
            await restartCameraStream();
        }
    }
}

async function toggleCameraLens() {
    if (availableVideoDevices.length > 1) {
        selectedCameraIndex = (selectedCameraIndex + 1) % availableVideoDevices.length;
        await restartCameraStream();
    }
}

async function restartCameraStream() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
    }
    await initCameraStream();
}

async function initCameraStream() {
    // Meminta resolusi 4:3 asli sensor tanpa crop digital
    const constraints = {
        audio: false,
        video: {
            width: { ideal: 1920 },
            height: { ideal: 1440 },
            aspectRatio: { ideal: 4 / 3 }
        }
    };

    if (availableVideoDevices.length > 0 && availableVideoDevices[selectedCameraIndex]) {
        constraints.video.deviceId = { exact: availableVideoDevices[selectedCameraIndex].deviceId };
    } else {
        constraints.video.facingMode = { ideal: 'environment' };
    }

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        cameraVideo.srcObject = cameraStream;
        currentVideoTrack = cameraStream.getVideoTracks()[0];

        await new Promise(resolve => {
            cameraVideo.onloadedmetadata = () => {
                cameraVideo.play();
                resolve();
            };
        });

        // PENTING: Panggil detectCameraDevices SETELAH izin kamera didapatkan
        // agar label kamera (misal "Ultra Wide", "0.5x") tidak kosong!
        await detectCameraDevices();

        // Terapkan zoom terlebar secara otomatis jika didukung oleh sensor HP
        if (currentVideoTrack && typeof currentVideoTrack.getCapabilities === 'function') {
            const caps = currentVideoTrack.getCapabilities();
            if (caps.zoom) {
                try {
                    const minZ = caps.zoom.min;
                    await currentVideoTrack.applyConstraints({
                        advanced: [{ zoom: minZ }]
                    });
                    if (minZ < 1.0) {
                        btnZoomWide.classList.add('active');
                        btnZoom1x.classList.remove('active');
                        btnZoomWide.classList.remove('hidden');
                    }
                } catch (e) {
                    console.log('Default zoom applied');
                }
            }
        }
    } catch (err) {
        console.error('Error saat mengakses kamera:', err);
        alert('Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan di browser!');
        closeCamera();
    }
}


// ==========================================
// 4. CAMERA CONTROLS & SIDE-STRIP PUZZLE LOGIC
// ==========================================
async function startCamera() {
    welcomeModal.classList.add('hidden');
    cameraOverlay.classList.remove('hidden');

    // Hentikan render loop Three.js untuk membebaskan GPU
    isCameraOpen = true;

    // Reset State
    currentStep = 0;
    baseAlpha = null;
    capturedCanvases = new Array(6).fill(null);

    // Reset UI thumbnail slots
    for (let i = 0; i < 6; i++) {
        const slot = document.querySelector(`.slot-item[data-step="${i}"]`);
        const thumb = document.getElementById(`thumb-${i}`);
        if (slot) slot.classList.remove('completed', 'active');
        if (thumb) {
            thumb.style.backgroundImage = 'none';
            thumb.textContent = (i + 1).toString();
        }
    }

    await initCameraStream();
    initOrientationSensors();
    updateStepUI();
}

function closeCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    cameraOverlay.classList.add('hidden');
    isCameraOpen = false;
}

// Update tampilan panduan step & puzzle guide
function updateStepUI() {
    const step = STEPS[currentStep];

    stepNumberText.textContent = `Langkah ${currentStep + 1} dari 6`;
    stepTitleText.textContent = step.title;
    stepDegreeText.textContent = step.targetLabel;
    stepDescText.textContent = step.desc;
    stepIcon.textContent = step.icon;

    // Update active slot
    document.querySelectorAll('.progress-slots .slot-item').forEach((item, index) => {
        if (index === currentStep) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    btnRetakeStep.disabled = currentStep === 0;

    // Update Side-Strip Puzzle Guides
    updatePuzzleGuides();

    // Trigger update sensor
    updateSensorFeedback();
}

// Update potongan puzzle foto sebelumnya di tepi layar kamera
function updatePuzzleGuides() {
    // Sembunyikan guide secara default
    puzzleGuideLeft.classList.add('hidden');
    puzzleGuideRight.classList.add('hidden');

    // Langkah 1 (Depan): Bebas arah, belum ada foto sebelumnya
    if (currentStep === 0) {
        return;
    }

    // Langkah 2, 3, 4 (Dinding): Munculkan potongan tepi kanan foto sebelumnya di sisi KIRI kamera
    if (currentStep >= 1 && currentStep <= 3) {
        const prevCanvas = capturedCanvases[currentStep - 1];
        if (prevCanvas) {
            // Potong 25% tepi kanan dari foto sebelumnya
            const sliceWidthRatio = 0.25;
            const sliceX = Math.round(prevCanvas.width * (1.0 - sliceWidthRatio));
            const sliceW = Math.round(prevCanvas.width * sliceWidthRatio);

            puzzleStripCanvas.width = sliceW;
            puzzleStripCanvas.height = prevCanvas.height;

            const pctx = puzzleStripCanvas.getContext('2d');
            pctx.clearRect(0, 0, sliceW, prevCanvas.height);
            pctx.drawImage(
                prevCanvas,
                sliceX, 0, sliceW, prevCanvas.height,
                0, 0, sliceW, prevCanvas.height
            );

            puzzleGuideLeft.classList.remove('hidden');
        }
    }

    // Langkah 4 (Tembok Kiri): Selain potongan kiri dari foto ke-3,
    // tampilkan juga potongan 20% tepi kiri dari foto pertama (Depan) di sisi KANAN kamera
    // untuk menutup putaran 360° secara sempurna!
    if (currentStep === 3) {
        const firstCanvas = capturedCanvases[0];
        if (firstCanvas) {
            const sliceWidthRatio = 0.20;
            const sliceW = Math.round(firstCanvas.width * sliceWidthRatio);

            puzzleStripRightCanvas.width = sliceW;
            puzzleStripRightCanvas.height = firstCanvas.height;

            const prctx = puzzleStripRightCanvas.getContext('2d');
            prctx.clearRect(0, 0, sliceW, firstCanvas.height);
            prctx.drawImage(
                firstCanvas,
                0, 0, sliceW, firstCanvas.height,
                0, 0, sliceW, firstCanvas.height
            );

            puzzleGuideRight.classList.remove('hidden');
        }
    }

    // Langkah 5 (Plafon) dan Langkah 6 (Lantai):
    // Puzzle strip tidak diperlukan karena foto diarahkan ke atas/bawah
}


// ==========================================
// 5. ORIENTATION & GYROSCOPE SENSOR CALIBRATION (FIXED DIRECTION)
// ==========================================
function initOrientationSensors() {
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
        DeviceOrientationEvent.requestPermission()
            .then(permissionState => {
                if (permissionState === 'granted') {
                    window.addEventListener('deviceorientation', handleOrientation, true);
                    hasGyroSensor = true;
                }
            })
            .catch(console.error);
    } else if ('ondeviceorientation' in window) {
        window.addEventListener('deviceorientation', handleOrientation, true);
        hasGyroSensor = true;
    }
}

function handleOrientation(event) {
    if (event.alpha !== null) {
        currentAlpha = event.alpha;
        currentBeta = event.beta;
        currentGamma = event.gamma;
        updateSensorFeedback();
    }
}

function updateSensorFeedback() {
    const step = STEPS[currentStep];

    // Toleransi agar mudah pas dan presisi
    const YAW_TOLERANCE = 12;   // Derajat horizontal
    const PITCH_TOLERANCE = 15; // Derajat vertikal

    // Konversi pitch: Di HP, kamera belakang menghadap plafon saat layar menghadap ke bawah (beta ≈ 180)
    // Tunduk ke lantai saat layar menghadap ke atas (beta ≈ 0)
    const currentPitch = (currentBeta !== null) ? currentBeta - 90 : 0;

    let deltaYaw = 0;
    let deltaPitch = 0;

    // LANGKAH 1: BEBAS ARAH AWAL
    if (step.targetYaw === null && step.targetPitch === 0) {
        deltaPitch = currentPitch;
        isAligned = Math.abs(deltaPitch) <= PITCH_TOLERANCE;

        targetAnchor.style.transform = `translate(0px, 0px)`;
        viewfinderBox.classList.toggle('aligned', isAligned);
        btnShutter.classList.toggle('shutter-ready', isAligned);

        if (isAligned) {
            angleStatusIcon.textContent = '✨';
            angleStatusText.textContent = 'Arah Awal Bebas - Siap Jepret!';
            shutterHint.textContent = '📸 Jepret Tembok Depan';
        } else {
            angleStatusIcon.textContent = '📐';
            angleStatusText.textContent = currentPitch > 0 ? '👇 Tundukkan sedikit HP' : '👆 Tegakkan sedikit HP';
            shutterHint.textContent = 'Posisikan HP Tegak';
        }
        return;
    }

    // LANGKAH 5: DONGAK KE ATAS (PLAFON)
    if (step.targetPitch === 90) {
        isAligned = currentPitch >= 65;
        const offsetPx = Math.max(-100, Math.min(100, (85 - currentPitch) * 2.5));
        targetAnchor.style.transform = `translate(0px, ${offsetPx}px)`;

        viewfinderBox.classList.toggle('aligned', isAligned);
        btnShutter.classList.toggle('shutter-ready', isAligned);

        if (isAligned) {
            angleStatusIcon.textContent = '✨';
            angleStatusText.textContent = 'Plafon Pas! Siap Jepret';
            shutterHint.textContent = '📸 Jepret Plafon';
        } else {
            angleStatusIcon.textContent = '🔼';
            angleStatusText.textContent = 'Arahkan HP tegak lurus ke ATAS';
            shutterHint.textContent = 'Dongakkan HP ke Atas';
        }
        return;
    }

    // LANGKAH 6: TUNDUK KE BAWAH (LANTAI)
    if (step.targetPitch === -90) {
        isAligned = currentPitch <= -65;
        const offsetPx = Math.max(-100, Math.min(100, (-85 - currentPitch) * 2.5));
        targetAnchor.style.transform = `translate(0px, ${offsetPx}px)`;

        viewfinderBox.classList.toggle('aligned', isAligned);
        btnShutter.classList.toggle('shutter-ready', isAligned);

        if (isAligned) {
            angleStatusIcon.textContent = '✨';
            angleStatusText.textContent = 'Lantai Pas! Siap Selesai';
            shutterHint.textContent = '📸 Selesai & Buat 3D';
        } else {
            angleStatusIcon.textContent = '🔽';
            angleStatusText.textContent = 'Arahkan HP tegak lurus ke BAWAH';
            shutterHint.textContent = 'Tundukkan HP ke Bawah';
        }
        return;
    }

    // LANGKAH 2, 3, 4: PUTAR HORIZONTAL DENGAN PANDUAN PUZZLE (FIX KANAN & KIRI SELARAS 100%)
    if (baseAlpha !== null && step.targetYaw !== null) {
        // Hitung berapa derajat user sudah berputar ke kanan (clockwise) dari posisi awal baseAlpha
        // Pada standar sensor kompas W3C: Putar ke KANAN membuat alpha berkurang
        let clockwiseTurn = (baseAlpha - currentAlpha) % 360;
        if (clockwiseTurn < 0) clockwiseTurn += 360;

        // Hitung sisa derajat yang diperlukan untuk mencapai target sudut
        // Jika remainingYaw > 0: Target berada di sebelah KANAN -> User harus putar ke KANAN
        // Jika remainingYaw < 0: User kelewatan ke kanan -> User harus putar balik ke KIRI
        let remainingYaw = step.targetYaw - clockwiseTurn;
        while (remainingYaw > 180) remainingYaw -= 360;
        while (remainingYaw < -180) remainingYaw += 360;

        deltaYaw = remainingYaw;
        deltaPitch = currentPitch;

        // Offset visual titik target melayang (🎯):
        // Jika deltaYaw > 0 (target di kanan) -> xOffset positif (titik melayang bergeser ke kanan)
        // Jika deltaYaw < 0 (target di kiri) -> xOffset negatif (titik melayang bergeser ke kiri)
        const maxOffset = 110;
        const xOffset = Math.max(-maxOffset, Math.min(maxOffset, deltaYaw * 3.5));
        const yOffset = Math.max(-maxOffset, Math.min(maxOffset, -deltaPitch * 3.0));

        targetAnchor.style.transform = `translate(${xOffset}px, ${yOffset}px)`;

        isAligned = Math.abs(deltaYaw) <= YAW_TOLERANCE && Math.abs(deltaPitch) <= PITCH_TOLERANCE;

        viewfinderBox.classList.toggle('aligned', isAligned);
        btnShutter.classList.toggle('shutter-ready', isAligned);

        if (isAligned) {
            angleStatusIcon.textContent = '🧩';
            angleStatusText.textContent = 'Sudut Pas! Cocokkan Garis Puzzle';
            shutterHint.textContent = `📸 Jepret ${step.name}`;
        } else {
            angleStatusIcon.textContent = '🧭';
            if (Math.abs(deltaYaw) > YAW_TOLERANCE) {
                const deg = Math.abs(Math.round(deltaYaw));
                // KONSISTEN DENGAN PETUNJUK ATAS:
                // deltaYaw > 0 artinya masih kurang ke KANAN -> Beritahu "Putar KANAN"
                // deltaYaw < 0 artinya kelewatan -> Beritahu "Putar KIRI"
                angleStatusText.textContent = deltaYaw > 0 ? `👉 Putar KANAN ${deg}°` : `👈 Putar KIRI ${deg}°`;
            } else {
                angleStatusText.textContent = deltaPitch > 0 ? '👇 Tundukkan sedikit HP' : '👆 Tegakkan sedikit HP';
            }
            shutterHint.textContent = 'Putar HP Mengikuti Target';
        }
    }
}


// ==========================================
// 6. PHOTO CAPTURE & PUZZLE SLICING
// ==========================================
function captureFrame() {
    if (!cameraVideo.videoWidth || !cameraVideo.videoHeight) return;

    // Jika ini adalah foto pertama (Depan), kunci sudut hadap kompas saat ini sebagai patokan (0°)
    if (currentStep === 0) {
        baseAlpha = currentAlpha;
    }

    // Efek Flash Foto
    cameraFlash.classList.add('flash-active');
    setTimeout(() => {
        cameraFlash.classList.remove('flash-active');
    }, 120);

    // Ambil crop 1:1 bujur sangkar resolusi tinggi (1440x1440) dari tengah video kamera
    const size = Math.min(cameraVideo.videoWidth, cameraVideo.videoHeight);
    const startX = (cameraVideo.videoWidth - size) / 2;
    const startY = (cameraVideo.videoHeight - size) / 2;

    const frameCanvas = document.createElement('canvas');
    frameCanvas.width = 1440;
    frameCanvas.height = 1440;
    const ctx = frameCanvas.getContext('2d');

    ctx.drawImage(cameraVideo, startX, startY, size, size, 0, 0, 1440, 1440);

    // Simpan canvas untuk tekstur Three.js
    capturedCanvases[currentStep] = frameCanvas;

    const photoDataUrl = frameCanvas.toDataURL('image/jpeg', 0.88);

    // Update thumbnail slot UI
    const slot = document.querySelector(`.slot-item[data-step="${currentStep}"]`);
    const thumb = document.getElementById(`thumb-${currentStep}`);
    if (slot && thumb) {
        slot.classList.add('completed');
        thumb.textContent = '';
        thumb.style.backgroundImage = `url(${photoDataUrl})`;
    }

    // Pindah ke langkah berikutnya atau selesai
    if (currentStep < STEPS.length - 1) {
        currentStep++;
        updateStepUI();
    } else {
        // Semua 6 foto sudah selesai diambil!
        finishGuidedTour();
    }
}

// Selesai: Terapkan 6 Foto ke Ruangan 3D Presisi (Cubic Room)
function finishGuidedTour() {
    closeCamera();
    processingOverlay.classList.remove('hidden');

    setTimeout(() => {
        const userMaterials = new Array(STEPS.length);

        // Petakan 6 foto hasil jepretan pengguna ke 6 bidang ruangan
        for (let i = 0; i < 6; i++) {
            const canvasFace = capturedCanvases[i];
            const texture = new THREE.CanvasTexture(canvasFace);
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.generateMipmaps = true;
            texture.minFilter = THREE.LinearMipmapLinearFilter;
            userMaterials[i] = new THREE.MeshBasicMaterial({
                map: texture,
                side: THREE.FrontSide
            });
        }

        createCubicRoom(userMaterials);

        // Reset camera ke titik pusat menghadap ke depan
        camera.position.set(0, 0, 0.1);
        controls.target.set(0, 0, -1);
        controls.update();

        processingOverlay.classList.add('hidden');
    }, 600);
}

// Tombol ulangi langkah sebelumnya
function retakePreviousStep() {
    if (currentStep > 0) {
        currentStep--;
        if (currentStep === 0) {
            baseAlpha = null; // Reset patokan agar user bebas memilih arah hadap lagi
        }
        const slot = document.querySelector(`.slot-item[data-step="${currentStep}"]`);
        const thumb = document.getElementById(`thumb-${currentStep}`);
        if (slot && thumb) {
            slot.classList.remove('completed');
            thumb.style.backgroundImage = 'none';
            thumb.textContent = (currentStep + 1).toString();
        }
        capturedCanvases[currentStep] = null;

        updateStepUI();
    }
}


// ==========================================
// 7. EVENT LISTENERS
// ==========================================
btnShutter.addEventListener('click', captureFrame);
btnRetakeStep.addEventListener('click', retakePreviousStep);
btnToggleLens.addEventListener('click', toggleCameraLens);
btnZoomWide.addEventListener('click', () => setZoomLevel(0.5));
btnZoom1x.addEventListener('click', () => setZoomLevel(1.0));

btnCancelCamera.addEventListener('click', () => {
    closeCamera();
});

modalBtnStart.addEventListener('click', () => startCamera());
btnStartRecord.addEventListener('click', () => startCamera());

modalBtnDemo.addEventListener('click', () => {
    welcomeModal.classList.add('hidden');
    loadDemoTour();
});

btnDemo.addEventListener('click', () => {
    loadDemoTour();
});
