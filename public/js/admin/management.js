import { openDialog, closeDialog } from '../store/dialogs.js';
import { request, serial } from '../shared/http.js';
import { toast } from '../shared/notices.js';

function editor(type) { return document.querySelector(`[data-editor="${type}"]`); }
async function show(dialog) {
    if (!dialog) return;
    dialog.setAttribute('aria-label', dialog.querySelector('h2').textContent);
    await openDialog(dialog);
    dialog.querySelector('input[name=name]')?.focus({ preventScroll: true });
}
function mode(dialog, id) {
    const form = dialog.querySelector('form');
    const edit = id && id !== 'create';
    if (form.elements._method) form.elements._method.disabled = !edit;
    if (form.elements._editor_mode) form.elements._editor_mode.value = edit ? id : 'create';
    if (form.dataset.createUrl) form.action = edit ? `${form.dataset.createUrl}/${id}` : form.dataset.createUrl;
    const type = dialog.dataset.editor;
    dialog.querySelector('[data-editor-title]')?.replaceChildren(document.createTextNode(`${edit ? 'Edit' : 'Add'} ${type === 'team' ? 'team member' : 'category'}`));
    if (type === 'team') {
        dialog.querySelector('[data-team-access]').hidden = !edit;
        dialog.querySelector('[data-team-role]').classList.toggle('full-span', !edit);
        form.elements.is_active.disabled = !edit;
        for (const name of ['password', 'password_confirmation']) form.elements[name].required = !edit;
        dialog.querySelector('[data-password-hint]').textContent = edit ? 'Leave blank to keep the current password.' : 'At least 12 characters: upper/lowercase letters, a number, and a symbol.';
    }
    return form;
}
document.addEventListener('click', event => {
    const close = event.target.closest('[data-editor-close]');
    if (close) { closeDialog(close.closest('dialog')); return; }
    const open = event.target.closest('[data-editor-open]');
    if (open) {
        const dialog = editor(open.dataset.editorOpen);
        const form = dialog.querySelector('form');
        form.reset(); mode(dialog, 'create');
        if (form.elements.parent_id) {
            form.elements.parent_id.disabled = false;
            [...form.elements.parent_id.options].forEach(option => { option.disabled = false; });
            form.elements.parent_id.value = open.dataset.parent || '';
        }
        dialog.querySelector('[data-remove-icon]')?.setAttribute('hidden', '');
        show(dialog); return;
    }
    const category = event.target.closest('[data-category-edit]');
    if (category) {
        const dialog = editor('category'); dialog.querySelector('form').reset();
        const form = mode(dialog, category.dataset.id);
        form.elements.name.value = category.dataset.name;
        form.elements.parent_id.disabled = false;
        [...form.elements.parent_id.options].forEach(option => { option.disabled = option.value === category.dataset.id || (Number(category.dataset.children) > 0 && option.value !== ''); });
        form.elements.parent_id.value = category.dataset.parent || '';
        form.elements.icon.value = category.dataset.icon || 'grid';
        form.querySelector('[name=is_active][type=checkbox]').checked = category.dataset.active === '1';
        dialog.querySelector('[data-remove-icon]').hidden = category.dataset.customIcon !== '1';
        show(dialog); return;
    }
    const team = event.target.closest('[data-team-edit]');
    if (team) {
        const dialog = editor('team'); dialog.querySelector('form').reset();
        const form = mode(dialog, team.dataset.id);
        for (const key of ['name', 'email', 'role']) form.elements[key].value = team.dataset[key];
        form.elements.is_active.value = team.dataset.active;
        show(dialog); return;
    }
    const step = event.target.closest('[data-quantity-step]');
    if (step) {
        const input = step.closest('form').elements.quantity;
        input.value = Math.min(10000000, Math.max(0, Number(input.value) + Number(step.dataset.quantityStep)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
});
for (const dialog of document.querySelectorAll('[data-editor]')) {
    const form = dialog.querySelector('form');
    if (form.dataset.reopen === '1') {
        const id = form.elements._editor_mode?.value;
        mode(dialog, /^\d+$/.test(id || '') ? id : 'create');
        show(dialog);
    }
}
document.addEventListener('input', event => { const form = event.target.closest('[data-quick-product]'); if (form) form.dataset.dirty = '1'; });
function applyProduct(form, product) {
    const row = form.closest('[data-product-row]');
    row.querySelector('[data-product-status]').textContent = { available: 'In stock', low: 'Low stock', out: 'Out of stock' }[product.stock_status];
    for (const target of row.querySelectorAll('[data-quick-product]')) {
        if (target !== form && target.dataset.dirty === '1') continue;
        for (const key of ['quantity', 'stock_revision', 'price', 'compare_price', 'manual_stock_status']) if (target.elements[key]) target.elements[key].value = product[key] ?? '';
    }
    const visibility = row.querySelector('[data-visibility-form]');
    visibility.elements.is_active.value = product.is_active ? '0' : '1';
    visibility.querySelector('button').setAttribute('aria-pressed', String(product.is_active));
    visibility.querySelector('span').textContent = product.is_active ? 'Visible' : 'Hidden';
    visibility.querySelector('button').setAttribute('aria-label', `${product.is_active ? 'Hide' : 'Publish'} ${row.querySelector('.table-product strong').textContent}`);
}
document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('[data-quick-product],[data-zone-quick]')) return;
    event.preventDefault();
    if (form.dataset.busy) return;
    const body = new FormData(form);
    form.dataset.busy = '1';
    const buttons = form.querySelectorAll('button[type=submit],button:not([type])');
    buttons.forEach(button => { button.disabled = true; });
    serial(async () => {
        try {
            const data = await request(form.action, { method: 'POST', body });
            delete form.dataset.dirty;
            if (data.product) applyProduct(form, data.product);
            if (form.matches('[data-zone-quick]')) {
                const row = form.closest('tr'); const input = row.querySelector('[name=is_active][type=checkbox]');
                input.nextElementSibling.textContent = input.checked ? 'Available' : 'Unavailable';
            }
            toast(data.message);
        } catch (error) {
            if (error.product && form.matches('[data-quick-product]')) { delete form.dataset.dirty; applyProduct(form, error.product); }
            toast(error.message, 'error');
        }
        finally { delete form.dataset.busy; buttons.forEach(button => { button.disabled = false; }); }
    });
});
const selectPage = document.querySelector('[data-select-page]');
const bulk = document.getElementById('delivery-bulk');
function selectionCount() {
    if (!bulk) return;
    const count = document.querySelectorAll('input[name="zones[]"]:checked').length;
    document.querySelector('[data-selection-count]').textContent = `${count} selected`;
    bulk.querySelector('[type=submit]').disabled = count === 0;
}
selectPage?.addEventListener('change', () => { document.querySelectorAll('input[name="zones[]"]').forEach(input => { input.checked = selectPage.checked; }); selectionCount(); });
document.querySelectorAll('input[name="zones[]"]').forEach(input => input.addEventListener('change', selectionCount));
selectionCount();
