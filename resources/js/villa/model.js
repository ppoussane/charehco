/*
 * A two-level modern villa, modelled in code so there is no file to download.
 * Units are metres, y points up and the front of the house faces +z.
 *
 * A glass ground floor sits under a white upper floor that cantilevers 4.8 m
 * over the terrace. A stone wall with a chimney anchors the house; timber fins
 * shade the upper windows. There is a roof terrace, a pool and a garden, all on
 * a round model base that turns like a turntable.
 */
import * as THREE from 'three';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';

export const BASE_RADIUS = 14;

const BASE_DEPTH = 0.6;

// Rings that enclose the model however far it is turned, as [radius, height]: the soft
// shadow under the base, the base itself, the widest tree crowns and the cypress tops.
export const BOUNDS = [[BASE_RADIUS * 1.12, -BASE_DEPTH], [BASE_RADIUS, -BASE_DEPTH], [BASE_RADIUS, 0], [13.2, 4.6], [11.6, 7.8]];
const GROUND = 0.45; // podium, terrace and ground-floor level
const FLOOR = 0.49; // top of the ground-floor finish
const CEILING = 3.55; // underside of the upper-floor slab
const UPPER = 3.9; // upper-floor level
const ROOF = 6.8; // underside of the roof slab
const ROOF_TOP = 7.15;

