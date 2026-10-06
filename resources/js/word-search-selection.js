export function cellsBetween(start, end) {
    if (!isCoordinate(start) || !isCoordinate(end)) {
        return [];
    }

    const rowDistance = end.row - start.row;
    const columnDistance = end.column - start.column;
    const isHorizontal = rowDistance === 0;
    const isVertical = columnDistance === 0;
    const isDiagonal = Math.abs(rowDistance) === Math.abs(columnDistance);

    if (!isHorizontal && !isVertical && !isDiagonal) {
        return [];
    }

    const rowStep = Math.sign(rowDistance);
    const columnStep = Math.sign(columnDistance);
    const cellCount = Math.max(Math.abs(rowDistance), Math.abs(columnDistance)) + 1;

    return Array.from({ length: cellCount }, (_, index) => ({
        row: start.row + rowStep * index,
        column: start.column + columnStep * index,
    }));
}

export function createWordSearchBoard(wire, disabled = false) {
    return {
        disabled,
        processing: false,
        pointerId: null,
        start: null,
        selectedCells: [],
        selectionHint: '',

        beginSelection(event) {
            if (this.disabled || this.processing || event.button > 0) {
                return;
            }

            const cell = coordinateFromElement(event.target);

            if (cell === null) {
                return;
            }

            event.preventDefault();
            this.pointerId = event.pointerId;
            this.start = cell;
            this.selectedCells = [cell];
            this.selectionHint = '';
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        moveSelection(event) {
            if (this.pointerId !== event.pointerId || this.start === null) {
                return;
            }

            const cell = coordinateAtPoint(event.clientX, event.clientY);

            if (cell === null) {
                return;
            }

            const cells = cellsBetween(this.start, cell);
            this.selectedCells = cells.length > 0 ? cells : [this.start];
            this.selectionHint = cells.length > 0 ? '' : 'Arraste em linha reta: horizontal, vertical ou diagonal.';
        },

        async finishSelection(event) {
            if (this.pointerId !== event.pointerId || this.start === null) {
                return;
            }

            const end = coordinateAtPoint(event.clientX, event.clientY);
            const cells = end === null ? [] : cellsBetween(this.start, end);

            if (end === null || cells.length < 2) {
                this.selectionHint = end !== null && this.start.row !== end.row && this.start.column !== end.column
                    ? 'Arraste em linha reta: horizontal, vertical ou diagonal.'
                    : 'Selecione da primeira até a última letra do termo.';
                this.resetSelection(false);

                return;
            }

            this.processing = true;

            try {
                const result = await wire.selectWord(
                    this.start.row,
                    this.start.column,
                    end.row,
                    end.column,
                );
                this.disabled = result?.active === false;
            } catch {
                this.selectionHint = 'A conexão falhou. Tente selecionar novamente.';
            } finally {
                this.processing = false;
                this.resetSelection(this.selectionHint === '');
            }
        },

        cancelSelection(event) {
            if (this.pointerId === event.pointerId) {
                this.resetSelection();
            }
        },

        isSelected(row, column) {
            return this.selectedCells.some((cell) => cell.row === row && cell.column === column);
        },

        resetSelection(clearHint = true) {
            this.pointerId = null;
            this.start = null;
            this.selectedCells = [];

            if (clearHint) {
                this.selectionHint = '';
            }
        },
    };
}

function isCoordinate(value) {
    return Number.isInteger(value?.row) && Number.isInteger(value?.column);
}

function coordinateAtPoint(clientX, clientY) {
    return coordinateFromElement(document.elementFromPoint(clientX, clientY));
}

function coordinateFromElement(element) {
    const cell = element?.closest?.('[data-word-cell]');

    if (cell === undefined || cell === null) {
        return null;
    }

    return {
        row: Number.parseInt(cell.dataset.row, 10),
        column: Number.parseInt(cell.dataset.column, 10),
    };
}
