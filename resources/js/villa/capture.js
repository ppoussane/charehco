/*
 * Renders the still frames of the villa. Not part of the website: it is
 * bundled and run in headless Chrome by scripts/render-villa-frames.mjs.
 */
import { createScene } from './scene.js';
import { FRAME_ASPECT, FRAME_COUNT, REST } from './settings.js';

export const count = FRAME_COUNT;

let view;

export function start(width) {
    view = createScene({ pixelRatio: 1, preserveDrawingBuffer: true });
    view.setSize(width, Math.round(width / FRAME_ASPECT));
    document.body.append(view.canvas);
}

/** Frame `index` as a WebP data URL. Frame 0 is the starting view; each next one turns a step further. */
export function frame(index, quality = 0.75) {
    view.render(REST + (index / FRAME_COUNT) * Math.PI * 2);
    return view.canvas.toDataURL('image/webp', quality);
}
