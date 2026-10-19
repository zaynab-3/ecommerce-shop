function removeToast(toast) {
    toast.classList.add("is-leaving");
    setTimeout(() => toast.remove(), 180);
}
function startToast(toast) { setTimeout(() => { if (toast.isConnected) removeToast(toast); }, 5000); }
export function toast(message, kind = "success") {
    const host = document.querySelector("dialog[open]:not(.is-closing)") || document.body;
    let stack = host.querySelector(":scope > [data-toast-stack]");
    if (!stack) { stack = document.createElement("div"); stack.className = "toast-stack"; stack.dataset.toastStack = ""; host.append(stack); }
    const notice = document.createElement("div");
    notice.className = `toast notice ${kind}`;
    notice.setAttribute("role", kind === "error" ? "alert" : "status");
    const text = document.createElement("span"); text.textContent = message;
    const close = document.createElement("button"); close.type = "button"; close.dataset.dismiss = ""; close.setAttribute("aria-label", "Dismiss notification"); close.textContent = "×";
    notice.append(text, close); stack.append(notice); startToast(notice);
    while (stack.children.length > 4) stack.firstElementChild.remove();
}
document.querySelectorAll("[data-toast]").forEach(startToast);
document.addEventListener("click", (event) => { const close = event.target.closest("[data-dismiss]"); if (close) removeToast(close.closest(".notice")); });