/** Repeatable random numbers, so the stone and timber look the same on every visit. */
function seeded(seed) {
    return () => {
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Random lengths between min and max that add up to exactly `total`. */
function lengths(rand, total, min, max) {
    const parts = [];
    let sum = 0;
    while (sum < total) {
        const part = min + rand() * (max - min);
        parts.push(part);
        sum += part;
    }
    return parts.map((part) => (part * total) / sum);
}

/** A tiling texture drawn on a canvas; `metres` is the size of one tile in the world. */
function canvasTexture(size, metres, draw) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    draw(canvas.getContext('2d'), size);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.wrapS = texture.wrapT = THREE.RepeatWrapping;
    texture.repeat.setScalar(1 / metres);
    texture.anisotropy = 8;
    return texture;
}

/** Draws a run of pieces along a row, wrapping round so the texture tiles. */
function row(rand, size, min, max, drawPiece) {
    let x = rand() * size;
    for (const width of lengths(rand, size, min, max)) {
        drawPiece(x, width);
        drawPiece(x - size, width);
        x += width;
    }
}

function stoneTexture() {
    const rand = seeded(7);
    return canvasTexture(512, 2.4, (ctx, size) => {
        ctx.fillStyle = '#857a6d';
        ctx.fillRect(0, 0, size, size);
        let y = 0;
        for (const height of lengths(rand, size, 26, 62)) {
            row(rand, size, 70, 210, (x, width) => {
                ctx.fillStyle = `hsl(${30 + rand() * 12} ${9 + rand() * 9}% ${55 + rand() * 13}%)`;
                ctx.fillRect(x + 1.5, y + 1.5, width - 3, height - 3);
            });
            y += height;
        }
    });
}

function deckTexture() {
    const rand = seeded(11);
    return canvasTexture(512, 2, (ctx, size) => {
        const boards = 16;
        const height = size / boards;
        ctx.fillStyle = '#3d2a1c';
        ctx.fillRect(0, 0, size, size);
        for (let i = 0; i < boards; i++) {
            row(rand, size, 150, 330, (x, width) => {
                ctx.fillStyle = `hsl(${26 + rand() * 6} ${22 + rand() * 9}% ${44 + rand() * 11}%)`;
                ctx.fillRect(x, i * height, width - 2, height - 2.5);
            });
        }
        ctx.globalAlpha = 0.07;
        for (let i = 0; i < 300; i++) {
            ctx.fillStyle = rand() > 0.5 ? '#000' : '#fff';
            ctx.fillRect(rand() * size, Math.floor(rand() * boards) * height + 3 + rand() * (height - 8), 30 + rand() * 120, 1);
        }
    });
}

function pavingTexture() {
    const rand = seeded(5);
    return canvasTexture(512, 4.8, (ctx, size) => {
        const cols = 4; // slabs of 1.2 × 0.6 m
        const rows = 8;
        const width = size / cols;
        const height = size / rows;
        ctx.fillStyle = '#bfb6a8';
        ctx.fillRect(0, 0, size, size);
        for (let r = 0; r < rows; r++) {
            const tones = Array.from({ length: cols }, () => `hsl(38 ${11 + rand() * 6}% ${82 + rand() * 5}%)`);
            const shift = (r % 2) * (width / 2);
            for (let c = -1; c < cols; c++) {
                ctx.fillStyle = tones[(c + cols) % cols];
                ctx.fillRect(c * width + shift + 1, r * height + 1, width - 2, height - 2);
            }
        }
    });
}

function lawnTexture() {
    const rand = seeded(3);
    const texture = canvasTexture(256, 1, (ctx, size) => {
        ctx.fillStyle = '#8d9870';
        ctx.fillRect(0, 0, size, size);
        for (let i = 0; i < 1800; i++) {
            ctx.fillStyle = `hsl(${70 + rand() * 20} ${14 + rand() * 12}% ${38 + rand() * 20}% / 0.35)`;
            const s = 1 + rand() * 3;
            ctx.fillRect(rand() * (size - s), rand() * (size - s), s, s);
        }
    });
    texture.repeat.setScalar(9); // the base's top is mapped once across its width
    return texture;
}

function createMaterials() {
    const standard = (options) => new THREE.MeshStandardMaterial({ roughness: 0.85, ...options });
    const clear = (options) => new THREE.MeshStandardMaterial({ transparent: true, depthWrite: false, ...options });

    return {
        render: standard({ color: '#f3efe8', roughness: 0.92 }),
        stone: standard({ map: stoneTexture(), roughness: 0.95 }),
        paving: standard({ map: pavingTexture(), roughness: 0.9 }),
        coping: standard({ color: '#e7e1d7' }),
        deck: standard({ map: deckTexture(), roughness: 0.78 }),
        timber: standard({ color: '#8d5d39', roughness: 0.7 }),
        frame: standard({ color: '#3a322b', roughness: 0.38, metalness: 0.6 }),
        roof: standard({ color: '#cfccc6', roughness: 1 }),
        glass: clear({ color: '#6f8589', roughness: 0.05, metalness: 0.4, opacity: 0.5, envMapIntensity: 1.8 }),
        balustrade: clear({ color: '#c9d6d6', roughness: 0.05, metalness: 0.2, opacity: 0.28 }),
        water: clear({ color: '#3a8494', roughness: 0.04, metalness: 0.25, opacity: 0.84, envMapIntensity: 1.4 }),
        tile: standard({ color: '#8fc0c4', roughness: 0.5 }),
        oak: standard({ color: '#c49c72', roughness: 0.6 }),
        walnut: standard({ color: '#5a3d29', roughness: 0.55 }),
        interior: standard({ color: '#ebe4d8' }),
        fabric: standard({ color: '#a39e94', roughness: 1 }),
        linen: standard({ color: '#efe9df', roughness: 1 }),
        lamp: standard({ color: '#fff4dc', emissive: '#ffcf8a', emissiveIntensity: 2 }),
        plinth: standard({ color: '#37383c', roughness: 0.7 }),
        lawn: standard({ map: lawnTexture(), roughness: 1 }),
        leaves: standard({ color: '#7b8b57', roughness: 0.95, flatShading: true }),
        cypress: standard({ color: '#55663f', roughness: 0.95, flatShading: true }),
        hedge: standard({ color: '#6c7c48', roughness: 1, flatShading: true }),
        bark: standard({ color: '#66503f', roughness: 1 }),
    };
}

/**
 * Texture coordinates in metres, projected along each face's main axis,
 * so stone and timber keep their scale and run on across neighbouring pieces.
 */
function mapUVsInMetres(geometry) {
    const { position, normal, uv } = geometry.attributes;
    for (let i = 0; i < position.count; i++) {
        const nx = Math.abs(normal.getX(i));
        const ny = Math.abs(normal.getY(i));
        const nz = Math.abs(normal.getZ(i));
        const [x, y, z] = [position.getX(i), position.getY(i), position.getZ(i)];
        if (ny >= nx && ny >= nz) uv.setXY(i, x, z);
        else if (nx >= nz) uv.setXY(i, z, y);
        else uv.setXY(i, x, y);
    }
    uv.needsUpdate = true;
}

/** Collects pieces per material and merges them, so the whole villa is a few draw calls. */
class Builder {
    parts = new Map();

    add(material, geometry, { castShadow = !material.transparent } = {}) {
        const key = `${material.uuid}:${castShadow}`;
        if (!this.parts.has(key)) this.parts.set(key, { material, castShadow, geometries: [] });
        this.parts.get(key).geometries.push(geometry.index ? geometry.toNonIndexed() : geometry);
    }

    /** A box between two corners, e.g. box(material, [x0, x1], [y0, y1], [z0, z1]). */
    box(material, [x0, x1], [y0, y1], [z0, z1], options) {
        const geometry = new THREE.BoxGeometry(x1 - x0, y1 - y0, z1 - z0);
        geometry.translate((x0 + x1) / 2, (y0 + y1) / 2, (z0 + z1) / 2);
        this.add(material, geometry, options);
    }

    build() {
        const group = new THREE.Group();
        for (const { material, castShadow, geometries } of this.parts.values()) {
            const geometry = mergeGeometries(geometries);
            geometries.forEach((part) => part.dispose());
            if (material.map) mapUVsInMetres(geometry);

            const mesh = new THREE.Mesh(geometry, material);
            mesh.castShadow = castShadow;
            mesh.receiveShadow = !material.transparent;
            group.add(mesh);
        }
        return group;
    }
}

function buildHouse(m) {
    const b = new Builder();
    const pool = { x: [-0.5, 9.5], z: [4.0, 7.0] };

    // Podium of limestone paving, with the pool set into it.
    b.box(m.paving, [-8, pool.x[0]], [0, GROUND], [-4.6, 8.2]);
    b.box(m.paving, [pool.x[1], 10.5], [0, GROUND], [-4.6, 8.2]);
    b.box(m.paving, pool.x, [0, GROUND], [-4.6, pool.z[0]]);
    b.box(m.paving, pool.x, [0, GROUND], [pool.z[1], 8.2]);

    // Pool: tiled basin, water and a stone coping.
    b.box(m.tile, pool.x, [0, 0.06], pool.z);
    b.box(m.tile, [-0.5, -0.42], [0.06, GROUND], pool.z);
    b.box(m.tile, [9.42, 9.5], [0.06, GROUND], pool.z);
    b.box(m.tile, [-0.42, 9.42], [0.06, GROUND], [4.0, 4.08]);
    b.box(m.tile, [-0.42, 9.42], [0.06, GROUND], [6.92, 7.0]);
    b.box(m.water, [-0.42, 9.42], [0.33, 0.36], [4.08, 6.92]);
    b.box(m.coping, [-0.8, 9.8], [GROUND, 0.5], [3.7, 4.08]);
    b.box(m.coping, [-0.8, 9.8], [GROUND, 0.5], [6.92, 7.3]);
    b.box(m.coping, [-0.8, -0.42], [GROUND, 0.5], [4.08, 6.92]);
    b.box(m.coping, [9.42, 9.8], [GROUND, 0.5], [4.08, 6.92]);

    // Timber decks: along the glass front, beside the pool and under the cantilever.
    b.box(m.deck, [-6.0, 10.3], [GROUND, FLOOR], [2.8, 3.7]);
    b.box(m.deck, [-6.0, -0.8], [GROUND, FLOOR], [3.7, 8.0]);
    b.box(m.deck, [3.0, 10.3], [GROUND, FLOOR], [-3.4, 2.8]);

    // Stone wall running past the house, and the chimney.
    b.box(m.stone, [-6.55, -6.0], [0, 4.5], [-4.2, 4.4]);
    b.box(m.stone, [-6.6, -5.45], [0, 7.7], [-2.5, -1.35]);
    b.box(m.frame, [-6.7, -5.35], [7.7, 7.78], [-2.6, -1.25]);
    b.box(m.frame, [-5.46, -5.42], [0.8, 1.45], [-2.25, -1.6]); // fireplace

    // Ground floor ------------------------------------------------------------
    b.box(m.oak, [-6.0, 3.0], [GROUND, FLOOR], [-2.8, 2.8]);

    // Rear wall with the front door and a kitchen window.
    const rear = [-3.05, -2.8];
    b.box(m.render, [-6.0, -4.6], [GROUND, CEILING], rear);
    b.box(m.render, [-4.6, -3.4], [3.0, CEILING], rear);
    b.box(m.walnut, [-4.55, -3.45], [FLOOR, 3.0], [-2.98, -2.9]);
    b.box(m.render, [-3.4, -1.0], [GROUND, CEILING], rear);
    b.box(m.render, [-1.0, 2.5], [GROUND, 1.45], rear);
    b.box(m.render, [-1.0, 2.5], [3.0, CEILING], rear);
    b.box(m.render, [2.5, 3.0], [GROUND, CEILING], rear);
    b.box(m.glass, [-1.0, 2.5], [1.45, 3.0], [-2.94, -2.92]);
    for (const x of [-1.0, 0.75, 2.5]) b.box(m.frame, [x - 0.03, x + 0.03], [1.45, 3.0], [-2.97, -2.89]);

    // Glass on the front and on the side under the cantilever.
    b.box(m.glass, [-6.0, 3.0], [FLOOR, CEILING], [2.77, 2.79]);
    for (let x = -6.0; x < 3.1; x += 1.8) b.box(m.frame, [x - 0.035, x + 0.035], [FLOOR, CEILING], [2.74, 2.82]);
    b.box(m.frame, [-6.0, 3.0], [FLOOR, FLOOR + 0.07], [2.74, 2.82]);
    b.box(m.frame, [-6.0, 3.0], [CEILING - 0.07, CEILING], [2.74, 2.82]);

    b.box(m.glass, [2.97, 2.99], [FLOOR, CEILING], [-2.8, 2.78]);
    for (const z of [-2.8, -0.93, 0.93]) b.box(m.frame, [2.94, 3.02], [FLOOR, CEILING], [z - 0.035, z + 0.035]);
    b.box(m.frame, [2.94, 3.02], [FLOOR, FLOOR + 0.07], [-2.8, 2.8]);
    b.box(m.frame, [2.94, 3.02], [CEILING - 0.07, CEILING], [-2.8, 2.8]);

    // Inside: living room, dining table, kitchen and a stair of floating treads.
    for (const z of [-1.5, 0, 1.5]) {
        b.box(m.lamp, [-5.6, 2.6], [CEILING - 0.03, CEILING], [z - 0.035, z + 0.035], { castShadow: false });
    }
    b.box(m.linen, [-5.6, -2.3], [FLOOR, FLOOR + 0.01], [-0.1, 2.4]);
    b.box(m.fabric, [-5.3, -2.6], [FLOOR + 0.01, 0.9], [0.0, 0.95]);
    b.box(m.fabric, [-5.3, -2.6], [0.9, 1.25], [0.0, 0.25]);
    b.box(m.walnut, [-4.4, -3.4], [FLOOR + 0.01, 0.84], [1.45, 2.05]);

    b.box(m.walnut, [0.2, 2.3], [1.18, 1.24], [0.7, 1.7]);
    for (const [x, z] of [[0.3, 0.8], [2.2, 0.8], [0.3, 1.6], [2.2, 1.6]]) {
        b.box(m.walnut, [x - 0.03, x + 0.03], [FLOOR, 1.18], [z - 0.03, z + 0.03]);
    }
    b.box(m.oak, [0.4, 2.1], [FLOOR, 0.93], [0.25, 0.5]);
    b.box(m.oak, [0.4, 2.1], [FLOOR, 0.93], [1.9, 2.15]);

    b.box(m.interior, [0.6, 2.9], [FLOOR, 1.36], [-2.8, -2.2]);
    b.box(m.walnut, [0.6, 2.9], [1.36, 1.4], [-2.8, -2.18]);
    b.box(m.interior, [0.9, 2.6], [FLOOR, 1.36], [-1.35, -0.65]);
    b.box(m.walnut, [0.85, 2.65], [1.36, 1.4], [-1.4, -0.6]);

    const steps = 12;
    for (let i = 0; i < steps; i++) {
        const x = -3.1 + i * 0.26;
        const top = FLOOR + ((i + 1) * (UPPER - FLOOR)) / steps;
        b.box(m.oak, [x, x + 0.28], [top - 0.06, top], [-2.8, -1.85]);
    }

    // Slabs: roof terrace over the living room, the upper floor and its timber soffit.
    b.box(m.render, [-6.0, -3.5], [CEILING, UPPER], [-3.05, 3.0]);
    b.box(m.render, [-3.5, 7.8], [CEILING, UPPER], [-3.1, 3.3]);
    b.box(m.timber, [3.0, 7.8], [CEILING - 0.04, CEILING], [-3.1, 3.3]);

    // Roof terrace with glass balustrades.
    b.box(m.deck, [-6.0, -3.5], [UPPER, UPPER + 0.04], [-3.05, 3.0]);
    for (const z of [2.91, -2.96]) {
        b.box(m.balustrade, [-6.0, -3.5], [UPPER + 0.04, UPPER + 1.05], [z - 0.01, z + 0.01]);
        b.box(m.frame, [-6.0, -3.5], [UPPER + 1.05, UPPER + 1.09], [z - 0.03, z + 0.03]);
    }

    // Upper floor --------------------------------------------------------------
    b.box(m.oak, [-3.25, 7.66], [UPPER, UPPER + 0.04], [-2.85, 3.05]);

    // Front wall with a long ribbon window.
    const front = [3.05, 3.3];
    b.box(m.render, [-3.5, 7.8], [UPPER, 4.45], front);
    b.box(m.render, [-3.5, 7.8], [6.3, ROOF], front);
    b.box(m.render, [-3.5, -2.6], [4.45, 6.3], front);
    b.box(m.render, [6.2, 7.8], [4.45, 6.3], front);
    b.box(m.glass, [-2.6, 6.2], [4.45, 6.3], [3.16, 3.18]);
    for (let i = 0; i <= 6; i++) {
        const x = -2.6 + (i * 8.8) / 6;
        b.box(m.frame, [x - 0.03, x + 0.03], [4.45, 6.3], [3.12, 3.22]);
    }

    // Timber fins hanging from the roof in front of part of that window.
    for (let x = -2.45; x < 1.8; x += 0.21) b.box(m.timber, [x - 0.025, x + 0.025], [UPPER + 0.05, ROOF], [3.3, 3.56]);

    // Rear wall.
    const back = [-3.1, -2.85];
    b.box(m.render, [-3.5, 7.8], [UPPER, 4.8], back);
    b.box(m.render, [-3.5, 7.8], [6.3, ROOF], back);
    b.box(m.render, [-3.5, 0.0], [4.8, 6.3], back);
    b.box(m.render, [5.0, 7.8], [4.8, 6.3], back);
    b.box(m.glass, [0.0, 5.0], [4.8, 6.3], [-2.99, -2.97]);
    for (const x of [0.0, 1.67, 3.33, 5.0]) b.box(m.frame, [x - 0.03, x + 0.03], [4.8, 6.3], [-3.03, -2.93]);

    // Side wall with a glass door onto the roof terrace.
    const side = [-3.5, -3.25];
    b.box(m.render, side, [UPPER, ROOF], [-2.85, -0.6]);
    b.box(m.render, side, [UPPER, ROOF], [0.6, 3.05]);
    b.box(m.render, side, [6.1, ROOF], [-0.6, 0.6]);
    b.box(m.glass, [-3.39, -3.37], [UPPER, 6.1], [-0.6, 0.6]);
    for (const z of [-0.6, 0.6]) b.box(m.frame, [-3.42, -3.34], [UPPER, 6.1], [z - 0.03, z + 0.03]);

    // The glazed end of the cantilever.
    b.box(m.glass, [7.66, 7.68], [UPPER, ROOF], [-2.85, 3.05]);
    for (const z of [-2.85, -0.88, 1.08, 3.05]) b.box(m.frame, [7.62, 7.72], [UPPER, ROOF], [z - 0.035, z + 0.035]);
    b.box(m.frame, [7.62, 7.72], [UPPER, UPPER + 0.06], [-2.85, 3.05]);
    b.box(m.frame, [7.62, 7.72], [ROOF - 0.06, ROOF], [-2.85, 3.05]);

    // Inside: a bedroom at the glazed end, and a sideboard along the window.
    for (const z of [-1.2, 1.2]) {
        b.box(m.lamp, [-3.0, 7.4], [ROOF - 0.03, ROOF], [z - 0.035, z + 0.035], { castShadow: false });
    }
    b.box(m.interior, [2.6, 2.75], [UPPER + 0.04, ROOF], [-2.85, 1.2]);
    b.box(m.linen, [5.4, 7.2], [UPPER + 0.04, 4.45], [-1.7, 0.5]);
    b.box(m.fabric, [5.4, 7.2], [4.45, 4.5], [-0.3, 0.5]);
    b.box(m.walnut, [5.4, 7.2], [UPPER + 0.04, 4.95], [-1.85, -1.7]);
    b.box(m.walnut, [-2.6, -0.4], [UPPER + 0.04, 4.35], [2.5, 2.95]);

    // Roof slab with a slim white edge.
    b.box(m.render, [-3.6, 7.9], [ROOF, ROOF_TOP], [-3.2, 3.6]);
    b.box(m.roof, [-3.3, 7.6], [ROOF_TOP, ROOF_TOP + 0.03], [-2.9, 3.3]);

    // Outdoor furniture ---------------------------------------------------------
    // Lounge under the cantilever.
    b.box(m.fabric, [4.4, 6.8], [FLOOR, 0.85], [-2.6, -1.8]);
    b.box(m.fabric, [4.4, 6.8], [0.85, 1.2], [-2.6, -2.4]);
    b.box(m.walnut, [5.0, 6.2], [FLOOR, 0.8], [-1.2, -0.4]);

    // Two sun loungers by the pool.
    for (const x of [-2.7, -1.75]) {
        b.box(m.render, [x - 0.33, x + 0.33], [FLOOR, 0.78], [5.2, 6.95]);
        b.box(m.linen, [x - 0.31, x + 0.31], [0.78, 0.84], [5.25, 6.9]);
        const backrest = new THREE.BoxGeometry(0.66, 0.08, 0.8);
        backrest.rotateX(0.95);
        backrest.translate(x, 1.12, 4.98);
        b.add(m.render, backrest);
    }

    // A small olive tree in a planter.
    b.box(m.render, [-5.5, -4.3], [FLOOR, 1.0], [6.2, 7.4]);
    roundTree(b, m, -4.9, 6.8, { radius: 0.75, height: 1.9, base: 1.0 });

    return b.build();
}

/** Nudges vertices in and out for a hand-made look; equal positions move together, so no gaps open. */
function roughen(geometry, amount, seed) {
    const position = geometry.attributes.position;
    for (let i = 0; i < position.count; i++) {
        const [x, y, z] = [position.getX(i), position.getY(i), position.getZ(i)];
        const n = Math.sin(Math.round(x * 1e3) * 12.9898 + Math.round(y * 1e3) * 78.233 + Math.round(z * 1e3) * 37.719 + seed) * 43758.5453;
        const scale = 1 + (n - Math.floor(n) - 0.5) * amount;
        position.setXYZ(i, x * scale, y * scale, z * scale);
    }
    geometry.computeVertexNormals();
    return geometry;
}

function roundTree(b, m, x, z, { radius, height, base = 0 }) {
    b.box(m.bark, [x - 0.09, x + 0.09], [base, base + height], [z - 0.09, z + 0.09]);
    const crown = roughen(new THREE.IcosahedronGeometry(radius, 1), 0.24, x + z);
    crown.scale(1, 0.88, 1);
    crown.translate(x, base + height + radius * 0.5, z);
    b.add(m.leaves, crown);
}

/** Tall, flame-shaped cypress, as in Persian gardens. */
function cypress(b, m, x, z, height) {
    b.box(m.bark, [x - 0.08, x + 0.08], [0, 0.6], [z - 0.08, z + 0.08]);
    const crown = new THREE.IcosahedronGeometry(1, 1);
    const position = crown.attributes.position;
    for (let i = 0; i < position.count; i++) {
        const taper = 1 - 0.5 * Math.max(0, position.getY(i));
        position.setX(i, position.getX(i) * taper);
        position.setZ(i, position.getZ(i) * taper);
    }
    roughen(crown, 0.12, x * z);
    crown.scale(0.68, height / 2, 0.68);
    crown.translate(x, 0.45 + height / 2, z);
    b.add(m.cypress, crown);
}

function shrub(b, m, x, z, radius) {
    const geometry = roughen(new THREE.IcosahedronGeometry(radius, 1), 0.26, x - z);
    geometry.scale(1, 0.72, 1);
    geometry.translate(x, radius * 0.42, z);
    b.add(m.hedge, geometry);
}

/** Lawn, paths and planting on the base, around the house. */
function buildGarden(m) {
    const b = new Builder();

    // Stepping stones from the edge of the lawn to the front door.
    for (let i = 0; i < 6; i++) {
        const z = -7.0 - i * 0.9;
        b.box(m.coping, [-5.95, -4.55], [0, 0.05], [z - 0.25, z + 0.25]);
    }

    // A clipped hedge behind the house.
    b.box(m.hedge, [-2.5, 8.5], [0, 0.75], [-7.3, -6.75]);

    for (const [x, z, height] of [[-10.9, -4.4, 6.4], [-11.1, -1.9, 7.2], [-11.0, 0.6, 5.8]]) {
        cypress(b, m, x, z, height);
    }
    for (const [x, z, radius, height] of [[6.4, -9.4, 1.8, 3.4], [-9.6, 6.9, 1.5, 2.8], [-1.6, -10.4, 1.5, 3.0]]) {
        roundTree(b, m, x, z, { radius, height });
    }
    for (const [x, z, radius] of [
        [10.2, -3.6, 0.7], [10.7, -2.2, 0.55], [10.1, 3.2, 0.6], [10.8, 4.6, 0.8],
        [-9.9, 3.4, 0.6], [2.5, 7.4, 0.6], [4.0, 7.9, 0.45], [-3.6, -7.7, 0.5], [9.0, -7.1, 0.6],
    ]) {
        shrub(b, m, x, z, radius);
    }

    return b.build();
}

function buildBase(m) {
    const geometry = new THREE.CylinderGeometry(BASE_RADIUS, BASE_RADIUS, BASE_DEPTH, 128);
    geometry.translate(0, -BASE_DEPTH / 2, 0);

    const base = new THREE.Mesh(geometry, [m.plinth, m.lawn, m.plinth]);
    base.receiveShadow = true;
    return base;
}

/** A soft shadow on the "table" under the base, fading out just past its edge. */
function buildGroundShadow() {
    const size = 256;
    const reach = BASE_RADIUS * 1.15;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    const edge = BASE_RADIUS / reach;
    gradient.addColorStop(0, 'rgb(43 36 28 / 0.3)');
    gradient.addColorStop(edge - 0.01, 'rgb(43 36 28 / 0.24)');
    gradient.addColorStop(edge + 0.04, 'rgb(43 36 28 / 0.1)');
    gradient.addColorStop(1, 'rgb(43 36 28 / 0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, size, size);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    const shadow = new THREE.Mesh(
        new THREE.PlaneGeometry(reach * 2, reach * 2),
        new THREE.MeshBasicMaterial({ map: texture, transparent: true, depthWrite: false, toneMapped: false }),
    );
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = -BASE_DEPTH - 0.01;
    return shadow;
}

export function createVilla() {
    const materials = createMaterials();
    const villa = new THREE.Group();

    villa.add(buildGroundShadow(), buildBase(materials), buildGarden(materials));

    const house = buildHouse(materials);
    house.position.set(-1.25, 0, -1.8); // centre the podium on the base
    villa.add(house);

    // Warm light inside the rooms.
    for (const [x, y, z] of [[-2.5, 2.6, 0], [1.5, 2.6, 0], [2.0, 5.9, 0]]) {
        const light = new THREE.PointLight('#ffd29a', 9, 9, 2);
        light.position.set(x, y, z);
        house.add(light);
    }

    return villa;
}
