import { closeDialog } from './dialogs.js';
import { serial } from '../shared/http.js';
import { toast } from '../shared/notices.js';
import { scrollPageTo } from '../shared/smooth-scroll.js';
import { capturePageState } from './page-state.js';

const main = document.getElementById('main');
const stateKey = 'meshPage';
let current = new URL(location.href);
let controller;
let generation = 0;
let pending = false;
let scrollFrame;
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');

export function isStorePage(value) {
    const url = new URL(value, location.href);
    return url.origin === location.origin && /^\/(?:shop\/?|products\/[^/]+\/?|help\/?|privacy-policy\/?|terms-and-conditions\/?)?$/.test(url.pathname);
}
function rememberScroll() {
    if (!isStorePage(location.href) || current.href !== location.href) return;
    history.replaceState({ ...history.state, [stateKey]: { x: scrollX, y: scrollY } }, '', location.href);
}
function focusSource(source, url) {
    if (source?.matches('.catalog-toolbar [name]')) {
        main.querySelector(`.catalog-toolbar [name="${CSS.escape(source.name)}"]`)?.focus({ preventScroll: true });
        return;
    }
    if (source?.matches('.catalog-category-nav a,.sidebar-category,.sidebar-child')) {
        const selector = source.closest('.catalog-category-nav') ? '.catalog-category-nav a' : source.matches('.sidebar-child') ? '.sidebar-child' : '.sidebar-category';
        const match = [...main.querySelectorAll(selector)].find((link) => link.href === url.href);
        if (match) { match.focus({ preventScroll: true }); return; }
    }
    main.focus({ preventScroll: true });
}
function scrollPage(url, position) {
    let anchor;
    try { anchor = url.hash && document.getElementById(decodeURIComponent(url.hash.slice(1))); } catch { /* Ignore malformed fragment escapes. */ }
    scrollPageTo(anchor || position.y, { immediate: true });
}
function updateChrome(url, page, refresh = false) {
    let after = main;
    for (const selector of ['.benefits', '.store-footer-area']) {
        let section = document.querySelector(selector);
        const next = page.querySelector(selector);
        if (!section && next) { section = document.importNode(next, true); after.after(section); }
        if (section) after = section;
    }
    const csrf = page.querySelector('meta[name="csrf-token"]')?.content;
    const revision = page.querySelector('meta[name="catalog-version"]')?.content;
    if (revision) document.querySelector('meta[name="catalog-version"]').content = revision;
    if (csrf) {
        document.querySelector('meta[name="csrf-token"]').content = csrf;
        document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = csrf; });
    }
    document.querySelectorAll('.drawer-links a').forEach((link) => {
        const path = new URL(link.href).pathname;
        const active = path === url.pathname && !(path === '/shop' && url.searchParams.has('saved'));
        link.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
    });
    if (!refresh) document.querySelector('[data-store-header]')?.classList.remove('is-hidden');
}
export async function navigate(value, options = {}) {
    const url = new URL(value, location.href);
    if (!main || !isStorePage(url)) return false;
    controller?.abort();
    const version = ++generation;
    if (url.href === current.href && options.history !== 'pop' && !options.refresh) {
        pending = false; main.removeAttribute('aria-busy'); return true;
    }
    const requestController = new AbortController();
    controller = requestController;
    const position = options.scroll || { x: 0, y: 0 };
    if (options.history !== 'pop') rememberScroll();
    pending = true;
    if (!options.refresh) scrollPageTo(scrollY, { immediate: true });
    main.setAttribute('aria-busy', 'true');
    try {
        await serial(async () => {
            if (requestController.signal.aborted) return;
            const response = await fetch(url, { signal: requestController.signal, credentials: 'same-origin', headers: { Accept: 'text/html', 'X-Store-Navigation': '1' } });
            if (response.status === 404 && options.refresh) { const error = new Error('This product or category is no longer available.'); error.status = 404; throw error; }
            if (!response.ok || !isStorePage(response.url)) throw new Error('Could not open this page. Please try again.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const content = page.getElementById('main');
            if (!content) throw new Error('Could not open this page. Please try again.');
            if (version !== generation) return;
            if (options.refresh && (document.querySelector('dialog[open]') || main.contains(document.activeElement) && document.activeElement.matches('input,select,textarea'))) return;
            const restore = options.refresh ? capturePageState(main) : null;
            if (options.refresh) { position.x = scrollX; position.y = scrollY; }
            document.dispatchEvent(new CustomEvent('store:page-leave', { detail: { refresh: !!options.refresh } }));
            await Promise.all([...document.querySelectorAll('dialog[open]')].map(closeDialog));
            if (version !== generation) return;
            // Page scripts bootstrap once; replacement content contains only markup.
            content.querySelectorAll('script').forEach((script) => script.remove());
            main.classList.toggle('is-catalog-refresh', !!options.refresh);
            main.replaceChildren(...[...content.childNodes].map((node) => document.importNode(node, true)));
            document.title = page.title;
            updateChrome(url, page, !!options.refresh);
            history.scrollRestoration = 'manual';
            // A directly opened cart/checkout has no public-page content to restore.
            if (options.refresh || options.history === 'replace' || !isStorePage(current)) history.replaceState({ [stateKey]: position }, '', url);
            else if (options.history !== 'pop') history.pushState({ [stateKey]: position }, '', url);
            current = url;
            document.dispatchEvent(new CustomEvent('store:page-updated', { detail: { url: url.href, refresh: !!options.refresh } }));
            restore?.();
            scrollPage(url, position);
            if (!options.refresh) focusSource(options.source, url);
            if (!options.refresh && !reducedMotion.matches) main.animate([{ transform: 'translateY(4px)' }, { transform: 'translateY(0)' }], { duration: 160, easing: 'ease-out' });
            let status = document.querySelector('[data-page-status]');
            if (!status) {
                status = document.createElement('p'); status.className = 'sr-only'; status.dataset.pageStatus = ''; status.setAttribute('role', 'status'); document.body.append(status);
            }
            status.textContent = page.title;
        });
    } catch (error) {
        if (version !== generation || error.name === 'AbortError') return true;
        if (error.status === 404 && options.refresh) {
            const fallback = new URL('/shop', location.origin);
            if (url.pathname === '/shop') { fallback.search = url.search; fallback.searchParams.delete('category'); fallback.searchParams.delete('page'); }
            await navigate(fallback, { history: 'replace' });
            toast(error.message);
            return true;
        }
        if (options.history === 'pop') history.replaceState({ [stateKey]: { x: scrollX, y: scrollY } }, '', current);
        if (!options.silent) toast(error.message || 'Could not open this page. Please try again.', 'error');
    } finally {
        if (version === generation) { pending = false; main.removeAttribute('aria-busy'); rememberScroll(); }
    }
    return true;
}
if (main) {
    main.tabIndex = -1;
    if (isStorePage(current)) { history.scrollRestoration = 'manual'; rememberScroll(); }
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || !link || link.hasAttribute('download') || (link.target && link.target !== '_self') || link.matches('[data-cookie-settings]')) return;
        const url = new URL(link.href);
        if (!isStorePage(url)) return;
        if (url.hash && url.pathname === current.pathname && url.search === current.search) return;
        event.preventDefault();
        navigate(url, { source: link });
    });
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.matches('.catalog-toolbar')) return;
        event.preventDefault();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form));
        navigate(url, { source: document.activeElement });
    });
    addEventListener('scroll', () => {
        if (pending || scrollFrame) return;
        scrollFrame = requestAnimationFrame(() => { scrollFrame = null; rememberScroll(); });
    }, { passive: true });
    addEventListener('popstate', (event) => {
        const url = new URL(location.href);
        if (!isStorePage(url)) return;
        const position = event.state?.[stateKey] || { x: 0, y: 0 };
        if (url.pathname === current.pathname && url.search === current.search) {
            controller?.abort(); generation++; pending = false; main.removeAttribute('aria-busy');
            current = url; scrollPage(url, position); return;
        }
        navigate(url, { history: 'pop', scroll: position });
    });
    addEventListener('hashchange', () => { if (isStorePage(location.href)) current = new URL(location.href); });
}
