import { openDialog, closeDialog } from "./dialogs.js";
import { request, serial } from "../shared/http.js";
import { toast } from "../shared/notices.js";
import { initCheckout, validatePhone } from "./checkout.js";
import { nationalNumber } from "./lebanese-phone.js";
const dialog = document.getElementById("cart-dialog");
const content = dialog?.querySelector("[data-cart-content]");
const originalUrl = location.href;
let directCart = false;
let receipt = false;
let draft = {};
export function updateCount(count) {
    document.querySelectorAll("[data-cart-count]").forEach((el) => el.textContent = count);
    document.querySelector(".cart-link")?.setAttribute("aria-label", `Shopping cart, ${count} items`);
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
    if (focused) [...content.querySelectorAll('[aria-label]')].find((el) => el.getAttribute('aria-label') === focused)?.focus({ preventScroll: true });
    document.dispatchEvent(new CustomEvent("store:cart-updated"));
}
async function loadCart() {
    content.setAttribute("aria-busy", "true");
    try {
        render(await request(dialog.dataset.cartUrl));
        dialog.querySelector('[data-cart-title]').textContent = 'Cart & checkout';
        dialog.querySelector('#cart-title [data-cart-count]').hidden = false;
        receipt = false;
    } finally { content.removeAttribute("aria-busy"); }
}
async function openCart() {
    if (!dialog) return;
    await openDialog(dialog);
    try { await serial(loadCart); }
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
    if (link.matches("[data-continue-shopping]") && dialog?.open && !directCart) { event.preventDefault(); closeDialog(dialog); return; }
    if (dialog && [dialog.dataset.cartUrl, dialog.dataset.checkoutUrl].filter(Boolean).some((url) => new URL(link.href).pathname === new URL(url).pathname)) { event.preventDefault(); openCart(); }
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
                history.replaceState(null, '', data.confirmation_url);
                if (data.whatsapp_url) location.assign(data.whatsapp_url);
            } else {
                if (data.html !== undefined) render(data); else updateCount(data.count);
                toast(data.message);
                if (data.redirect) { await openDialog(dialog); await loadCart(); }
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
    if (directCart) location.assign(dialog.dataset.shopUrl);
    else if (receipt) history.replaceState(null, '', originalUrl);
});
const fallback = document.querySelector("[data-cart-fallback]");
if (fallback && dialog) {
    directCart = true;
    content.append(document.querySelector("[data-cart-page-content]"));
    fallback.hidden = true;
    initCheckout(content);
    openDialog(dialog);
}
