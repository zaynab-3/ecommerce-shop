const tracks = new Map();
function initCategories() {
    for (const [track, dispose] of tracks) if (!track.isConnected) { dispose(); tracks.delete(track); }
    document.querySelectorAll('.category-nav-track').forEach((track) => {
        if (tracks.has(track)) return;
        const align = () => {
            if (!track.isConnected || !track.clientWidth) return;
            const link = track.querySelector('[aria-current="page"]');
            if (!link) { track.scrollTo({ left: 0, behavior: 'instant' }); return; }
            const bounds = track.getBoundingClientRect();
            const item = link.getBoundingClientRect();
            const left = track.scrollLeft + item.left - bounds.left - (bounds.width - item.width) / 2;
            track.scrollTo({ left: Math.max(0, Math.min(left, track.scrollWidth - track.clientWidth)), behavior: 'instant' });
        };
        const observer = new ResizeObserver(align); observer.observe(track);
        tracks.set(track, () => observer.disconnect());
        requestAnimationFrame(align); document.fonts?.ready.then(align);
    });
}
initCategories();
document.addEventListener('store:page-updated', initCategories);
addEventListener('pageshow', initCategories);
