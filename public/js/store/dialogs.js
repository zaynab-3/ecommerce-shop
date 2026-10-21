const closing = new WeakMap();
let opening = 0;
function syncLock() {
    document.documentElement.classList.toggle("has-store-dialog", !!document.querySelector("dialog[open]"));
}
export function closeDialog(dialog) {
    if (!dialog?.open) return Promise.resolve();
    if (closing.has(dialog)) return closing.get(dialog);
    dialog.classList.add("is-closing");
    const pending = Promise.all(dialog.getAnimations().map((animation) => animation.finished.catch(() => {}))).then(() => {
        dialog.close();
        dialog.classList.remove("is-closing");
        closing.delete(dialog);
        syncLock();
    });
    closing.set(dialog, pending);
    return pending;
}
export async function openDialog(dialog) {
    if (!dialog || (dialog.open && !closing.has(dialog))) return;
    const version = ++opening;
    await Promise.all([...document.querySelectorAll("dialog[open]")].map(closeDialog));
    if (version !== opening) return;
    dialog.showModal();
    syncLock();
    document.dispatchEvent(new CustomEvent("store:dialog-open", { detail: dialog }));
}
document.querySelectorAll("dialog").forEach((dialog) => {
    let outsidePress = false;
    const outside = (event) => {
        const b = dialog.getBoundingClientRect();
        return event.clientX < b.left || event.clientX > b.right || event.clientY < b.top || event.clientY > b.bottom;
    };
    dialog.addEventListener('pointerdown', (event) => { outsidePress = event.target === dialog && outside(event); });
    dialog.addEventListener("cancel", (event) => { event.preventDefault(); closeDialog(dialog); });
    dialog.addEventListener("close", syncLock);
    dialog.addEventListener("click", (event) => {
        if (event.target.closest("[data-dialog-close],[data-menu-close]")) closeDialog(dialog);
        if (event.target !== dialog) return;
        if (outsidePress && outside(event)) closeDialog(dialog);
        outsidePress = false;
    });
});
