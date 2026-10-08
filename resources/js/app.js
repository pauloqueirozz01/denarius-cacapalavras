import { installFormSubmitGuard } from './form-submit-guard.js';
import { createGameClock } from './game-clock.js';
import { createSessionExpiredHandler } from './livewire-session-expired.js';
import { createWordSearchBoard } from './word-search-selection.js';

installFormSubmitGuard();

document.addEventListener('livewire:init', () => {
    const handleSessionExpired = createSessionExpiredHandler(
        (message) => window.confirm(message),
        () => window.location.reload(),
    );

    window.Livewire.interceptRequest(({ onError }) => onError(handleSessionExpired));
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('gameClock', (startedAt, officialDuration, active) =>
        createGameClock(startedAt, officialDuration, active),
    );
    window.Alpine.data('wordSearchBoard', (wire, disabled = false) =>
        createWordSearchBoard(wire, disabled),
    );
});
