export function formatElapsedTime(seconds) {
    const safeSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
    const minutes = Math.floor(safeSeconds / 60);
    const remainder = safeSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
}

export function createGameClock(startedAt, officialDuration, active) {
    return {
        elapsedSeconds: active
            ? Math.max(0, Math.floor((Date.now() - Number(startedAt)) / 1000))
            : Number(officialDuration) || 0,
        timer: null,

        init() {
            if (!active) {
                return;
            }

            this.timer = window.setInterval(() => {
                this.elapsedSeconds = Math.max(0, Math.floor((Date.now() - Number(startedAt)) / 1000));
            }, 1000);
        },

        destroy() {
            if (this.timer !== null) {
                window.clearInterval(this.timer);
            }
        },

        get formattedTime() {
            return formatElapsedTime(this.elapsedSeconds);
        },
    };
}
