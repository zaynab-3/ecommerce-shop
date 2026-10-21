// Kept for previously cached entry modules; navigation must never hide content.
export { navigate } from './page-navigation.js';

// Restored documents do not replay CSS entry animations, so give Back/Forward
// the same small movement without changing scroll position or delaying links.
addEventListener("pageshow", (event) => {
    if (!event.persisted || matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    document.getElementById("main")?.animate(
        [{ transform: "translateY(8px)" }, { transform: "translateY(0)" }],
        { duration: 220, easing: "cubic-bezier(.22,1,.36,1)" },
    );
});
