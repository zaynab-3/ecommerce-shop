import { navigate } from './page-motion.js';
import { closeDialog } from './dialogs.js';
function pageUrl(href) {
    const url = new URL(href, location.href);
    if (url.origin !== location.origin || !['http:', 'https:'].includes(url.protocol)) return null;
    const cart = document.getElementById('cart-dialog');
    if ([cart?.dataset.cartUrl, cart?.dataset.checkoutUrl].filter(Boolean).some((route) => new URL(route).pathname === url.pathname)) return null;
    return url;
}
// Registered after cart/favorite handlers so their async actions stay in place.
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (event.defaultPrevented || !link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || (link.target && link.target !== '_self') || link.hasAttribute('download')) return;
    const url = pageUrl(link.href);
    if (!url || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
    event.preventDefault();
    navigate(url.href);
});
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (event.defaultPrevented || (event.submitter?.formMethod || form.method).toLowerCase() !== 'get' || (form.target && form.target !== '_self')) return;
    const url = pageUrl(form.action);
    if (!url) return;
    event.preventDefault();
    const data = new FormData(form);
    if (event.submitter?.name) data.append(event.submitter.name, event.submitter.value);
    url.search = new URLSearchParams(data).toString();
    const dialog = form.closest('dialog');
    if (dialog) await closeDialog(dialog);
    navigate(url.href);
});
