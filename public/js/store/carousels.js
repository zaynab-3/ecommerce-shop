const reducedMotion = matchMedia("(prefers-reduced-motion: reduce)");
const controllers = new Map();
function initCarousels() {
    for (const [carousel, dispose] of controllers) if (!carousel.isConnected) { dispose(); controllers.delete(carousel); }
    document.querySelectorAll("[data-carousel]").forEach((carousel) => {
        if (controllers.has(carousel)) return;
        const listeners = new AbortController();
        const on = (target, type, callback, options = {}) => target.addEventListener(type, callback, { ...options, signal: listeners.signal });
        const track = carousel.querySelector("[data-carousel-track]");
        const previous = carousel.querySelector("[data-carousel-prev]");
        const next = carousel.querySelector("[data-carousel-next]");
        const playback = carousel.querySelector("[data-carousel-autoplay]");
        let timer;
        let paused = false;
        let hovered = false;
        let focused = false;
        let touching = false;
        let visible = false;
        const overflowing = () => track.scrollWidth > track.clientWidth + 2;
        const schedule = () => {
            clearTimeout(timer);
            if (!carousel.isConnected || paused || hovered || focused || touching || !visible || document.hidden || reducedMotion.matches || !overflowing() || document.querySelector("dialog[open]")) return;
            timer = setTimeout(() => {
                const first = track.firstElementChild;
                const step = first.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap);
                const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
                track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + step, behavior: "smooth" });
                schedule();
            }, 5000);
        };
        const update = () => {
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
            playback.hidden = reducedMotion.matches || !overflowing();
            schedule();
        };
        const move = (direction) => track.scrollBy({
            left: direction * track.clientWidth * .8,
            behavior: reducedMotion.matches ? "instant" : "smooth",
        });
        playback.addEventListener("click", () => {
            paused = !paused;
            if (!paused) focused = false; // Explicit resume also works from the keyboard.
            const label = paused ? "Resume automatic scrolling" : "Pause automatic scrolling";
            playback.setAttribute("aria-label", label);
            playback.title = label;
            playback.querySelector("[data-carousel-pause]").toggleAttribute("hidden", paused);
            playback.querySelector("[data-carousel-play]").toggleAttribute("hidden", !paused);
            schedule();
        });
        previous.addEventListener("click", () => move(-1));
        next.addEventListener("click", () => move(1));
        track.addEventListener("scroll", update, { passive: true });
        carousel.addEventListener("pointerenter", (event) => { if (event.pointerType === "mouse") { hovered = true; schedule(); } });
        carousel.addEventListener("pointerleave", () => { hovered = false; schedule(); });
        carousel.addEventListener("pointerdown", () => { touching = true; schedule(); }, { passive: true });
        on(document, "pointerup", () => { if (touching) { touching = false; schedule(); } }, { passive: true });
        on(document, "pointercancel", () => { touching = false; schedule(); }, { passive: true });
        carousel.addEventListener("focusin", () => { focused = true; schedule(); });
        carousel.addEventListener("focusout", (event) => { if (!carousel.contains(event.relatedTarget)) { focused = false; schedule(); } });
        on(document, "visibilitychange", schedule);
        on(document, "store:dialog-open", schedule);
        document.querySelectorAll("dialog").forEach((dialog) => on(dialog, "close", schedule));
        on(window, "pagehide", () => clearTimeout(timer));
        on(window, "pageshow", schedule);
        on(reducedMotion, "change", update);
        const intersection = new IntersectionObserver(([entry]) => { visible = entry.intersectionRatio >= .2; schedule(); }, { threshold: .2 }); intersection.observe(carousel);
        const resize = new ResizeObserver(update); resize.observe(track);
        controllers.set(carousel, () => { clearTimeout(timer); listeners.abort(); intersection.disconnect(); resize.disconnect(); });
        update();
    });

}
initCarousels();
document.addEventListener('store:page-updated', initCarousels);
