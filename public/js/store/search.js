const dialog = document.getElementById('search-dialog');
const form = document.getElementById('store-search');
const input = document.getElementById('search');
const results = document.getElementById('search-results');
const status = dialog?.querySelector('[data-search-status]');
let timer;
let controller;
let version = 0;
let selected = -1;
const links = () => [...results.querySelectorAll('a')];
function select(index) {
    const options = links();
    selected = index;
    options.forEach((link, i) => link.setAttribute('aria-selected', String(i === index)));
    if (options[index]) { input.setAttribute('aria-activedescendant', options[index].id); options[index].scrollIntoView({ block: 'nearest' }); }
    else input.removeAttribute('aria-activedescendant');
}
function clear(message = 'Type at least 2 characters to find a product.') {
    results.replaceChildren();
    results.removeAttribute('aria-busy');
    status.textContent = message;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    selected = -1;
}
async function suggest() {
    clearTimeout(timer);
    controller?.abort();
    const current = ++version;
    const query = input.value.trim();
    if (query.length < 2) { clear(); return; }
    controller = new AbortController();
    clear('Searching…');
    results.setAttribute('aria-busy', 'true');
    try {
        const url = new URL(form.dataset.suggestionsUrl);
        url.searchParams.set('q', query);
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal, credentials: 'omit' });
        if (!response.ok) throw new Error(response.status === 429 ? 'Please pause a moment and try again.' : 'Could not load suggestions. Please try again.');
        const data = await response.json();
        if (current !== version || !dialog.open || input.value.trim() !== query) return;
        clear(data.products.length ? `${data.products.length} matching products` : 'No matching products. Try another name.');
        data.products.forEach((product, i) => {
            const link = document.createElement('a');
            link.href = product.url;
            link.className = 'search-result';
            link.id = `search-option-${i}`;
            link.tabIndex = -1;
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');
            const image = document.createElement('img');
            image.src = product.image;
            image.alt = '';
            image.width = image.height = 52;
            const details = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = product.name;
            const category = document.createElement('small');
            category.textContent = `${product.category}${product.stock === 'out' ? ' · Out of stock' : ''}`;
            details.append(name, category);
            const price = document.createElement('span');
            price.className = 'search-result-price';
            price.textContent = `$${(product.price_cents / 100).toFixed(2)}`;
            link.append(image, details, price);
            results.append(link);
        });
        input.setAttribute('aria-expanded', String(data.products.length > 0));
    } catch (error) {
        if (error.name !== 'AbortError' && current === version && dialog.open) clear(error.message);
    } finally {
        if (current === version) results.removeAttribute('aria-busy');
    }
}
input?.addEventListener('input', () => {
    clearTimeout(timer);
    controller?.abort();
    version++;
    clear(input.value.trim().length < 2 ? undefined : 'Searching…');
    timer = setTimeout(suggest, 220);
});
input?.addEventListener('keydown', (event) => {
    const options = links();
    if (!options.length || !['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
    if (event.key === 'Enter' && selected < 0) return;
    event.preventDefault();
    if (event.key === 'Enter') location.assign(options[selected].href);
    else select(selected < 0 ? (event.key === 'ArrowDown' ? 0 : options.length - 1) : (selected + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length);
});
form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    await suggest();
    if (links().length) { select(0); input.focus(); }
});
dialog?.addEventListener('close', () => {
    clearTimeout(timer);
    controller?.abort();
    version++;
    input.value = '';
    clear();
    results.removeAttribute('aria-busy');
});
