document.addEventListener("click", (event) => {
    const color = event.target.closest("[data-card-color]");
    if (!color) return;
    const card = color.closest(".product-card");
    card.querySelectorAll("[data-card-color]").forEach((button) => button.setAttribute("aria-pressed", String(button === color)));
    const input = card.querySelector('[name="color_id"]');
    if (input) input.value = color.dataset.colorId;
    const image = card.querySelector(".product-image-link img");
    image.src = color.dataset.colorImage;
    image.srcset = color.dataset.colorSrcset;
});
