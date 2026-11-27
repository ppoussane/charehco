/*
 * Still frames of the villa all the way round, rendered from the 3D scene by
 * scripts/render-villa-frames.mjs. They stand in for the 3D view in browsers
 * without WebGL and turn with the same controls. Each view is an exact frame,
 * and frames are never blended, so the villa always looks sharp.
 */
import { FRAME_COUNT, REST, VIEWS } from './settings.js';

const TURN = Math.PI * 2;

/**
 * @param {string} pattern URL of a frame, with {n} for its two-digit number
 */
export function createFrameView(pattern) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    const frames = Array.from({ length: FRAME_COUNT });
    const listeners = new Set();
    let ready;
    const loaded = new Promise((resolve, reject) => (ready = { resolve, reject }));

    // The four views load first, then the frames between them, nearest first.
    const views = Array.from({ length: VIEWS }, (_, view) => (view * FRAME_COUNT) / VIEWS);
    const order = [...views];
    for (let step = 1; order.length < FRAME_COUNT; step++) {
        for (const view of views) {
            for (const index of [(view + step) % FRAME_COUNT, (view - step + FRAME_COUNT) % FRAME_COUNT]) {
                if (!order.includes(index)) order.push(index);
            }
        }
    }

    for (const index of order) {
        const image = new Image();
        image.decoding = 'async';
        image.addEventListener('load', () => {
            frames[index] = image;
            if (index === 0) ready.resolve();
            listeners.forEach((listener) => listener());
        });
        if (index === 0) image.addEventListener('error', () => ready.reject(new Error(`Could not load ${image.src}`)));
        image.src = pattern.replace('{n}', String(index).padStart(2, '0'));
    }

    /** The loaded frame nearest to `index`, while the others are still arriving. */
    function nearest(index) {
        for (let distance = 1; distance <= FRAME_COUNT / 2; distance++) {
            const frame = frames[(index + distance) % FRAME_COUNT] ?? frames[(index - distance + FRAME_COUNT) % FRAME_COUNT];
            if (frame) return frame;
        }
        return null;
    }

    /** Scaled to fit and centred, like the picture shown before. */
    function draw(image) {
        const scale = Math.min(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
        const width = image.naturalWidth * scale;
        const height = image.naturalHeight * scale;
        context.drawImage(image, (canvas.width - width) / 2, (canvas.height - height) / 2, width, height);
    }

    return {
        canvas,
        ready: loaded,
        onFrameLoaded: (listener) => listeners.add(listener),

        setSize(width, height) {
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            context.imageSmoothingQuality = 'high';
        },

        render(angle) {
            const index = ((Math.round(((angle - REST) / TURN) * FRAME_COUNT) % FRAME_COUNT) + FRAME_COUNT) % FRAME_COUNT;
            const image = frames[index] ?? nearest(index);

            context.clearRect(0, 0, canvas.width, canvas.height);
            if (image) draw(image);
        },
    };
}
