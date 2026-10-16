document
    .querySelectorAll("[data-submit]")
    .forEach((input) =>
        input.addEventListener("change", () => input.form.requestSubmit()),
    );
