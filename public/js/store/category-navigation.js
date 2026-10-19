const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
document.querySelectorAll('.catalog-category-nav').forEach((nav) => {
    function center(link, smooth = false) {
        if (!link || !nav.clientWidth) return;
        const bounds = nav.getBoundingClientRect();
        const item = link.getBoundingClientRect();
        nav.scrollTo({ left: nav.scrollLeft + item.left - bounds.left - (bounds.width - item.width) / 2, behavior: smooth && !reducedMotion.matches ? 'smooth' : 'instant' });
    }
    const active = () => center(nav.querySelector('[aria-current="page"]'));
    requestAnimationFrame(active);
    document.fonts?.ready.then(active);
    new ResizeObserver(active).observe(nav);
    nav.addEventListener('click', (event) => center(event.target.closest('a'), true));
    window.addEventListener('pageshow', active);
});
