/*
 * The interactive villa at the top of the home page.
 *
 * It has four views, a quarter turn apart. Hover over the villa and scroll:
 * each scroll turns it straight to the next view (down) or back to the one
 * before (up). After the last view the page scrolls on again, so nobody gets
 * stuck. It can also be dragged or swiped (it settles on the nearest view),
 * turned with the arrow keys, or sent to a view with the markers below it.
 *
 * Where WebGL works the model is real 3D (scene.js, which brings in Three.js).
 * Other browsers get still frames (frames.js). Until either is ready, the page
 * shows the first frame as a picture.
 */
import { createFrameView } from './frames.js';
import { REST, VIEWS } from './settings.js';

const TURN = Math.PI * 2;
const QUARTER = TURN / VIEWS; // from one view to the next
const TURN_MS = 750; // how long the turn to the next view takes
const STEP_LOCK_MS = 650; // after each turn starts, further wheel input is held this long
const STEP_PIXELS = 30; // wheel distance that turns to the next view
const PAUSE_MS = 400; // a pause in wheel input this long ends a scroll gesture
const DRAG_SPEED = 0.008; // radians per pixel dragged

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
const easeOut = (t) => 1 - (1 - t) ** 3;

/** The view nearest an angle, counting whole turns (0 is the first view). */
const viewAt = (angle) => Math.round((angle - REST) / QUARTER);

/** Wheel distance in pixels, whatever unit the device reports. */
function wheelPixels(event) {
    const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
    if (event.deltaMode === 1) return delta * 16;
    if (event.deltaMode === 2) return delta * window.innerHeight;
    return delta;
}

/** Trackpads keep sending ever smaller wheel events after the fingers lift; those are not a new scroll. */
function coasting(sizes) {
    if (sizes.length < 6) return false;
    const mean = (list) => list.reduce((sum, size) => sum + size, 0) / list.length;
    return mean(sizes.slice(-3)) < mean(sizes.slice(0, -3)) * 0.7;
}

function supportsWebGL() {
    try {
        const context = document.createElement('canvas').getContext('webgl2');
        context?.getExtension('WEBGL_lose_context')?.loseContext();
        return Boolean(context);
    } catch {
        return false;
    }
}

/** The 3D view where WebGL works, otherwise the still frames. */
async function createView(stage) {
    if (supportsWebGL()) {
        try {
            const { createScene } = await import('./scene.js');
            return createScene();
        } catch (error) {
            console.warn('The 3D view could not start; showing still frames instead.', error);
        }
    }
    return createFrameView(stage.dataset.frames);
}

