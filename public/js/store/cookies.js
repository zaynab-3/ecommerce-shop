import { request, serial } from "../shared/http.js";

const notice = document.querySelector("[data-cookie-notice]");
const form = notice?.querySelector("[data-cookie-form]");
let opener;

document.addEventListener("click", (event) => {
    const link = event.target.closest("[data-cookie-settings]");
    if (!link || !notice) return;
    event.preventDefault();
    opener = link;
    notice.hidden = false;
    notice.querySelector(".icon-button").focus({ preventScroll: true });
});

form?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (form.dataset.busy) return;
    form.dataset.busy = "1";
    const buttons = [...form.querySelectorAll("button")];
    const errorMessage = form.querySelector("[data-cookie-error]");
    errorMessage.hidden = true;
    buttons.forEach((button) => { button.disabled = true; });
    serial(async () => {
        try {
            await request(form.action, { method: "POST", body: new FormData(form) });
            notice.hidden = true;
            if (opener?.isConnected) opener.focus({ preventScroll: true });
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        } finally {
            delete form.dataset.busy;
            buttons.forEach((button) => { button.disabled = false; });
        }
    });
});
