import assert from 'node:assert/strict';
import test from 'node:test';

import {
    guardFormSubmission,
    installFormSubmitGuard,
    resetFormSubmission,
} from '../../resources/js/form-submit-guard.js';

function fakeButton(label, submittingLabel) {
    const attributes = new Map();

    return {
        textContent: label,
        disabled: false,
        dataset: { submittingLabel },
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: (name) => attributes.delete(name),
        getAttribute: (name) => attributes.get(name) ?? null,
    };
}

function fakeForm(button = null, marked = true) {
    return {
        dataset: {},
        querySelector: (selector) => (selector === '[data-submitting-label]' ? button : null),
        hasAttribute: (name) => marked && name === 'data-submit-once',
    };
}

function fakeEventTarget() {
    const listeners = new Map();

    return {
        addEventListener: (type, listener) => listeners.set(type, listener),
        dispatch: (type, event) => listeners.get(type)?.(event),
    };
}

test('first submission disables the button and swaps its label', () => {
    const button = fakeButton('Entrar', 'Entrando…');
    const form = fakeForm(button);

    assert.equal(guardFormSubmission(form), true);
    assert.equal(button.disabled, true);
    assert.equal(button.textContent, 'Entrando…');
    assert.equal(button.getAttribute('aria-busy'), 'true');
});

test('repeated submissions of the same form are refused', () => {
    const form = fakeForm(fakeButton('Criar conta', 'Criando conta…'));

    assert.equal(guardFormSubmission(form), true);
    assert.equal(guardFormSubmission(form), false);
    assert.equal(guardFormSubmission(form), false);
});

test('reset restores the original label and allows a new submission', () => {
    const button = fakeButton('Sair', 'Saindo…');
    const form = fakeForm(button);

    guardFormSubmission(form);
    resetFormSubmission(form);

    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Sair');
    assert.equal(button.getAttribute('aria-busy'), null);
    assert.equal(guardFormSubmission(form), true);
});

test('installed guard cancels only the second submit of marked forms', () => {
    const root = fakeEventTarget();
    const view = fakeEventTarget();
    installFormSubmitGuard(root, view);

    const markedForm = fakeForm(fakeButton('Entrar', 'Entrando…'));
    const unmarkedForm = fakeForm(null, false);
    const submit = (form) => {
        const event = { target: form, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        root.dispatch('submit', event);

        return event.defaultPrevented;
    };

    assert.equal(submit(markedForm), false);
    assert.equal(submit(markedForm), true);
    assert.equal(submit(unmarkedForm), false);
    assert.equal(submit(unmarkedForm), false);
});
