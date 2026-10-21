const chrome = document.querySelector("[data-store-header]");
let last = scrollY;
let travel = 0;
let direction = 0;
let scheduled = false;
function updateHeader() {
    scheduled = false;
    const position = Math.max(0, scrollY);
    const delta = position - last;
    last = position;
    if (!chrome || document.querySelector("dialog[open]")) return;
    const nextDirection = Math.sign(delta);
    if (nextDirection !== direction) travel = 0;
    direction = nextDirection;
    travel += delta;
    if (position < chrome.offsetHeight || travel < -4) chrome.classList.remove("is-hidden");
    else if (travel > 10) chrome.classList.add("is-hidden");
}
addEventListener("scroll", () => { if (!scheduled) { scheduled = true; requestAnimationFrame(updateHeader); } }, { passive: true });
document.addEventListener("store:dialog-open", () => chrome?.classList.remove("is-hidden"));
document.addEventListener('store:page-updated', (event) => { last = scrollY; travel = 0; direction = 0; if (!event.detail?.refresh) chrome?.classList.remove('is-hidden'); });
