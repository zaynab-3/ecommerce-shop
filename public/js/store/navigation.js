import { openDialog, closeDialog } from "./dialogs.js";
import { navigate } from "./page-motion.js";
const menuButton = document.querySelector("[data-menu-toggle]");
const drawer = document.getElementById("mobile-navigation");
const search = document.getElementById("search-dialog");
const searchButtons = document.querySelectorAll("[data-search-toggle]");
const mobile = matchMedia("(max-width: 600px)");
menuButton?.addEventListener("click", () => { if (mobile.matches) openDialog(drawer); });
drawer?.addEventListener("close", () => menuButton?.setAttribute("aria-expanded", "false"));
drawer?.addEventListener("click", async (event) => {
    const link = event.target.closest("a[href]");
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === "_blank" || link.hasAttribute("download")) return;
    // Cart links have their own dialog handler. Page links wait for the drawer
    // to finish sliding away before the page content fades out.
    const cartUrl = document.getElementById("cart-dialog")?.dataset.cartUrl;
    if (cartUrl && new URL(link.href).pathname === new URL(cartUrl).pathname) return;
    event.preventDefault();
    await closeDialog(drawer);
    navigate(link.href);
});
searchButtons.forEach((button) => button.addEventListener("click", () => openDialog(search)));
search?.addEventListener("close", () => searchButtons.forEach((button) => button.setAttribute("aria-expanded", "false")));
document.addEventListener("store:dialog-open", ({ detail }) => {
    if (detail === drawer) menuButton?.setAttribute("aria-expanded", "true");
    if (detail === search) { searchButtons.forEach((button) => button.setAttribute("aria-expanded", "true")); document.getElementById("search").focus(); }
});
mobile.addEventListener("change", () => { if (!mobile.matches) closeDialog(drawer); });
