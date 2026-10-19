const menuButton = document.querySelector("[data-menu-toggle]");
const searchButton = document.querySelector("[data-search-toggle]");
const header = document.querySelector(".site-header");
const closeSearch = () => {
    header?.classList.remove("has-open-search");
    searchButton?.setAttribute("aria-expanded", "false");
    searchButton?.setAttribute("aria-label", "Open search");
};
menuButton?.addEventListener("click", () => {
    closeSearch();
    const open = document
        .querySelector(".main-nav")
        .classList.toggle("is-open");
    menuButton.closest(".site-header").classList.toggle("has-open-menu", open);
    menuButton.setAttribute("aria-expanded", String(open));
    menuButton.setAttribute(
        "aria-label",
        open ? "Close navigation" : "Open navigation",
    );
});
searchButton?.addEventListener("click", () => {
    if (menuButton?.getAttribute("aria-expanded") === "true") menuButton.click();
    const open = header.classList.toggle("has-open-search");
    searchButton.setAttribute("aria-expanded", String(open));
    searchButton.setAttribute("aria-label", open ? "Close search" : "Open search");
    if (open) document.getElementById("search").focus();
});
document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    if (header?.classList.contains("has-open-search")) { closeSearch(); searchButton.focus(); }
    if (menuButton?.getAttribute("aria-expanded") === "true") { menuButton.click(); menuButton.focus(); }
});
