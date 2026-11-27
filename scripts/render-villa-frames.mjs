#!/usr/bin/env node
/*
 * Renders the home page villa's still frames into public/images/villa: the
 * picture shown while the 3D view loads, and the frames that turn instead of
 * it in browsers without WebGL. Run it again after changing the model:
 *
 *     node scripts/render-villa-frames.mjs
 *
 * It needs Google Chrome or Chromium (set CHROME_PATH to choose one) and uses
 * Chrome's software WebGL, so no graphics card is required.
 */
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { mkdir, mkdtemp, readdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { delimiter, join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { build } from 'vite';

const ROOT = resolve(import.meta.dirname, '..');
const OUTPUT = join(ROOT, 'public/images/villa');
const WIDTH = 1000; // pixels; the height follows from the frame shape (16:11)
const sleep = (ms) => new Promise((done) => setTimeout(done, ms));

function findChrome() {
    if (process.env.CHROME_PATH) return process.env.CHROME_PATH;
    const names = ['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'];
    const dirs = (process.env.PATH ?? '').split(delimiter);
    const found = names.flatMap((name) => dirs.map((dir) => join(dir, name))).find((path) => existsSync(path));
    const mac = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
    if (found ?? existsSync(mac)) return found ?? mac;
    throw new Error('Google Chrome or Chromium not found; set CHROME_PATH.');
}

/** Minimal DevTools protocol client for one page. */
async function connect(profile) {
    let port;
    for (let i = 0; i < 100 && !port; i++) {
        port = await readFile(join(profile, 'DevToolsActivePort'), 'utf8').then((text) => text.split('\n')[0], () => null);
        if (!port) await sleep(100);
    }
    if (!port) throw new Error('Chrome did not start.');

    const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    const socket = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await new Promise((open, fail) => Object.assign(socket, { onopen: open, onerror: fail }));

    let id = 0;
    const pending = new Map();
    const events = new Set();
    socket.onmessage = ({ data }) => {
        const message = JSON.parse(data);
        if (pending.has(message.id)) {
            const { done, fail } = pending.get(message.id);
            pending.delete(message.id);
            if (message.error) fail(new Error(message.error.message));
            else done(message.result);
        } else if (message.method) {
            events.forEach((listener) => listener(message));
        }
    };

    const send = (method, params = {}) =>
        new Promise((done, fail) => {
            pending.set(++id, { done, fail });
            socket.send(JSON.stringify({ id, method, params }));
        });

    return {
        send,
        once: (method) =>
            new Promise((done) => {
                const listener = (message) => message.method === method && (events.delete(listener), done(message.params));
                events.add(listener);
            }),
        async evaluate(expression) {
            const { result, exceptionDetails } = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
            if (exceptionDetails) throw new Error(exceptionDetails.exception?.description ?? exceptionDetails.text);
            return result.value;
        },
    };
}

const work = await mkdtemp(join(tmpdir(), 'villa-frames-'));
const profile = join(work, 'profile');
let chrome;

try {
    // Bundle the capture entry as a classic script, which a file:// page can load.
    await build({
        configFile: false,
        root: ROOT,
        publicDir: false,
        logLevel: 'warn',
        build: {
            outDir: work,
            emptyOutDir: false,
            lib: { entry: join(ROOT, 'resources/js/villa/capture.js'), formats: ['iife'], name: 'VillaCapture', fileName: () => 'capture.js' },
        },
    });
    await writeFile(join(work, 'capture.html'), '<!doctype html><body style="margin:0"><script src="capture.js"></script>');

    chrome = spawn(findChrome(), [
        '--headless=new', '--remote-debugging-port=0', `--user-data-dir=${profile}`,
        '--use-angle=swiftshader', '--enable-unsafe-swiftshader',
        '--no-first-run', '--no-default-browser-check', '--mute-audio', 'about:blank',
    ], { stdio: 'ignore' });

    const page = await connect(profile);
    await page.send('Page.enable');
    const loaded = page.once('Page.loadEventFired');
    await page.send('Page.navigate', { url: pathToFileURL(join(work, 'capture.html')).href });
    await loaded;

    await page.evaluate(`VillaCapture.start(${WIDTH})`);
    const count = await page.evaluate('VillaCapture.count');

    await mkdir(OUTPUT, { recursive: true });
    for (const old of await readdir(OUTPUT)) {
        if (/^frame-\d+\.webp$/.test(old)) await rm(join(OUTPUT, old));
    }

    let bytes = 0;
    for (let index = 0; index < count; index++) {
        const dataUrl = await page.evaluate(`VillaCapture.frame(${index})`);
        if (!dataUrl.startsWith('data:image/webp')) throw new Error('This Chrome cannot encode WebP images.');
        const image = Buffer.from(dataUrl.slice(dataUrl.indexOf(',') + 1), 'base64');
        await writeFile(join(OUTPUT, `frame-${String(index).padStart(2, '0')}.webp`), image);
        bytes += image.length;
        process.stdout.write(`\rRendered ${index + 1}/${count} frames`);
    }
    console.log(` — ${Math.round(bytes / 1024)} KB in public/images/villa`);
} finally {
    chrome?.kill();
    await sleep(300);
    await rm(work, { recursive: true, force: true });
}
