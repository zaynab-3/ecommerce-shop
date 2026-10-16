const checkout = document.querySelector("[data-checkout]");
if (checkout) {
    const zones = document.getElementById("delivery-zone");
    function refreshTotal() {
        const option = zones?.selectedOptions[0];
        const fee = Number(option?.dataset.fee || 0);
        document.getElementById("delivery-fee").textContent =
            zones && !zones.value
                ? "Select area"
                : `$${(fee / 100).toFixed(2)}`;
        document.getElementById("checkout-total").textContent =
            `$${((Number(checkout.dataset.subtotal) + fee) / 100).toFixed(2)}`;
    }
    zones?.addEventListener("change", refreshTotal);
    refreshTotal();
    checkout.addEventListener("submit", () => {
        const button = checkout.querySelector(
            "button[type=submit],button.button",
        );
        if (button) {
            button.disabled = true;
            button.textContent = "Placing your order…";
        }
    });
}
