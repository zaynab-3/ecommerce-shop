// Scroll events update memory only. Writing History on every frame trips Firefox's limit.
const key = 'meshPage';
const positions = new Map();
// Entry IDs are local bookkeeping, including on a phone's plain-HTTP LAN URL.
const makeId = () => `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`;
let entry = history.state?.[key]?.id || makeId();
let url = location.href;

export function cachePosition() {
    if (location.href !== url) return;
    positions.set(entry, { x: scrollX, y: scrollY });
}
export function savedPosition(state) {
    const saved = state?.[key];
    return positions.get(saved?.id) || { x: saved?.x || 0, y: saved?.y || 0 };
}
function write(method, target, id, position) {
    const saved = history.state?.[key];
    if (method === 'replaceState' && location.href === String(target) && saved?.id === id && saved.x === position.x && saved.y === position.y) return true;
    try {
        history[method]({ ...history.state, [key]: { id, x: position.x, y: position.y } }, '', target);
        return true;
    } catch {
        // Keep the existing URL/content if the browser refuses a history change.
        return false;
    }
}
export function rememberPosition() {
    cachePosition();
    if (location.href === url) return write('replaceState', url, entry, positions.get(entry));
    return true;
}
export function changePageHistory(target, position, mode = 'push') {
    const saved = history.state?.[key];
    const next = mode === 'push' || (mode === 'pop' && !saved?.id) ? makeId() : saved?.id || entry;
    // Migrate entries left by older app versions once, without sharing another entry's cache.
    if (mode === 'pop' && !saved?.id) write('replaceState', target, next, position);
    if (!['pop', 'refresh'].includes(mode) && !write(mode === 'push' ? 'pushState' : 'replaceState', target, next, position)) return false;
    entry = next;
    url = String(target);
    positions.set(entry, { x: position.x, y: position.y });
    if (positions.size > 200) positions.delete(positions.keys().next().value);
    return true;
}
addEventListener('scroll', cachePosition, { passive: true });
