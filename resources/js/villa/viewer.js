/*
 * The interactive villa on the home page.
 *
 * Hover over the model and scroll: it turns right as you scroll down and left
 * as you scroll up. After half a turn either way the page scrolls on again, so
 * nobody gets stuck. It can also be dragged or swiped sideways, and turned with
 * the arrow keys. Frames are drawn only while it moves and is on screen.
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { BOUNDS, createVilla } from './model.js';

const REST = -0.55; // starting view: three-quarters, showing the front and the cantilever
const LIMIT = Math.PI; // how far scrolling turns it each way before the page scrolls on
const WHEEL_SPEED = 0.0035; // radians per pixel scrolled: about 20° per wheel notch
const DRAG_SPEED = 0.008; // radians per pixel dragged
const KEY_STEP = Math.PI / 12; // 15° per arrow key press
const PAUSE_MS = 400; // a pause in wheel input this long ends a scroll gesture
const ELEVATION = THREE.MathUtils.degToRad(24); // camera height, as an angle
const MARGIN = 0.94; // share of the frame the model may fill
const TURN = Math.PI * 2;

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
const wrap = (angle) => angle - TURN * Math.round(angle / TURN);

/** How far an angle is from the starting view: -π to π, keeping exactly ±π as it is. */
const offsetFrom = (angle) => (Math.abs(angle - REST) <= Math.PI + 1e-6 ? angle - REST : wrap(angle - REST));

/** Wheel distance in pixels, whatever unit the device reports. */
function wheelPixels(event) {
    const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
    if (event.deltaMode === 1) return delta * 16;
    if (event.deltaMode === 2) return delta * window.innerHeight;
    return delta;
}

