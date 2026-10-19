// A shared five-second server cache keeps polling inexpensive across visitors.
const stockIds = [
    ...new Set(
        [...document.querySelectorAll("[data-stock-id]")].map(
            (el) => el.dataset.stockId,
        ),
    ),
].slice(0, 48);
let stockInFlight = false;
function updateButtons(id, disabled, label) {
    document.querySelectorAll(`[data-stock-button="${id}"]`).forEach((buy) => {
        buy.disabled = disabled;
        buy.querySelector("[data-stock-button-label]").textContent =
            label ?? buy.dataset.availableLabel;
    });
}
async function refreshStock() {
    if (!stockIds.length || document.hidden || stockInFlight) return;
    stockInFlight = true;
    try {
        const query = new URLSearchParams();
        stockIds.forEach((id) => query.append("ids[]", id));
        const response = await fetch(`/stock?${query}`, {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) return;
        const rows = await response.json();
        const seen = new Set();
        for (const row of rows) {
            seen.add(String(row.id));
            document
                .querySelectorAll(`[data-stock-id="${row.id}"]`)
                .forEach((el) => {
                    el.classList.remove(
                        "stock-available",
                        "stock-low",
                        "stock-out",
                    );
                    el.classList.add(`stock-${row.status}`);
                    el.querySelector("[data-stock-label]").textContent = {
                        available: "In stock",
                        low: "Low stock",
                        out: "Out of stock",
                    }[row.status];
                });
            updateButtons(row.id, row.status === "out", row.status === "out" ? "Out of stock" : null);
            if (document.querySelector(`[data-product-detail] [data-stock-button="${row.id}"]`)) {
                const quantity = document.getElementById("quantity");
                if (quantity) quantity.max = row.quantity === null ? 99 : Math.max(1, Math.min(99, row.quantity));
            }
        }
        for (const id of stockIds) {
            if (seen.has(id)) continue;
            document
                .querySelectorAll(`[data-stock-id="${id}"]`)
                .forEach((el) => {
                    el.classList.remove("stock-available", "stock-low");
                    el.classList.add("stock-out");
                    el.querySelector("[data-stock-label]").textContent =
                        "Unavailable";
                });
            updateButtons(id, true, "Unavailable");
        }
    } catch {
    } finally {
        stockInFlight = false;
    }
}
if (stockIds.length) {
    setInterval(refreshStock, 15000);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) refreshStock();
    });
}
