// Preserve shopping choices and horizontal positions during background updates.
export function capturePageState(main) {
    const quantity = main.querySelector('#quantity')?.value;
    const color = main.querySelector('[data-color-name]:checked')?.value;
    const cardKey = (card) => `${card.closest('[data-carousel]')?.getAttribute('aria-label') || 'catalog'}:${card.dataset.productId}`;
    const colors = new Map([...main.querySelectorAll('.product-card')].map((card) => [cardKey(card), card.querySelector('[data-card-color][aria-pressed=true]')?.dataset.colorId]));
    const positions = ['[data-carousel-track]', '.category-nav-track', '.category-grid', '.gallery-thumbs'].flatMap((selector) => [...main.querySelectorAll(selector)].map((node, index) => ({ selector, index, left: node.scrollLeft, top: node.scrollTop })));
    const paused = [...main.querySelectorAll('[data-carousel-autoplay]')].map((button) => button.getAttribute('aria-label') === 'Resume automatic scrolling');
    const focused = main.contains(document.activeElement) ? document.activeElement : null;
    return () => {
        const input = main.querySelector('#quantity');
        if (input && quantity) input.value = Math.max(Number(input.min) || 1, Math.min(Number(input.max) || 99, Number(quantity)));
        const selected = [...main.querySelectorAll('[data-color-name]')].find((node) => node.value === color);
        if (selected) { selected.checked = true; selected.dispatchEvent(new Event('change', { bubbles: true })); }
        for (const card of main.querySelectorAll('.product-card')) {
            const chosen = colors.get(cardKey(card));
            [...card.querySelectorAll('[data-card-color]')].find((node) => node.dataset.colorId === chosen)?.click();
        }
        for (const { selector, index, left, top } of positions) main.querySelectorAll(selector)[index]?.scrollTo({ left, top, behavior: 'instant' });
        main.querySelectorAll('[data-carousel-autoplay]').forEach((button, index) => { if (paused[index]) button.click(); });
        let target;
        if (focused?.id) target = document.getElementById(focused.id);
        else if (focused?.getAttribute('aria-label')) target = [...main.querySelectorAll('[aria-label]')].find((node) => node.getAttribute('aria-label') === focused.getAttribute('aria-label'));
        target?.focus({ preventScroll: true });
    };
}
