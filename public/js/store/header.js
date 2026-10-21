const chrome = document.querySelector("[data-store-header]");
let last = scrollY;
let travel = 0;
let direction = 0;
let scheduled = false;
let height = chrome?.offsetHeight || 0;
if (chrome) new ResizeObserver(([entry]) => { height = entry.borderBoxSize?.[0]?.blockSize || entry.contentRect.height; }).observe(chrome);
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
    const hidden = chrome.classList.contains('is-hidden');
    if ((position < height || travel < -8) && hidden) chrome.classList.remove("is-hidden");
    else if (position >= height && travel > 16 && !hidden) chrome.classList.add("is-hidden");
}
addEventListener("scroll", () => { if (!scheduled) { scheduled = true; requestAnimationFrame(updateHeader); } }, { passive: true });
document.addEventListener("store:dialog-open", () => chrome?.classList.remove("is-hidden"));
document.addEventListener('store:page-updated', (event) => { last = scrollY; travel = 0; direction = 0; if (!event.detail?.refresh) chrome?.classList.remove('is-hidden'); });