export function mountVilla(stage, { reduceMotion = false } = {}) {
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    } catch {
        stage.dataset.villaState = 'unsupported';
        return;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;

    const canvas = renderer.domElement;
    canvas.setAttribute('aria-hidden', 'true');
    stage.prepend(canvas);

    // Scene ----------------------------------------------------------------------
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

    const camera = new THREE.PerspectiveCamera(26, 1, 1, 300);
    const forward = new THREE.Vector3(0, -Math.sin(ELEVATION), -Math.cos(ELEVATION));
    const up = new THREE.Vector3(0, Math.cos(ELEVATION), -Math.sin(ELEVATION));
    const bounds = BOUNDS.flatMap(([radius, height]) =>
        Array.from({ length: 48 }, (_, i) => new THREE.Vector3(Math.cos((i / 48) * TURN) * radius, height, Math.sin((i / 48) * TURN) * radius)),
    );

    // State ----------------------------------------------------------------------
    // `angle` is what is drawn; it eases towards `target` at `speed` (per second).
    // It starts turned away and turns into view the first time it is seen.
    let angle = reduceMotion ? REST : REST - 1.2;
    let target = angle;
    let speed = 7;
    let introduced = reduceMotion;
    let visible = false;
    let frame = 0;
    let lastTime = 0;

    const track = stage.closest('figure')?.querySelector('[data-villa-track]');
    const numbers = new Intl.NumberFormat(document.documentElement.lang || undefined);

    function draw() {
        villa.rotation.y = angle;
        renderer.render(scene, camera);
        track?.style.setProperty('--turn', ((offsetFrom(angle) / Math.PI + 1) / 2).toFixed(4));
    }

    function animate() {
        if (frame || !visible) return;
        lastTime = performance.now();
        frame = requestAnimationFrame(tick);
    }

    function tick(now) {
        frame = 0;
        const seconds = Math.min(0.25, (now - lastTime) / 1000);
        lastTime = now;
        angle += (target - angle) * (1 - Math.exp(-speed * seconds));
        if (Math.abs(target - angle) < 0.001) angle = target;
        draw();

        if (angle !== target) {
            if (visible) frame = requestAnimationFrame(tick);
        } else {
            speed = 7;
            normalize();
            announce();
        }
    }

    /** After dragging past half a turn, drops the whole turns (the view does not change). */
    function normalize() {
        const shift = target - REST - offsetFrom(target);
        target -= shift;
        angle -= shift;
    }

    function turnTo(offset, rate) {
        introduced = true;
        target = REST + clamp(offset, -LIMIT, LIMIT);
        speed = rate;
        animate();
    }

    function announce() {
        const degrees = Math.round(THREE.MathUtils.radToDeg(offsetFrom(target)));
        const { textFront, textRight, textLeft } = stage.dataset;
        stage.setAttribute('aria-valuenow', String(degrees));
        stage.setAttribute(
            'aria-valuetext',
            degrees === 0 ? textFront : (degrees > 0 ? textRight : textLeft).replace(':degrees', numbers.format(Math.abs(degrees))),
        );
    }

    function resize() {
        const width = stage.clientWidth;
        const height = stage.clientHeight;
        if (!width || !height) return;

        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        fitCamera();
        draw();
    }

    /**
     * Moves the camera (looking down at a fixed angle) as close as it can get
     * while every bounding point stays inside the frame. With the camera's axes
     * fixed, each point gives a straight-line limit on its position.
     */
    function fitCamera() {
        const tanV = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * MARGIN;
        const tanH = tanV * camera.aspect;
        const points = bounds.map((p) => ({ x: p.x, y: p.dot(up), z: p.dot(forward) }));

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

    // Hover and scroll -------------------------------------------------------------
    // A page scrolling under a resting pointer also sends pointer events, at an
    // unchanged position, so only a pointer that really moves counts as hovering.
    // A resting pointer counts too, once the wheel has paused: a wheel gesture that
    // was scrolling the page carries on scrolling it as the villa passes by.
    let hovering = false;
    let pointer = null;
    let lastPageWheel = -Infinity; // input time of the last wheel event that scrolled the page

    // Runs after the villa's own handler; whatever that handler let through scrolled the page.
    window.addEventListener('wheel', (event) => event.defaultPrevented || (lastPageWheel = event.timeStamp), { passive: true });

    stage.addEventListener('pointerleave', () => {
        hovering = false;
        pointer = null;
    });

    stage.addEventListener(
        'wheel',
        (event) => {
            if (event.ctrlKey || !event.cancelable) return; // pinch-zoom, or a scroll already under way
            if (!hovering && event.timeStamp - lastPageWheel < PAUSE_MS) return; // passing by

            const pixels = wheelPixels(event);
            if (!pixels) return;

            normalize();
            const offset = target - REST;
            if ((pixels > 0 && offset >= LIMIT - 0.001) || (pixels < 0 && offset <= -LIMIT + 0.001)) return;

            event.preventDefault();
            turnTo(offset + pixels * WHEEL_SPEED, 7);
        },
        { passive: false },
    );

    // Drag and swipe -----------------------------------------------------------------
    let drag = null;

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        drag = { id: event.pointerId, x: event.clientX, y: event.clientY, from: target, moving: false, lastX: event.clientX, lastTime: event.timeStamp, velocity: 0 };
    });

    stage.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'touch') {
            if (pointer && (event.clientX !== pointer.x || event.clientY !== pointer.y)) hovering = true;
            pointer = { x: event.clientX, y: event.clientY };
        }

        if (!drag || event.pointerId !== drag.id) return;
        const dx = event.clientX - drag.x;
        if (!drag.moving) {
            const dy = event.clientY - drag.y;
            if (Math.hypot(dx, dy) < 6) return;
            if (Math.abs(dy) > Math.abs(dx)) {
                drag = null; // an up-and-down swipe scrolls the page
                return;
            }
            drag.moving = true;
            stage.setPointerCapture(event.pointerId);
            stage.classList.add('is-dragging');
        }

        const elapsed = event.timeStamp - drag.lastTime;
        if (elapsed > 0) drag.velocity = (event.clientX - drag.lastX) / elapsed;
        drag.lastX = event.clientX;
        drag.lastTime = event.timeStamp;

        introduced = true;
        target = drag.from + dx * DRAG_SPEED;
        speed = 20;
        animate();
    });

    const endDrag = (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        if (drag.moving) {
            stage.classList.remove('is-dragging');
            // Let go while moving and it spins on a little.
            if (event.type === 'pointerup' && event.timeStamp - drag.lastTime < 80 && !reduceMotion) {
                target += clamp(drag.velocity, -2.5, 2.5) * 160 * DRAG_SPEED;
                speed = 3.5;
            }
            animate();
        }
        drag = null;
    };
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    // Keyboard ---------------------------------------------------------------------
    stage.addEventListener('keydown', (event) => {
        const step = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1 }[event.key];
        if (event.key !== 'Home' && !step) return;

        event.preventDefault();
        normalize();
        turnTo(event.key === 'Home' ? 0 : target - REST + step * KEY_STEP, 7);
    });

    // Visibility and size ------------------------------------------------------------
    new IntersectionObserver(
        ([entry]) => {
            visible = entry.isIntersecting;
            if (!introduced && entry.intersectionRatio >= 0.35) {
                introduced = true;
                target = REST;
                speed = 2.2;
            }
            animate();
        },
        { threshold: [0, 0.35] },
    ).observe(stage);

    new ResizeObserver(resize).observe(stage);
    canvas.addEventListener('webglcontextrestored', draw);

    resize();
    announce();
    Object.entries({ role: 'slider', tabindex: '0', 'aria-label': stage.dataset.label, 'aria-valuemin': '-180', 'aria-valuemax': '180' })
        .forEach(([name, value]) => stage.setAttribute(name, value));
    stage.dataset.villaState = 'ready';
}
