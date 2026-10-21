import { openDialog, closeDialog } from "./dialogs.js";
import { request, serial } from "../shared/http.js";
import { toast } from "../shared/notices.js";
import { initCheckout, validatePhone } from "./checkout.js";
import { nationalNumber } from "./lebanese-phone.js";
import { navigate } from './page-navigation.js';
const dialog = document.getElementById("cart-dialog");
const content = dialog?.querySelector("[data-cart-content]");
let originalUrl = location.href;
let directCart = false;
let receipt = false;
let draft = {};
let phase = 'cart';
export function updateCount(count) {
    document.querySelectorAll("[data-cart-count]").forEach((el) => el.textContent = count);
    document.querySelector(".cart-link")?.setAttribute("aria-label", `Shopping cart, ${count} items`);
}
function setPhase(next, focus = false) {
    phase = next === 'checkout' && content.querySelector('[data-checkout]') ? 'checkout' : 'cart';
    content.querySelectorAll('[data-cart-phase]').forEach((panel) => panel.hidden = panel.dataset.cartPhase !== phase);
    dialog.querySelector('[data-cart-title]').textContent = phase === 'checkout' ? 'Checkout' : 'Your cart';
    dialog.querySelector('#cart-title [data-cart-count]').hidden = false;
    document.dispatchEvent(new CustomEvent('store:layout-updated'));
    if (focus) {
        content.scrollTo({ top: 0, behavior: 'instant' });
        const heading = dialog.querySelector('#cart-title');
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
    }
}
function rememberDetails() {
    const form = content?.querySelector('[data-checkout]');
    if (!form) return;
    for (const [name, value] of new FormData(form)) {
        if (!['_token', 'idempotency_key'].includes(name)) draft[name] = value;
    }
}
function render(data) {
    rememberDetails();
    const focused = content.contains(document.activeElement) ? document.activeElement.getAttribute('aria-label') : null;
    if (data.html !== undefined) content.innerHTML = data.html;
    const form = content.querySelector('[data-checkout]');
    if (form) {
        for (const [name, value] of Object.entries(draft)) {
            const input = form.elements.namedItem(name);
            if (input) input.value = value;
        }
        initCheckout(content);
    }
    updateCount(data.count);
    setPhase(phase);
    if (focused) [...content.querySelectorAll('[aria-label]')].find((el) => el.getAttribute('aria-label') === focused)?.focus({ preventScroll: true });
    document.dispatchEvent(new CustomEvent("store:cart-updated"));
}
async function loadCart(nextPhase = 'cart') {
    content.setAttribute("aria-busy", "true");
    try {
        const data = await request(dialog.dataset.cartUrl);
        phase = nextPhase;
        receipt = false;
        render(data);
        content.scrollTo({ top: 0, behavior: 'instant' });
    } finally { content.removeAttribute("aria-busy"); }
}
async function openCart(nextPhase = 'cart') {
    if (!dialog) return;
    if (!dialog.open) originalUrl = location.href;
    await openDialog(dialog);
    try { await serial(() => loadCart(nextPhase)); }
    catch (error) { toast(error.message, "error"); }
}
document.addEventListener("click", (event) => {
    const step = event.target.closest('[data-cart-step]');
    if (step) {
        const form = step.form;
        if (form.dataset.busy) return;
        const input = form.elements.quantity;
        input.value = Math.max(1, Math.min(99, Number(input.value) + Number(step.dataset.cartStep)));
        form.requestSubmit();
        return;
    }
    const link = event.target.closest("a[href]");
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === "_blank") return;
    if (link.matches('[data-proceed-checkout]') && dialog?.open) { event.preventDefault(); setPhase('checkout', true); return; }
    if (link.matches('[data-cart-back]') && dialog?.open) { event.preventDefault(); setPhase('cart', true); return; }
    if (dialog && [dialog.dataset.cartUrl, dialog.dataset.checkoutUrl].filter(Boolean).some((url) => new URL(link.href).pathname === new URL(url).pathname)) { event.preventDefault(); openCart(new URL(link.href).pathname === new URL(dialog.dataset.checkoutUrl).pathname ? 'checkout' : 'cart'); }
});
// Handle sheet dismissal before the document's public-link navigation handler.
dialog?.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-continue-shopping]');
    if (!link || directCart || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || (link.target && link.target !== '_self')) return;
    event.preventDefault();
    closeDialog(dialog);
});
function showCheckoutError(form, error) {
    const box = form.querySelector('[data-checkout-errors]');
    box.textContent = error.message;
    box.hidden = false;
    const name = Object.keys(error.details || {})[0];
    const input = form.elements.namedItem(name);
    if (input?.focus) { input.setAttribute('aria-invalid', 'true'); input.focus(); }
    else box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    toast(error.message, 'error');
}
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!form.matches("[data-ajax-cart],[data-cart-update],[data-checkout]")) return;
    event.preventDefault();
    if (form.dataset.busy) return;
    const checkout = form.matches('[data-checkout]');
    if (checkout && !validatePhone(form)) return;
    const submitter = event.submitter;
    const body = new FormData(form);
    if (submitter?.name) body.set(submitter.name, submitter.value);
    if (checkout) {
        form.querySelector('[data-checkout-errors]').hidden = true;
        form.querySelectorAll('[aria-invalid]').forEach((input) => input.removeAttribute('aria-invalid'));
        body.set('phone', '+961' + nationalNumber(body.get('phone')));
    }
    form.dataset.busy = "1";
    const controls = [...form.querySelectorAll('button')].filter((button) => !button.disabled);
    controls.forEach((button) => { button.disabled = true; button.setAttribute('aria-busy', 'true'); });
    serial(async () => {
        try {
            const data = await request(form.action, { method: "POST", body });
            if (checkout) {
                draft = {};
                content.innerHTML = data.html;
                updateCount(0);
                dialog.querySelector('[data-cart-title]').textContent = 'Order received';
                dialog.querySelector('#cart-title [data-cart-count]').hidden = true;
                receipt = true;
                content.scrollTop = 0;
                content.querySelector('h1')?.setAttribute('tabindex', '-1');
                content.querySelector('h1')?.focus({ preventScroll: true });
                document.dispatchEvent(new CustomEvent('store:layout-updated'));
                history.replaceState(null, '', data.confirmation_url);
                if (data.whatsapp_url) location.assign(data.whatsapp_url);
            } else {
                if (data.html !== undefined) render(data); else updateCount(data.count);
                toast(data.message);
                if (data.redirect) { originalUrl = location.href; await openDialog(dialog); await loadCart('checkout'); }
            }
        } catch (error) {
            if (checkout) showCheckoutError(form, error);
            else toast(error.message || "The update could not be confirmed. Check your cart before trying again.", "error");
        } finally {
            delete form.dataset.busy;
            controls.forEach((button) => { button.disabled = button.dataset.stockUnavailable === "1"; button.removeAttribute('aria-busy'); });
        }
    });
});
document.addEventListener("change", (event) => {
    if (event.target.matches('[data-cart-update] input[name=quantity]') && event.target.reportValidity()) event.target.form.requestSubmit();
});
dialog?.addEventListener('close', () => {
    rememberDetails();
    if (directCart) { directCart = false; navigate(dialog.dataset.shopUrl, { history: 'replace' }); }
    else if (receipt && location.pathname.startsWith('/orders/')) history.replaceState(history.state, '', originalUrl);
});
document.addEventListener('store:page-leave', () => { directCart = false; });
const fallback = document.querySelector("[data-cart-fallback]");
if (fallback && dialog) {
    directCart = true;
    content.append(document.querySelector("[data-cart-page-content]"));
    fallback.hidden = true;
    initCheckout(content);
    setPhase(fallback.dataset.startPhase || 'cart');
    openDialog(dialog);
}
