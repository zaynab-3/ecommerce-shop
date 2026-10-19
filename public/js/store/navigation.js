import { openDialog, closeDialog } from "./dialogs.js";
const menuButton = document.querySelector("[data-menu-toggle]");
const drawer = document.getElementById("mobile-navigation");
const search = document.getElementById("search-dialog");
const searchButtons = document.querySelectorAll("[data-search-toggle]");
const mobile = matchMedia("(max-width: 600px)");
menuButton?.addEventListener("click", () => { if (mobile.matches) openDialog(drawer); });
drawer?.addEventListener("close", () => menuButton?.setAttribute("aria-expanded", "false"));
drawer?.addEventListener("click", (event) => { if (event.target.closest("a[href]")) closeDialog(drawer); });
searchButtons.forEach((button) => button.addEventListener("click", () => openDialog(search)));
search?.addEventListener("close", () => searchButtons.forEach((button) => button.setAttribute("aria-expanded", "false")));
document.addEventListener("store:dialog-open", ({ detail }) => {
    if (detail === drawer) menuButton?.setAttribute("aria-expanded", "true");
    if (detail === search) { searchButtons.forEach((button) => button.setAttribute("aria-expanded", "true")); document.getElementById("search").focus(); }
});
mobile.addEventListener("change", () => { if (!mobile.matches) closeDialog(drawer); });
