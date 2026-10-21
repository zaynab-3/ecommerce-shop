import { navigate } from './page-navigation.js';
import { isPageScrolling } from '../shared/smooth-scroll.js';

const revision = document.querySelector('meta[name="catalog-version"]');
let busy = false;
async function synchronize() {
    if (!revision || busy || document.hidden || !navigator.onLine || !/^\/(?:shop\/?|products\/[^/]+\/?)?$/.test(location.pathname)) return;
    const main = document.getElementById('main');
    if (main.hasAttribute('aria-busy') || isPageScrolling() || document.querySelector('dialog[open]') || main.contains(document.activeElement) && document.activeElement.matches('input,select,textarea')) return;
    const href = location.href;
    busy = true;
    try {
        const response = await fetch(revision.dataset.url, { credentials: 'omit', cache: 'no-store', headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(8000) });
        if (!response.ok) return;
        const data = await response.json();
        if (location.href !== href || main.hasAttribute('aria-busy') || document.hidden || isPageScrolling()) return;
        if (data.version !== revision.content) await navigate(location.href, { refresh: true, silent: true });
    } catch { /* A later visible/online check retries without disturbing shopping. */ }
    finally { busy = false; }
}
setInterval(synchronize, 3000);
document.addEventListener('visibilitychange', synchronize);
document.addEventListener('focusout', () => setTimeout(synchronize, 0));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('close', synchronize));
addEventListener('online', synchronize);
