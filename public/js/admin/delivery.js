document.querySelectorAll("[data-select-all]").forEach((input) =>
    input.addEventListener("change", () => {
        document.querySelectorAll('input[name="zones[]"]').forEach((zone) => {
            zone.checked = input.checked;
        });
    }),
);
document.querySelectorAll("[data-select-group]").forEach((button) =>
    button.addEventListener("click", () => {
        const inputs = [
            ...document.querySelectorAll(
                `[data-zone-group="${button.dataset.selectGroup}"] input[type=checkbox]`,
            ),
        ];
        const check = inputs.some((input) => !input.checked);
        inputs.forEach((input) => {
            input.checked = check;
        });
    }),
);
