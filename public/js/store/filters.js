document.addEventListener('change', (event) => {
    if (event.target.matches('[data-submit]')) event.target.form.requestSubmit();
});
