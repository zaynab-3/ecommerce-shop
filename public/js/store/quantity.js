document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-quantity]');
    if (!button) return;
    const input = button.form.elements.quantity;
    input.value = Math.max(Number(input.min) || 1, Math.min(Number(input.max) || 99, (Number(input.value) || 1) + Number(button.dataset.quantity)));
});
