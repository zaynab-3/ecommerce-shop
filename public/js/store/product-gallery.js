const detailImage = document.getElementById("detail-image");
function showImage(url, alt) {
    if (!detailImage) return;
    detailImage.src = url;
    if (alt) detailImage.alt = alt;
    document.querySelectorAll("[data-image]").forEach((button) => {
        const active = button.dataset.image === url;
        button.classList.toggle("selected", active);
        button.setAttribute("aria-pressed", String(active));
    });
}
document
    .querySelectorAll("[data-image]")
    .forEach((button) =>
        button.addEventListener("click", () =>
            showImage(button.dataset.image, button.dataset.alt),
        ),
    );
document.querySelectorAll("[data-color-name]").forEach((input) =>
    input.addEventListener("change", () => {
        document.getElementById("selected-color").textContent =
            input.dataset.colorName;
        showImage(input.dataset.colorImage);
    }),
);
const initialColor = document.querySelector("[data-color-name]:checked");
if (initialColor) showImage(initialColor.dataset.colorImage);
