import assert from 'node:assert/strict';
import test from 'node:test';

import { formatElapsedTime } from '../../resources/js/game-clock.js';
import { cellsBetween } from '../../resources/js/word-search-selection.js';

test('builds a horizontal trajectory in both directions', () => {
    assert.deepEqual(cellsBetween({ row: 2, column: 1 }, { row: 2, column: 4 }), [
        { row: 2, column: 1 },
        { row: 2, column: 2 },
        { row: 2, column: 3 },
        { row: 2, column: 4 },
    ]);
    assert.deepEqual(cellsBetween({ row: 2, column: 4 }, { row: 2, column: 1 }), [
        { row: 2, column: 4 },
        { row: 2, column: 3 },
        { row: 2, column: 2 },
        { row: 2, column: 1 },
    ]);
});

test('builds a vertical trajectory in both directions', () => {
    assert.deepEqual(cellsBetween({ row: 1, column: 3 }, { row: 4, column: 3 }), [
        { row: 1, column: 3 },
        { row: 2, column: 3 },
        { row: 3, column: 3 },
        { row: 4, column: 3 },
    ]);
    assert.deepEqual(cellsBetween({ row: 4, column: 3 }, { row: 1, column: 3 }), [
        { row: 4, column: 3 },
        { row: 3, column: 3 },
        { row: 2, column: 3 },
        { row: 1, column: 3 },
    ]);
});

test('builds diagonal trajectories in every orientation', () => {
    assert.deepEqual(cellsBetween({ row: 1, column: 1 }, { row: 3, column: 3 }), [
        { row: 1, column: 1 },
        { row: 2, column: 2 },
        { row: 3, column: 3 },
    ]);
    assert.deepEqual(cellsBetween({ row: 3, column: 1 }, { row: 1, column: 3 }), [
        { row: 3, column: 1 },
        { row: 2, column: 2 },
        { row: 1, column: 3 },
    ]);
});

test('rejects an irregular trajectory', () => {
    assert.deepEqual(cellsBetween({ row: 1, column: 1 }, { row: 4, column: 6 }), []);
});

test('formats the visual clock without affecting the official duration', () => {
    assert.equal(formatElapsedTime(0), '00:00');
    assert.equal(formatElapsedTime(65), '01:05');
    assert.equal(formatElapsedTime(3_661), '61:01');
});
