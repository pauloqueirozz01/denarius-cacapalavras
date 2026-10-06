import { createGameClock } from './game-clock.js';
import { createWordSearchBoard } from './word-search-selection.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('gameClock', (startedAt, officialDuration, active) =>
        createGameClock(startedAt, officialDuration, active),
    );
    window.Alpine.data('wordSearchBoard', (wire, disabled = false) =>
        createWordSearchBoard(wire, disabled),
    );
});
