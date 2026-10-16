document.querySelectorAll("[data-quantity]").forEach((button) =>
    button.addEventListener("click", () => {
        const input = document.getElementById("quantity");
        const min = Number(input.min) || 1;
        const max = Number(input.max) || 99;
        input.value = Math.max(
            min,
            Math.min(
                max,
                (Number(input.value) || 1) + Number(button.dataset.quantity),
            ),
        );
    }),
);
