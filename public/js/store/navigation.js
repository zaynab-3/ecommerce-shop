const menuButton = document.querySelector("[data-menu-toggle]");
menuButton?.addEventListener("click", () => {
    const open = document
        .querySelector(".main-nav")
        .classList.toggle("is-open");
    menuButton.setAttribute("aria-expanded", String(open));
    menuButton.setAttribute(
        "aria-label",
        open ? "Close navigation" : "Open navigation",
    );
});
