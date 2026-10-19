const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
document.querySelectorAll('.category-nav-track').forEach((track) => {
    function center(link, smooth = false) {
        if (!track.clientWidth) return;
        if (!link) { track.scrollTo({ left: 0, behavior: 'instant' }); return; }
        const bounds = track.getBoundingClientRect();
        const item = link.getBoundingClientRect();
        track.scrollTo({ left: track.scrollLeft + item.left - bounds.left - (bounds.width - item.width) / 2, behavior: smooth && !reducedMotion.matches ? 'smooth' : 'instant' });
    }
    const active = () => center(track.querySelector('[aria-current="page"]'));
    requestAnimationFrame(active);
    document.fonts?.ready.then(active);
    new ResizeObserver(active).observe(track);
    window.addEventListener('pageshow', active);
});