export async function mountVilla(stage, { reduceMotion = false } = {}) {
    const view = await createView(stage);
    await view.ready;

    const { canvas } = view;
    canvas.classList.add('villa-stage__view');
    canvas.setAttribute('aria-hidden', 'true');
    stage.append(canvas);

    let angle = REST; // what is drawn
    let goal = 0; // the view shown or being turned to, counted without wrapping round
    let tween = null; // the turn under way: { from, to, start, duration }
    let visible = false;
    let frame = 0;

    const numbers = new Intl.NumberFormat(document.documentElement.lang || undefined);
    const current = () => ((goal % VIEWS) + VIEWS) % VIEWS;

    // One marker per view, below the villa.
    const markers = stage.closest('figure')?.querySelector('[data-villa-dots]');
    const dots = markers
        ? Array.from({ length: VIEWS }, (_, index) => {
              const dot = document.createElement('button');
              dot.type = 'button';
              dot.setAttribute('aria-label', markers.dataset.label.replace(':n', numbers.format(index + 1)));
              dot.addEventListener('click', () => showView(index));
              markers.append(dot);
              return dot;
          })
        : [];

    function draw() {
        view.render(angle);
    }

    function animate() {
        if (frame || !visible) return;
        frame = requestAnimationFrame(tick);
    }

    function tick(now) {
        frame = 0;
        if (tween) {
            const t = tween.duration ? clamp((now - tween.start) / tween.duration, 0, 1) : 1;
            angle = tween.from + (tween.to - tween.from) * easeOut(t);
            if (t === 1) {
                tween = null;
                goal = current(); // drop whole turns; the view looks the same
                angle = REST + goal * QUARTER;
            }
        }
        draw();
        if (tween && visible) frame = requestAnimationFrame(tick);
    }

    /** Turns to view `next`, counted like `goal` (it may run past the last view to wrap round). */
    function turnTo(next) {
        goal = next;
        tween = { from: angle, to: REST + goal * QUARTER, start: performance.now(), duration: reduceMotion ? 0 : TURN_MS };
        announce();
        animate();
    }

    /** Turns to view `index` (0 to VIEWS - 1) the short way round. */
    function showView(index) {
        let steps = index - current();
        if (steps > VIEWS / 2) steps -= VIEWS;
        if (steps < -VIEWS / 2) steps += VIEWS;
        if (steps) turnTo(goal + steps);
    }

    function announce() {
        const shown = current();
        dots.forEach((dot, index) => {
            dot.classList.toggle('is-active', index === shown);
            dot.setAttribute('aria-current', index === shown ? 'true' : 'false');
        });
        stage.setAttribute('aria-valuenow', String(shown + 1));
        stage.setAttribute('aria-valuetext', stage.dataset.textView.replace(':n', numbers.format(shown + 1)).replace(':total', numbers.format(VIEWS)));
    }

    function resize() {
        const width = stage.clientWidth;
        const height = stage.clientHeight;
        if (!width || !height) return;
        view.setSize(width, height);
        draw();
    }

    // Hover and scroll -------------------------------------------------------------
    // A page scrolling under a resting pointer also sends pointer events, at an
    // unchanged position, so only a pointer that really moves counts as hovering.
    // A resting pointer counts too, once the wheel has paused: a wheel gesture that
    // was scrolling the page carries on scrolling it as the villa passes by.
    let hovering = false;
    let pointer = null;
    let lastPageWheel = -Infinity; // input time of the last wheel event that scrolled the page
    let lastWheel = -Infinity;
    let lockedUntil = -Infinity;
    let distance = 0; // wheel distance gathered towards the next turn
    const sizes = []; // sizes of the latest wheel events

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

            const direction = Math.sign(pixels);
            const atEnd = direction > 0 ? current() === VIEWS - 1 : current() === 0;
            const locked = event.timeStamp < lockedUntil;
            if (atEnd && !locked) return; // past the last view (or before the first) the page scrolls on

            event.preventDefault();
            if (event.timeStamp - lastWheel > 250) {
                sizes.length = 0;
                distance = 0;
            }
            lastWheel = event.timeStamp;
            sizes.push(Math.abs(pixels));
            if (sizes.length > 12) sizes.shift();

            // Hold input while a turn gets going, and ignore a trackpad still coasting from the last swipe.
            if (locked || atEnd || coasting(sizes)) {
                distance = 0;
                return;
            }

            distance = Math.sign(distance) === direction ? distance + pixels : pixels;
            if (Math.abs(distance) < STEP_PIXELS) return;

            distance = 0;
            lockedUntil = event.timeStamp + STEP_LOCK_MS;
            turnTo(goal + direction);
        },
        { passive: false },
    );

    // Drag and swipe: the villa follows, then settles on the nearest view ------------
    let drag = null;

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        drag = { id: event.pointerId, x: event.clientX, y: event.clientY, moving: false };
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
            drag.from = angle;
            tween = null;
            stage.setPointerCapture(event.pointerId);
            stage.classList.add('is-dragging');
        }

        angle = drag.from + dx * DRAG_SPEED;
        animate();
    });

    const endDrag = (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        if (drag.moving) {
            stage.classList.remove('is-dragging');
            // A short swipe turns to the next view that way; a longer drag settles on the nearest view.
            const from = viewAt(drag.from);
            const nearest = viewAt(angle);
            const dx = event.clientX - drag.x;
            const swipe = event.type === 'pointerup' && Math.abs(dx) >= 30 ? Math.sign(dx) : 0;
            turnTo(nearest === from ? from + swipe : nearest);
        }
        drag = null;
    };
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    // Keyboard ---------------------------------------------------------------------
    stage.addEventListener('keydown', (event) => {
        const step = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1 }[event.key];
        if (event.key !== 'Home' && event.key !== 'End' && !step) return;

        event.preventDefault();
        if (event.key === 'Home') showView(0);
        else if (event.key === 'End') showView(VIEWS - 1);
        else if (clamp(current() + step, 0, VIEWS - 1) !== current()) turnTo(goal + step);
    });

    // Visibility, size and late frames -------------------------------------------------
    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        animate();
    }).observe(stage);

    new ResizeObserver(resize).observe(stage);
    canvas.addEventListener('webglcontextrestored', draw);
    view.onFrameLoaded?.(() => visible && draw());

    resize();
    announce();
    Object.entries({ role: 'slider', tabindex: '0', 'aria-label': stage.dataset.label, 'aria-valuemin': '1', 'aria-valuemax': String(VIEWS) })
        .forEach(([name, value]) => stage.setAttribute(name, value));
    stage.dataset.villaState = 'ready';
}
