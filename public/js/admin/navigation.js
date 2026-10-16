document
    .querySelector("[data-admin-menu]")
    ?.addEventListener("click", (event) => {
        const open = document
            .querySelector(".admin-sidebar")
            .classList.toggle("is-open");
        event.currentTarget.setAttribute("aria-expanded", String(open));
    });
