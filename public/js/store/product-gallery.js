function showImage(url, alt) {
    const image = document.getElementById('detail-image');
    if (!image) return;
    image.src = url;
    if (alt) image.alt = alt;
    document.querySelectorAll('[data-image]').forEach((button) => {
        const active = button.dataset.image === url;
        button.classList.toggle('selected', active);
        button.setAttribute('aria-pressed', String(active));
    });
}
function initGallery() {
    const color = document.querySelector('[data-color-name]:checked');
    if (color) showImage(color.dataset.colorImage);
}
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-image]');
    if (button) showImage(button.dataset.image, button.dataset.alt);
});
document.addEventListener('change', (event) => {
    const input = event.target;
    if (!input.matches('[data-color-name]')) return;
    document.getElementById('selected-color').textContent = input.dataset.colorName;
    showImage(input.dataset.colorImage);
});
initGallery();
document.addEventListener('store:page-updated', initGallery);
