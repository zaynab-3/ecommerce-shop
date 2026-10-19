const menuButton = document.querySelector("[data-menu-toggle]");
const searchButton = document.querySelector("[data-search-toggle]");
const header = document.querySelector(".site-header");
const drawer = document.getElementById("mobile-navigation");
const mobile = matchMedia("(max-width: 600px)");

const closeSearch = () => {
    header?.classList.remove("has-open-search");
    searchButton?.setAttribute("aria-expanded", "false");
    searchButton?.setAttribute("aria-label", "Open search");
};
const syncClosedMenu = () => {
    if (drawer?.open) return;
    document.documentElement.classList.remove("has-mobile-navigation");
    menuButton?.setAttribute("aria-expanded", "false");
    menuButton?.setAttribute("aria-label", "Open navigation");
};
const closeMenu = () => { if (drawer?.open) drawer.close(); syncClosedMenu(); };
drawer?.addEventListener("close", syncClosedMenu);
menuButton?.addEventListener("click", () => {
    if (!mobile.matches || !drawer) return;
    if (drawer.open) { closeMenu(); return; }
    closeSearch();
    drawer.showModal();
    document.documentElement.classList.add("has-mobile-navigation");
    menuButton.setAttribute("aria-expanded", "true");
    menuButton.setAttribute("aria-label", "Close navigation");
});
drawer?.querySelector("[data-menu-close]")?.addEventListener("click", closeMenu);
drawer?.addEventListener("click", (event) => {
    if (event.target.closest("a[href]")) closeMenu();
    if (event.target !== drawer) return;
    const bounds = drawer.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right ||
        event.clientY < bounds.top || event.clientY > bounds.bottom) closeMenu();
});
searchButton?.addEventListener("click", () => {
    closeMenu();
    const open = header.classList.toggle("has-open-search");
    searchButton.setAttribute("aria-expanded", String(open));
    searchButton.setAttribute("aria-label", open ? "Close search" : "Open search");
    if (open) document.getElementById("search").focus();
});
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && header?.classList.contains("has-open-search")) {
        closeSearch(); searchButton.focus();
    }
});
mobile.addEventListener("change", () => { closeSearch(); closeMenu(); });
