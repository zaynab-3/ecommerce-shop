// The order is already saved. Navigate in the same tab so pop-up blockers do not
// prevent the WhatsApp handoff. The link remains available when returning here.
const whatsappLink = document.querySelector("[data-whatsapp-auto-open]");
if (whatsappLink) {
    // Wait until the confirmation finishes loading. Navigating during load can
    // replace its history entry and make Back return to the checkout form.
    window.addEventListener("load", () => {
        setTimeout(() => window.location.assign(whatsappLink.href), 0);
    }, { once: true });
}
