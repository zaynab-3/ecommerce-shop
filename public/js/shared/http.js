let tail = Promise.resolve();
export function serial(operation) {
    const next = tail.then(operation, operation);
    tail = next.catch(() => {});
    return next;
}
export async function request(url, options = {}) {
    const response = await fetch(url, { credentials: "same-origin", ...options, headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest", ...options.headers } });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = response.status >= 500 ? "Something went wrong. Please try again." : response.status === 404 ? "This item is no longer available." : response.status === 419 ? "Your session expired. Reload this page and try again." : Object.values(payload.errors || {}).flat().join(" ") || payload.message || "Unable to complete this request.";
        const error = new Error(message);
        error.details = payload.errors || {};
        error.product = payload.product;
        throw error;
    }
    return payload;
}
