const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
let leaving = false;
let exitAnimation;
let generation = 0;
export async function navigate(url) {
    if (leaving) return;
    leaving = true;
    const version = ++generation;
    const surface = document.querySelector('dialog[open]') || document.getElementById('main');
    if (surface?.animate && !reducedMotion.matches) {
        exitAnimation = surface.animate([{ opacity: getComputedStyle(surface).opacity }, { opacity: 0 }], { duration: 140, easing: 'ease-out', fill: 'forwards' });
        await exitAnimation.finished.catch(() => {});
    }
    if (version === generation && leaving) location.assign(url);
}
addEventListener('pagehide', () => { generation++; leaving = false; exitAnimation?.cancel(); });
// A page restored from the browser's cache must not retain its exit frame.
addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    generation++;
    leaving = false;
    exitAnimation?.cancel();
    if (!reducedMotion.matches) document.getElementById('main')?.animate?.([{ opacity: 0 }, { opacity: 1 }], { duration: 180, easing: 'ease-out' });
});
