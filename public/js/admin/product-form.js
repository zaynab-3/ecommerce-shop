let colorIndex = 1000;
const colors = document.querySelector("[data-colors]");
document.querySelector("[data-add-color]")?.addEventListener("click", () => {
    if (colors.children.length >= 20) {
        alert("A product can have up to 20 colors.");
        return;
    }
    const row = document
        .getElementById("color-template")
        .content.cloneNode(true);
    row.querySelectorAll("[data-name]").forEach((input) => {
        input.name = `colors[${colorIndex}][${input.dataset.name}]`;
    });
    colorIndex++;
    colors.append(row);
});
colors?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-remove-color]");
    if (button) button.closest(".color-editor-row").remove();
});
const stockMode = document.querySelector("[data-stock-mode]");
function updateStockInput() {
    const input = document.querySelector("[data-stock-quantity]");
    if (input && stockMode) {
        input.required = stockMode.value === "tracked";
        input.disabled = stockMode.value === "open";
    }
}
stockMode?.addEventListener("change", updateStockInput);
updateStockInput();
