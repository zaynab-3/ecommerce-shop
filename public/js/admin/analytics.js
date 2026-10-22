import { scrollPageTo } from '../shared/smooth-scroll.js';

const report = document.querySelector('[data-analytics-report]');
const form = document.querySelector('[data-analytics-form]');
const refresh = document.querySelector('[data-analytics-refresh]');
let pending = false;
async function update(manual = false) {
    if (!report || pending || document.hidden || (!manual && (form.contains(document.activeElement) || report.contains(document.activeElement) || !getSelection().isCollapsed))) return;
    pending = true;
    refresh.disabled = true;
    try {
        const response = await fetch(report.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: AbortSignal.timeout(15000) });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;
        const data = await response.json();
        if (document.hidden || (!manual && (report.contains(document.activeElement) || !getSelection().isCollapsed))) return;
        const position = scrollY;
        report.innerHTML = data.html;
        scrollPageTo(position, { immediate: true });
        document.querySelector('[data-analytics-updated]').textContent = `Updated ${data.updated_at} · refreshes every 30 seconds`;
    } catch { /* Keep the last readable report; the next visible refresh retries. */ }
    finally { pending = false; refresh.disabled = false; }
}
if (report && form) {
    form.querySelectorAll('input[type=date]').forEach(input => input.addEventListener('change', () => { form.elements.period.value = 'custom'; }));
    form.elements.period.addEventListener('change', () => { if (form.elements.period.value !== 'custom') form.requestSubmit(); });
    refresh.addEventListener('click', () => update(true));
    setInterval(update, 30000);
    document.addEventListener('visibilitychange', () => update());
}
