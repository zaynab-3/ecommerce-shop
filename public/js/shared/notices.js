document
    .querySelectorAll("[data-dismiss]")
    .forEach((button) =>
        button.addEventListener("click", () =>
            button.closest(".notice").remove(),
        ),
    );
