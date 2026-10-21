import Lenis from '../../vendor/lenis/lenis.mjs';

// One frame loop; touch keeps the browser's momentum and selection behavior.
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const options = { lerp: .12, syncTouch: false, smoothWheel: !reducedMotion.matches, respectReducedMotion: true };
const page = new Lenis({
    ...options,
    prevent: (node) => node.matches('dialog,.admin-sidebar,textarea,select'),
    virtualScroll: ({ deltaX, deltaY, event }) => !event.shiftKey && Math.abs(deltaY) >= Math.abs(deltaX),
});
const panels = new Map();
document.querySelectorAll('.cart-dialog-body,.drawer-content,.search-dialog,.admin-sidebar').forEach((wrapper) => {
    wrapper.setAttribute('data-lenis-prevent', '');
    panels.set(wrapper, new Lenis({ ...options, wrapper, content: wrapper, prevent: (node) => node.matches('textarea,select,.search-results') }));
});
function syncPanels() {
    if (document.querySelector('dialog[open]')) page.stop(); else page.start();
    page.resize();
    for (const [wrapper, panel] of panels) {
        const dialog = wrapper.closest('dialog');
        if (dialog && !dialog.open) panel.stop(); else panel.start();
        panel.resize();
        panel.scrollTo(panel.actualScroll, { immediate: true, force: true });
    }
}
function frame(time) {
    if (!page.isStopped) page.raf(time);
    for (const panel of panels.values()) if (!panel.isStopped) panel.raf(time);
    requestAnimationFrame(frame);
}
syncPanels();
requestAnimationFrame(frame);
reducedMotion.addEventListener('change', () => {
    for (const engine of [page, ...panels.values()]) {
        engine.options.smoothWheel = !reducedMotion.matches;
        engine.scrollTo(engine.actualScroll, { immediate: true, force: true });
    }
});
document.addEventListener('store:dialog-open', syncPanels);
document.addEventListener('store:layout-updated', syncPanels);
document.addEventListener('store:cart-updated', syncPanels);
document.addEventListener('store:page-updated', syncPanels);
document.addEventListener('store:page-leave', () => page.scrollTo(page.actualScroll, { immediate: true, force: true }));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('close', syncPanels));
addEventListener('pageshow', syncPanels);

export function scrollPageTo(target, { immediate = false } = {}) {
    page.resize();
    page.scrollTo(target, { immediate, force: true });
}
export function isPageScrolling() { return !!page.isScrolling; }
// Same-page anchors use the same engine, including keyboard/skip navigation.
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || !link || link.hasAttribute('download') || (link.target && link.target !== '_self') || link.matches('[data-cookie-settings]')) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin || url.pathname !== location.pathname || url.search !== location.search || !url.hash) return;
    let target;
    try { target = document.getElementById(decodeURIComponent(url.hash.slice(1))); } catch { return; }
    if (!target) return;
    event.preventDefault();
    if (url.href !== location.href) {
        history.pushState(history.state, '', url);
        dispatchEvent(new HashChangeEvent('hashchange'));
    }
    if (!target.matches('a,button,input,select,textarea,[tabindex]')) target.tabIndex = -1;
    target.focus({ preventScroll: true });
    scrollPageTo(target);
});
