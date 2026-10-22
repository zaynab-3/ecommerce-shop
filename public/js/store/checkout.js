import { formatPhone, validPhone } from "./lebanese-phone.js";
export function refreshTotal(form) {
    const zones = form.querySelector('[name="delivery_zone_id"]');
    const fee = Number(zones?.selectedOptions[0]?.dataset.fee || 0);
    form.querySelector('#delivery-fee').textContent = zones && !zones.value ? "Select area" : `$${(fee / 100).toFixed(2)}`;
    form.querySelector('#checkout-total').textContent = `$${((Number(form.dataset.subtotal) + fee) / 100).toFixed(2)}`;
}
export function initCheckout(root = document) {
    root.querySelectorAll('[data-checkout]').forEach((form) => {
        const phone = form.querySelector('[data-lebanese-phone]');
        if (phone.value && validPhone(phone.value)) formatPhone(phone);
        refreshTotal(form);
    });
}
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-checkout] select')) {
        refreshTotal(event.target.form);
        if (matchMedia('(max-width: 850px), (pointer: coarse)').matches) event.target.blur();
    }
});
document.addEventListener('input', (event) => {
    const input = event.target;
    if (!input.matches('[data-lebanese-phone]')) return;
    input.setCustomValidity('');
    // Format as they type without moving a cursor that is editing in the middle.
    if (input.selectionStart === input.value.length && /^\+?[0-9 ()-]*$/.test(input.value)) formatPhone(input);
});
document.addEventListener('focusout', (event) => {
    if (event.target.matches('[data-lebanese-phone]') && validPhone(event.target.value)) formatPhone(event.target);
});
export function validatePhone(form) {
    const input = form.querySelector('[data-lebanese-phone]');
    input.setCustomValidity((!input.required && !input.value.trim()) || validPhone(input.value) ? '' : 'Enter a Lebanese number, for example 03 123 456 or 70 123 456.');
    return form.reportValidity();
}
initCheckout();
