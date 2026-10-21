import { request, serial } from "../shared/http.js";
import { toast } from "../shared/notices.js";
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!form.matches("[data-ajax-save]")) return;
    event.preventDefault();
    if (form.dataset.busy) return;
    form.dataset.busy = "1";
    const button = form.querySelector("button"); button.disabled = true;
    serial(async () => {
        try {
            const data = await request(form.action, { method: "POST", body: new FormData(form) });
            document.querySelectorAll("[data-saved-count]").forEach((counter) => { counter.textContent = data.count; });
            document.querySelector(".saved-link")?.setAttribute("aria-label", `Saved items, ${data.count} items`);
            document.querySelectorAll(`[data-ajax-save][data-product-id="${data.product_id}"]`).forEach((match) => {
                const control = match.querySelector("button");
                control.classList.toggle("is-saved", data.saved);
                control.setAttribute("aria-pressed", String(data.saved));
                control.setAttribute("aria-label", `${data.saved ? "Unsave" : "Save"} ${match.dataset.productName}`);
                const label = control.querySelector("[data-saved-label]");
                if (label) label.textContent = data.saved ? "Saved to your favorites" : "Save for later";
            });
            toast(data.message);
        } catch (error) { toast(error.message, "error"); }
        finally { delete form.dataset.busy; button.disabled = false; }
    });
});
