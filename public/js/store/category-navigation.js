const tracks = new Map();
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
let previousLeft = 0;

function align(track, link, smooth) {
    if (!track.isConnected || !track.clientWidth) return;
    const bounds = track.getBoundingClientRect();
    const item = link?.getBoundingClientRect();
    const left = item ? track.scrollLeft + item.left - bounds.left - (bounds.width - item.width) / 2 : 0;
    track.scrollTo({ left: Math.max(0, Math.min(left, track.scrollWidth - track.clientWidth)), behavior: smooth && !reducedMotion.matches ? 'smooth' : 'instant' });
}
function initCategories(event) {
    for (const [track, dispose] of tracks) if (!track.isConnected) { dispose(); tracks.delete(track); }
    document.querySelectorAll('.category-nav-track').forEach((track) => {
        if (tracks.has(track)) return;
        let width = track.clientWidth;
        track.scrollTo({ left: previousLeft, behavior: 'instant' });
        const observer = new ResizeObserver(() => {
            if (width === track.clientWidth) return;
            width = track.clientWidth;
            align(track, track.querySelector('[aria-current="page"]'), false);
        });
        observer.observe(track); tracks.set(track, () => observer.disconnect());
        // Preserve the first paint, then travel to the selected category.
        if (!event?.detail?.refresh) requestAnimationFrame(() => requestAnimationFrame(() => align(track, track.querySelector('[aria-current="page"]'), true)));
    });
}
document.addEventListener('store:page-leave', () => { previousLeft = document.querySelector('.category-nav-track')?.scrollLeft || 0; });
document.addEventListener('click', (event) => {
    const link = event.target.closest('.catalog-category-nav a');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const track = link.closest('.catalog-category-nav').querySelector('.category-nav-track');
    align(track, link.matches('.category-all') ? null : link, true);
});
initCategories();
document.fonts?.ready.then(() => {
    const track = document.querySelector('.category-nav-track');
    if (track) align(track, track.querySelector('[aria-current="page"]'), true);
});
document.addEventListener('store:page-updated', initCategories);
addEventListener('pageshow', initCategories);
