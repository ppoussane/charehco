/*
 * Public website interactions: header state, mobile menu, fade sliders
 * and GSAP scroll animations (parallax, fade-up, clip-path reveal).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const ease = 'power2.inOut';

function initHeader() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const update = () => header.classList.toggle('is-scrolled', window.scrollY > 25);
    update();
    window.addEventListener('scroll', update, { passive: true });

    const toggle = header.querySelector('[data-menu-toggle]');
    const panel = document.querySelector('[data-menu-panel]');
    if (!toggle || !panel) return;

    toggle.addEventListener('click', () => {
        const open = !panel.classList.contains('is-open');
        panel.classList.toggle('is-open', open);
        panel.toggleAttribute('inert', !open);
        header.classList.toggle('menu-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        document.documentElement.classList.toggle('overflow-hidden', open);
    });
}

/**
 * Cross-fading slideshow. Markup:
 *   <div data-slider="name" data-autoplay="7500"> <div class="fade-slide" data-slide> … </div> … </div>
 *   <div data-slider-dots="name"> <button> … </div>
 */
function initSliders() {
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const slides = [...slider.querySelectorAll('[data-slide]')];
        const dots = [...document.querySelectorAll(`[data-slider-dots="${slider.dataset.slider}"] button`)];
        const delay = Number(slider.dataset.autoplay || 0);
        let current = 0;
        let timer = null;

        const show = (index) => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === current);
                dot.setAttribute('aria-current', i === current ? 'true' : 'false');
            });
        };

        const restart = () => {
            clearInterval(timer);
            if (delay && slides.length > 1 && !reduceMotion) {
                timer = setInterval(() => show(current + 1), delay);
            }
        };

        dots.forEach((dot, i) =>
            dot.addEventListener('click', () => {
                show(i);
                restart();
            }),
        );

        show(0);
        restart();
    });
}

/**
 * Images drift slower than the page, like the reference site.
 * A section at the very top moves its image down as you scroll away;
 * other sections need a `.parallax-media` layer (15% overscan) and drift both ways.
 */
function initParallax() {
    gsap.utils.toArray('[data-parallax-parent]').forEach((parent) => {
        const atTop = parent.getBoundingClientRect().top + window.scrollY < 10;

        parent.querySelectorAll('[data-parallax-item]').forEach((item) => {
            const speed = 1 + Number(item.dataset.parallaxItem || 0);

            gsap.fromTo(
                item,
                { y: () => (atTop ? 0 : -parent.offsetHeight * 0.12 * speed) },
                {
                    y: () => (atTop ? parent.offsetHeight * 0.4 * speed : parent.offsetHeight * 0.12 * speed),
                    ease: 'none',
                    scrollTrigger: {
                        trigger: parent,
                        start: atTop ? 'top top' : 'top bottom',
                        end: 'bottom top',
                        scrub: 0.1,
                        invalidateOnRefresh: true,
                    },
                },
            );
        });
    });
}

/** Children of [data-fade] rise and fade in, one after another. */
function initFadeIn() {
    ScrollTrigger.batch('[data-fade]', {
        start: 'top 85%',
        once: true,
        onEnter: (batch) =>
            batch.forEach((parent) => {
                const items = parent.dataset.fade === 'current' ? [parent] : [...parent.children];
                gsap.to(items, { opacity: 1, y: 0, duration: 0.6, ease, stagger: 0.1 });
            }),
    });
}

/** Images inside [data-reveal-parent] wipe open from the bottom. */
function initReveal() {
    ScrollTrigger.batch('[data-reveal-parent]', {
        start: 'top 85%',
        once: true,
        onEnter: (batch) =>
            batch.forEach((parent) =>
                gsap.to(parent.querySelectorAll('[data-reveal-item]'), {
                    clipPath: 'polygon(100% 100%, 0% 100%, 0% 0%, 100% 0%)',
                    duration: 0.9,
                    ease,
                    stagger: 0.2,
                }),
            ),
    });
}

/** Hero headline lines rise in after load. */
function initHeroTitle() {
    const lines = document.querySelectorAll('[data-hero-line]');
    if (!lines.length) return;

    gsap.to(lines, { opacity: 1, y: 0, duration: 1.1, ease: 'power3.out', stagger: 0.15, delay: 0.25 });
}

function boot() {
    initHeader();
    initSliders();

    if (!reduceMotion) {
        initHeroTitle();
        initParallax();
        initFadeIn();
        initReveal();

        // Recalculate once images have their final size.
        window.addEventListener('load', () => ScrollTrigger.refresh());
    }

    window.__siteReady = true;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
