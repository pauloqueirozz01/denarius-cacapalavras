/**
 * Blocks repeated submissions of plain HTML forms marked with `data-submit-once`.
 *
 * The first submit disables the button marked with `data-submitting-label` and
 * swaps its text; later submits are cancelled. Without JavaScript the form keeps
 * working normally, the server-side rules still apply.
 */
export function guardFormSubmission(form) {
    if (form.dataset.submitting === 'true') {
        return false;
    }

    form.dataset.submitting = 'true';

    const button = form.querySelector('[data-submitting-label]');

    if (button) {
        button.dataset.idleLabel = button.textContent.trim();
        button.textContent = button.dataset.submittingLabel;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    }

    return true;
}

export function resetFormSubmission(form) {
    delete form.dataset.submitting;

    const button = form.querySelector('[data-submitting-label]');

    if (button && button.dataset.idleLabel) {
        button.textContent = button.dataset.idleLabel;
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
}

export function installFormSubmitGuard(root = document, view = window) {
    root.addEventListener('submit', (event) => {
        const form = event.target;

        if (!form?.hasAttribute?.('data-submit-once')) {
            return;
        }

        if (!guardFormSubmission(form)) {
            event.preventDefault();
        }
    });

    // Back/forward cache restores the page with the button still disabled.
    view.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            root.querySelectorAll('form[data-submit-once]').forEach(resetFormSubmission);
        }
    });
}
