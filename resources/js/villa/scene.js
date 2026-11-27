/*
 * The villa's 3D scene: renderer settings, lighting, and a camera that frames
 * the model the same way at every angle. Used by the home page viewer and by
 * scripts/render-villa-frames.mjs, which renders the still frames from it.
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { BOUNDS, createVilla } from './model.js';
import { FRAME_ASPECT } from './settings.js';

const FOV = 26;
const ELEVATION = THREE.MathUtils.degToRad(24); // camera height, as an angle
const MARGIN = 0.94; // share of the frame the model may fill

/**
 * Builds the scene on a new WebGL canvas. Throws if WebGL is not available.
 */
export function createScene({ pixelRatio = Math.min(window.devicePixelRatio || 1, 2), ...rendererOptions } = {}) {
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance', ...rendererOptions });
    renderer.setPixelRatio(pixelRatio);
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.5;
    pmrem.dispose();

    scene.add(new THREE.HemisphereLight('#fff8ee', '#a39882', 0.9));

    // Low afternoon sun from the left, so the cantilever throws its shadow across the deck.
    const sun = new THREE.DirectionalLight('#fff2df', 3.2);
    sun.position.set(-22, 19, 10);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -17, right: 17, top: 17, bottom: -17, near: 10, far: 70 });
    sun.shadow.camera.updateProjectionMatrix();
    sun.shadow.bias = -0.0003;
    sun.shadow.normalBias = 0.02;
    sun.shadow.radius = 3;
    scene.add(sun);

    const villa = createVilla();
    scene.add(villa);

    const camera = new THREE.PerspectiveCamera(FOV, FRAME_ASPECT, 1, 300);
    placeCamera(camera);

    return {
        canvas: renderer.domElement,
        ready: Promise.resolve(),

        setSize(width, height) {
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            // Narrower than the frames: show more above and below rather than cropping the sides,
            // so the model sits exactly where it does in a still frame scaled to fit.
            const tan = Math.tan(THREE.MathUtils.degToRad(FOV / 2));
            camera.fov = camera.aspect >= FRAME_ASPECT ? FOV : THREE.MathUtils.radToDeg(2 * Math.atan((tan * FRAME_ASPECT) / camera.aspect));
            camera.updateProjectionMatrix();
        },

        render(angle) {
            villa.rotation.y = angle;
            renderer.render(scene, camera);
        },
    };
}

/**
 * Puts the camera, looking down at a fixed angle, as close as it can get while
 * every bounding point fits in a frame of FRAME_ASPECT. With the camera's axes
 * fixed, each point gives a straight-line limit on its position.
 */
function placeCamera(camera) {
    const forward = new THREE.Vector3(0, -Math.sin(ELEVATION), -Math.cos(ELEVATION));
    const up = new THREE.Vector3(0, Math.cos(ELEVATION), -Math.sin(ELEVATION));
    const tanV = Math.tan(THREE.MathUtils.degToRad(FOV / 2)) * MARGIN;
    const tanH = tanV * FRAME_ASPECT;

    const points = BOUNDS.flatMap(([radius, height]) =>
        Array.from({ length: 48 }, (_, i) => {
            const turn = (i / 48) * Math.PI * 2;
            const point = new THREE.Vector3(Math.cos(turn) * radius, height, Math.sin(turn) * radius);
            return { x: point.x, y: point.dot(up), z: point.dot(forward) };
        }),
    );

    let depth = Infinity; // camera position along `forward`
    for (const p of points) {
        depth = Math.min(depth, p.z - Math.abs(p.x) / tanH);
        for (const q of points) depth = Math.min(depth, (p.z + q.z) / 2 - (p.y - q.y) / (2 * tanV));
    }

    let low = -Infinity; // range of camera heights (along `up`) that fits everything
    let high = Infinity;
    for (const p of points) {
        low = Math.max(low, p.y - (p.z - depth) * tanV);
        high = Math.min(high, p.y + (p.z - depth) * tanV);
    }

    camera.position.copy(up).multiplyScalar((low + high) / 2).addScaledVector(forward, depth);
    camera.lookAt(camera.position.clone().add(forward));
}
