import { openDialog, closeDialog } from "./dialogs.js";
import { request, serial } from "../shared/http.js";
import { toast } from "../shared/notices.js";
const dialog = document.getElementById("cart-dialog");
const content = dialog?.querySelector("[data-cart-content]");
let directCart = false;
export function updateCount(count) {
    document.querySelectorAll("[data-cart-count]").forEach((el) => el.textContent = count);
    document.querySelector(".cart-link")?.setAttribute("aria-label", `Shopping cart, ${count} items`);
}
function render(data) {
    if (data.html !== undefined) content.innerHTML = data.html;
    updateCount(data.count);
    document.dispatchEvent(new CustomEvent("store:cart-updated"));
}
async function openCart() {
    if (!dialog) return;
    await openDialog(dialog);
    content.setAttribute("aria-busy", "true");
    try { await serial(async () => render(await request(dialog.dataset.cartUrl))); }
    catch (error) { content.textContent = "Your cart could not load. Close it and try again."; toast(error.message, "error"); }
    finally { content.removeAttribute("aria-busy"); }
}
document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === "_blank") return;
    if (link.matches("[data-continue-shopping]") && dialog?.open && !directCart) { event.preventDefault(); closeDialog(dialog); return; }
    if (dialog && new URL(link.href).pathname === new URL(dialog.dataset.cartUrl).pathname) { event.preventDefault(); openCart(); }
});
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!form.matches("[data-ajax-cart],[data-cart-update],[data-cart-delivery]")) return;
    event.preventDefault();
    if (form.dataset.busy) return;
    const submitter = event.submitter;
    const body = new FormData(form);
    if (submitter?.name) body.set(submitter.name, submitter.value);
    form.dataset.busy = "1";
    if (submitter) { submitter.disabled = true; submitter.setAttribute("aria-busy", "true"); }
    serial(async () => {
        try {
            const url = form.matches("[data-cart-delivery]") ? `${form.action}?${new URLSearchParams(body)}` : form.action;
            const data = await request(url, form.matches("[data-cart-delivery]") ? {} : { method: "POST", body });
            if (data.html !== undefined) render(data); else updateCount(data.count);
            toast(data.message || "Delivery updated.");
            if (data.redirect) location.assign(data.redirect);
        } catch (error) { toast(error.message || "The update could not be confirmed. Check your cart before trying again.", "error"); }
        finally { delete form.dataset.busy; if (submitter) { submitter.disabled = submitter.dataset.stockUnavailable === "1"; submitter.removeAttribute("aria-busy"); } }
    });
});
document.addEventListener("change", (event) => { if (event.target.matches("[data-cart-delivery] select")) event.target.form.requestSubmit(); });
const fallback = document.querySelector("[data-cart-fallback]");
if (fallback && dialog) {
    directCart = true;
    content.append(document.querySelector("[data-cart-page-content]"));
    fallback.hidden = true;
    openDialog(dialog);
    dialog.addEventListener("close", () => location.assign(dialog.dataset.shopUrl), { once: true });
}
