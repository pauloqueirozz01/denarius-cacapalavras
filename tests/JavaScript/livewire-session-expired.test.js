import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createSessionExpiredHandler,
    SESSION_EXPIRED_MESSAGE,
} from '../../resources/js/livewire-session-expired.js';

function failure(status) {
    const event = { response: { status }, prevented: false };
    event.preventDefault = () => {
        event.prevented = true;
    };

    return event;
}

test('expired session asks in pt-BR once and reloads when confirmed', () => {
    const questions = [];
    let reloads = 0;
    const handle = createSessionExpiredHandler(
        (message) => {
            questions.push(message);

            return true;
        },
        () => reloads++,
    );

    const first = failure(419);
    const second = failure(419);
    handle(first);
    handle(second);

    assert.deepEqual(questions, [SESSION_EXPIRED_MESSAGE]);
    assert.match(SESSION_EXPIRED_MESSAGE, /Sua sessão expirou/);
    assert.equal(reloads, 1);
    assert.equal(first.prevented, true);
    assert.equal(second.prevented, true);
});

test('declining keeps the page and other errors keep Livewire defaults', () => {
    let reloads = 0;
    const handle = createSessionExpiredHandler(() => false, () => reloads++);

    const expired = failure(419);
    const serverError = failure(500);
    handle(expired);
    handle(serverError);

    assert.equal(reloads, 0);
    assert.equal(expired.prevented, true);
    assert.equal(serverError.prevented, false);
});
